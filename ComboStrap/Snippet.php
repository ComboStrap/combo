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


use JsonSerializable;

/**
 * Class Snippet
 * @package ComboStrap
 * A HTML tag:
 *   * CSS: link for href or style with content
 *   * Javascript: script
 *
 * A component to manage the extra HTML that
 * comes from components and that should come in the head HTML node
 *
 * A snippet identifier is a {@link Snippet::getLocalUrl() local file}
 *   * if there is content defined, it will be an {@link Snippet::hasInlineContent() inline}
 *   * if not, it will be the local file with the {@link Snippet::getLocalUrl()}
 *   * if not found or if the usage of the cdn is required, the {@link Snippet::getRemoteUrl() url} is used
 *
 */
class Snippet implements JsonSerializable
{
    /**
     * The head in css format
     * We need to add the style node
     */
    const EXTENSION_CSS = "css";
    /**
     * The head in javascript
     * We need to wrap it in a script node
     */
    const EXTENSION_JS = "js";

    /**
     * Properties of the JSON array
     */
    const JSON_COMPONENT_PROPERTY = "component"; // mandatory
    const JSON_DRIVE_PROPERTY = "drive";
    const JSON_PATH_PROPERTY = "path";
    const JSON_URL_PROPERTY = "url"; // mandatory if external
    const JSON_CRITICAL_PROPERTY = "critical";
    const JSON_ASYNC_PROPERTY = "async";
    const JSON_CONTENT_PROPERTY = "content";
    const JSON_INTEGRITY_PROPERTY = "integrity";
    const JSON_HTML_ATTRIBUTES_PROPERTY = "attributes";

    /**
     * The type identifier for a script snippet
     * (ie inline javascript or style)
     *
     * To make the difference with library
     * that have already an identifier with the url value
     * (ie external)
     */
    const INTERNAL_TYPE = "internal";
    const EXTERNAL_TYPE = "external";

    /**
     * When a snippet is scoped to the request
     * (ie not saved with a slot)
     *
     * They are unique on a request scope
     *
     * TlDR: The snippet does not depends to a slot but to a {@link FetcherPage page} and cannot therefore be cached along.
     *
     * The code that adds this snippet is not created by the parsing of content
     * or depends on the page.
     *
     * It's always called and add the snippet whatsoever.
     * Generally, this is an action plugin with a `TPL_METAHEADER_OUTPUT` hook
     * such as {@link Bootstrap}, {@link HistoricalBreadcrumbMenuItem},
     * ,...
     *
     * The request scope snippets are needed in admin page where there is no parsing at all
     *
     */
    const REQUEST_SCOPE = "request";
    const SLOT_SCOPE = "slot";
    const ALL_SCOPE = "all";
    public const COMBO_POPOVER = "combo-popover";
    public const DATA_DOKUWIKI_ATT = "_data";
    const CANONICAL = "snippet";
    public const STYLE_TAG = "style";
    public const SCRIPT_TAG = "script";
    public const LINK_TAG = "link";
    /**
     * Use CDN for local stored library
     */
    public const CONF_USE_CDN = "useCDN";

    /**
     * Where to find the file in the combo resources
     * if any in wiki path syntax
     */
    public const LIBRARY_BASE = ':library'; // external script, combo library
    public const SNIPPET_BASE = ":snippet"; // quick internal snippet


    protected static array $globalSnippets;


    /**
     * @var bool
     */
    private bool $critical;

    /**
     * @var string the text script / style (may be null if it's an external resources)
     */
    private string $inlineContent;

    private Url $remoteUrl;

    private string $integrity;

    private array $htmlAttributes;


    /**
     * @var array - the slots that needs this snippet (as key to get only one snippet by scope)
     * A special slot exists for {@link Snippet::REQUEST_SCOPE}
     * where a snippet is for the whole requested page
     *
     * It's also used in the cache because not all bars
     * may render at the same time due to the other been cached.
     *
     * There is two scope:
     *   * a slot - cached along the HTML
     *   * or  {@link Snippet::REQUEST_SCOPE} - never cached
     */
    private $slots;

    /**
     * @var bool run as soon as possible
     */
    private bool $async;
    private WikiPath $path;
    private string $componentId;

