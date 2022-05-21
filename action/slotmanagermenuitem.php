<?php

use ComboStrap\BacklinkMenuItem;
use ComboStrap\Event;
use ComboStrap\FileSystems;
use ComboStrap\Identity;
use ComboStrap\MarkupRef;
use ComboStrap\MetadataDokuWikiStore;
use ComboStrap\Mime;
use ComboStrap\Page;
use ComboStrap\PagePath;
use ComboStrap\PluginUtility;
use ComboStrap\Reference;
use ComboStrap\References;


require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');

/**
 * Handle the slot manager menu item
 */
class action_plugin_combo_slotmanagermenuitem extends DokuWiki_Action_Plugin
{


    const CANONICAL = "edit-page-menu";


    public function register(Doku_Event_Handler $controller)
    {


        /**
         * Add a icon in the page tools menu
         * https://www.dokuwiki.org/devel:event:menu_items_assembly
         */
        $controller->register_hook('MENU_ITEMS_ASSEMBLY', 'AFTER', $this, 'addMenuItem');


    }

    function addMenuItem(Doku_Event $event, $param)
    {


        if (!Identity::isWriter()) {
            return;
        }

        /**
         * The `view` property defines the menu that is currently built
         * https://www.dokuwiki.org/devel:menus
         * If this is not the page menu, return
         */
        if ($event->data['view'] != 'page') return;

        $menuItems = &$event->data["items"];
        foreach ($menuItems as $key => $menuItem) {
            if ($menuItem instanceof \dokuwiki\Menu\Item\Edit) {
                array_splice($menuItems, $key + 1, 0, [new \ComboStrap\SlotManagerMenuItem()]);
                break;
            }
        }


    }


}



