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
 */
class Snippet implements JsonSerializable
{
    /**
     * The head in css format
     * We need to add the style node
     */
    const MIME_CSS = "css";
    /**
     * The head in javascript
     * We need to wrap it in a script node
     */
    const EXTENSION_JS = "js";
    const JSON_SNIPPET_ID_PROPERTY = "id";
    const JSON_TYPE_PROPERTY = "type";
    const JSON_CRITICAL_PROPERTY = "critical";
    const JSON_CONTENT_PROPERTY = "content";

    /**
     * The identifier for a script snippet
     * (ie inline javascript or style)
     * To make the difference with library
     * that have already an identifier with the url value
     */
    public const INTERNAL_JAVASCRIPT_IDENTIFIER = "internal-javascript";
    public const INTERNAL_STYLESHEET_IDENTIFIER = "internal-stylesheet";
    const INTERNAL = "internal";
    const EXTERNAL = "external";

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
    private $mime;

    /**
     * @var bool
     */
    private $critical;

    /**
     * @var string the text script / style (may be null if it's an external resources)
     */
    private $content;

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
    private $htmlAttributes = [];

    /**
     * @var string ie internal or external
     */
    private $type;
    /**
     * @var string The name of the component (used for internal style sheet to retrieve the file)
     */
    private $componentId;

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
     * Snippet constructor.
     */
    public function __construct($snippetId, $mime, $type, $url, $componentId)
    {
        $this->snippetId = $snippetId;
        $this->mime = $mime;
        $this->type = $type;
        $this->url = $url;
        $this->componentId = $componentId;
    }


    public static function createInternalCssSnippet($componentId): Snippet
    {
        return self::getOrCreateSnippet(self::INTERNAL_STYLESHEET_IDENTIFIER, self::MIME_CSS, $componentId);
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

    public static function &getOrCreateSnippet(string $identifier, string $mime, string $componentId): Snippet
    {

        /**
         * The snippet id is the url for external resources (ie external javascript / stylesheet)
         * otherwise if it's internal, it's the component id and it's type
         * @param string $componentId
         * @param string $identifier
         * @return string
         */
        if (in_array($identifier, [self::INTERNAL_JAVASCRIPT_IDENTIFIER, self::INTERNAL_STYLESHEET_IDENTIFIER])) {
            $snippetId = $identifier . "-" . $componentId;
            $type = self::INTERNAL;
            $url = null;
        } else {
            $type = self::EXTERNAL;
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
            $snippets[$snippetId] = new Snippet($snippetId, $mime, $type, $url, $componentId);
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
        return array_shift(self::$globalSnippets);
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
     * @param $content - Set an inline content for a script or stylesheet
     * @return Snippet for chaining
     */
    public function setContent($content): Snippet
    {
        $this->content = $content;
        return $this;
    }

    /**
     * @return string
     */
    public function getInternalDynamicContent(): ?string
    {
        return $this->content;
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
        switch ($this->mime) {
            case self::MIME_CSS:
                $extension = "css";
                $subDirectory = "style";
                break;
            case self::EXTENSION_JS:
                $extension = "js";
                $subDirectory = "js";
                break;
            default:
                $message = "Unknown snippet type ($this->mime)";
                if (PluginUtility::isDevOrTest()) {
                    throw new ExceptionComboRuntime($message);
                } else {
                    LogUtility::msg($message);
                }
                return null;
        }
        return Site::getComboResourceSnippetDirectory()
            ->resolve($subDirectory)
            ->resolve(strtolower($this->snippetId) . ".$extension");
    }


    public function __toString()
    {
        return $this->snippetId . "-" . $this->mime;
    }

    public function getCritical(): bool
    {
        if ($this->critical === null) {
            if ($this->mime == self::MIME_CSS) {
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
        return "snippet-" . $this->snippetId . "-" . SnippetManager::COMBO_CLASS_SUFFIX;

    }

    /**
     * @return string the HTML of the tag (works for now only with CSS content)
     */
    public function getHtmlStyleTag(): string
    {
        $content = $this->getInternalDynamicContent();
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


    public function jsonSerialize(): array
    {
        $dataToSerialize = [
            self::JSON_SNIPPET_ID_PROPERTY => $this->snippetId,
            self::JSON_TYPE_PROPERTY => $this->mime
        ];
        if ($this->critical !== null) {
            $dataToSerialize[self::JSON_CRITICAL_PROPERTY] = $this->critical;
        }
        if ($this->content !== null) {
            $dataToSerialize[self::JSON_CONTENT_PROPERTY] = $this->content;
        }

        return $dataToSerialize;

    }

    /**
     * @throws ExceptionCombo
     */
    public static function createFromJson($array): Snippet
    {
        $snippetId = $array[self::JSON_SNIPPET_ID_PROPERTY];
        if ($snippetId === null) {
            throw new ExceptionCombo("The snippet id property was not found in the json array");
        }
        $type = $array[self::JSON_TYPE_PROPERTY];
        if ($type === null) {
            throw new ExceptionCombo("The snippet type property was not found in the json array");
        }
        $snippet = new Snippet($snippetId, $type);
        $critical = $array[self::JSON_CRITICAL_PROPERTY];
        if ($critical !== null) {
            $snippet->setCritical($critical);
        }

        $content = $array[self::JSON_CONTENT_PROPERTY];
        if ($content !== null) {
            $snippet->setContent($content);
        }

        return $snippet;

    }

    public function getExtension()
    {
        return $this->mime;
    }

    public function setUrl(string $url, ?string $integrity): Snippet
    {
        $this->url = $url;
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

    public function getHtmlAttributes(): array
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


}
