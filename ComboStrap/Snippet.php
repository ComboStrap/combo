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
use ComboStrap\TagAttribute\StyleAttribute;
use ComboStrap\Web\Url;
use JsonSerializable;
use splitbrain\slika\Exception;

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
    const JSON_URI_PROPERTY = "uri"; // internal uri
    const JSON_URL_PROPERTY = "url"; // external url
    const JSON_CRITICAL_PROPERTY = "critical";
    const JSON_ASYNC_PROPERTY = "async";
    const JSON_CONTENT_PROPERTY = "content";
    const JSON_INTEGRITY_PROPERTY = "integrity";
    const JSON_HTML_ATTRIBUTES_PROPERTY = "attributes";

    /**
     * Not all snippet comes from a component markup
     * * a menu item may want to add a snippet on a dynamic page
     * * a snippet may be added just from the head html meta (for anaytics purpose)
     * * the global css variables
     * TODO: it should be migrated to the {@link TemplateForWebPage}, ie the request scope is the template scope
     *    has, these is this object that creates pages
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
     * With a raw format, we do nothing
     * We take it without any questions
     */
    const RAW_FORMAT = "raw";
    /**
     * With a iife, if the javascript snippet is not critical
     * It will be wrapped to execute after page load
     */
    const IIFE_FORMAT = "iife";
    /**
     * Javascript es module
     */
    const ES_FORMAT = "es";
    /**
     * Umd module
     */
    const UMD_FORMAT = "umd";
    const JSON_FORMAT_PROPERTY = "format";
    const DEFAULT_FORMAT = self::RAW_FORMAT;


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
    private $elements;

    /**
     * @var bool run as soon as possible
     */
    private bool $async;
    private Path $path;
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
    private string $format = self::DEFAULT_FORMAT;

    /**
     * @param Path $path - path mandatory because it's the path of fetch and it's the storage format
     * use {@link Snippet::getOrCreateFromContext()}
     */
    private function __construct(Path $path)
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
     * @param WikiPath $path - a local path of the snippet (if the path does not exist, a remote url should be given)
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
        return StyleAttribute::addComboStrapSuffix("snippet-" . $snippetId);
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
                 * but still used to store the snippets
                 */
                $wikiId = $executingFetcher->getSourcePath()->toWikiPath()->getWikiId();
                $snippet->addElement($wikiId);
            } catch (ExceptionCast $e) {
                // not a wiki path
            } catch (ExceptionNotFound $e) {
                /**
                 * String/dynamic run
                 * (Example via an {@link \syntax_plugin_combo_iterator})
                 * The fetcher should have then a parent
                 */
                try {
                    $wikiId = $executionContext->getExecutingParentMarkupHandler()->getSourcePath()->toWikiPath()->getWikiId();
                    $snippet->addElement($wikiId);
                } catch (ExceptionCast $e) {
                    // not a wiki path
                } catch (ExceptionNotFound $e) {
                    // no parent found
                }

            }
        } catch (ExceptionNotFound $e) {
            /**
             * admin page, page scope or theme is not used
             * This snippets are not due to the markup
             */
            try {
                $executingId = $executionContext->getExecutingWikiId();
                $snippet->addElement($executingId);
            } catch (ExceptionNotFound $e) {
                $snippet->addElement(Snippet::REQUEST_SCOPE);
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
     * @throws ExceptionBadArgument
     */
    public static function createJavascriptSnippetFromComponentId(string $componentId): Snippet
    {
        return Snippet::createSnippetFromComponentId($componentId, self::EXTENSION_JS);
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

    public function getPath(): Path
    {
        return $this->path;
    }

    public static function getInternalPathFromNameAndExtension($name, $extension): WikiPath
    {

        switch ($extension) {
            case self::EXTENSION_CSS:
                $extension = "css";
                return TemplateEngine::createFromContext()
                    ->getComponentStylePathByName(strtolower($name) . ".$extension");
            case self::EXTENSION_JS:
                $extension = "js";
                $subDirectory = "js";
                return WikiPath::createComboResource(self::SNIPPET_BASE)
                    ->resolve($subDirectory)
                    ->resolve(strtolower($name) . ".$extension");
            default:
                $message = "Unknown snippet type ($extension)";
                throw new ExceptionRuntimeInternal($message);
        }

    }

    public function hasSlot($slot): bool
    {
        if ($this->elements === null) {
            return false;
        }
        return key_exists($slot, $this->elements);
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
            return StyleAttribute::addComboStrapSuffix("snippet-" . $this->getComponentId());
        } catch (ExceptionNotFound $e) {
            LogUtility::internalError("A component id was not found for the snippet ($this)", self::CANONICAL);
            return StyleAttribute::addComboStrapSuffix("snippet");
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

        $uri = $array[self::JSON_URI_PROPERTY] ?? null;
        if ($uri === null) {
            throw new ExceptionCompile("The snippet uri property was not found in the json array");
        }

        $wikiPath = FileSystems::createPathFromUri($uri);
        $snippet = Snippet::getOrCreateFromContext($wikiPath);

        $componentName = $array[self::JSON_COMPONENT_PROPERTY] ?? null;
        if ($componentName !== null) {
            $snippet->setComponentId($componentName);
        }

        $critical = $array[self::JSON_CRITICAL_PROPERTY] ?? null;
        if ($critical !== null) {
            $snippet->setCritical($critical);
        }

        $async = $array[self::JSON_ASYNC_PROPERTY] ?? null;
        if ($async !== null) {
            $snippet->setDoesManipulateTheDomOnRun($async);
        }

        $format = $array[self::JSON_FORMAT_PROPERTY] ?? null;
        if ($format !== null) {
            $snippet->setFormat($format);
        }

        $content = $array[self::JSON_CONTENT_PROPERTY] ?? null;
        if ($content !== null) {
            $snippet->setInlineContent($content);
        }

        $attributes = $array[self::JSON_HTML_ATTRIBUTES_PROPERTY] ?? null;
        if ($attributes !== null) {
            foreach ($attributes as $name => $value) {
                $snippet->addHtmlAttribute($name, $value);
            }
        }

        $integrity = $array[self::JSON_INTEGRITY_PROPERTY] ?? null;
        if ($integrity !== null) {
            $snippet->setIntegrity($integrity);
        }

        $remoteUrl = $array[self::JSON_URL_PROPERTY] ?? null;
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

    public function addElement(string $element): Snippet
    {
        $this->elements[$element] = 1;
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
     *
     */
    public function getLocalUrl(): Url
    {
        try {
            $path = WikiPath::createFromPathObject($this->path);
            return FetcherRawLocalPath::createFromPath($path)->getFetchUrl();
        } catch (ExceptionBadArgument $e) {
            throw new ExceptionRuntimeInternal("The local url should ne asked. use (hasLocalUrl) before calling this function", self::CANONICAL, $e);
        }

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
            self::JSON_URI_PROPERTY => $this->getPath()->toUriString(),
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
        if ($this->format !== self::DEFAULT_FORMAT) {
            $dataToSerialize[self::JSON_FORMAT_PROPERTY] = $this->format;
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
        return SiteConfig::getConfValue(SiteConfig::HTML_MAX_KB_SIZE_FOR_INLINE_ELEMENT, 2) * 1024;
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
         * If the file does not exist
         * and that there is a remote url
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
         * The file exists
         * If we can't serve it locally, it should be inlined
         */
        if (!$this->hasLocalUrl()) {
            return true;
        }

        /**
         * File exists and can be served
         */

        /**
         * Local Javascript Library ?
         */
        if ($this->getExtension() === Snippet::EXTENSION_JS) {

            try {
                $lastName = $internalPath->getLastName();
            } catch (ExceptionNotFound $e) {
                LogUtility::internalError("Every snippet should have a last name");
                return false;
            }

            /**
             * If this is a local library don't inline
             * Why ?
             * The local combo.min.js library depends on bootstrap.min.js
             * If we inject it, we get `bootstrap` does not exist.
             * Other solution, to resolve it, we could:
             * * inline all javascript
             * * start a local server and serve the local library
             * * publish the local library (not really realistic if we test but yeah)
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
     * @return array
     * @throws ExceptionBadArgument
     * @throws ExceptionBadState - an error where for instance an inline script does not have any content
     * @throws ExceptionCast
     * @throws ExceptionNotFound - an error where the source was not found
     */
    public function toDokuWikiArray(): array
    {
        $tagAttributes = $this->toTagAttributes();
        $array = $tagAttributes->toCallStackArray();
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
     * @return TagAttributes
     * @throws ExceptionBadArgument
     * @throws ExceptionBadState - if no content was found
     * @throws ExceptionCast
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
                        $tagAttributes->setInnerText($this->getInnerHtml());
                        return $tagAttributes;
                    } catch (ExceptionNotFound $e) {
                        throw new ExceptionBadState("The internal js snippet ($this) has no content. Skipped", self::CANONICAL);
                    }

                } else {

                    if ($this->useRemoteUrl()) {
                        $fetchUrl = $this->getRemoteUrl();
                    } else {
                        $fetchUrl = $this->getLocalUrl();
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
                        $tagAttributes->setInnerText($this->getInnerHtml());
                        return $tagAttributes;
                    } catch (ExceptionNotFound $e) {
                        throw new ExceptionNotFound("The internal css snippet ($this) has no content.", self::CANONICAL);
                    }

                } else {

                    if ($this->useRemoteUrl()) {
                        $fetchUrl = $this->getRemoteUrl();
                    } else {
                        $fetchUrl = $this->getLocalUrl();
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
                    if (!$critical && action_plugin_combo_docustom::isThemeSystemEnabled()) {
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

    public function toXhtml(): string
    {
        try {
            $tagAttributes = $this->toTagAttributes();
        } catch (\Exception $e) {
            throw new ExceptionRuntimeInternal("We couldn't output the snippet ($this). Error: {$e->getMessage()}", self::CANONICAL, $e);
        }
        $htmlElement = $this->getHtmlTag();
        /**
         * This code runs in editing mode
         * or if the template is not strap
         * No preload is then supported
         */
        if ($htmlElement === "link") {
            try {
                $relValue = $tagAttributes->getOutputAttribute("rel");
                $relAs = $tagAttributes->getOutputAttribute("as");
                if ($relValue === "preload") {
                    if ($relAs === "style") {
                        $tagAttributes->removeOutputAttributeIfPresent("rel");
                        $tagAttributes->addOutputAttributeValue("rel", "stylesheet");
                        $tagAttributes->removeOutputAttributeIfPresent("as");
                    }
                }
            } catch (ExceptionNotFound $e) {
                // rel or as was not found
            }
        }
        $xhtmlContent = $tagAttributes->toHtmlEnterTag($htmlElement);

        try {
            $xhtmlContent .= $tagAttributes->getInnerText();
        } catch (ExceptionNotFound $e) {
            // ok
        }
        $xhtmlContent .= "</$htmlElement>";
        return $xhtmlContent;
    }

    /**
     * If is not a wiki path
     * It can't be served
     *
     * Example from theming, ...
     */
    private function hasLocalUrl(): bool
    {
        try {
            $this->getLocalUrl();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * The format
     * for javascript as specified by [rollup](https://rollupjs.org/configuration-options/#output-format)
     * @param string $format
     * @return Snippet
     */
    public function setFormat(string $format): Snippet
    {
        $this->format = $format;
        return $this;
    }

    /**
     * Retrieve the content and wrap it if necessary
     * to define the execution time
     * (ie there is no `defer` option for inline html
     * @throws ExceptionNotFound
     */
    private function getInnerHtml(): string
    {
        $internal = $this->getInternalInlineAndFileContent();
        if (
            $this->getExtension() === self::EXTENSION_JS
            && $this->format === self::IIFE_FORMAT
            && $this->getCritical() === false
        ) {
            $internal = <<<EOF
window.addEventListener('load', function () { $internal });
EOF;
        }
        return $internal;
    }

    public function getFormat(): string
    {
        return $this->format;
    }


}