    /**
     * @param WikiPath $path - wiki path mandatory
     *   because it's the path of fetch and it's the storage format
     */
    public function __construct(WikiPath $path)
    {
        $this->path = $path;
    }


    /**
     * @throws ExceptionBadArgument
     */
    public static function createCssSnippetFromComponentId($componentId): Snippet
    {
        return Snippet::createSnippetFromComponentId($componentId, self::EXTENSION_CSS);
    }

    /**
     * @throws ExceptionBadArgument
     */
    public static function createSnippetFromComponentId($componentId, $type): Snippet
    {
        $path = self::getInternalPathFromNameAndExtension($componentId, $type);
        return Snippet::createSnippetFromPath($path)
            ->setComponentId($componentId);
    }


    /**
     * The snippet id is the url for external resources (ie external javascript / stylesheet)
     * otherwise if it's internal, it's the component id and it's type
     * @param string $componentId - the component id is a short id that you will found in the class (for internal snippet, it helps also resolve the file)
     * @param string $extension - {@link Snippet::EXTENSION_CSS css} or {@link Snippet::EXTENSION_JS js}
     * @return Snippet
     */
    public static function getOrCreateSnippetWithComponentId(string $componentId, string $extension): Snippet
    {

        $snippetPath = self::getInternalPathFromNameAndExtension($componentId, $extension);
        return self::getOrCreateSnippetWithPath($snippetPath)
            ->setComponentId($componentId);

    }


    /**
     * @return Snippet[]
     */
    public static function getSnippets(): array
    {
        if (self::$globalSnippets === null) {
            return [];
        }
        $keys = array_keys(self::$globalSnippets);
        return self::$globalSnippets[$keys[0]];
    }

    /**
     * When the snippets have been added to the page
     * the snippets are cleared
     *
     * It can happens that some snippet are in the head
     * and other are in the content
     * when the template is not strap
     *
     * @return void
     */
    public static function reset()
    {
        self::$globalSnippets = [];
    }

    /**
     * @param WikiPath $path
     * @return Snippet
     */
    public static function createSnippet(Path $path): Snippet
    {
        return new Snippet($path);
    }


    /**
     * @param $snippetId - a logical id
     * @return string - the class
     * See also {@link Snippet::getClass()} function
     */
    public static function getClassFromSnippetId($snippetId): string
    {
        return StyleUtility::addComboStrapSuffix("snippet-" . $snippetId);
    }

    /**
     * @throws ExceptionBadArgument
     */
    public static function createSnippetFromPath(WikiPath $path): Snippet
    {
        return new Snippet($path);
    }


    /**
     * @param Path $snippetPath
     * @return Snippet
     */
    public static function getOrCreateSnippetWithPath(Path $snippetPath)
    {

        $requestedPageId = WikiRequest::getOrCreateFromEnv()->getRequestedId();
        $snippets = &self::$globalSnippets[$requestedPageId];
        if ($snippets === null) {
            self::reset();
            self::$globalSnippets[$requestedPageId] = [];
            $snippets = &self::$globalSnippets[$requestedPageId];
        }
        $snippetGuid = $snippetPath->toUriString();
        $snippet = &$snippets[$snippetGuid];
        if ($snippet === null) {
            $snippet = self::createSnippet($snippetPath);
            $snippets[$snippetGuid] = $snippet;
        }
        return $snippet;
    }


    /**
     * @param $bool - if the snippet is critical, it would not be deferred or preloaded
     * @return Snippet for chaining
     * All css that are for animation or background for instance
     * should not be set as critical as they are not needed to paint
     * exactly the page
     *
     * If a snippet is critical, it should not be deferred
     *
     * By default:
     *   * all css are critical (except animation or background stylesheet)
     *   * all javascript are not critical
     *
     * This attribute is passed in the dokuwiki array
     * The value is stored in the {@link Snippet::getCritical()}
     */
    public function setCritical($bool): Snippet
    {
        $this->critical = $bool;
        return $this;
    }

    /**
     * If the library does not manipulate the DOM,
     * it can be ran as soon as possible (ie async)
     * @param $bool
     * @return $this
     */
    public function setDoesManipulateTheDomOnRun($bool): Snippet
    {
        $this->async = !$bool;
        return $this;
    }

