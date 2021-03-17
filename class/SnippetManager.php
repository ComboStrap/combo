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
 * A component to manage the HTML that
 * from components that should come in the head HTML node
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

    /**
     * @var array the content array of all heads
     */
    var $heads = array();


    public function addCssSnippetOnlyOnce($id, $css = null)
    {

        $cssForPage = &$this->getHeadsForPage(self::CSS_TYPE);
        if (!isset($cssForPage[$id])) {
            if ($css == null) {
                $cssForPage[$id] = PluginUtility::getCssRules($id);
            } else {
                $cssForPage[$id] = $css;
            }
        }

    }

    public function addJavascriptSnippetIfNeeded($id, $script)
    {
        $js = &$this->getHeadsForPage(self::JS_TYPE);
        if (!isset($js[$id])) {

            $js[$id] = $script;

        }
    }

    /**
     * @param $id
     * @param array $componentTags - an array of tags where the key is the node and the value the attributes
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
        global $ID;
        if (!isset($this->heads[$ID])) {
            $this->heads[$ID] = array();
        }
        if (!isset($this->heads[$ID][$type])) {
            $this->heads[$ID][$type] = array();
        }
        return $this->heads[$ID][$type];
    }

    /**
     * @return SnippetManager - the global reference
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
        $this->heads = array_merge($previousRun, $this->heads);

    }

    /**
     * @param $localType
     * @return array
     */
    private function getSnippets($localType)
    {
        $distinctSnippets = array();
        foreach ($this->heads as $page => $components) {
            foreach ($components as $type => $snippets) {
                if ($type == $localType) {
                    $distinctSnippets = array_merge($distinctSnippets, $snippets);
                }
            }
        }
        return $distinctSnippets;
    }



}

global $componentScript;
$componentScript = new SnippetManager();


