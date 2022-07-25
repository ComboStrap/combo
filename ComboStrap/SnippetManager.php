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


    /**
     * @var array SnippetManager array that contains one element (one {@link SnippetManager} scoped to the requested id
     */
    private static array $globalSnippetManager = [];

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
     * @return SnippetManager - the global reference
     * that is set for every run at the end of this file
     * TODO: migrate the attach function to {@link Snippet}
     *   because Snippet has already a global variable {@link Snippet::getOrCreateSnippetWithComponentId()}
     */
    public static function getOrCreate(): SnippetManager
    {

        $id = ExecutionContext::getActualOrCreateFromEnv()->getWikiId();
        $snippetManager = self::$globalSnippetManager[$id];
        if ($snippetManager === null) {
            self::reset(); // delete old snippet manager for other request
            $snippetManager = new SnippetManager();
            self::$globalSnippetManager[$id] = $snippetManager;
        }
        return $snippetManager;
    }


    /**
     * Returns all snippets (request and slot scoped)
     *
     * @return array of node type and an array of array of html attributes
     */
    public function getAllSnippets(): array
    {
        return Snippet::getSnippets();
    }

    /**
     * @return Snippet[] - the slot snippets (not the request snippet)
     */
    private function getSlotSnippets(): array
    {
        $snippets = Snippet::getSnippets();
        $slotSnippets = [];
        foreach ($snippets as $snippet) {
            if ($snippet->hasSlot(Snippet::REQUEST_SCOPE)) {
                continue;
            }
            $slotSnippets[] = $snippet;
        }
        return $slotSnippets;
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
         * on the config that is an internal inline script)
         *
         */
        foreach ($snippets as $snippet) {

            try {
                $returnedDokuWikiFormat[$snippet->getHtmlTag()][] = $snippet->toDokuWikiArray();
            } catch (ExceptionBadState|ExceptionNotFound $e) {
                LogUtility::error("An error has occurred while trying to add the HTML snippet ($snippet). Error:{$e->getMessage()}");
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
     * @throws ExceptionBadArgument
     * @throws ExceptionBadSyntax
     * @throws ExceptionNotFound
     */
    public
    function &attachCssInternalStyleSheetForSlot($snippetId, string $script = null): Snippet
    {
        $snippet = $this->attachSnippetFromSlot($snippetId, Snippet::EXTENSION_CSS);
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
     * have run to set back the wiki id properly
     *
     * If you need to split the css by type of action, see {@link \action_plugin_combo_docss::handleCssForDoAction()}
     */
    public
    function &attachCssInternalStylesheetForRequest($snippetId, string $script = null): Snippet
    {
        $snippet = $this->attachSnippetFromRequest($snippetId, Snippet::EXTENSION_CSS);
        if ($script != null) {
            $snippet->setInlineContent($script);
        }
        return $snippet;
    }

    /**
     * @param $snippetId
     * @param string|null $script
     * @return Snippet a snippet in a slot
     * @throws ExceptionBadArgument
     * @throws ExceptionBadSyntax
     * @throws ExceptionNotFound
     */
    public function &attachInternalJavascriptForSlot($snippetId, string $script = null): Snippet
    {
        $snippet = &$this->attachSnippetFromSlot($snippetId, Snippet::EXTENSION_JS);
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
    public
    function &attachJavascriptInternalInlineForRequest($snippetId, string $script = null): Snippet
    {
        $snippet = $this->attachSnippetFromRequest($snippetId, Snippet::EXTENSION_JS);
        if ($script != null) {
            $snippet->setInlineContent($script);
        }
        return $snippet;
    }

    public
    function attachInternalJavascriptFromPathForRequest($componentId, WikiPath $path): Snippet
    {
        return Snippet::getOrCreateSnippetWithPath($path)
            ->addSlot(Snippet::REQUEST_SCOPE)
            ->setComponentId($componentId);
    }

    public
    function &attachInternalJavascriptForRequest($snippetId): Snippet
    {
        $snippet = $this->attachSnippetFromRequest($snippetId, Snippet::EXTENSION_JS);
        return $snippet;
    }

    /**
     * @param string $snippetId
     * @param string $type
     * @return Snippet
     */
    private
    function &attachSnippetFromSlot(string $snippetId, string $type): Snippet
    {
        $slot = ExecutionContext::getActualOrCreateFromEnv()->getWikiId();
        $snippet = Snippet::getOrCreateSnippetWithComponentId($snippetId, $type)
            ->addSlot($slot);
        return $snippet;
    }

    private
    function &attachSnippetFromRequest($componentName, $type): Snippet
    {
        $snippet = Snippet::getOrCreateSnippetWithComponentId($componentName, $type)
            ->addSlot(Snippet::REQUEST_SCOPE);
        return $snippet;
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
        $dokuPath = WikiPath::createComboResource($relativeId);
        try {
            $url = FetcherRawLocalPath::createFromPath($dokuPath)->getFetchUrl()->toAbsoluteUrlString();
        } catch (ExceptionNotFound $e) {
            LogUtility::internalError($e->getMessage());
            $url = "";
        }
        return $this->attachExternalJavascriptLibraryForRunningSlot(
            $snippetId,
            $url,
            $integrity
        );

    }

    /**
     * Add a local javascript script as tag
     * (ie same as {@link SnippetManager::attachExternalJavascriptLibraryForRunningSlot()})
     * but for local resource combo file (library)
     *
     * For instance:
     *   * library:combo:combo.js
     *   * for a file located at dokuwiki_home\lib\plugins\combo\resources\library\combo\combo.js
     * @return Snippet
     */
    public
    function attachJavascriptComboLibrary(): Snippet
    {

        $wikiPath = ":library:combo:combo.min.js";
        $componentId = "combo";
        return $this->attachSnippetFromComboResourceDrive($wikiPath, $componentId);

    }

    public function attachSnippetFromComboResourceDrive(string $path, string $componentId): Snippet
    {

        $dokuPath = WikiPath::createComboResource($path);
        return Snippet::getOrCreateSnippetWithPath($dokuPath)
            ->setComponentId($componentId);

    }

    /**
     * @throws ExceptionBadSyntax
     * @throws ExceptionBadArgument
     * @throws ExceptionNotFound
     */
    public
    function attachExternalJavascriptLibraryForRunningSlot(string $componentId, string $url, string $integrity = null): Snippet
    {
        $url = Url::createFromString($url);
        $name = $url->getLastNameWithoutExtension();
        $path = Snippet::getInternalPathFromNameAndExtension($name, Snippet::EXTENSION_JS, Snippet::LIBRARY_BASE);
        return Snippet::getOrCreateSnippetWithPath($path)
            ->setScopeAsRunningSlot()
            ->setIntegrity($integrity)
            ->setRemoteUrl($url)
            ->setComponentId($componentId);
    }

    /**
     * @param string $componentId - the component id attached to this URL
     * @param string $url - the external url (The URL should have a file name as last name in the path)
     * @param string|null $integrity - the file integrity
     * @return Snippet
     * @throws ExceptionBadArgument
     * @throws ExceptionBadSyntax
     * @throws ExceptionNotFound
     */
    public
    function attachCssExternalStyleSheetForSlot(string $componentId, string $url, string $integrity = null): Snippet
    {
        $url = Url::createFromString($url);
        $libraryName = $url->getLastName();
        return $this
            ->attachSnippetFromSlot($libraryName, Snippet::EXTENSION_CSS)
            ->setIntegrity($integrity)
            ->setRemoteUrl($url)
            ->setComponentId($componentId);
    }


    public
    function attachJavascriptLibraryForRequest(string $componentName, string $url, string $integrity): Snippet
    {
        return $this
            ->attachSnippetFromRequest(
                $componentName,
                Snippet::EXTENSION_JS,
                $url)
            ->setIntegrity($integrity);

    }


    /**
     * @return Snippet[]
     */
    public
    function getSnippets(): array
    {
        return Snippet::getSnippets();
    }

    private
    function getRequestSnippets(): array
    {
        $snippets = Snippet::getSnippets();
        $slotSnippets = [];
        foreach ($snippets as $snippet) {
            if (!$snippet->hasSlot(Snippet::REQUEST_SCOPE)) {
                continue;
            }
            $slotSnippets[] = $snippet;
        }
        return $slotSnippets;
    }

    /**
     * Output the snippet in HTML format
     * The scope is mandatory:
     *  * {@link Snippet::ALL_SCOPE}
     *  * {@link Snippet::REQUEST_SCOPE}
     *  * {@link Snippet::SLOT_SCOPE}
     *
     * @return string - html string
     */
    private
    function toHtml($scope): string
    {
        switch ($scope) {
            case Snippet::SLOT_SCOPE:
                $snippets = $this->getSlotSnippets();
                break;
            case Snippet::REQUEST_SCOPE:
                $snippets = $this->getRequestSnippets();
                break;
            default:
            case Snippet::ALL_SCOPE:
                $snippets = $this->getAllSnippets();
                if ($scope !== Snippet::ALL_SCOPE) {
                    LogUtility::internalError("Scope ($scope) is unknown, we have defaulted to all");
                }
                break;
        }

        $snippetsArray = $this->snippetsToDokuwikiArray($snippets);
        $xhtmlContent = "";
        foreach ($snippetsArray as $htmlElement => $tags) {

            foreach ($tags as $tag) {
                $xhtmlContent .= "<$htmlElement";
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
                $xhtmlContent .= "</$htmlElement>";
            }

        }
        return $xhtmlContent;
    }

    public
    function toHtmlForAllSnippets(): string
    {
        return $this->toHtml(Snippet::ALL_SCOPE);
    }

    public
    function toHtmlForSlotSnippets(): string
    {
        return $this->toHtml(Snippet::SLOT_SCOPE);
    }

    public function addPopoverLibrary(): SnippetManager
    {
        $this->attachJavascriptInternalInlineForRequest(Snippet::COMBO_POPOVER);
        $this->attachCssInternalStylesheetForRequest(Snippet::COMBO_POPOVER);
        return $this;
    }


}
