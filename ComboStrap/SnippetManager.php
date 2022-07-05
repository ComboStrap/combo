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
 *
 *
 * Minification:
 * Wrapper: https://packagist.org/packages/jalle19/php-yui-compressor
 * Require Yui compressor: https://packagist.org/packages/nervo/yuicompressor
 * sudo apt-get install default-jre
 *
 */
class SnippetManager
{


    const CANONICAL = "snippet-manager";
    const SCRIPT_TAG = "script";
    const LINK_TAG = "link";
    const STYLE_TAG = "style";
    const DATA_DOKUWIKI_ATT = "_data";


    /**
     * @var array SnippetManager array that contains one element (one {@link SnippetManager} scoped to the requested id
     */
    private static array $globalSnippetManager;

    /**
     *
     * Still needed anymore even if we scope the global object to the requested id
     * because the request id may not be set between test
     * Meaning that when we render dynamic content (ie without request id), we
     * will get the snippets of the first test and of the second
     */
    public static function reset()
    {
        self::$globalSnippetManager = [];
        Snippet::reset();
    }


    /**
     * @param $tag
     * @return string
     * See also {@link Snippet::getClass()} function
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

        if (PluginUtility::isTest()) {
            $id = "test_dynamic_script_execution";
        } else {
            LogUtility::msg("The requested Id could not be found, the snippets may not be scoped properly");
        }

        $snippetManager = self::$globalSnippetManager[$id];
        if ($snippetManager === null) {
            self::$globalSnippetManager = []; // delete old snippet manager for other request
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
    public function getAllSnippetsInDokuwikiArrayFormat(): array
    {
        $snippets = Snippet::getSnippets();
        return $this->snippetsToDokuwikiArray($snippets);
    }

    /**
     * @return array - the slot snippets (not the request snippet)
     */
    public function getSlotSnippetsInDokuwikiArrayFormat(): array
    {
        $snippets = Snippet::getSnippets();
        $slotSnippets = [];
        foreach ($snippets as $snippet) {
            if ($snippet->hasSlot(Snippet::REQUEST_SLOT)) {
                continue;
            }
            $slotSnippets[] = $snippet;
        }
        return $this->snippetsToDokuwikiArray($slotSnippets);
    }

