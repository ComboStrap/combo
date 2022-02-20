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


    const CANONICAL = "snippet-manager";
    const SCRIPT_TAG = "script";
    const LINK_TAG = "link";
    const STYLE_TAG = "style";
    const DATA_DOKUWIKI_ATT = "_data";


    /**
     * @var SnippetManager array that contains one element (one {@link SnippetManager} scoped to the requested id
     */
    private static $globalSnippetManager;

    /**
     * Empty the snippets
     * This is used to render the snippet only once
     * The snippets renders first in the head
     * and otherwise at the end of the document
     * if the user are using another template or are in edit mode
     */
    public static function reset()
    {
        self::$globalSnippetManager = null;
        Snippet::reset();
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
     * TODO: migrate the attach function to {@link Snippet}
     *   because Snippet has already a global variable {@link Snippet::getOrCreateSnippet()}
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

        $snippetManager = self::$globalSnippetManager[$id];
        if ($snippetManager === null) {
            self::$globalSnippetManager = null; // delete old snippet manager for other request
            $snippetManager = new SnippetManager();
            self::$globalSnippetManager[$id] = $snippetManager;
        }
        return $snippetManager;
    }


    /**
     */
    public function getAllSnippetsInDokuwikiArray(): array
    {
        return $this->snippetsToDokuwikiArray(Snippet::getSnippets());
    }

    /**
     * Transform in dokuwiki format
     *
     * @return array of node type and an array of array of html attributes
     */
    private function snippetsToDokuwikiArray($snippets = null): array
    {

        if ($snippets === null) {
            return [];
        }
        /**
         * The returned array in dokuwiki format
         */
        $returnedDokuWikiFormat = array();

        /**
         * Processing the external resources
         * and collecting the internal one
         *
         * We collect the separately head that have content
         * from the head that refers to external resources
         * because the content will depends on the resources
         * and should then come in the last position
         *
         * @var Snippet[] $internalSnippets
         */
        $internalSnippets = [];

        foreach ($snippets as $snippet) {

            $type = $snippet->getType();
            if ($type === Snippet::INTERNAL) {
                $internalSnippets[] = $snippet;
                continue;
            }

            $extension = $snippet->getExtension();
            switch ($extension) {
                case Snippet::EXTENSION_JS:
                    $jsDokuwiki = array(
                        "class" => $snippet->getClass(),
                        "src" => $snippet->getUrl(),
                        "crossorigin" => "anonymous"
                    );
                    $integrity = $snippet->getIntegrity();
                    if ($integrity !== null) {
                        $jsDokuwiki["integrity"] = $integrity;
                    }
                    $critical = $snippet->getCritical();
                    if (!$critical) {
                        $jsDokuwiki["defer"] = null;
                        $jsDokuwiki["async"] = null;
                    }
                    foreach ($snippet->getHtmlAttributes() as $name => $value) {
                        $jsDokuwiki[$name] = $value;
                    }
                    ksort($jsDokuwiki);
                    $returnedDokuWikiFormat[self::SCRIPT_TAG][] = $jsDokuwiki;
                    break;
                case Snippet::MIME_CSS:
                    $cssDokuwiki = array(
                        "class" => $snippet->getClass(),
                        "rel" => "stylesheet",
                        "href" => $snippet->getUrl(),
                        "crossorigin" => "anonymous"
                    );
                    $integrity = $snippet->getIntegrity();
                    if ($integrity !== null) {
                        $cssDokuwiki["integrity"] = $integrity;
                    }
                    $critical = $snippet->getCritical();
                    if (!$critical && Site::getTemplate() === Site::STRAP_TEMPLATE_NAME) {
                        $cssDokuwiki["rel"] = "preload";
                        $cssDokuwiki['as'] = self::STYLE_TAG;
                    }
                    foreach ($snippet->getHtmlAttributes() as $name => $value) {
                        $cssDokuwiki[$name] = $value;
                    }
                    ksort($cssDokuwiki);
                    $returnedDokuWikiFormat[self::LINK_TAG][] = $cssDokuwiki;
                    break;
                default:
                    LogUtility::msg("The extension ($extension) is unknown, the external snippet ($snippet) was not added");
            }

        }

        foreach ($internalSnippets as $snippet) {
            $extension = $snippet->getExtension();
            switch ($extension) {
                case Snippet::EXTENSION_JS:

                    $content = $snippet->getInternalInlineAndFileContent();
                    if ($content === null) {
                        LogUtility::msg("The internal snippet ($snippet) has no content. Skipped");
                        continue 2;
                    }

                    $returnedDokuWikiFormat[self::SCRIPT_TAG][] = array(
                        "class" => $snippet->getClass(),
                        self::DATA_DOKUWIKI_ATT => $content
                    );

                    break;
                case Snippet::MIME_CSS:
                    /**
                     * CSS inline in script tag
                     * They are all critical
                     */
                    $content = $snippet->getInternalInlineAndFileContent();
                    if ($content === null) {
                        LogUtility::msg("The internal snippet ($snippet) has no content. Skipped");
                        continue 2;
                    }
                    $snippetArray = array(
                        "class" => $snippet->getClass(),
                        self::DATA_DOKUWIKI_ATT => $content
                    );

                    $returnedDokuWikiFormat[self::STYLE_TAG][] = $snippetArray;
                    break;
                default:
                    LogUtility::msg("The extension ($extension) is unknown, the internal snippet ($snippet) was not added");
            }
        }
        return $returnedDokuWikiFormat;
    }

    /**
     * @deprecated see {@link SnippetManager::reset()}
     *
     */
    public
    function close()
    {
        self::reset();
    }


    /**
     * A function to be able to add snippets from the snippets cache
     * when a bar was served from the cache
     * @param $bar
     * @param $snippets
     */
    public
    function addSnippetsFromCacheForSlot($bar, $snippets)
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


    public
    function getSlotSnippetsInDokuwikiArrayFormat($slot): ?array
    {
        $snippets = Snippet::getSnippets();
        $snippetsForSlot = array_filter($snippets,
            function ($s) use ($slot) {
                return $s->hasSlot($slot);
            });
        return $this->snippetsToDokuwikiArray($snippetsForSlot);

    }


    /**
     * @param $snippetId
     * @param string|null $script - the css snippet to add, otherwise it takes the file
     * @return Snippet a snippet not in a slot
     */
    public
    function &attachCssInternalStyleSheetForSlot($snippetId, string $script = null): Snippet
    {
        $snippet = $this->attachSnippetFromSlot($snippetId, Snippet::MIME_CSS, Snippet::INTERNAL_STYLESHEET_IDENTIFIER);
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
    public
    function &attachCssSnippetForRequest($snippetId, string $script = null): Snippet
    {
        $snippet = $this->attachSnippetFromRequest($snippetId, Snippet::MIME_CSS, Snippet::INTERNAL_JAVASCRIPT_IDENTIFIER);
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
    public
    function &attachJavascriptScriptForSlot($snippetId, string $script = null): Snippet
    {
        $snippet = &$this->attachSnippetFromSlot($snippetId, Snippet::EXTENSION_JS, Snippet::INTERNAL_JAVASCRIPT_IDENTIFIER);
        if ($script !== null) {
            $content = $snippet->getInternalDynamicContent();
            if ($content !== null) {
                $content .= $script;
            } else {
                $content = $script;
            }
            $snippet->setContent($content);
        }
        return $snippet;
    }

    /**
     * @param $snippetId
     * @return Snippet a snippet not in a slot
     */
    public
    function &attachJavascriptSnippetForRequest($snippetId): Snippet
    {
        return $this->attachSnippetFromRequest($snippetId, Snippet::EXTENSION_JS, Snippet::INTERNAL_JAVASCRIPT_IDENTIFIER);
    }

    /**
     * @param string $componentId
     * @param string $type
     * @param string $identifier
     * @return Snippet
     */
    private
    function &attachSnippetFromSlot(string $componentId, string $type, string $identifier): Snippet
    {
        global $ID;
        $slot = $ID;
        if ($slot === null) {
            LogUtility::log2file("The slot could not be identified (global ID is null)", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
        }
        $snippet = Snippet::getOrCreateSnippet($identifier, $type, $componentId)
            ->addSlot($slot);
        return $snippet;
    }

    private
    function &attachSnippetFromRequest($componentId, $type, $identifier): Snippet
    {
        $snippet = Snippet::getOrCreateSnippet($identifier, $type, $componentId)
            ->addSlot(Snippet::REQUEST_SLOT);
        return $snippet;
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
    public
    function attachJavascriptScriptForRequest(string $snippetId, string $relativeId)
    {
        $javascriptMedia = JavascriptLibrary::createJavascriptLibraryFromDokuwikiId($relativeId);
        $url = $javascriptMedia->getUrl();
        return $this->attachSnippetFromRequest($snippetId, Snippet::EXTENSION_JS, $url);

    }

    /**
     * @param string $snippetId
     * @param string $relativeId
     * @param string|null $integrity
     * @return Snippet
     */
    public
    function attachJavascriptComboResourceForSlot(string $snippetId, string $relativeId, string $integrity = null): Snippet
    {
        $javascriptMedia = JavascriptLibrary::createJavascriptLibraryFromDokuwikiId($relativeId);
        $url = $javascriptMedia->getUrl();
        return $this->attachJavascriptLibraryForSlot(
            $snippetId,
            $url,
            $integrity
        );

    }

    public
    function attachJavascriptComboLibrary()
    {
        return $this->attachJavascriptScriptForRequest("combo", "library:combo:dist:combo.min.js");
    }

    public
    function attachJavascriptLibraryForSlot(string $snippetId, string $url, string $integrity = null): Snippet
    {
        return $this
            ->attachSnippetFromSlot(
                $snippetId,
                Snippet::EXTENSION_JS,
                $url)
            ->setUrl($url, $integrity);
    }

    public
    function attachCssExternalStyleSheetForSlot(string $snippetId, string $url, string $integrity = null): Snippet
    {
        return $this
            ->attachSnippetFromSlot(
                $snippetId,
                Snippet::MIME_CSS,
                $url)
            ->setUrl($url, $integrity);
    }


}
