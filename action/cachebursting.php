<?php

use ComboStrap\Page;

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

        $sidebars = [
            $conf['sidebar']
        ];

        /**
         * @see {@link \ComboStrap\TplConstant::CONF_SIDEKICK}
         */
        $sideKickBarName = $conf['tpl']['strap']['sidekickbar'];
        if(!empty($sideKickBarName)){
            $sidebars[]=$sideKickBarName;
        }

        /**
         * Delete the cache for the sidebar if they exists
         */
        foreach ($sidebars as $sidebar) {
            if (!empty($namespace)){
                $id = new Page("$namespace:$sidebar");
            } else {
                $id = new Page($sidebar);
            }
            $id->deleteCache();
        }

    }

}
