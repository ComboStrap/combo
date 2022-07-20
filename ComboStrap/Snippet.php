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
    const JSON_TYPE_PROPERTY = "type"; // mandatory
    const JSON_COMPONENT_PROPERTY = "component"; // mandatory
    const JSON_EXTENSION_PROPERTY = "extension"; // mandatory
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
    public const COMBO_HTML = "combo-html";
    public const DATA_DOKUWIKI_ATT = "_data";
    const CANONICAL = "snippet";
    public const STYLE_TAG = "style";
    public const SCRIPT_TAG = "script";
    public const LINK_TAG = "link";


    protected static $globalSnippets;

    private $snippetId;
    private $extension;

    /**
     * @var bool
     */
    private $critical;

    /**
     * @var string the text script / style (may be null if it's an external resources)
     */
    private string $inlineContent;

    /**
     * @var string
     */
    private $url;
    /**
     * @var string
     */
    private $integrity;
    /**
     * @var array Extra html attributes if needed
     */
    private $htmlAttributes;

    /**
     * @var string ie internal or external
     */
    private $type;
    /**
     * @var string The name of the component (used for internal style sheet to retrieve the file)
     */
    private $internalIdentifier;

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
    private $async;
    private WikiPath $internalPath;

    /**
     * Snippet constructor.
     */
    public function __construct($snippetId, $mime, $type, $url, $internalIdentifier)
    {
        $this->snippetId = $snippetId;
        $this->extension = $mime;
        $this->type = $type;
        $this->url = $url;
        $this->internalIdentifier = $internalIdentifier;
    }


    public static function createInternalCssSnippet($componentId): Snippet
    {
        return self::getOrCreateSnippet(self::INTERNAL_TYPE, self::EXTENSION_CSS, $componentId);
    }


    /**
     * @param $componentId
     * @return Snippet
     * @deprecated You should create a snippet with a known type, this constructor was created for refactoring
     */
    public static function createUnknownSnippet($componentId): Snippet
    {
        return new Snippet("unknown", "unknwon", "unknwon", "unknwon", $componentId);
    }

    /**
     * The snippet id is the url for external resources (ie external javascript / stylesheet)
     * otherwise if it's internal, it's the component id and it's type
     * @param string $identifier - the snippet identifier - the url for an external snippet or {@link Snippet::INTERNAL_TYPE} for an internal one
     * @param string $extension - {@link Snippet::EXTENSION_CSS css} or {@link Snippet::EXTENSION_JS js}
     * @param string $internalIdentifier - the internal snippet identifier to resolve the file.
     * @return Snippet
     */
    public static function &getOrCreateSnippet(string $identifier, string $extension, string $internalIdentifier): Snippet
    {

        if ($identifier === Snippet::INTERNAL_TYPE) {
            $snippetId = self::getInternalGlobalSnippetIdentifier($internalIdentifier, $extension);
            $type = self::INTERNAL_TYPE;
            $url = null;
        } else {
            $type = self::EXTERNAL_TYPE;
            $snippetId = $identifier;
            $url = $identifier;
        }
        $requestedPageId = PluginUtility::getRequestedWikiId();
        $snippets = &self::$globalSnippets[$requestedPageId];
        if ($snippets === null) {
            self::reset();
            self::$globalSnippets[$requestedPageId] = [];
            $snippets = &self::$globalSnippets[$requestedPageId];
        }
        $snippet = &$snippets[$snippetId];
        if ($snippet === null) {
            $snippets[$snippetId] = new Snippet($snippetId, $extension, $type, $url, $internalIdentifier);
            $snippet = &$snippets[$snippetId];
        }
        return $snippet;

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
        self::$globalSnippets = null;
    }

    /**
     * @param $extension - the file type
     * @param $snippetName - the component name if the snippet is generated or the file name otherwise
     * @return string - the global identifier
     */
    public static function getInternalGlobalSnippetIdentifier($snippetName, $extension): string
    {
        return Snippet::INTERNAL_TYPE . "-" . $extension . "-" . $snippetName;
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
        $path = $this->getInternalPath();
        return FileSystems::getContent($path);
    }

    public function getInternalPath(): WikiPath
    {
        if (isset($this->internalPath)) {
            return $this->internalPath;
        }
        switch ($this->extension) {
            case self::EXTENSION_CSS:
                $extension = "css";
                $subDirectory = "style";
                break;
            case self::EXTENSION_JS:
                $extension = "js";
                $subDirectory = "js";
                break;
            default:
                $message = "Unknown snippet type ($this->extension)";
                throw new ExceptionRuntimeInternal($message);

        }
        return WikiPath::createComboResource("snippet")
            ->resolve($subDirectory)
            ->resolve(strtolower($this->internalIdentifier) . ".$extension");
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
        return $this->snippetId . "-" . $this->extension;
    }

    public function getCritical(): bool
    {
        if ($this->critical === null) {
            if ($this->extension == self::EXTENSION_CSS) {
                // All CSS should be loaded first
                // The CSS animation / background can set this to false
                return true;
            }
            return false;
        }
        return $this->critical;
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
        return StyleUtility::addComboStrapSuffix("snippet-" . $this->internalIdentifier);

    }


    public function getId()
    {
        return $this->snippetId;
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
        $snippetType = $array[self::JSON_TYPE_PROPERTY];
        if ($snippetType === null) {
            throw new ExceptionCompile("The snippet type property was not found in the json array");
        }
        switch ($snippetType) {
            case Snippet::INTERNAL_TYPE:
                $identifier = Snippet::INTERNAL_TYPE;
                break;
            case Snippet::EXTERNAL_TYPE:
                $identifier = $array[self::JSON_URL_PROPERTY];
                break;
            default:
                throw new ExceptionCompile("snippet type unknown ($snippetType");
        }
        $extension = $array[self::JSON_EXTENSION_PROPERTY];
        if ($extension === null) {
            throw new ExceptionCompile("The snippet extension property was not found in the json array");
        }
        $componentName = $array[self::JSON_COMPONENT_PROPERTY];
        if ($componentName === null) {
            throw new ExceptionCompile("The snippet component name property was not found in the json array");
        }
        $snippet = Snippet::getOrCreateSnippet($identifier, $extension, $componentName);


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
        return $this->extension;
    }

    public function setIntegrity(?string $integrity): Snippet
    {
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
        return $this->type;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getIntegrity(): ?string
    {
        return $this->integrity;
    }

    public function getHtmlAttributes(): ?array
    {
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


    public function jsonSerialize(): array
    {
        $dataToSerialize = [
            self::JSON_COMPONENT_PROPERTY => $this->internalIdentifier,
            self::JSON_EXTENSION_PROPERTY => $this->extension,
            self::JSON_TYPE_PROPERTY => $this->type
        ];
        if ($this->url !== null) {
            $dataToSerialize[self::JSON_URL_PROPERTY] = $this->url;
        }
        if ($this->integrity !== null) {
            $dataToSerialize[self::JSON_INTEGRITY_PROPERTY] = $this->integrity;
        }
        if ($this->critical !== null) {
            $dataToSerialize[self::JSON_CRITICAL_PROPERTY] = $this->critical;
        }
        if ($this->async !== null) {
            $dataToSerialize[self::JSON_ASYNC_PROPERTY] = $this->async;
        }
        if (isset($this->inlineContent)) {
            $dataToSerialize[self::JSON_CONTENT_PROPERTY] = $this->inlineContent;
        }
        if ($this->htmlAttributes !== null) {
            $dataToSerialize[self::JSON_HTML_ATTRIBUTES_PROPERTY] = $this->htmlAttributes;
        }
        return $dataToSerialize;
    }

    public function getInternalId(): string
    {
        return $this->internalIdentifier;
    }

    public function setInternalPath(WikiPath $path): Snippet
    {
        $this->internalPath = $path;
        return $this;
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
    public function shouldBeInHtmlPage(): bool
    {
        /**
         * If there is inline content, true
         */
        if ($this->hasInlineContent()) {
            return true;
        }
        /**
         * If this is a path, true if the file is small enough
         */
        $internalPath = $this->getInternalPath();
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
        $type = $this->getType();
        $extension = $this->getExtension();
        switch ($extension) {
            case Snippet::EXTENSION_JS:
                switch ($type) {
                    case Snippet::EXTERNAL_TYPE:
                        $htmlAttributes = TagAttributes::createFromCallStackArray($this->getHtmlAttributes());
                        $htmlAttributes
                            ->addClassName($this->getClass())
                            ->addOutputAttributeValue("src", $this->getUrl())
                            ->addOutputAttributeValue("crossorigin", "anonymous");
                        $integrity = $this->getIntegrity();
                        if ($integrity !== null) {
                            $htmlAttributes->addOutputAttributeValue("integrity", $integrity);
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
                        $htmlAttributes = TagAttributes::createFromCallStackArray($this->getHtmlAttributes());
                        /**
                         * This may broke the dependencies
                         * if a small javascript depend on a large one
                         * that is not yet loaded and does not wait for it
                         */
                        switch ($this->shouldBeInHtmlPage()) {
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

                                $wikiPath = $this->getInternalPath();
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
                        $htmlAttributes = TagAttributes::createFromCallStackArray($this->getHtmlAttributes())
                            ->addOutputAttributeValue("rel", "stylesheet")
                            ->addOutputAttributeValue("href", $this->getUrl())
                            ->addOutputAttributeValue("crossorigin", "anonymous");

                        $integrity = $this->getIntegrity();
                        if ($integrity !== null) {
                            $htmlAttributes->addOutputAttributeValue("integrity", $integrity);
                        }
                        $critical = $this->getCritical();
                        if (!$critical && FetcherPage::isEnabledAsShowAction()) {
                            $htmlAttributes->addOutputAttributeValue("rel", "preload")
                                ->addOutputAttributeValue('as', self::STYLE_TAG);
                        }
                        return $htmlAttributes->toCallStackArray();
                    case
                    Snippet::INTERNAL_TYPE:
                        /**
                         * CSS inline in script tag
                         * If they are critical or inline dynamic content is set, we add them in the page
                         */
                        $htmlAttributes = TagAttributes::createFromCallStackArray($this->getHtmlAttributes());
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
                            try {
                                $fetchUrl = FetcherRawLocalPath::createFromPath($this->getInternalPath())->getFetchUrl();
                                /**
                                 * Dokuwiki transforms/encode the href in HTML
                                 */
                                return $htmlAttributes
                                    ->addOutputAttributeValue("rel", "stylesheet")
                                    ->addOutputAttributeValue("href", $fetchUrl->toString())
                                    ->toCallStackArray();
                            } catch (ExceptionNotFound $e) {
                                // the file should have been found at this point
                                throw new ExceptionNotFound("The internal css ($this) could not be added. Error:{$e->getMessage()}", self::CANONICAL);
                            }
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
                return Snippet::LINK_TAG;
            default:
                throw new ExceptionBadState("The extension ($extension) is unknown");
        }
    }


}
