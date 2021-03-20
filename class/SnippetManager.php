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
    public function addCssSnippetOnlyOnce($id, $css = null)
    {

        $cssForPage = &$this->getHeadsForPage(self::CSS_TYPE);
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
    public function addJavascriptSnippetIfNeeded($id, $script = null)
    {
        $js = &$this->getHeadsForPage(self::JS_TYPE);
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
    public function addHeadTagsOnce($id, $componentTags)
    {
        $headTags = &$this->getHeadsForPage(self::TAG_TYPE);
        if (!isset($headTags[$id])) {
            $headTags[$id] = $componentTags;
        }
    }


    /**
     * @param $type
     * @return mixed a reference to the heads array
     */
    private function &getHeadsForPage($type)
    {
        /**
         * To be able to get
         * all component used by page (sidebar included)
         */
        $ID = PluginUtility::getPageId();
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
     *
     * Add the style from the previous run
     * because one of the bar may have not run because it was cached
     *
     * @param array $previousRun
     */
    public function mergeWithPreviousRun(array $previousRun)
    {
        $this->headsByBar = array_merge($previousRun, $this->headsByBar);

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
                    foreach($snippets as $comboTag => $tags) {
                        foreach($tags as $htmlTagName => $htmlTags) {
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
     * @param array $array - add a tag each time that this function is called
     * Used to add meta by request (for instance, during the rendering of a sidebar
     * and after during the rendering of a page)
     */
    public function addHeadTagEachTime($comboTag, $htmlTag, array $array)
    {
        $id = PluginUtility::getPageId();
        $this->headsByRequest[$id][self::TAG_TYPE][$comboTag][$htmlTag][] = $array;
    }


}

global $componentScript;
$componentScript = new SnippetManager();