    /**
     * @param $inlineContent - Set an inline content for a script or stylesheet
     * @return Snippet for chaining
     */
    public function setInlineContent($inlineContent): Snippet
    {
        $this->inlineContent = $inlineContent;
        return $this;
    }

    /**
     * The content that was set via a string (It should be used
     * for dynamic content, that's why it's called dynamic)
     * @return string
     * @throws ExceptionNotFound
     */
    public function getInternalDynamicContent(): string
    {
        if (!isset($this->inlineContent)) {
            throw new ExceptionNotFound("No inline content set");
        }
        return $this->inlineContent;
    }

    /**
     * @return string|null
     * @throws ExceptionNotFound -  if not found
     */
    public function getInternalFileContent(): string
    {
        $path = $this->getPath();
        return FileSystems::getContent($path);
    }

    public function getPath(): WikiPath
    {
        return $this->path;
    }

    public static function getInternalPathFromNameAndExtension($name, $extension, $baseDirectory = self::SNIPPET_BASE): WikiPath
    {

        switch ($extension) {
            case self::EXTENSION_CSS:
                $extension = "css";
                $subDirectory = "style";
                break;
            case self::EXTENSION_JS:
                $extension = "js";
                $subDirectory = "js";
                break;
            default:
                $message = "Unknown snippet type ($extension)";
                throw new ExceptionRuntimeInternal($message);

        }
        return WikiPath::createComboResource($baseDirectory)
            ->resolve($subDirectory)
            ->resolve(strtolower($name) . ".$extension");
    }

    public function hasSlot($slot): bool
    {
        if ($this->slots === null) {
            return false;
        }
        return key_exists($slot, $this->slots);
    }

    public function __toString()
    {
        return $this->path->toUriString();
    }

    public function getCritical(): bool
    {

        if (isset($this->critical)) {
            return $this->critical;
        }
        try {
            if ($this->path->getExtension() === self::EXTENSION_CSS) {
                // All CSS should be loaded first
                // The CSS animation / background can set this to false
                return true;
            }
        } catch (ExceptionNotFound $e) {
            // no path extension
        }
        return false;

    }

    public function getClass(): string
    {
        /**
         * The class for the snippet is just to be able to identify them
         *
         * The `snippet` prefix was added to be sure that the class
         * name will not conflict with a css class
         * Example: if you set the class to `combo-list`
         * and that you use it in a inline `style` tag with
         * the same class name, the inline `style` tag is not applied
         *
         */
        try {
            return StyleUtility::addComboStrapSuffix("snippet-" . $this->getComponentId());
        } catch (ExceptionNotFound $e) {
            LogUtility::internalError("A component id was not found for the snippet ($this)", self::CANONICAL);
            return StyleUtility::addComboStrapSuffix("snippet");
        }

    }


    /**
     * @return string - the component name identifier
     * All snippet with this component id are from the same component
     * @throws ExceptionNotFound
     */
    public function getComponentId(): string
    {
        if (isset($this->componentId)) {
            return $this->componentId;
        }
        throw new ExceptionNotFound("No component id was set");
    }


    public function toJsonArray(): array
    {
        return $this->jsonSerialize();

    }

    /**
     * @throws ExceptionCompile
     */
    public static function createFromJson($array): Snippet
    {


        $path = $array[self::JSON_PATH_PROPERTY];
        if ($path === null) {
            throw new ExceptionCompile("The snippet path property was not found in the json array");
        }

        $drive = $array[self::JSON_DRIVE_PROPERTY];
        if ($drive === null) {
            throw new ExceptionCompile("The snippet drive property was not found in the json array");
        }

        $wikiPath = WikiPath::create($path, $drive);
        $snippet = Snippet::getOrCreateSnippetWithPath($wikiPath);

        $componentName = $array[self::JSON_COMPONENT_PROPERTY];
        if ($componentName !== null) {
            $snippet->setComponentId($componentName);
        }

        $critical = $array[self::JSON_CRITICAL_PROPERTY];
        if ($critical !== null) {
            $snippet->setCritical($critical);
        }

        $async = $array[self::JSON_ASYNC_PROPERTY];
        if ($async !== null) {
            $snippet->setDoesManipulateTheDomOnRun($async);
        }

        $content = $array[self::JSON_CONTENT_PROPERTY];
        if ($content !== null) {
            $snippet->setInlineContent($content);
        }

        $attributes = $array[self::JSON_HTML_ATTRIBUTES_PROPERTY];
        if ($attributes !== null) {
            foreach ($attributes as $name => $value) {
                $snippet->addHtmlAttribute($name, $value);
            }
        }

        $integrity = $array[self::JSON_INTEGRITY_PROPERTY];
        if ($integrity !== null) {
            $snippet->setIntegrity($integrity);
        }

        return $snippet;

    }

