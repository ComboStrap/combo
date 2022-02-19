<?php
/**
 * Copyright (c) 2021. ComboStrap, Inc. and its affiliates. All Rights Reserved.
 *
 * This source code is licensed under the GPL license found in the
 * COPYING  file in the root directory of this source tree.
 *
 * @license  GPL 3 (https://www.gnu.org/licenses/gpl-3.0.en.html)
 * @author   ComboStrap <support@combostrap.com>
 *
 */

namespace ComboStrap;

require_once(__DIR__ . '/Snippet.php');

/**
 * @package ComboStrap
 * A component to manage the extra HTML that
 * comes from components and that should come in the head HTML node
 *
 * The snippet manager handles two scope of snippet
 * All function with the suffix
 *   * `ForSlot` are snippets for a bar (ie page, sidebar, ...) - cached
 *   * `ForRequests` are snippets added for the HTTP request - not cached. Example of request component: message, anchor
 *
 */
class SnippetManager
{

    const COMBO_CLASS_SUFFIX = "combo";


    /**
     * The identifier for a script snippet
     * (ie inline javascript or style)
     * To make the difference with library
     * that have already an identifier with the url value
     */
    const SCRIPT_IDENTIFIER = "script";

    const CANONICAL = "snippet-manager";

    /**
     * @var SnippetManager array that contains one element (one {@link SnippetManager} scoped to the requested id
     */
    private static $componentScript;


    /**
     *
     * The scope is used for snippet that are not added
     * by the syntax plugin but by the actions plugin
     * It's also used in the cache because not all bars
     * may render at the same time due to the other been cached.
     *
     * There is two scope:
     *   * {@link SnippetManager::$snippetsBySlotScope} - cached
     *   * or {@link SnippetManager::$snippetsByRequestScope} - never cached
     */

    /**
     * @var array all snippets scope to the bar level
     */
    private $snippetsBySlotScope = array();

    /**
     * @var array heads that are unique on a request scope
     *
     * TlDR: The snippet does not depends to a Page and cannot therefore be cached along.
     *
     * The code that adds this snippet is not created by the parsing of content
     * or depends on the page.
     *
     * It's always called and add the snippet whatsoever.
     * Generally, this is an action plugin with a `TPL_METAHEADER_OUTPUT` hook
     * such as {@link Bootstrap}, {@link HistoricalBreadcrumbMenuItem},
     * ,...
     */
    private $snippetsByRequestScope = array();


    public static function reset()
    {
        self::$componentScript = null;
    }


    /**
     * @param $tag
     * @return string
     * @deprecated create a {@link Snippet} instead and use the {@link Snippet::getClass()} function instead
     */
    public static function getClassFromSnippetId($tag): string
    {
        $snippet = Snippet::createUnknownSnippet($tag);
        return $snippet->getClass();
    }


    /**
     * @return SnippetManager - the global reference
     * that is set for every run at the end of this file
     */
    public static function getOrCreate(): SnippetManager
    {
        $id = PluginUtility::getRequestedWikiId();
        if ($id === null) {
            if (PluginUtility::isTest()) {
                $id = "test_dynamic_script_execution";
            } else {
                LogUtility::msg("The requested Id could not be found, the snippets may not be scoped properly");
            }
        }

        $snippetManager = self::$componentScript[$id];
        if ($snippetManager === null) {
            self::$componentScript = null; // delete old snippet manager for other request
            $snippetManager = new SnippetManager();
            self::$componentScript[$id] = $snippetManager;
        }
        return $snippetManager;
    }


