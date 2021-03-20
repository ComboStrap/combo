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
 * Add cache information to the head
 *
 */
class action_plugin_combo_cache extends DokuWiki_Action_Plugin
{

    const tag = "cache";
    const COMBO_CACHE_PREFIX = "combo:cache:";


    function __construct()
    {
        // enable direct access to language strings
        // ie $this->lang
        $this->setupLocale();
    }

    public function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('PARSER_CACHE_USE', 'AFTER', $this, 'cache', array());
    }

    /**
     *
     * @param $event
     */
    function cache($event)
    {
        $data = $event->data;
        if ($data->mode == "xhtml") {

            /* @var CacheRenderer $data */
            $page = $data->page;
            $result = $event->result;

            PluginUtility::getSnippetManager()->addHeadTagEachTime(
                self::tag,
                "meta",
                array("name" => self::COMBO_CACHE_PREFIX . $page, "content" => var_export($result, true))
            );

        }


    }


}
