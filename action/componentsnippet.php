<?php

use ComboStrap\LogUtility;
use ComboStrap\MetadataUtility;
use ComboStrap\PluginUtility;
use ComboStrap\Page;
use ComboStrap\TplUtility;
use dokuwiki\Cache\CacheRenderer;

if (!defined('DOKU_INC')) die();

/**
 *
 *
 * Add the snippet needed by the components
 *
 */
class action_plugin_combo_componentsnippet extends DokuWiki_Action_Plugin
{


    function __construct()
    {
        // enable direct access to language strings
        // ie $this->lang
        $this->setupLocale();
    }

    public function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'componentSnippet', array());
    }

    /**
     * Dokuwiki has already a canonical methodology
     * https://www.dokuwiki.org/canonical
     *
     * @param $event
     */
    function componentSnippet($event)
    {


        global $ID;
        if (empty($ID)) {
            return;
        }

        $snippetManager = PluginUtility::getSnippetManager();

        /**
         * Adapted from {@link p_cached_output()}
         */
        $storedArray = array();
        $file = wikiFN($ID);
        $cache = new CacheRenderer($ID, $file, "snippet");
        if ($cache->useCache()) { // useCache means isCacheUsable
            $data = $cache->retrieveCache();
            global $conf;
            if ($conf['allowdebug']) {
                LogUtility::log2file("Snippet cache file {$cache->cache} used");
            }
            if (!empty($data)) {
                $storedArray = unserialize($data);
            }
            $snippetManager->mergeWithPreviousRun($storedArray);
        }


        /**
         * Css
         */
        foreach ($snippetManager->getCss() as $component => $snippet) {
            $event->data['style'][] = array(
                "class" => $component,
                "_data" => $snippet
            );
        }

        /**
         * Javascript
         */
        foreach ($snippetManager->getJavascript() as $component => $snippet) {
            $event->data['script'][] = array(
                "class" => $component,
                "type" => "text/javascript",
                "_data" => $snippet
            );
        }

        /**
         * tags
         */
        foreach ($snippetManager->getTags() as $component => $tags) {
            foreach ($tags as $tagType => $tagRows) {
                foreach ($tagRows as $tagRow) {
                    $tagRow["class"] = $component;
                    $event->data[$tagType][] = $tagRow;
                }
            }
        }

    }

    /**
     * Adapted from {@link p_cached_output()}
     */
    function getCachedSnippet()
    {

        global $ID;
        $file = wikiFN($ID);
        $format = "combo_head";
        global $conf;


    }

//    function storeSnippetArray()
//    {
//        global $conf;
//
//        $cache = new CacheRenderer($ID, $file, $format);
//        if (!empty($headHtml)) {
//            if ($cache->storeCache($headHtml)) {              // storeCache() attempts to save cachefile
//                if ($conf['allowdebug']) {
//
//                    LogUtility::log2file("No cache file used, but created {$cache->cache} ");
//                }
//            } else {
//                $cache->removeCache();                     //try to delete cachefile
//                if ($conf['allowdebug']) {
//                    LogUtility::log2file("no cachefile used, caching forbidden");
//                }
//            }
//        }
//    }


}
