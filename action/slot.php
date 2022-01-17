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


    /**
     * Name of the main header slot
     */
    const SLOT_MAIN_HEADER_NAME = "slot_main_header";
    /**
     * Name of the main footer slot
     */
    const SLOT_MAIN_FOOTER_NAME = "slot_main_footer";
    const SLOT_MAIN_NAMES = [self::SLOT_MAIN_FOOTER_NAME, self::SLOT_MAIN_HEADER_NAME];

    public function register(Doku_Event_Handler $controller)
    {

        /**
         * https://www.dokuwiki.org/devel:event:tpl_act_render
         */
        $controller->register_hook('TPL_ACT_RENDER', 'BEFORE', $this, 'handleSlotMainBefore');
        /**
         * https://www.dokuwiki.org/devel:event:tpl_act_render
         */
        $controller->register_hook('TPL_ACT_RENDER', 'BEFORE', $this, 'handleSlotMainAfter');


    }

    /**
     * @param Doku_Event $event
     */
    public function handleSlotMainBefore(Doku_Event &$event)
    {

        $data = &$event->data;

        $mainHeader = page_findnearest(self::SLOT_MAIN_HEADER_NAME);
        global $ACT;
        $showMainHeader = $mainHeader!==false && ($ACT == 'show');
        if ($showMainHeader !== false) {
            $sideBarHtml = TplUtility::renderSlot($mainHeader);
            $data .= $content;
        }

    }

    public function handleSlotMainAfter(Doku_Event &$event)
    {
        $data = &$event->data;
        $mainFooter = page_findnearest(self::SLOT_MAIN_FOOTER_NAME);
        if ($mainFooter !== false) {
            $path = DokuPath::createPagePathFromId($mainFooter);
            $content = FileSystems::getContent($path);
            $data = $data . $content;
        }
    }


}