    /**
     * @return array of node type and an array of array of html attributes
     * @throws ExceptionCombo
     */
    public function getSnippets(): array
    {
        /**
         * Distinct Snippet
         */
        $distinctSnippetIdByType = [];
        if (sizeof($this->snippetsByRequestScope) == 1) {
            /**
             * There is only 0 or 1 value
             * because this is scoped to the actual request (by the requested id)
             */
            $distinctSnippetIdByType = array_shift($this->snippetsByRequestScope);
        }
        foreach ($this->snippetsBySlotScope as $snippet) {
            $distinctSnippetIdByType = $this->mergeSnippetArray($distinctSnippetIdByType, $snippet);
        }


        /**
         * Transform in dokuwiki format
         * We collect the separately head that have content
         * from the head that refers to external resources
         * because the content will depends on the resources
         * and should then come in the last position
         */
        $dokuWikiHeadsFormatContent = array();
        $dokuWikiHeadsSrc = array();
        foreach ($distinctSnippetIdByType as $snippetType => $snippetBySnippetId) {
            switch ($snippetType) {
                case Snippet::TYPE_JS:
                    foreach ($snippetBySnippetId as $snippetId => $snippet) {
                        /**
                         * Bug (Quick fix)
                         */
                        if (is_string($snippet)) {
                            LogUtility::msg("The snippet ($snippetId) is a string ($snippet) and not a snippet object", LogUtility::LVL_MSG_ERROR);
                            $content = $snippet;
                        } else {
                            $content = $snippet->getContent();
                        }
                        /** @var Snippet $snippet */
                        $dokuWikiHeadsFormatContent["script"][] = array(
                            "class" => $snippet->getClass(),
                            "_data" => $content
                        );
                    }
                    break;
                case Snippet::TYPE_CSS:
                    /**
                     * CSS inline in script tag
                     * They are all critical
                     */
                    foreach ($snippetBySnippetId as $snippetId => $snippet) {
                        /**
                         * Bug (Quick fix)
                         */
                        if (is_string($snippet)) {
                            LogUtility::msg("The snippet ($snippetId) is a string ($snippet) and not a snippet object", LogUtility::LVL_MSG_ERROR);
                            $content = $snippet;
                        } else {
                            /**
                             * @var Snippet $snippet
                             */
                            $content = $snippet->getContent();
                        }
                        $snippetArray = array(
                            "class" => $snippet->getClass(),
                            "_data" => $content
                        );
                        /** @var Snippet $snippet */
                        $dokuWikiHeadsFormatContent["style"][] = $snippetArray;
                    }
                    break;
                case Snippet::TAG_TYPE:
                    foreach ($snippetBySnippetId as $snippetId => $tagsSnippet) {
                        /** @var Snippet $tagsSnippet */
                        foreach ($tagsSnippet->getTags() as $htmlElement => $heads) {
                            $classFromSnippetId = self::getClassFromSnippetId($snippetId);
                            foreach ($heads as $head) {
                                if (isset($head["class"])) {
                                    $head["class"] = $head["class"] . " " . $classFromSnippetId;
                                } else {
                                    $head["class"] = $classFromSnippetId;
                                }
                                /**
                                 * Critical treated now via the
                                 * html attribute because the snippets
                                 * can be rendered by the strap template
                                 * or combo via {@link \action_plugin_combo_snippets::componentSnippetContent()}
                                 */
                                if (!$tagsSnippet->getCritical()) {
                                    switch ($htmlElement) {
                                        case "script":
                                            $head["defer"] = null;
                                            break;
                                        case "link":
                                            $relValue = $head["rel"];
                                            if ($relValue !== null && $relValue === "stylesheet") {
                                                $head["rel"] = "preload";
                                                $head['as'] = 'style';
                                            }
                                            break;
                                        default:
                                            throw new ExceptionCombo("The non-critical tag snippet ($tagsSnippet) has an unknown html element ($htmlElement)");
                                    }
                                }
                                $dokuWikiHeadsSrc[$htmlElement][] = $head;
                            }
                        }
                    }
                    break;
            }
        }

        /**
         * Merge the content head node at the last position of the head ref node
         */
        foreach ($dokuWikiHeadsFormatContent as $headsNodeType => $headsData) {
            foreach ($headsData as $heads) {
                $dokuWikiHeadsSrc[$headsNodeType][] = $heads;
            }
        }
        return $dokuWikiHeadsSrc;
    }

    /**
     * Empty the snippets
     * This is used to render the snippet only once
     * The snippets renders first in the head
     * and otherwise at the end of the document
     * if the user are using another template or are in edit mode
     */
    public function close()
    {
        $this->snippetsBySlotScope = array();
        $this->snippetsByRequestScope = array();
    }


    /**
     * A function to be able to add snippets from the snippets cache
     * when a bar was served from the cache
     * @param $bar
     * @param $snippets
     */
    public function addSnippetsFromCacheForSlot($bar, $snippets)
    {

        /**
         * It may happens that this snippetsByBarScope is not empty
         * when the snippet is added with the bad scope
         *
         * For instance, due to the {@link HistoricalBreadcrumbMenuItem},
         * A protected link can be used in a slot but also added on a page level (ie
         * that has a {@link PageProtection::addPageProtectionSnippet() page protection}
         *
         * Therefore we just merge.
         */
        if (!isset($this->snippetsBySlotScope[$bar])) {

            $this->snippetsBySlotScope[$bar] = $snippets;

        } else {

            $this->snippetsBySlotScope[$bar] = $this->mergeSnippetArray($this->snippetsBySlotScope[$bar], $snippets);

        }
    }

    public function getSnippetsForSlot($slot)
    {
        if (isset($this->snippetsBySlotScope[$slot])) {
            return $this->snippetsBySlotScope[$slot];
        } else {
            return null;
        }

    }


    /**
     * @param $snippetId
     * @param string|null $script - the css snippet to add, otherwise it takes the file
     * @return Snippet a snippet not in a slot
     */
    public function &attachCssInternalStyleSheetForSlot($snippetId, string $script = null): Snippet
    {
        $snippet = $this->attachSnippetFromSlot($snippetId, Snippet::TYPE_CSS, self::SCRIPT_IDENTIFIER);
        if ($script !== null) {
            $snippet->setContent($script);
        }
        return $snippet;
    }

    /**
     * @param $snippetId
     * @param string|null $script -  the css if any, otherwise the css file will be taken
     * @return Snippet a snippet scoped at the request scope (not in a slot)
     */
    public function &attachCssSnippetForRequest($snippetId, string $script = null): Snippet
    {
        $snippet = $this->attachSnippetFromRequest($snippetId, Snippet::TYPE_CSS, self::SCRIPT_IDENTIFIER);
        if ($script != null) {
            $snippet->setContent($script);
        }
        return $snippet;
    }

