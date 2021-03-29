<?php

use ComboStrap\Call;
use ComboStrap\LogUtility;
use ComboStrap\MetadataUtility;
use ComboStrap\PluginUtility;
use ComboStrap\Page;
use ComboStrap\SnippetManager;
use ComboStrap\Tag;
use ComboStrap\TplUtility;
use dokuwiki\Cache\CacheRenderer;

if (!defined('DOKU_INC')) die();

/**
 *
 *
 * Lazy load image
 *
 */
class action_plugin_combo_lazyloading extends DokuWiki_Action_Plugin
{

    function __construct()
    {
        // enable direct access to language strings
        // ie $this->lang
        $this->setupLocale();
    }

    public function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('PARSER_HANDLER_DONE', 'BEFORE', $this, 'lazyload', array());
    }

    /**
     * Dokuwiki has already a canonical methodology
     * https://www.dokuwiki.org/canonical
     *
     * @param $event
     */
    function lazyload($event)
    {

        /**
         * @var Doku_Handler
         */
        $data = &$event->data;
        foreach ($data->calls as $id => &$call) {
            $tag = new Call($call);
            $tagName = $tag->getTagName();
            switch ($tagName){
                case "internalmedia":
                    $image = $call[1][0];
                    break;
            }
        }



    }


}
