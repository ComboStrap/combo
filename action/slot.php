<?php


require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');

use ComboStrap\Dimension;
use ComboStrap\ExceptionCombo;
use ComboStrap\FileSystems;
use ComboStrap\Identity;
use ComboStrap\CacheMedia;
use ComboStrap\DokuPath;
use ComboStrap\ImageSvg;
use ComboStrap\MediaLink;
use ComboStrap\LogUtility;
use ComboStrap\Page;
use ComboStrap\PluginUtility;
use ComboStrap\Resources;
use ComboStrap\SvgImageLink;
use ComboStrap\TagAttributes;


/**
 * Class action_plugin_combo_slot
 * Returned an svg optimized version
 */
class action_plugin_combo_slot extends DokuWiki_Action_Plugin
{


    public function register(Doku_Event_Handler $controller)
    {

        /**
         * https://www.dokuwiki.org/devel:event:parser_wikitext_preprocess
         */
        $controller->register_hook('PARSER_WIKITEXT_PREPROCESS', 'AFTER', $this, 'handleSlot');


    }

    /**
     * @param Doku_Event $event
     */
    public function handleSlot(Doku_Event &$event)
    {

        $data = &$event->data;

        $mainHeader = page_findnearest("slot_main_header");
        if ($mainHeader !== false) {
            $path = DokuPath::createPagePathFromId($mainHeader);
            $content = FileSystems::getContent($path);
            $data .= $content;
        }

        $mainFooter = page_findnearest("slot_main_footer");
        if ($mainFooter !== false) {
            $path = DokuPath::createPagePathFromId($mainFooter);
            $content = FileSystems::getContent($path);
            $data = $data . $content;
        }


    }


}
