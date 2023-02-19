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


use action_plugin_combo_docustom;
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
     * Not all page requested have an id
     * (for instance, the admin page)
     * A menu item may want to add a snippet
     * on a dynamic page
     */
    const REQUEST_SCOPE = "request";

    const SLOT_SCOPE = "slot";
    const ALL_SCOPE = "all";
    public const COMBO_POPOVER = "combo-popover";
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
    public const CONF_USE_CDN_DEFAULT = 1;


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
     * @var bool a property to track if a snippet has already been asked in a html output
     * (ie with a to function such as {@link Snippet::toTagAttributes()} or {@link Snippet::toDokuWikiArray()}
     * We use it to not delete the state of {@link ExecutionContext} in order to check the created snippet
     * during an execution
     *
     * The positive side effect is that even if the snippet is used in multiple markup for a page,
     * It will be outputted only once.
     */
    private bool $hasHtmlOutputOccurred = false;

    /**
     * @param WikiPath $path - wiki path mandatory
     *   because it's the path of fetch and it's the storage format
     * use {@link Snippet::getOrCreateFromContext()}
     */
    private function __construct(WikiPath $path)
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
    public static function getOrCreateFromComponentId(string $componentId, string $extension): Snippet
    {

        $snippetPath = self::getInternalPathFromNameAndExtension($componentId, $extension);
        return self::getOrCreateFromContext($snippetPath)
            ->setComponentId($componentId);

    }


    /**
     *
     *
     * The order is the order where they were added/created.
     *
     * The internal script may be dependent on the external javascript
     * and vice-versa (for instance, Math-Jax library is dependent
     * on the config that is an internal inline script)
     *
     * @return Snippet[]
     *
     */
    public static function getSnippets(): array
    {
        try {
            return ExecutionContext::getActualOrCreateFromEnv()->getRuntimeObject(self::CANONICAL);
        } catch (ExceptionNotFound $e) {
            return [];
        }
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
    public static function getClassFromComponentId($snippetId): string
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
     * @param Path $localSnippetPath - the path is the snippet identifier (it's not mandatory that the snippet is locally available
     * but if it's, it permits to work without any connection by setting the {@link Snippet::CONF_USE_CDN cdn} to off
     * @return Snippet
     */
    public static function getOrCreateFromContext(Path $localSnippetPath): Snippet
    {

        $executionContext = ExecutionContext::getActualOrCreateFromEnv();
        try {
            $snippets = &$executionContext->getRuntimeObject(self::CANONICAL);
        } catch (ExceptionNotFound $e) {
            $snippets = [];
            $executionContext->setRuntimeObject(self::CANONICAL, $snippets);
        }
        $snippetGuid = $localSnippetPath->toUriString();
        $snippet = &$snippets[$snippetGuid];
        if ($snippet === null) {
            $snippet = self::createSnippet($localSnippetPath);
            /**
             *
             * The order is the order where they were added/created.
             *
             * The internal script may be dependent on the external javascript
             * and vice-versa (for instance, Math-Jax library is dependent
             * on the config that is an internal inline script)
             *
             */
            $snippets[$snippetGuid] = $snippet;
        }

        try {
            $executingFetcher = $executionContext
                ->getExecutingMarkupHandler();
            /**
             * New way
             */
            $executingFetcher->addSnippet($snippet);
            try {
                /**
                 * Old way
                 * @deprecated
                 */
                $wikiId = $executingFetcher->getSourcePath()->toWikiPath()->getWikiId();
                $snippet->addSlot($wikiId);
            } catch (ExceptionCast $e) {
                // not a wiki path
                $wikiId = $executingFetcher->getSourcePath()->toQualifiedId();
                $snippet->addSlot($wikiId);
            } catch (ExceptionNotFound $e) {
                // string/dynamic run
            }
        } catch (ExceptionNotFound $e) {
            // admin page or templating not on
            try {
                $wikiId = $executionContext->getExecutingWikiId();
                $snippet->addSlot($wikiId);
            } catch (ExceptionNotFound $e) {
                $snippet->addSlot(Snippet::REQUEST_SCOPE);
            }
        }

        return $snippet;

    }

    /**
     * Create a snippet from the ComboDrive
     * @throws ExceptionBadArgument
     */
    public static function createComboSnippet(string $wikiPath): Snippet
    {
        $wikiPathObject = WikiPath::createComboResource($wikiPath);
        return self::createSnippetFromPath($wikiPathObject);
    }

    /**
     * @param string $wikiPath - the wiki path should be absolute relative to the library namespace
     * @return Snippet
     *
     * Example: `:bootstrap:4.5.0:bootstrap.min.css`
     */
    public static function getOrCreateFromLibraryNamespace(string $wikiPath): Snippet
    {
        $wikiPathObject = WikiPath::createComboResource(self::LIBRARY_BASE . $wikiPath);
        return self::getOrCreateFromContext($wikiPathObject);
    }

    /**
     * An utility class to create a snippet from a remote url
     *
     * If you want to be able to serve the library locally, you
     * should create the snippet via the {@link Snippet::getOrCreateFromLibraryNamespace() local path}
     * and set {@link Snippet::setRemoteUrl() remote url}
     *
     * @throws ExceptionBadArgument - if the url does not have a file name
     */
    public static function getOrCreateFromRemoteUrl(Url $url): Snippet
    {

        try {
            $libraryName = $url->getLastName();
        } catch (ExceptionNotFound $e) {
            $messageFormat = "The following url ($url) does not have a file name. To create a snippet from a remote url, the url should have a path where the last is the name of the library file.";
            throw new ExceptionBadArgument($messageFormat);
        }
        /**
         * The file generally does not exists
         */
        $localPath = WikiPath::createComboResource(Snippet::LIBRARY_BASE . ":$libraryName");
        try {
            $localPath->getExtension();
        } catch (ExceptionNotFound $e) {
            $messageFormat = "The url has a file name ($libraryName) that does not have any extension. To create a snippet from a remote url, the url should have a path where the last is the name of the library file. ";
            throw new ExceptionBadArgument($messageFormat);
        }
        return self::getOrCreateFromContext($localPath)
            ->setRemoteUrl($url);


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

        $wikiPath = WikiPath::createFromPath($path, $drive);
        $snippet = Snippet::getOrCreateFromContext($wikiPath);

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

        $remoteUrl = $array[self::JSON_URL_PROPERTY];
        if ($remoteUrl !== null) {
            $snippet->setRemoteUrl(Url::createFromString($remoteUrl));
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

    public function useLocalUrl(): bool
    {

        /**
         * use cdn is on and there is a remote url
         */
        $useCdn = ExecutionContext::getActualOrCreateFromEnv()->getConfValue(self::CONF_USE_CDN, self::CONF_USE_CDN_DEFAULT) === 1;
        if ($useCdn && isset($this->remoteUrl)) {
            return false;
        }

        /**
         * use cdn is off and there is a file
         */
        $fileExists = FileSystems::exists($this->path);
        if ($fileExists) {
            return true;
        }

        /**
         * Use cdn is off and there is a remote url
         */
        if (isset($this->remoteUrl)) {
            return false;
        }

        /**
         *
         * This is a inline script (no local file then)
         *
         * We default to the local url that will return an error
         * when fetched
         */
        if (!$this->shouldBeInlined()) {
            LogUtility::internalError("The snippet ($this) is not a inline script, it has a path ($this->path) that does not exists and does not have any external url.");
        }
        return false;

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
            self::JSON_PATH_PROPERTY => $this->getPath()->toQualifiedId(),
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
        return Site::getConfValue(SiteConfig::HTML_MAX_KB_SIZE_FOR_INLINE_ELEMENT, 2) * 1024;
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
         * If the file does not exist and that there is a remote url
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
         * The local file
         * If javascript and always inline
         */
        if ($this->getExtension() === Snippet::EXTENSION_JS) {

            try {
                $lastName = $internalPath->getLastName();
            } catch (ExceptionNotFound $e) {
                LogUtility::internalError("Every snippet should have a last name");
                return false;
            }

            /**
             * If this is a library don't inline
             * Why ? The local combo.min.js library depends on bootstrap.min.js
             */
            $libraryExtension = ".min.js";
            $isLibrary = substr($lastName, -strlen($libraryExtension)) === $libraryExtension;
            if (!$this->hasRemoteUrl() && !$isLibrary) {
                $alwaysInline = ExecutionContext::getActualOrCreateFromEnv()
                    ->getConfig()
                    ->isLocalJavascriptAlwaysInlined();
                if ($alwaysInline) {
                    return true;
                }
            }
        }

        /**
         * The file exists (inline if small size)
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
        $array = $this->toTagAttributes()->toCallStackArray();
        unset($array[TagAttributes::GENERATED_ID_KEY]);
        return $array;
    }


    /**
     * The HTML tag
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
                // it should not happen as the devs are the creator of snippet (not the user)
                LogUtility::internalError("The extension ($extension) is unknown", self::CANONICAL);
                return "";
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


    public function useRemoteUrl(): bool
    {
        return !$this->useLocalUrl();
    }

    /**
     * @throws ExceptionBadState - if no content was found
     * @throws ExceptionNotFound - if the file was not found
     */
    public function toTagAttributes(): TagAttributes
    {

        if ($this->hasHtmlOutputOccurred) {
            $message = "The snippet ($this) has already been asked. It may have been added twice to the HTML page";
            if (PluginUtility::isTest()) {
                $message = "Error: you may be running two pages fetch in the same execution context. $message";
            }
            LogUtility::internalError($message);
        }
        $this->hasHtmlOutputOccurred = true;

        $tagAttributes = TagAttributes::createFromCallStackArray($this->getHtmlAttributes())
            ->addClassName($this->getClass());
        $extension = $this->getExtension();
        switch ($extension) {
            case Snippet::EXTENSION_JS:

                if ($this->shouldBeInlined()) {

                    try {
                        $tagAttributes->setInnerText($this->getInternalInlineAndFileContent());
                        return $tagAttributes;
                    } catch (ExceptionNotFound $e) {
                        throw new ExceptionBadState("The internal js snippet ($this) has no content. Skipped", self::CANONICAL);
                    }

                } else {

                    if ($this->useRemoteUrl()) {
                        $fetchUrl = $this->getRemoteUrl();
                    } else {
                        $wikiPath = $this->getPath();
                        $fetchUrl = FetcherRawLocalPath::createFromPath($wikiPath)->getFetchUrl();
                    }

                    /**
                     * Dokuwiki encodes the URL in HTML format
                     */
                    $tagAttributes
                        ->addOutputAttributeValue("src", $fetchUrl->toString())
                        ->addOutputAttributeValue("crossorigin", "anonymous");
                    try {
                        $integrity = $this->getIntegrity();
                        $tagAttributes->addOutputAttributeValue("integrity", $integrity);
                    } catch (ExceptionNotFound $e) {
                        // ok
                    }
                    $critical = $this->getCritical();
                    if (!$critical) {
                        $tagAttributes->addBooleanOutputAttributeValue("defer");
                        // not async: it will run as soon as possible
                        // the dom main not be loaded completely, the script may miss HTML dom element
                    }
                    return $tagAttributes;

                }

            case Snippet::EXTENSION_CSS:

                if ($this->shouldBeInlined()) {

                    try {
                        $tagAttributes->setInnerText($this->getInternalInlineAndFileContent());
                        return $tagAttributes;
                    } catch (ExceptionNotFound $e) {
                        throw new ExceptionNotFound("The internal css snippet ($this) has no content.", self::CANONICAL);
                    }

                } else {

                    if ($this->useRemoteUrl()) {
                        $fetchUrl = $this->getRemoteUrl();
                    } else {
                        $fetchUrl = FetcherRawLocalPath::createFromPath($this->getPath())->getFetchUrl();
                    }

                    /**
                     * Dokuwiki transforms/encode the href in HTML
                     */
                    $tagAttributes
                        ->addOutputAttributeValue("href", $fetchUrl->toString())
                        ->addOutputAttributeValue("crossorigin", "anonymous");

                    try {
                        $integrity = $this->getIntegrity();
                        $tagAttributes->addOutputAttributeValue("integrity", $integrity);
                    } catch (ExceptionNotFound $e) {
                        // ok
                    }

                    $critical = $this->getCritical();
                    if (!$critical && action_plugin_combo_docustom::isTemplateSystemEnabled()) {
                        $tagAttributes
                            ->addOutputAttributeValue("rel", "preload")
                            ->addOutputAttributeValue('as', self::STYLE_TAG);
                    } else {
                        $tagAttributes->addOutputAttributeValue("rel", "stylesheet");
                    }

                    return $tagAttributes;

                }


            default:
                throw new ExceptionBadState("The extension ($extension) is unknown", self::CANONICAL);
        }

    }

    /**
     * @return bool - yes if the function {@link Snippet::toTagAttributes()}
     * or {@link Snippet::toDokuWikiArray()} has been called
     * to prevent having the snippet two times (one in the head and one in the body)
     */
    public function hasHtmlOutputAlreadyOccurred(): bool
    {

        return $this->hasHtmlOutputOccurred;

    }

    private function hasRemoteUrl(): bool
    {
        try {
            $this->getRemoteUrl();
            return true;
        } catch (ExceptionNotFound $e) {
            return false;
        }
    }


}
