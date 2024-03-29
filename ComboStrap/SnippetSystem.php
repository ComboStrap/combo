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


use ComboStrap\Web\Url;
use RuntimeException;

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
class SnippetSystem
{


    const CANONICAL = "snippet-system";


    /**
     * @return SnippetSystem - the global reference
     * that is set for every run at the end of this file
     * TODO: migrate the attach function to {@link Snippet}
     *   because Snippet has already a global variable {@link Snippet::getOrCreateFromComponentId()}
     */
    public static function getFromContext(): SnippetSystem
    {

        $executionContext = ExecutionContext::getActualOrCreateFromEnv();
        try {
            return $executionContext->getRuntimeObject(self::CANONICAL);
        } catch (ExceptionNotFound $e) {
            $snippetSystem = new SnippetSystem();
            $executionContext->setRuntimeObject(self::CANONICAL, $snippetSystem);
            return $snippetSystem;
        }

    }

    /**
     * @param Snippet[] $snippets
     * @return string
     */
    public static function toHtmlFromSnippetArray(array $snippets): string
    {
        $xhtmlContent = "";
        foreach ($snippets as $snippet) {

            if ($snippet->hasHtmlOutputAlreadyOccurred()) {
                continue;
            }

            $xhtmlContent .= $snippet->toXhtml();


        }
        return $xhtmlContent;
    }


    /**
     * Returns all snippets (request and slot scoped)
     *
     * @return Snippet[] of node type and an array of array of html attributes
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


    public static
    function toJsonArrayFromSlotSnippets($snippetsForSlot): array
    {

        $jsonSnippets = [];
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
                ->addElement($slot);
        }
        return $snippets;
    }


    /**
     * @param $componentId
     * @param string|null $script - the css snippet to add, otherwise it takes the file
     * @return Snippet a snippet not in a slot
     *
     * If you need to split the css by type of action, see {@link \action_plugin_combo_docss::handleCssForDoAction()}
     */
    public
    function &attachCssInternalStyleSheet($componentId, string $script = null): Snippet
    {
        $snippet = Snippet::getOrCreateFromComponentId($componentId, Snippet::EXTENSION_CSS);
        if ($script !== null) {
            $snippet->setInlineContent($script);
        }
        return $snippet;
    }


    /**
     * @param $componentId
     * @param string|null $script
     * @return Snippet a snippet in a slot
     */
    public function attachJavascriptFromComponentId($componentId, string $script = null): Snippet
    {
        $snippet = Snippet::getOrCreateFromComponentId($componentId, Snippet::EXTENSION_JS);
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


    public
    function attachInternalJavascriptFromPathForRequest($componentId, Path $path): Snippet
    {
        return Snippet::getOrCreateFromContext($path)
            ->addElement(Snippet::REQUEST_SCOPE)
            ->setComponentId($componentId);
    }


    /**
     * @param $componentId
     * @return Snippet[]
     */
    public function getSnippetsForComponent($componentId): array
    {
        $snippets = [];
        foreach ($this->getSnippets() as $snippet) {
            try {
                if ($snippet->getComponentId() === $componentId) {
                    $snippets[] = $snippet;
                }
            } catch (ExceptionNotFound $e) {
                //
            }
        }
        return $snippets;
    }

    /**
     * Utility function used in test
     * or to show how to test if snippets are present
     * @param $componentId
     * @return bool
     */
    public function hasSnippetsForComponent($componentId): bool
    {
        return count($this->getSnippetsForComponent($componentId)) > 0;
    }

    /**
     * @param $componentId
     * @param $type
     * @return Snippet
     * @deprecated - the slot is now added automatically at creation time via the context system
     */
    private
    function attachSnippetFromRequest($componentId, $type): Snippet
    {
        return Snippet::getOrCreateFromComponentId($componentId, $type)
            ->addElement(Snippet::REQUEST_SCOPE);
    }


    /**
     * @param string $snippetId
     * @param string $pathFromComboDrive
     * @param string|null $integrity
     * @return Snippet
     */
    public
    function attachJavascriptComboResourceForSlot(string $snippetId, string $pathFromComboDrive, string $integrity = null): Snippet
    {

        $dokuPath = WikiPath::createComboResource($pathFromComboDrive);
        return Snippet::getOrCreateFromContext($dokuPath)
            ->setComponentId($snippetId)
            ->setIntegrity($integrity);

    }

    /**
     * Add a local javascript script as tag
     * (ie same as {@link SnippetSystem::attachRemoteJavascriptLibrary()})
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

    public function attachSnippetFromComboResourceDrive(string $pathFromComboDrive, string $componentId): Snippet
    {

        $dokuPath = WikiPath::createComboResource($pathFromComboDrive);
        return Snippet::getOrCreateFromContext($dokuPath)
            ->setComponentId($componentId);

    }

    /**
     * @throws ExceptionBadSyntax - bad url
     * @throws ExceptionBadArgument - the url needs to have a file name
     */
    public
    function attachRemoteJavascriptLibrary(string $componentId, string $url, string $integrity = null): Snippet
    {
        $url = Url::createFromString($url);
        return Snippet::getOrCreateFromRemoteUrl($url)
            ->setIntegrity($integrity)
            ->setComponentId($componentId);
    }

    /**
     * Same component as attachRemoteJavascriptLibrary but without error
     * as the url is a code literal (ie written in the code)
     * @param string $componentId
     * @param string $url
     * @param string|null $integrity
     * @return Snippet
     */
    public
    function attachRemoteJavascriptLibraryFromLiteral(string $componentId, string $url, string $integrity = null): Snippet
    {
        try {
            $url = Url::createFromString($url);
            return Snippet::getOrCreateFromRemoteUrl($url)
                ->setIntegrity($integrity)
                ->setComponentId($componentId);
        } catch (ExceptionBadArgument|ExceptionBadSyntax $e) {
            throw new RuntimeException("Bad URL (" . $e->getMessage() .")", $e);
        }

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
    function attachRemoteCssStyleSheet(string $componentId, string $url, string $integrity = null): Snippet
    {
        $url = Url::createFromString($url);

        return Snippet::getOrCreateFromRemoteUrl($url)
            ->setIntegrity($integrity)
            ->setRemoteUrl($url)
            ->setComponentId($componentId);
    }

    /**
     * The same as attachRemoteCssStyleSheet but without any exception
     * as the URL is written in the code, it's to the dev to not messed up
     * @param string $componentId
     * @param string $url
     * @param string|null $integrity
     * @return Snippet
     */
    public
    function attachRemoteCssStyleSheetFromLiteral(string $componentId, string $url, string $integrity = null): Snippet
    {
        try {
            $url = Url::createFromString($url);
            return Snippet::getOrCreateFromRemoteUrl($url)
                ->setIntegrity($integrity)
                ->setRemoteUrl($url)
                ->setComponentId($componentId);
        } catch (ExceptionBadArgument|ExceptionBadSyntax $e) {
            throw new RuntimeException("Bad URL (" . $e->getMessage() .")", $e);
        }
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


        return self::toHtmlFromSnippetArray($snippets);
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

    public function addPopoverLibrary(): SnippetSystem
    {
        $this->attachJavascriptFromComponentId(Snippet::COMBO_POPOVER);
        $this->attachCssInternalStylesheet(Snippet::COMBO_POPOVER);
        return $this;
    }

    /**
     * @param $slot
     * @return Snippet[]
     */
    public function getSnippetsForSlot($slot): array
    {
        $snippets = Snippet::getSnippets();
        return array_filter($snippets,
            function ($s) use ($slot) {
                return $s->hasSlot($slot);
            });
    }


}
