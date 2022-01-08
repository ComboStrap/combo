<?php

use ComboStrap\BacklinkMenuItem;
use ComboStrap\Event;
use ComboStrap\FileSystems;
use ComboStrap\Identity;
use ComboStrap\LinkUtility;
use ComboStrap\MetadataDokuWikiStore;
use ComboStrap\Mime;
use ComboStrap\Page;
use ComboStrap\PagePath;
use ComboStrap\Reference;
use ComboStrap\References;


require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');

/**
 * Handle the backlink menu item
 */
class action_plugin_combo_backlinkmenuitem extends DokuWiki_Action_Plugin
{


    const CALL_ID = "combo-backlink";
    const CANONICAL = "backlink";

    public function register(Doku_Event_Handler $controller)
    {


        /**
         * Add a icon in the page tools menu
         * https://www.dokuwiki.org/devel:event:menu_items_assembly
         */
        $controller->register_hook('MENU_ITEMS_ASSEMBLY', 'AFTER', $this, 'addMenuItem');


        /**
         * The ajax api to return data
         */
        $controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE', $this, 'ajaxCall');


    }

    function addMenuItem(Doku_Event $event, $param)
    {


        /**
         * The `view` property defines the menu that is currently built
         * https://www.dokuwiki.org/devel:menus
         * If this is not the page menu, return
         */
        if ($event->data['view'] != 'page') return;

        global $INFO;
        if (!$INFO['exists']) {
            return;
        }
        array_splice($event->data['items'], -1, 0, array(new BacklinkMenuItem()));

    }

    /**
     * Main function; dispatches the visual comment actions
     * @param   $event Doku_Event
     */
    function ajaxCall(&$event, $param): void
    {
        $call = $event->data;
        if ($call != self::CALL_ID) {
            return;
        }
        //no other ajax call handlers needed
        $event->stopPropagation();
        $event->preventDefault();

        /**
         * Shared check between post and get HTTP method
         */
        $id = $_GET["id"];
        if ($id === null) {
            /**
             * With {@link TestRequest}
             * for instance
             */
            $id = $_REQUEST["id"];
        }

        if (empty($id)) {
            \ComboStrap\HttpResponse::create(\ComboStrap\HttpResponse::STATUS_BAD_REQUEST)
                ->setEvent($event)
                ->setCanonical(self::CANONICAL)
                ->send("The page id should not be empty", Mime::HTML);
            return;
        }


        $backlinkPages = Page::createPageFromId($id)
            ->getBacklinks();
        $data = [];

        foreach ($backlinkPages as $backlinkPage) {
            $link = LinkUtility::createFromPageId($backlinkPage->getDokuwikiId());
            $data[] = $link->renderOpenTag() . $backlinkPage->getTitleOrDefault() . $link->renderClosingTag();
        }


        \ComboStrap\HttpResponse::create(\ComboStrap\HttpResponse::STATUS_ALL_GOOD)
            ->setEvent($event)
            ->setCanonical(self::CANONICAL)
            ->send(json_encode($data), Mime::JSON);

    }


}



