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

/**
 * @package ComboStrap
 * A component to manage the extra HTML that
 * comes from components and that should come in the head HTML node
 *
 * The snippet manager handles two scope of snippet
 * All function with the suffix
 *   * `ForBar` are snippets for a bar (ie page, sidebar, ...) - cached
 *   * `ForRequests` are snippets added for the HTTP request - not cached. Example of request component: message, anchor
 *
 */
class SnippetManager
{

    /**
     * The head in css format
     * We need to add the style node
     */
    const CSS_TYPE = "css";
    /**
     * The head in javascript
     * We need to wrap it in a script node
     */
    const JS_TYPE = "js";
    /**
     * A tag head in array format
     * No need
     */
    const TAG_TYPE = "tag";
    const COMBO_CLASS_PREFIX = "combo-";

    /**
     * @var array the content array of all heads
     */
    private $headsByBar = array();

    /**
     * @var array heads that are unique on a request scope
     */
    private $headsByRequest = array();

    /**
     * @var array the processed bar
     */
    private $barsProcessed = array();


    public static function getClassFromTag($tag)
    {
        return self::COMBO_CLASS_PREFIX . $tag;
    }


    /**
     * @param $id
     * @param string|null $css - the css
     *   if null, the file $id.css is searched in the `style` directory
     *
     */
    public function upsertCssSnippetForBar($id, $css = null)
    {

        $cssForPage = &$this->getHeadsByBarByElementType(self::CSS_TYPE);
        if (!isset($cssForPage[$id])) {
            if ($css == null) {
                $cssForPage[$id] = $this->getCssRulesFromFile($id);
            } else {
                $cssForPage[$id] = $css;
            }
        }

    }

    /**
     * @param $id
     * @param $script - javascript code if null, it will search in the js directory
     */
    public function upsertJavascriptForBar($id, $script = null)
    {
        $js = &$this->getHeadsByBarByElementType(self::JS_TYPE);
        if (!isset($js[$id])) {
            if ($script == null) {
                $js[$id] = $this->getJavascriptContentFromFile($id);
            } else {
                $js[$id] = $script;
            }

        }
    }

    /**
     * @param $id
     * @param array $componentTags - an array of tags without content where the key is the node and the value the attributes
     */
    public function upsertHeadTagsForBar($id, $componentTags)
    {
        $headTags = &$this->getHeadsByBarByElementType(self::TAG_TYPE);
        if (!isset($headTags[$id])) {
            $headTags[$id] = $componentTags;
        }
    }


    /**
     * @param $type
     * @return mixed a reference to the heads array
     */
    private function &getHeadsByBarByElementType($type)
    {
        /**
         * To be able to get
         * all component used by page (sidebar included)
         */
        global $ID; // the bar id
        if (!isset($this->headsByBar[$ID])) {
            $this->headsByBar[$ID] = array();
        }
        if (!isset($this->headsByBar[$ID][$type])) {
            $this->headsByBar[$ID][$type] = array();
        }
        return $this->headsByBar[$ID][$type];
    }

    /**
     * @return SnippetManager - the global reference
     * that is set for every run at the end of this fille
     */
    public static function get()
    {
        global $componentScript;
        return $componentScript;
    }

    public function getCss()
    {
        return $this->getSnippets(self::CSS_TYPE);
    }

    public function getJavascript()
    {
        return $this->getSnippets(self::JS_TYPE);
    }

    public function getTags()
    {
        return $this->getSnippets(self::TAG_TYPE);
    }


    /**
     * @param $localType
     * @return array
     */
    private function getSnippets($localType)
    {
        $distinctSnippets = array();
        foreach ($this->headsByBar as $page => $components) {
            foreach ($components as $type => $snippets) {
                if ($type == $localType) {
                    $distinctSnippets = array_merge($distinctSnippets, $snippets);
                }
            }
        }
        foreach ($this->headsByRequest as $page => $components) {
            foreach ($components as $type => $snippets) {
                if ($type == $localType) {
                    foreach ($snippets as $comboTag => $tags) {
                        foreach ($tags as $htmlTagName => $htmlTags) {
                            foreach ($htmlTags as $htmlTag) {
                                $distinctSnippets[$comboTag][$htmlTagName][] = $htmlTag;
                            }
                        }
                    }
                }
            }
        }
        return $distinctSnippets;
    }

