<?php

use ComboStrap\LogUtility;
use ComboStrap\MetadataUtility;
use ComboStrap\PluginUtility;
use ComboStrap\Page;
use ComboStrap\SnippetManager;
use ComboStrap\TplUtility;
use dokuwiki\Cache\CacheRenderer;

if (!defined('DOKU_INC')) die();

/**
 *
 *
 * Add the snippet needed by the components
 *
 */
class action_plugin_combo_snippets extends DokuWiki_Action_Plugin
{


    /**
     * @var bool - to trace if the header output was called
     */
    private $headerOutputWasCalled = false;

    function __construct()
    {
        // enable direct access to language strings
        // ie $this->lang
        $this->setupLocale();
    }

    public function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'componentSnippetHead', array());
        $controller->register_hook('TPL_CONTENT_DISPLAY', 'BEFORE', $this, 'componentSnippetContent', array());
    }

    /**
     * Dokuwiki has already a canonical methodology
     * https://www.dokuwiki.org/canonical
     *
     * @param $event
     */
    function componentSnippetHead($event)
    {


        global $ID;
        if (empty($ID)) {
            return;
        }

        /**
         * Advertise that the header output was called
         * If the user is using another template
         * than strap that does not put the component snippet
         * in the head
         * Used in
         */
        $this->headerOutputWasCalled = true;

        $snippetManager = PluginUtility::getSnippetManager();

        /**
         * Because the cache is at the bar level,
         * a rendering for a page may run without the others
         * Therefore we saved the data in between
         * (The storage is done at the page level)
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
        $cache->storeCache(serialize($snippetManager->getData()));


        /**
         * tags
         */
        foreach ($snippetManager->getTags() as $component => $tags) {
            foreach ($tags as $tagType => $tagRows) {
                foreach ($tagRows as $tagRow) {
                    $tagRow["class"] = SnippetManager::getClassFromTag($component);;
                    $event->data[$tagType][] = $tagRow;
                }
            }
        }

        /**
         * Css
         */
        foreach ($snippetManager->getCss() as $component => $snippet) {
            $event->data['style'][] = array(
                "class" => SnippetManager::getClassFromTag($component),
                "_data" => $snippet
            );
        }

        /**
         * Javascript
         */
        foreach ($snippetManager->getJavascript() as $component => $snippet) {
            $event->data['script'][] = array(
                "class" => SnippetManager::getClassFromTag($component),
                "type" => "text/javascript",
                "_data" => $snippet
            );
        }


        $snippetManager->close();

    }

    /**
     * Used if the template does not run the content
     * before the calling of the header as strap does.
     *
     * In this case, the {@link \ComboStrap\SnippetManager::close()} has
     * not run, and the snippets are still in memory.
     *
     * We store them in the HTML and they
     * follows then the HTML cache of DokuWiki
     * @param $event
     */
    function componentSnippetContent($event)
    {


        /**
         * Run only if the header output was already called
         */
        if ($this->headerOutputWasCalled) {

            $snippetManager = PluginUtility::getSnippetManager();

            /**
             * tags
             */
            foreach ($snippetManager->getTags() as $component => $tags) {
                foreach ($tags as $tagType => $tagRows) {
                    foreach ($tagRows as $tagRow) {
                        $class = SnippetManager::getClassFromTag($component);
                        $event->data .= "<$tagType class=\"$class\"";
                        foreach ($tagRow as $attributeName => $attributeValue) {
                            if ($attributeName != "_data") {
                                $event->data .= " $attributeName=\"$attributeValue\"";
                            } else {
                                $content = $attributeValue;
                            }
                        }
                        $event->data .= ">";
                        if (!empty($content)) {
                            $event->data .= $content;
                        }
                        $event->data .= "</$tagType>";
                    }
                }
            }

            /**
             * Css
             */
            foreach ($snippetManager->getCss() as $component => $snippet) {

                $class = SnippetManager::getClassFromTag($component);
                $event->data .= "<style class=\"$class\">$snippet</style>" . DOKU_LF;

            }

            /**
             * Javascript
             */
            foreach ($snippetManager->getJavascript() as $component => $snippet) {
                $class = SnippetManager::getClassFromTag($component);
                $event->data .= "<script class=\"$class\" type=\"text/javascript\">$snippet</script>" . DOKU_LF;
            }

            $snippetManager->close();

            /**
             * Set the value back
             */
            $this->headerOutputWasCalled = false;

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
