<?php

use ComboStrap\PluginUtility;
use ComboStrap\Page;
use dokuwiki\Cache\CacheInstructions;
use dokuwiki\Cache\CacheParser;
use dokuwiki\Cache\CacheRenderer;

if (!defined('DOKU_INC')) die();

/**
 *
 *
 * To delete sidebar (cache) cache when a page was modified in a namespace
 */
class action_plugin_combo_cachebursting extends DokuWiki_Action_Plugin
{


    function __construct()
    {
        // enable direct access to language strings
        // ie $this->lang
        $this->setupLocale();
    }

    public function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('IO_WIKIPAGE_WRITE', 'BEFORE', $this, 'cacheBursting', array());
    }

    /**
     * Dokuwiki has already a canonical methodology
     * https://www.dokuwiki.org/canonical
     *
     * @param $event
     */
    function cacheBursting($event)
    {

        $namespace = $event->data[1];
        global $conf;
        /**
         * @see {@link \ComboStrap\TplConstant::CONF_SIDEKICK}
         */
        $sidebars = [
            $conf['sidebar'],
            $conf['tpl']['strap']['sidekickbar']
        ];

        /**
         * Delete the cache for the sidebar if they exists
         */
        foreach ($sidebars as $sidebar) {
            $id = new Page("$namespace:$sidebar");
            $id->deleteCache();
        }

    }

}