    /**
     * Empty the snippets
     * This is used to render the snippet only once
     * The snippets renders first in the head
     * and otherwise at the end of the document.
     */
    public function close()
    {
        $this->headsByBar = array();
        $this->headsByRequest = array();
    }

    public function getData()
    {
        return $this->headsByBar;
    }

    /**
     * @param $tagName - the tag name
     * @return false|string - the specific javascript content for the tag
     */
    private function getJavascriptContentFromFile($tagName)
    {

        $path = DOKU_PLUGIN . PluginUtility::PLUGIN_BASE_NAME . "/js/" . strtolower($tagName) . ".js";
        if (file_exists($path)) {
            return file_get_contents($path);
        } else {
            LogUtility::msg("The javascript file ($path) was not found", LogUtility::LVL_MSG_WARNING, $tagName);
            return "";
        }

    }

    /**
     * @param $tagName
     * @return false|string - the css content of the css file
     */
    private function getCssRulesFromFile($tagName)
    {

        $path = DOKU_PLUGIN . PluginUtility::PLUGIN_BASE_NAME . "/style/" . strtolower($tagName) . ".css";
        if (file_exists($path)) {
            return file_get_contents($path);
        } else {
            LogUtility::msg("The css file ($path) was not found", LogUtility::LVL_MSG_WARNING, $tagName);
            return "";
        }

    }

    /**
     * @param $comboTag
     * @param $htmlTag
     * @param array $htmlAttributes - upsert a tag each time that this function is called
     */
    public function upsertHeadTagForRequest($comboTag, $htmlTag, array $htmlAttributes)
    {
        $id = PluginUtility::getPageId();
        $this->headsByRequest[$id][self::TAG_TYPE][$comboTag][$htmlTag] = [$htmlAttributes];
    }

    /**
     * Keep track of the parsed bar (ie page in page)
     * @param $pageId
     * @param $cached
     */
    public function addBar($pageId, $cached)
    {
        $this->barsProcessed[$pageId] = $cached;
    }

    public function getBarsOfPage()
    {
        return $this->barsProcessed;
    }

    /**
     * A function to be able to add snippets from the snippets cache
     * when a bar was served from the cache
     * @param $bar
     * @param $snippets
     */
    public function addSnippetsFromCacheForBar($bar, $snippets)
    {
        if (!isset($this->headsByBar[$bar])) {
            $this->headsByBar[$bar] = $snippets;
        } else {
            LogUtility::msg("Snippets for the bar ($bar) have been already added. Are you sure that the bar was not rendered ?", LogUtility::LVL_MSG_ERROR);
        }
    }

    public function getSnippetsForBar($bar)
    {
        if (isset($this->headsByBar[$bar])) {
            return $this->headsByBar[$bar];
        } else {
            return null;
        }

    }

    /**
     * Add a javascript snippet at a request level
     * (Meaning that it should never be cached)
     * @param $comboComponent
     * @param $script
     */
    public function upsertJavascriptSnippetForRequest($comboComponent, $script = null)
    {

        $id = PluginUtility::getPageId();
        if ($script == null) {
            $script = $this->getJavascriptContentFromFile($comboComponent);
        }

        $this->headsByRequest[$id][self::JS_TYPE][$comboComponent]["script"] = [
            array(
                "class" => SnippetManager::getClassFromTag($comboComponent),
                "type" => "text/javascript",
                "_data" => $script
            )
        ];


    }

    public function upsertCssSnippetForRequest($comboComponent, $script = null)
    {
        $id = PluginUtility::getPageId();
        if ($script == null) {
            $script = $this->getCssRulesFromFile($comboComponent);
        }

        $this->headsByRequest[$id][self::CSS_TYPE][$comboComponent]["style"] = [
            array(
                "class" => SnippetManager::getClassFromTag($comboComponent),
                "_data" => $script
            )
        ];

    }


}

global $componentScript;
$componentScript = new SnippetManager();


