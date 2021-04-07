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


class Snippet
{
    /**
     * The head in css format
     * We need to add the style node
     */
    const TYPE_CSS = "css";

    /**
     * The snippet is attached to a bar (main, sidebar, ...) or to the page (request)
     */
    const SCOPE_BAR = "bar";
    const SCOPE_PAGE = "page";
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

    private $snippetId;
    private $scope;
    private $type;
    /**
     * @var bool
     */
    private $critical = false;

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
        if ($this->type == self::TYPE_CSS) {
            // All CSS should be loaded first
            // The CSS animation can set this to false
            $this->critical = true;
        }
    }


    /**
     * @param $bool - if the snippet is critical, it would not be deferred or preloaded
     * @return Snippet for chaining
     */
    public function setCritical($bool)
    {
        $this->critical = $bool;
        return $this;
    }

    /**
     * @param $content - Set an inline content for a script or stylesheet
     * @return Snippet for chaining
     */
    public function setContent($content)
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
        return $this->snippetId."-".$this->type;
    }

    /**
     * Set all tags at once.
     * @param array $tags
     */
    public function setTags(array $tags)
    {
        $this->headsTags = $tags;
    }




}
