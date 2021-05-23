<?php

use ComboStrap\LogUtility;
use ComboStrap\PluginUtility;
use ComboStrap\Site;
use ComboStrap\SnippetManager;
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

    const COMBO_CACHE_PREFIX = "combo:cache:";

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

        /**
         * To add the snippets in the header
         */
        $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'componentSnippetHead', array());

        /**
         * To add the snippets in the content
         * if they have not been added to the header
         */
        $controller->register_hook('TPL_CONTENT_DISPLAY', 'BEFORE', $this, 'componentSnippetContent', array());

        /**
         * To reset the value
         */
        $controller->register_hook('DOKUWIKI_DONE', 'BEFORE', $this, 'close', array());


        /**
         * To log the cache used by bar
         */
        $controller->register_hook('PARSER_CACHE_USE', 'AFTER', $this, 'barParsed', array());

    }

    /**
     * Reset variable
     * Otherwise in test, when we call it two times, it just fail
     */
    function close()
    {

        $this->headerOutputWasCalled = false;

        /**
         * Fighting the fact that in 7.2,
         * there is still a cache
         */
        PluginUtility::initSnippetManager();

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
            global $_SERVER;
            $requestUri = $_SERVER['REQUEST_URI'];
            if (!strpos($requestUri, "/lib/exe/ajax.php") !== false) {
                global $_REQUEST;
                $call = $_REQUEST['call'];
                if($call != action_plugin_combo_webcode::CALL_ID) {
                    return;
                }
            }
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
         * For each processed bar in the page
         *   * retrieve the snippets from the cache or store the process one
         *   * add the cache information in meta
         */
        $bars = $snippetManager->getBarsOfPage();
        foreach ($bars as $bar => $servedFromCache) {

            // Add cache information into the head meta
            $event->data["meta"][] = array("name" => self::COMBO_CACHE_PREFIX . $bar, "content" => var_export($servedFromCache, true));

            // Get or store the data
            $cache = new \dokuwiki\Cache\Cache($bar, "snippet");

            // if the bar was served from the cache
            if ($servedFromCache) {

                // Retrieve snippets from previous run
                $data = $cache->retrieveCache();

                if (!empty($data)) {
                    $snippets = unserialize($data);
                    $snippetManager->addSnippetsFromCacheForBar($bar, $snippets);

                    if (Site::debugIsOn()) {
                        LogUtility::log2file("Snippet cache file {$cache->cache} used", LogUtility::LVL_MSG_DEBUG);
                        $event->data['script'][] = array(
                            "type" => "application/json",
                            "_data" => json_encode($snippets),
                            "class" => "combo-snippet-cache-" . str_replace(":", "-", $bar));
                    }

                }
            } else {
                $snippets = $snippetManager->getSnippetsForBar($bar);
                if (!empty($snippets)) {
                    $cache->storeCache(serialize($snippets));
                }
            }

        }

        /**
         * Snippets
         */
        foreach ($snippetManager->getSnippets() as $tagType => $tags) {

            foreach ($tags as $tag) {
                $event->data[$tagType][] = $tag;
            }

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

            foreach ($snippetManager->getSnippets() as $tagType => $tags) {

                foreach ($tags as $tag) {
                    $event->data .= DOKU_LF . "<$tagType";
                    $attributes = "";
                    $content = null;
                    foreach ($tag as $attributeName => $attributeValue) {
                        if ($attributeName != "_data") {
                            $attributes .= " $attributeName=\"$attributeValue\"";
                        } else {
                            $content = $attributeValue;
                        }
                    }
                    $event->data .= "$attributes>";
                    if (!empty($content)) {
                        $event->data .= $content;
                    }
                    $event->data .= "</$tagType>" . DOKU_LF;
                }

            }

            $snippetManager->close();

            /**
             * Set the value back
             */
            $this->headerOutputWasCalled = false;

        }

    }


    /**
     *
     * @param $event
     */
    function barParsed($event)
    {
        $data = $event->data;
        if ($data->mode == "xhtml") {

            /* @var CacheRenderer $data */
            $page = $data->page;
            $cached = $event->result;
            PluginUtility::getSnippetManager()->addBar($page, $cached);

        }


    }


}