    public function getExtension()
    {
        return $this->path->getExtension();
    }

    public function setIntegrity(?string $integrity): Snippet
    {
        if ($integrity === null) {
            return $this;
        }
        $this->integrity = $integrity;
        return $this;
    }

    public function addHtmlAttribute(string $name, string $value): Snippet
    {
        $this->htmlAttributes[$name] = $value;
        return $this;
    }

    public function addSlot(string $slot): Snippet
    {
        $this->slots[$slot] = 1;
        return $this;
    }

    public function getType(): string
    {
        if ($this->hasInlineContent()) {
            return self::INTERNAL_TYPE;
        }
        $fileExists = FileSystems::exists($this->path);
        if (!$fileExists) {
            if (isset($this->remoteUrl)) {
                return self::EXTERNAL_TYPE;
            } else {
                LogUtility::internalError("The snippet ($this) have a path ($this->path) that does not exists and does not have any external url.");
                return self::INTERNAL_TYPE;
            }
        }
        // if cdn
        $useCdn = $this->shouldUseCdn();
        if ($useCdn && isset($this->remoteUrl)) {
            return self::EXTERNAL_TYPE;
        }
        return self::INTERNAL_TYPE;
    }

    /**
     * @throws ExceptionBadArgument - if the path cannot be served (ie transformed as wiki path)
     */
    public function getLocalUrl(): Url
    {
        $path = WikiPath::createFromPathObject($this->path);
        return FetcherRawLocalPath::createFromPath($path)->getFetchUrl();
    }


    /**
     * @throws ExceptionNotFound
     */
    public function getRemoteUrl(): Url
    {
        if (!isset($this->remoteUrl)) {
            throw new ExceptionNotFound("No remote url found");
        }
        return $this->remoteUrl;
    }

    /**
     * @throws ExceptionNotFound
     */
    public function getIntegrity(): ?string
    {
        if (!isset($this->integrity)) {
            throw new ExceptionNotFound("No integrity");
        }
        return $this->integrity;
    }

    public function getHtmlAttributes(): array
    {
        if (!isset($this->htmlAttributes)) {
            return [];
        }
        return $this->htmlAttributes;
    }


    /**
     * @throws ExceptionNotFound
     */
    public function getInternalInlineAndFileContent(): string
    {
        $totalContent = null;
        try {
            $totalContent = $this->getInternalFileContent();
        } catch (ExceptionNotFound $e) {
            // no
        }

        try {
            $totalContent .= $this->getInternalDynamicContent();
        } catch (ExceptionNotFound $e) {
            // no
        }
        if ($totalContent === null) {
            throw new ExceptionNotFound("No content");
        }
        return $totalContent;

    }


    /**
     */
    public function jsonSerialize(): array
    {

        $dataToSerialize = [
            self::JSON_PATH_PROPERTY => $this->getPath()->toPathString(),
            self::JSON_DRIVE_PROPERTY => $this->getPath()->getDrive()
        ];

        try {
            $dataToSerialize[self::JSON_COMPONENT_PROPERTY] = $this->getComponentId();
        } catch (ExceptionNotFound $e) {
            LogUtility::internalError("The component id was not set for the snippet ($this)");
        }

        if (isset($this->remoteUrl)) {
            $dataToSerialize[self::JSON_URL_PROPERTY] = $this->remoteUrl->toString();
        }
        if (isset($this->integrity)) {
            $dataToSerialize[self::JSON_INTEGRITY_PROPERTY] = $this->integrity;
        }
        if (isset($this->critical)) {
            $dataToSerialize[self::JSON_CRITICAL_PROPERTY] = $this->critical;
        }
        if (isset($this->async)) {
            $dataToSerialize[self::JSON_ASYNC_PROPERTY] = $this->async;
        }
        if (isset($this->inlineContent)) {
            $dataToSerialize[self::JSON_CONTENT_PROPERTY] = $this->inlineContent;
        }
        if (isset($this->htmlAttributes)) {
            $dataToSerialize[self::JSON_HTML_ATTRIBUTES_PROPERTY] = $this->htmlAttributes;
        }
        return $dataToSerialize;
    }


