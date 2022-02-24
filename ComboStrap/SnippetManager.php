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
 *
 * Public interface of {@link Snippet}
 *
 * All plugin/component should use the attach functions to add a internal or external
 * stylesheet/javascript to a slot or request scoped
 *
 * Note:
 * All function with the suffix
 *   * `ForSlot` are snippets for a bar (ie page, sidebar, ...) - cached
 *   * `ForRequests` are snippets added for the HTTP request - not cached. Example of request component: message, anchor
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
     * Transform in dokuwiki format
     *
     * @return array of node type and an array of array of html attributes
     */
    public function getAllSnippetsToDokuwikiArray(): array
    {
        $snippets = Snippet::getSnippets();
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
         * The order is the order where they were added/created.
         *
         * The internal script may be dependent on the external javascript
         * and vice-versa (for instance, Math-Jax library is dependent
         * on the config that is an internal script)
         *
         */
        foreach ($snippets as $snippet) {

            $type = $snippet->getType();


            $extension = $snippet->getExtension();
            switch ($extension) {
                case Snippet::EXTENSION_JS:
                    switch ($type) {
                        case Snippet::EXTERNAL_TYPE:

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
                                // not async: it will run as soon as possible
                                // the dom main not be loaded completely, the script may miss HTML dom element
                            }
                            $jsDokuwiki = $this->addExtraHtml($jsDokuwiki, $snippet);
                            ksort($jsDokuwiki);
                            $returnedDokuWikiFormat[self::SCRIPT_TAG][] = $jsDokuwiki;
                            break;
                        case Snippet::INTERNAL_TYPE:
                            $content = $snippet->getInternalInlineAndFileContent();
                            if ($content === null) {
                                LogUtility::msg("The internal js snippet ($snippet) has no content. Skipped");
                                continue 3;
                            }
                            $jsDokuwiki = array(
                                "class" => $snippet->getClass(),
                                self::DATA_DOKUWIKI_ATT => $content
                            );
                            $jsDokuwiki = $this->addExtraHtml($jsDokuwiki, $snippet);
                            $returnedDokuWikiFormat[self::SCRIPT_TAG][] = $jsDokuwiki;
                            break;
                        default:
                            LogUtility::msg("Unknown javascript snippet type");
                    }
                    break;
                case Snippet::EXTENSION_CSS:
                    switch ($type) {
                        case Snippet::EXTERNAL_TYPE:
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
                            $cssDokuwiki = $this->addExtraHtml($cssDokuwiki, $snippet);
                            ksort($cssDokuwiki);
                            $returnedDokuWikiFormat[self::LINK_TAG][] = $cssDokuwiki;
                            break;
                        case Snippet::INTERNAL_TYPE:
                            /**
                             * CSS inline in script tag
                             * They are all critical
                             */
                            $content = $snippet->getInternalInlineAndFileContent();
                            if ($content === null) {
                                LogUtility::msg("The internal css snippet ($snippet) has no content. Skipped");
                                continue 3;
                            }
                            $cssInternalArray = array(
                                "class" => $snippet->getClass(),
                                self::DATA_DOKUWIKI_ATT => $content
                            );
                            $cssInternalArray = $this->addExtraHtml($cssInternalArray, $snippet);
                            $returnedDokuWikiFormat[self::STYLE_TAG][] = $cssInternalArray;
                            break;
                        default:
                            LogUtility::msg("Unknown css snippet type");
                    }
                    break;
                default:
                    LogUtility::msg("The extension ($extension) is unknown, the external snippet ($snippet) was not added");
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


    public
    function getJsonArrayFromSlotSnippets($slot): ?array
    {
        $snippets = Snippet::getSnippets();
        if ($snippets === null) {
            return null;
        }
        $snippetsForSlot = array_filter($snippets,
            function ($s) use ($slot) {
                return $s->hasSlot($slot);
            });
        $jsonSnippets = null;
        foreach ($snippetsForSlot as $snippet) {
            $jsonSnippets[] = $snippet->toJsonArray();
        }
        return $jsonSnippets;

    }

    /**
     * @param array $array
     * @param string $slot
     * @return null|Snippet[]
     * @throws ExceptionCombo
     */
    public
    function getSlotSnippetsFromJsonArray(array $array, string $slot): ?array
    {
        $snippets = null;
        foreach ($array as $element) {
            $snippets[] = Snippet::createFromJson($element)
                ->addSlot($slot);
        }
        return $snippets;
    }


    /**
     * @param $snippetId
     * @param string|null $script - the css snippet to add, otherwise it takes the file
     * @return Snippet a snippet not in a slot
     */
    public
    function &attachCssInternalStyleSheetForSlot($snippetId, string $script = null): Snippet
    {
        $snippet = $this->attachSnippetFromSlot($snippetId, Snippet::EXTENSION_CSS, Snippet::INTERNAL_TYPE);
        if ($script !== null) {
            $snippet->setInlineContent($script);
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
        $snippet = $this->attachSnippetFromRequest($snippetId, Snippet::EXTENSION_CSS, Snippet::INTERNAL_TYPE);
        if ($script != null) {
            $snippet->setInlineContent($script);
        }
        return $snippet;
    }

    /**
     * @param $snippetId
     * @param string|null $script
     * @return Snippet a snippet in a slot
     */
    public
    function &attachInternalJavascriptForSlot($snippetId, string $script = null): Snippet
    {
        $snippet = &$this->attachSnippetFromSlot($snippetId, Snippet::EXTENSION_JS, Snippet::INTERNAL_TYPE);
        if ($script !== null) {
            $content = $snippet->getInternalDynamicContent();
            if ($content !== null) {
                $content .= $script;
            } else {
                $content = $script;
            }
            $snippet->setInlineContent($content);
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
        return $this->attachSnippetFromRequest($snippetId, Snippet::EXTENSION_JS, Snippet::INTERNAL_TYPE);
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
        $slot = PluginUtility::getCurrentSlotId();
        $snippet = Snippet::getOrCreateSnippet($identifier, $type, $componentId)
            ->addSlot($slot);
        return $snippet;
    }

    private
    function &attachSnippetFromRequest($componentName, $type, $internalOrUrlIdentifier): Snippet
    {
        $snippet = Snippet::getOrCreateSnippet($internalOrUrlIdentifier, $type, $componentName)
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
            ->setIntegrity($integrity);
    }

    public
    function attachCssExternalStyleSheetForSlot(string $snippetId, string $url, string $integrity = null): Snippet
    {
        return $this
            ->attachSnippetFromSlot(
                $snippetId,
                Snippet::EXTENSION_CSS,
                $url)
            ->setIntegrity($integrity);
    }


    public function attachJavascriptLibraryForRequest(string $componentName, string $url, string $integrity): Snippet
    {
        return $this
            ->attachSnippetFromRequest(
                $componentName,
                Snippet::EXTENSION_JS,
                $url)
            ->setIntegrity($integrity);

    }

    private function addExtraHtml(array $attributesArray, Snippet $snippet): array
    {
        $htmlAttributes = $snippet->getHtmlAttributes();
        if ($htmlAttributes !== null) {
            foreach ($htmlAttributes as $name => $value) {
                $attributesArray[$name] = $value;
            }
        }
        return $attributesArray;
    }


}
