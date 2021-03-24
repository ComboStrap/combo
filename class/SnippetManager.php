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

    public static function init()
    {
        global $componentScript;
        $componentScript = new SnippetManager();
    }


    public static function getClassFromSnippetId($tag)
    {
        return self::COMBO_CLASS_PREFIX . $tag;
    }


    /**
     * @param $snippetId
     * @param string|null $css - the css
     *   if null, the file $snippetId.css is searched in the `style` directory
     */
    public function upsertCssSnippetForBar($snippetId, $css = null)
    {
        global $ID;
        $bar = $ID;
        if ($css == null) {
            $css = $this->getCssRulesFromFile($snippetId);
        }
        $this->headsByBar[$bar][self::CSS_TYPE][$snippetId] = $css;

    }

    /**
     * @param $snippetId
     * @param $script - javascript code if null, it will search in the js directory
     */
    public function upsertJavascriptForBar($snippetId, $script = null)
    {
        global $ID;
        $bar = $ID;
        if ($script == null) {
            $script = $this->getJavascriptContentFromFile($snippetId);
        }
        $this->headsByBar[$bar][self::JS_TYPE][$snippetId] = $script;
    }

    /**
     * @param $snippetId
     * @param array $componentTags - an array of tags without content where the key is the node type and the value a array of attributes array
     */
    public function upsertHeadTagsForBar($snippetId, $componentTags)
    {
        global $ID;
        $bar = $ID;
        $this->headsByBar[$bar][self::TAG_TYPE][$snippetId] = $componentTags;
    }


    /**
     * @return SnippetManager - the global reference
     * that is set for every run at the end of this fille
     */
    public static function get()
    {
        global $componentScript;
        if (empty($componentScript)) {
            SnippetManager::init();
        }
        return $componentScript;
    }


    /**
     * @return array of node type and an array of array of html attributes
     */
    public function getSnippets()
    {
        /**
         * Delete the bar, page
         */
        $distinctSnippets = array();
        $allSnippets = [$this->headsByBar, $this->headsByRequest];
        foreach ($allSnippets as $snippets) {
            foreach ($snippets as $barOrPageId => $snippetTypes) {
                foreach ($snippetTypes as $snippetType => $snippetId) {
                    if (is_array($snippetId)) {
                        if (isset($distinctSnippets[$snippetType])) {
                            $actualSnippetId = $distinctSnippets[$snippetType];
                            $mergeSnippetId = array_merge($actualSnippetId, $snippetId);
                        } else {
                            $mergeSnippetId = $snippetId;
                        }
                    } else {
                        // css and javascript script
                        $mergeSnippetId = $snippetId;
                    }
                    $distinctSnippets[$snippetType] = $mergeSnippetId;
                }
            }
        }

        /**
         * Transform in dokuwuiki format
         * We collect the separately head that have content
         * from the head that refers to external resources
         * because the content will depends on the resources
         * and should then come in the last position
         */
        $dokuWikiHeadsFormatContent = array();
        $dokuWikiHeadsSrc = array();
        foreach ($distinctSnippets as $snippetType => $snippetBySnippetId) {
            switch ($snippetType) {
                case self::JS_TYPE:
                    foreach ($snippetBySnippetId as $snippetId => $snippet) {
                        $dokuWikiHeadsFormatContent["script"][] = array(
                            "class" => self::getClassFromSnippetId($snippetId),
                            "_data" => $snippet
                        );
                    }
                    break;
                case self::CSS_TYPE:
                    foreach ($snippetBySnippetId as $snippetId => $snippet) {
                        $dokuWikiHeadsFormatContent["style"][] = array(
                            "class" => self::getClassFromSnippetId($snippetId),
                            "_data" => $snippet
                        );
                    }
                    break;
                case self::TAG_TYPE:
                    foreach ($snippetBySnippetId as $snippetId => $snippetTags) {
                        foreach($snippetTags as $snippetType => $heads) {
                            $classFromSnippetId = self::getClassFromSnippetId($snippetId);
                            foreach ($heads as $head) {
                                if(isset($head["class"])){
                                    $head["class"] = $head["class"]." ". $classFromSnippetId;
                                } else {
                                    $head["class"] = $classFromSnippetId;
                                }
                                $dokuWikiHeadsSrc[$snippetType][] = $head;
                            }
                        }
                    }
                    break;
            }
        }

        /**
         * Merge the content head node at the last position of the head ref node
         */
        foreach ($dokuWikiHeadsFormatContent as $headsNodeType => $headsData){
            foreach ($headsData as $heads){
                $dokuWikiHeadsSrc[$headsNodeType][]=$heads;
            }

        }
        return $dokuWikiHeadsSrc;
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
        $this->barsProcessed = array();
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

        $path = Resources::getSnippetResourceDirectory() . "/js/" . strtolower($tagName) . ".js";
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

        $path = Resources::getSnippetResourceDirectory() . "/style/" . strtolower($tagName) . ".css";
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
            // Bad data
            $data = var_export($this->headsByBar[$bar], true);
            LogUtility::msg("Internal error: Snippets for the bar ($bar) have been added while the bar was cached. The snippets added are ($data). This snippet should be added at the request level", LogUtility::LVL_MSG_ERROR);
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

        $this->headsByRequest[$id][self::JS_TYPE][$comboComponent] = $script;

    }

    public function upsertCssSnippetForRequest($comboComponent, $script = null)
    {
        $id = PluginUtility::getPageId();
        if ($script == null) {
            $script = $this->getCssRulesFromFile($comboComponent);
        }

        $this->headsByRequest[$id][self::CSS_TYPE][$comboComponent] = $script;

    }


}