    /**
     * @param $snippetId
     * @param string|null $script
     * @return Snippet a snippet in a slot
     */
    public function &attachJavascriptSnippetForSlot($snippetId, string $script = null): Snippet
    {
        $snippet = $this->attachSnippetFromSlot($snippetId, Snippet::TYPE_JS, self::SCRIPT_IDENTIFIER);
        if ($script != null) {
            $snippet->setContent($script);
        }
        return $snippet;
    }

    /**
     * @param $snippetId
     * @return Snippet a snippet not in a slot
     */
    public function &attachJavascriptSnippetForRequest($snippetId): Snippet
    {
        return $this->attachSnippetFromRequest($snippetId, Snippet::TYPE_JS, self::SCRIPT_IDENTIFIER);
    }

    /**
     * @param $snippetId
     * @param $type
     * @param $identifier
     * @return Snippet
     */
    private function &attachSnippetFromSlot($snippetId, $type, $identifier): Snippet
    {
        global $ID;
        $slot = $ID;
        if ($slot === null) {
            LogUtility::log2file("The slot could not be identified (global ID is null)",LogUtility::LVL_MSG_ERROR,self::CANONICAL);
        }
        $snippetFromArray = &$this->snippetsBySlotScope[$slot][$type][$snippetId][$identifier];
        if (!isset($snippetFromArray)) {
            $snippet = new Snippet($snippetId, $type);
            $snippetFromArray = $snippet;
        }
        return $snippetFromArray;
    }

    private function &attachSnippetFromRequest($snippetId, $type, $identifier)
    {

        $primarySlot = PluginUtility::getRequestedWikiId();
        $snippetFromArray = &$this->snippetsByRequestScope[$primarySlot][$type][$snippetId][$identifier];
        if (!isset($snippetFromArray)) {
            $snippet = new Snippet($snippetId, $type);
            $snippetFromArray = $snippet;
        }
        return $snippetFromArray;
    }


    private function mergeSnippetArray($left, $right): array
    {

        $distinctSnippetIdByType = $left;
        foreach (array_keys($right) as $snippetContentType) {
            /**
             * @var $snippetObject Snippet
             */
            foreach ($right[$snippetContentType] as $snippetObject) {

                if (!$snippetObject instanceof Snippet) {
                    LogUtility::msg("The value is not a snippet object");
                    continue;
                }
                /**
                 * Snippet is an object
                 */
                if (isset($distinctSnippetIdByType[$snippetContentType])) {
                    if (!array_key_exists($snippetObject->getId(), $distinctSnippetIdByType[$snippetContentType])) {
                        $distinctSnippetIdByType[$snippetContentType][$snippetObject->getId()] = $snippetObject;
                    }
                } else {
                    $distinctSnippetIdByType[$snippetContentType][$snippetObject->getId()] = $snippetObject;
                }
            }
        }

        return $distinctSnippetIdByType;

    }

    /**
     * Add a local javascript script as tag
     * (ie same as {@link SnippetManager::attachJavascriptLibraryForSlot()})
     * but for local resource combo file (library)
     *
     * For instance:
     *   * library:combo:combo.js
     *   * for a file located at dokuwiki_home\lib\plugins\combo\resources\library\combo\combo.js
     * @param string $snippetId - the snippet id
     * @param string $relativeId - the relative id from the resources directory
     */
    public function attachJavascriptScriptForRequest(string $snippetId, string $relativeId)
    {
        $javascriptMedia = JavascriptLibrary::createJavascriptLibraryFromDokuwikiId($relativeId);
        $url = $javascriptMedia->getUrl();
        return $this->attachSnippetFromRequest($snippetId, Snippet::TYPE_JS, $url);

    }

    /**
     * @param string $snippetId
     * @param string $relativeId
     * @param string|null $integrity
     * @return Snippet
     */
    public function attachJavascriptComboResourceForSlot(string $snippetId, string $relativeId, string $integrity = null): Snippet
    {
        $javascriptMedia = JavascriptLibrary::createJavascriptLibraryFromDokuwikiId($relativeId);
        $url = $javascriptMedia->getUrl();
        return $this->attachJavascriptLibraryForSlot(
            $snippetId,
            $url,
            $integrity
        );

    }

    public function attachJavascriptComboLibrary()
    {
        return $this->attachJavascriptScriptForRequest("combo", "library:combo:dist:combo.min.js");
    }

    public function attachJavascriptLibraryForSlot(string $snippetId, string $url, string $integrity = null): Snippet
    {
        return $this
            ->attachSnippetFromSlot(
                $snippetId,
                Snippet::TYPE_JS,
                $url)
            ->setUrl($url, $integrity);
    }

    public function attachCssStyleSheetForSlot(string $snippetId, string $url, string $integrity = null): Snippet
    {
        return $this
            ->attachSnippetFromSlot(
                $snippetId,
                Snippet::TYPE_CSS,
                $url)
            ->setUrl($url, $integrity);
    }


}