    public function hasInlineContent(): bool
    {
        return isset($this->inlineContent);
    }

    private
    function getMaxInlineSize()
    {
        return PluginUtility::getConfValue(SvgImageLink::CONF_MAX_KB_SIZE_FOR_INLINE_SVG, 2) * 1024;
    }

    /**
     * Returns if the internal snippet should be incorporated
     * in the page or not
     *
     * Requiring a lot of small javascript file adds a penalty to page load
     *
     * @return bool
     */
    public function shouldBeInlined(): bool
    {
        /**
         * If there is inline content, true
         */
        if ($this->hasInlineContent()) {
            return true;
        }

        /**
         *
         */
        $internalPath = $this->getPath();
        if (!FileSystems::exists($internalPath)) {
            try {
                $this->getRemoteUrl();
                return false;
            } catch (ExceptionNotFound $e) {
                // no remote url, no file, no inline: error
                LogUtility::internalError("The snippet ($this) does not have content defined (the path does not exist, no inline content and no remote url)", self::CANONICAL);
                return true;
            }

        }
        /**
         * If this is a path, true if the file is small enough
         */
        if (FileSystems::getSize($internalPath) > $this->getMaxInlineSize()) {
            return false;
        }

        return true;
    }

    /**
     * @throws ExceptionBadState - an error where for instance an inline script doe snot have any content
     * @throws ExceptionNotFound - an error where the source was not found
     */
    public function toDokuWikiArray(): array
    {
        $array = $this->toDokuWikiArrayWithGeneratedId();
        unset($array[TagAttributes::GENERATED_ID_KEY]);
        return $array;
    }

