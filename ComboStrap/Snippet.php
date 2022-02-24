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
     * TlDR: The snippet does not depends to a slot and cannot therefore be cached along.
     *
     * The code that adds this snippet is not created by the parsing of content
     * or depends on the page.
     *
     * It's always called and add the snippet whatsoever.
     * Generally, this is an action plugin with a `TPL_METAHEADER_OUTPUT` hook
     * such as {@link Bootstrap}, {@link HistoricalBreadcrumbMenuItem},
     * ,...
     */
    const REQUEST_SLOT = "request";


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
    private $inlineContent;

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
    private $componentName;

    /**
     * @var array - the slots that needs this snippet (as key to get only one snippet by scope)
     * A special slot exists for {@link Snippet::REQUEST_SLOT}
     * where a snippet is for the whole requested page
     *
     * It's also used in the cache because not all bars
     * may render at the same time due to the other been cached.
     *
     * There is two scope:
     *   * a slot - cached along the HTML
     *   * or  {@link Snippet::REQUEST_SLOT} - never cached
     */
    private $slots;
    /**
     * @var bool run as soon as possible
     */
    private $async;

    /**
     * Snippet constructor.
     */
    public function __construct($snippetId, $mime, $type, $url, $componentId)
    {
        $this->snippetId = $snippetId;
        $this->extension = $mime;
        $this->type = $type;
        $this->url = $url;
        $this->componentName = $componentId;
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

    public static function &getOrCreateSnippet(string $identifier, string $extension, string $componentId): Snippet
    {

        /**
         * The snippet id is the url for external resources (ie external javascript / stylesheet)
         * otherwise if it's internal, it's the component id and it's type
         * @param string $componentId
         * @param string $identifier
         * @return string
         */
        if ($identifier === Snippet::INTERNAL_TYPE) {
            $snippetId = $identifier . "-" . $extension . "-" . $componentId;
            $type = self::INTERNAL_TYPE;
            $url = null;
        } else {
            $type = self::EXTERNAL_TYPE;
            $snippetId = $identifier;
            $url = $identifier;
        }
        $requestedPageId = PluginUtility::getRequestedWikiId();
        if ($requestedPageId === null) {
            if (PluginUtility::isTest()) {
                $requestedPageId = "test-id";
            } else {
                $requestedPageId = "unknown";
                LogUtility::msg("The requested id is unknown. We couldn't scope the snippets.");
            }
        }
        $snippets = &self::$globalSnippets[$requestedPageId];
        if ($snippets === null) {
            self::$globalSnippets = null;
            self::$globalSnippets[$requestedPageId] = [];
            $snippets = &self::$globalSnippets[$requestedPageId];
        }
        $snippet = &$snippets[$snippetId];
        if ($snippet === null) {
            $snippets[$snippetId] = new Snippet($snippetId, $extension, $type, $url, $componentId);
            $snippet = &$snippets[$snippetId];
        }
        return $snippet;

    }

    public static function reset()
    {
        self::$globalSnippets = null;
    }

    /**
     * @return Snippet[]|null
     */
    public static function getSnippets(): ?array
    {
        if (self::$globalSnippets === null) {
            return null;
        }
        $keys = array_keys(self::$globalSnippets);
        return self::$globalSnippets[$keys[0]];
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
     * @return string
     */
    public function getInternalDynamicContent(): ?string
    {
        return $this->inlineContent;
    }

    /**
     * @return string|null
     */
    public function getInternalFileContent(): ?string
    {
        $path = $this->getInternalFile();
        if (!FileSystems::exists($path)) {
            return null;
        }
        return FileSystems::getContent($path);
    }

    public function getInternalFile(): ?LocalPath
    {
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
                if (PluginUtility::isDevOrTest()) {
                    throw new ExceptionComboRuntime($message);
                } else {
                    LogUtility::msg($message);
                }
                return null;
        }
        return Site::getComboResourceSnippetDirectory()
            ->resolve($subDirectory)
            ->resolve(strtolower($this->componentName) . ".$extension");
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
        return "snippet-" . $this->componentName . "-" . SnippetManager::COMBO_CLASS_SUFFIX;

    }

    /**
     * @return string the HTML of the tag (works for now only with CSS content)
     */
    public function getHtmlStyleTag(): string
    {
        $content = $this->getInternalInlineAndFileContent();
        $class = $this->getClass();
        return <<<EOF
<style class="$class">
$content
</style>
EOF;

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
     * @throws ExceptionCombo
     */
    public static function createFromJson($array): Snippet
    {
        $snippetType = $array[self::JSON_TYPE_PROPERTY];
        if ($snippetType === null) {
            throw new ExceptionCombo("The snippet type property was not found in the json array");
        }
        switch ($snippetType) {
            case Snippet::INTERNAL_TYPE:
                $identifier = Snippet::INTERNAL_TYPE;
                break;
            case Snippet::EXTERNAL_TYPE:
                $identifier = $array[self::JSON_URL_PROPERTY];
                break;
            default:
                throw new ExceptionCombo("snippet type unknown ($snippetType");
        }
        $extension = $array[self::JSON_EXTENSION_PROPERTY];
        if ($extension === null) {
            throw new ExceptionCombo("The snippet extension property was not found in the json array");
        }
        $componentName = $array[self::JSON_COMPONENT_PROPERTY];
        if ($componentName === null) {
            throw new ExceptionCombo("The snippet component name property was not found in the json array");
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

    public function getInternalInlineAndFileContent(): ?string
    {
        $totalContent = null;
        $internalFileContent = $this->getInternalFileContent();
        if ($internalFileContent !== null) {
            $totalContent = $internalFileContent;
        }

        $content = $this->getInternalDynamicContent();
        if ($content !== null) {
            if ($totalContent === null) {
                $totalContent = $content;
            } else {
                $totalContent .= $content;
            }
        }
        return $totalContent;

    }


    public function jsonSerialize(): array
    {
        $dataToSerialize = [
            self::JSON_COMPONENT_PROPERTY => $this->componentName,
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
        if ($this->inlineContent !== null) {
            $dataToSerialize[self::JSON_CONTENT_PROPERTY] = $this->inlineContent;
        }
        if ($this->htmlAttributes !== null) {
            $dataToSerialize[self::JSON_HTML_ATTRIBUTES_PROPERTY] = $this->htmlAttributes;
        }
        return $dataToSerialize;
    }
}