    /**
     * @param Snippet[] $snippets
     * @return array
     */
    public function snippetsToDokuwikiArray(array $snippets): array
    {
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
                            $jsDokuwiki = $this->addHtmlAttributes($jsDokuwiki, $snippet);
                            ksort($jsDokuwiki);
                            $returnedDokuWikiFormat[self::SCRIPT_TAG][] = $jsDokuwiki;
                            break;
                        case Snippet::INTERNAL_TYPE:
                            $jsDokuwiki = []; //reset
                            if ($snippet->hasInlineContent()) {
                                try {
                                    $jsDokuwiki[self::DATA_DOKUWIKI_ATT] = $snippet->getInternalInlineAndFileContent();
                                } catch (ExceptionNotFound $e) {
                                    LogUtility::internalError("The internal js snippet ($snippet) has no content. Skipped", self::CANONICAL);
                                    continue 3;
                                }
                            } else {
                                $wikiPath = $snippet->getInternalPath();
                                try {
                                    $fetchUrl = FetcherLocalPath::createFromPath($wikiPath)->getFetchUrl();
                                    $jsDokuwiki["src"] = $fetchUrl->toHtmlString(); // html string at this point
                                    if (!$snippet->getCritical()) {
                                        $jsDokuwiki["defer"] = null;
                                    }
                                } catch (ExceptionNotFound $e) {
                                    LogUtility::internalError("The internal snippet path ($wikiPath) was not found. Skipped", self::CANONICAL);
                                    continue 3;
                                }

                            }
                            $jsDokuwiki = $this->addHtmlAttributes($jsDokuwiki, $snippet);
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
                            $cssDokuwiki = $this->addHtmlAttributes($cssDokuwiki, $snippet);
                            ksort($cssDokuwiki);
                            $returnedDokuWikiFormat[self::LINK_TAG][] = $cssDokuwiki;
                            break;
                        case Snippet::INTERNAL_TYPE:
                            /**
                             * CSS inline in script tag
                             * If they are critical or inline dynamic content is set, we add them in the page
                             */
                            $cssInternalArray = []; // reset
                            $inline = $snippet->getCritical() === true ||
                                ($snippet->getCritical() === false && $snippet->hasInlineContent());
                            if ($inline) {
                                try {
                                    $cssInternalArray[self::DATA_DOKUWIKI_ATT] = $snippet->getInternalInlineAndFileContent();
                                } catch (ExceptionNotFound $e) {
                                    LogUtility::internalError("The internal css snippet ($snippet) has no content. Skipped", self::CANONICAL);
                                    continue 3;
                                }
                            } else {
                                try {
                                    $fetchUrl = FetcherLocalPath::createFromPath($snippet->getInternalPath())->getFetchUrl();
                                    $cssInternalArray["rel"] = "stylesheet";
                                    $cssInternalArray["href"] = $fetchUrl->toHtmlString(); // html string at this point
                                } catch (ExceptionNotFound $e) {
                                    // the file should have been found at this point
                                    LogUtility::internalError("The internal css could not be added. Error:{$e->getMessage()}",self::CANONICAL);
                                    continue 3;
                                }

                            }
                            $cssInternalArray = $this->addHtmlAttributes($cssInternalArray, $snippet);
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


    public
    function getJsonArrayFromSlotSnippets($slot): ?array
    {
        $snippets = Snippet::getSnippets();
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
     * @throws ExceptionCompile
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
     *
     * This function should be called with a ACTION_HEADERS_SEND event
     * (not DOKUWIKI_STARTED because the {@link \action_plugin_combo_router} should
     * have run to set back the wiki id propertly
     *
     * If you need to split the css by type of action, see {@link \action_plugin_combo_docss::handleCssForDoAction()}
     */
    public
    function &attachCssInternalStylesheetForRequest($snippetId, string $script = null): Snippet
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
            try {
                $content = "{$snippet->getInternalDynamicContent()} $script";
            } catch (ExceptionNotFound $e) {
                $content = $script;
            }
            $snippet->setInlineContent($content);
        }
        return $snippet;
    }

    /**
     * @param $snippetId
     * @param string|null $script
     * @return Snippet a snippet not in a slot
     */
    public function &attachJavascriptInternalInlineForRequest($snippetId, string $script = null): Snippet
    {
        $snippet = $this->attachSnippetFromRequest($snippetId, Snippet::EXTENSION_JS, Snippet::INTERNAL_TYPE);
        if ($script != null) {
            $snippet->setInlineContent($script);
        }
        return $snippet;
    }

    public function &attachInternalJavascriptFromPathForRequest($snippetId, WikiPath $path): Snippet
    {
        $snippet = $this->attachSnippetFromRequest($snippetId, Snippet::EXTENSION_JS, Snippet::INTERNAL_TYPE)
            ->setInternalPath($path);
        return $snippet;
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
    public function attachJavascriptScriptForRequest(string $snippetId, string $relativeId): Snippet
    {

        $dokuPath = WikiPath::createComboResource($relativeId);
        try {
            $url = FetcherLocalPath::createFromPath($dokuPath)->getFetchUrl()->toAbsoluteUrlString();
        } catch (ExceptionNotFound $e) {
            LogUtility::internalError($e->getMessage());
            $url = "";
        }
        return $this->attachSnippetFromRequest($snippetId, Snippet::EXTENSION_JS, $url);

    }

    /**
     * @param string $snippetId
     * @param string $relativeId
     * @param string|null $integrity
     * @return Snippet
     */
    public function attachJavascriptComboResourceForSlot(string $snippetId, string $relativeId, string $integrity = null): Snippet
    {
        $dokuPath = WikiPath::createComboResource($relativeId);
        try {
            $url = FetcherLocalPath::createFromPath($dokuPath)->getFetchUrl()->toAbsoluteUrlString();
        } catch (ExceptionNotFound $e) {
            LogUtility::internalError($e->getMessage());
            $url = "";
        }
        return $this->attachJavascriptLibraryForSlot(
            $snippetId,
            $url,
            $integrity
        );

    }

    public
    function attachJavascriptComboLibrary(): Snippet
    {
        return $this->attachJavascriptScriptForRequest("combo", "library:combo:combo.min.js");
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

    private function addHtmlAttributes(array $attributesArray, Snippet $snippet): array
    {
        $htmlAttributes = $snippet->getHtmlAttributes();
        if ($htmlAttributes !== null) {
            foreach ($htmlAttributes as $name => $value) {
                $attributesArray[$name] = $value;
            }
        }
        $attributesArray["class"] = $snippet->getClass();
        return $attributesArray;
    }


    /**
     * @return Snippet[]
     */
    public function getSnippets(): array
    {
        return Snippet::getSnippets();
    }

    public function getRequestSnippetsInDokuwikiArrayFormat(): array
    {
        $snippets = Snippet::getSnippets();
        $slotSnippets = [];
        foreach ($snippets as $snippet) {
            if (!$snippet->hasSlot(Snippet::REQUEST_SLOT)) {
                continue;
            }
            $slotSnippets[] = $snippet;
        }
        return $this->snippetsToDokuwikiArray($slotSnippets);
    }

    /**
     * Output the snippet in HTML format
     * @return string - html string
     */
    public function toHtml(): string
    {
        $snippets = $this->getSlotSnippetsInDokuwikiArrayFormat();
        $xhtmlContent = "";
        foreach ($snippets as $htmlElement => $tags) {

            foreach ($tags as $tag) {
                $xhtmlContent .= DOKU_LF . "<$htmlElement";
                $attributes = "";
                $content = null;

                /**
                 * This code runs in editing mode
                 * or if the template is not strap
                 * No preload is then supported
                 */
                if ($htmlElement === "link") {
                    $relValue = $tag["rel"];
                    $relAs = $tag["as"];
                    if ($relValue === "preload") {
                        if ($relAs === "style") {
                            $tag["rel"] = "stylesheet";
                            unset($tag["as"]);
                        }
                    }
                }

                /**
                 * Print
                 */
                foreach ($tag as $attributeName => $attributeValue) {
                    if ($attributeName !== "_data") {
                        if ($attributeValue !== null) {
                            $attributes .= " $attributeName=\"$attributeValue\"";
                        } else {
                            $attributes .= " $attributeName";
                        }
                    } else {
                        $content = $attributeValue;
                    }
                }
                $xhtmlContent .= "$attributes>";
                if (!empty($content)) {
                    $xhtmlContent .= $content;
                }
                $xhtmlContent .= "</$htmlElement>" . DOKU_LF;
            }

        }
        return $xhtmlContent;
    }



}