    /**
     * @throws ExceptionBadState - an error where for instance an inline script doe snot have any content
     * @throws ExceptionNotFound - an error where the source was not found
     */
    private function toDokuWikiArrayWithGeneratedId(): array
    {
        $htmlAttributes = TagAttributes::createFromCallStackArray($this->getHtmlAttributes())
            ->addClassName($this->getClass());
        $type = $this->getType();
        $extension = $this->getExtension();
        switch ($extension) {
            case Snippet::EXTENSION_JS:
                switch ($type) {
                    case Snippet::EXTERNAL_TYPE:
                        $htmlAttributes
                            ->addOutputAttributeValue("src", $this->getRemoteUrl()->toString())
                            ->addOutputAttributeValue("crossorigin", "anonymous");
                        try {
                            $integrity = $this->getIntegrity();
                            $htmlAttributes->addOutputAttributeValue("integrity", $integrity);
                        } catch (ExceptionNotFound $e) {
                            // ok
                        }
                        $critical = $this->getCritical();
                        if (!$critical) {
                            $htmlAttributes->addBooleanOutputAttributeValue("defer");
                            // not async: it will run as soon as possible
                            // the dom main not be loaded completely, the script may miss HTML dom element
                        }
                        return $htmlAttributes->toCallStackArray();

                    case Snippet::INTERNAL_TYPE:
                    default:
                        /**
                         * This may broke the dependencies
                         * if a small javascript depend on a large one
                         * that is not yet loaded and does not wait for it
                         */
                        switch ($this->shouldBeInlined()) {
                            default:
                            case true:
                                try {
                                    $jsDokuwiki = $htmlAttributes->toCallStackArray();
                                    $jsDokuwiki[self::DATA_DOKUWIKI_ATT] = $this->getInternalInlineAndFileContent();
                                    return $jsDokuwiki;
                                } catch (ExceptionNotFound $e) {
                                    throw new ExceptionBadState("The internal js snippet ($this) has no content. Skipped", self::CANONICAL);
                                }
                            case false:

                                $wikiPath = $this->getPath();
                                try {
                                    $fetchUrl = FetcherRawLocalPath::createFromPath($wikiPath)->getFetchUrl();
                                    /**
                                     * Dokuwiki transforms them in HTML format
                                     */
                                    $htmlAttributes->addOutputAttributeValue("src", $fetchUrl->toString());
                                    if (!$this->getCritical()) {
                                        $htmlAttributes->addBooleanOutputAttributeValue("defer");
                                    }
                                } catch (ExceptionNotFound $e) {
                                    throw new ExceptionNotFound("The internal snippet path ($wikiPath) was not found. Skipped", self::CANONICAL);
                                }
                                return $htmlAttributes->toCallStackArray();

                        }
                }

            case Snippet::EXTENSION_CSS:
                switch ($type) {
                    case Snippet::EXTERNAL_TYPE:
                        $htmlAttributes
                            ->addOutputAttributeValue("rel", "stylesheet")
                            ->addOutputAttributeValue("href", $this->getRemoteUrl()->toString())
                            ->addOutputAttributeValue("crossorigin", "anonymous");

                        try {
                            $integrity = $this->getIntegrity();
                            $htmlAttributes->addOutputAttributeValue("integrity", $integrity);
                        } catch (ExceptionNotFound $e) {
                            // ok
                        }

                        $critical = $this->getCritical();
                        if (!$critical && FetcherPage::isEnabledAsShowAction()) {
                            $htmlAttributes->addOutputAttributeValue("rel", "preload")
                                ->addOutputAttributeValue('as', self::STYLE_TAG);
                        }
                        return $htmlAttributes->toCallStackArray();
                    case Snippet::INTERNAL_TYPE:
                        /**
                         * CSS inline in script tag
                         * If they are critical or inline dynamic content is set, we add them in the page
                         */
                        $inline = $this->getCritical() === true ||
                            ($this->getCritical() === false && $this->hasInlineContent());
                        if ($inline) {
                            try {
                                $cssInternalArray = $htmlAttributes->toCallStackArray();
                                $cssInternalArray[self::DATA_DOKUWIKI_ATT] = $this->getInternalInlineAndFileContent();
                                return $cssInternalArray;
                            } catch (ExceptionNotFound $e) {
                                throw new ExceptionNotFound("The internal css snippet ($this) has no content.", self::CANONICAL);
                            }
                        } else {

                            $fetchUrl = FetcherRawLocalPath::createFromPath($this->getPath())->getFetchUrl();
                            /**
                             * Dokuwiki transforms/encode the href in HTML
                             */
                            return $htmlAttributes
                                ->addOutputAttributeValue("rel", "stylesheet")
                                ->addOutputAttributeValue("href", $fetchUrl->toString())
                                ->toCallStackArray();

                        }
                    default:
                        throw new ExceptionBadState("Unknown css snippet type ($type", self::CANONICAL);
                }

            default:
                throw new ExceptionBadState("The extension ($extension) is unknown", self::CANONICAL);
        }
    }

    /**
     * The HTML tag
     * @throws ExceptionBadState
     */
    public function getHtmlTag(): string
    {
        $extension = $this->getExtension();
        switch ($extension) {
            case Snippet::EXTENSION_JS:
                return self::SCRIPT_TAG;
            case Snippet::EXTENSION_CSS:
                if ($this->shouldBeInlined()) {
                    return Snippet::STYLE_TAG;
                } else {
                    return Snippet::LINK_TAG;
                }
            default:
                throw new ExceptionBadState("The extension ($extension) is unknown");
        }
    }

    public function setComponentId(string $componentId): Snippet
    {
        $this->componentId = $componentId;
        return $this;
    }

    public function setRemoteUrl(Url $url): Snippet
    {
        $this->remoteUrl = $url;
        return $this;
    }

    public function setScopeAsRunningSlot(): Snippet
    {
        return $this;
    }


    private function shouldUseCdn()
    {
        return PluginUtility::getConfValue(self::CONF_USE_CDN, SnippetManager::CONF_USE_CDN_DEFAULT);
    }


}
