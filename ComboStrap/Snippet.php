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

class Snippet implements JsonSerializable
{
    /**
     * The head in css format
     * We need to add the style node
     */
    const TYPE_CSS = "css";


    /**
     * The head in javascript
     * We need to wrap it in a script node
     */
    const TYPE_JS = "js";
    /**
     * A tag head in array format
     * No need
     */
    const TAG_TYPE = "tag";
    const JSON_SNIPPET_ID_PROPERTY = "id";
    const JSON_TYPE_PROPERTY = "type";
    const JSON_CRITICAL_PROPERTY = "critical";
    const JSON_CONTENT_PROPERTY = "content";
    const JSON_HEAD_PROPERTY = "head";

    private $snippetId;
    private $type;

    /**
     * @var bool
     */
    private $critical;

    /**
     * @var string the text script / style (may be null if it's an external resources)
     */
    private $content;
    /**
     * @var array
     */
    private $headsTags;

    /**
     * Snippet constructor.
     */
    public function __construct($snippetId, $snippetType)
    {
        $this->snippetId = $snippetId;
        $this->type = $snippetType;
    }

    public static function createJavascriptSnippet($snippetId): Snippet
    {
        return new Snippet($snippetId, self::TYPE_JS);
    }

    public static function createCssSnippet($snippetId): Snippet
    {
        return new Snippet($snippetId, self::TYPE_CSS);
    }

    /**
     * @param $snippetId
     * @return Snippet
     * @deprecated You should create a snippet with a known type, this constructor was created for refactoring
     */
    public static function createUnknownSnippet($snippetId): Snippet
    {
        return new Snippet($snippetId, "unknwon");
    }


    /**
     * @param $bool - if the snippet is critical, it would not be deferred or preloaded
     * @return Snippet for chaining
     * All css that are for animation or background for instance
     * should not be set as critical as they are not needed to paint
     * exactly the page
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
    public function getContent()
    {
        if ($this->content == null) {
            switch ($this->type) {
                case self::TYPE_CSS:
                    $this->content = $this->getCssRulesFromFile($this->snippetId);
                    break;
                case self::TYPE_JS:
                    $this->content = $this->getJavascriptContentFromFile($this->snippetId);
                    break;
                default:
                    LogUtility::msg("The snippet ($this) has no content", LogUtility::LVL_MSG_ERROR, "support");
            }
        }
        return $this->content;
    }

    /**
     * @param $tagName
     * @return false|string - the css content of the css file
     */
    private function getCssRulesFromFile($tagName)
    {

        $path = Resources::getSnippetResourceDirectory() . "/style/" . strtolower($tagName) . ".css";
        if (file_exists($path)) {
            return file_get_contents($path);
        } else {
            LogUtility::msg("The css file ($path) was not found", LogUtility::LVL_MSG_WARNING, $tagName);
            return "";
        }

    }

    /**
     * @param $tagName - the tag name
     * @return false|string - the specific javascript content for the tag
     */
    private function getJavascriptContentFromFile($tagName)
    {

        $path = Resources::getSnippetResourceDirectory() . "/js/" . strtolower($tagName) . ".js";
        if (file_exists($path)) {
            return file_get_contents($path);
        } else {
            LogUtility::msg("The javascript file ($path) was not found", LogUtility::LVL_MSG_WARNING, $tagName);
            return "";
        }

    }

    public function __toString()
    {
        return $this->snippetId . "-" . $this->type;
    }

    /**
     * Set all tags at once.
     * @param array $tags
     * @return Snippet
     */
    public function setTags(array $tags): Snippet
    {
        $this->headsTags = $tags;
        return $this;
    }

    public function getTags(): array
    {
        return $this->headsTags;
    }

    public function getCritical(): bool
    {

        if ($this->critical === null) {
            if ($this->type == self::TYPE_CSS) {
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
        $content = $this->getContent();
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
            self::JSON_TYPE_PROPERTY => $this->type
        ];
        if ($this->critical !== null) {
            $dataToSerialize[self::JSON_CRITICAL_PROPERTY] = $this->critical;
        }
        if ($this->content !== null) {
            $dataToSerialize[self::JSON_CONTENT_PROPERTY] = $this->content;
        }
        if ($this->headsTags !== null) {
            $dataToSerialize[self::JSON_HEAD_PROPERTY] = $this->headsTags;
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

        $heads = $array[self::JSON_HEAD_PROPERTY];
        if ($heads !== null) {
            $snippet->setTags($heads);
        }
        return $snippet;

    }

    public function getType()
    {
        return $this->type;
    }
}
