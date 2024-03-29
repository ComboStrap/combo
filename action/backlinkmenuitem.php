<?php

require_once(__DIR__ . '/../vendor/autoload.php');

use ComboStrap\BacklinkMenuItem;
use ComboStrap\ExceptionNotFound;
use ComboStrap\ExecutionContext;
use ComboStrap\FetcherPage;
use ComboStrap\HttpResponseStatus;
use ComboStrap\MarkupPath;
use ComboStrap\Mime;
use ComboStrap\Tag\RelatedTag;
use dokuwiki\Menu\Item\Backlink;




/**
 * Handle the backlink menu item
 */
class action_plugin_combo_backlinkmenuitem extends DokuWiki_Action_Plugin
{


    const CALL_ID = "combo-backlink";
    const CANONICAL = "backlink";
    const WHREF = "whref";

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


        $menuItems = &$event->data["items"];
        foreach ($menuItems as $key => $menuItem) {
            if ($menuItem instanceof Backlink) {
                $menuItems[$key] = new BacklinkMenuItem();
                break;
            }
        }
        /**
         * Add the link to build the link to the backlinks actions
         */
        try {
            $requestedContextPage = ExecutionContext::getActualOrCreateFromEnv()->getRequestedPath();
        } catch (ExceptionNotFound $e) {
            // admin
            return;
        }
        global $JSINFO;
        $JSINFO[self::WHREF] = FetcherPage::createPageFetcherFromPath($requestedContextPage)->getFetchUrl()->toString();

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
        $executionContext = ExecutionContext::getActualOrCreateFromEnv();
        if (empty($id)) {
            $executionContext->response()
                ->setStatus(HttpResponseStatus::BAD_REQUEST)
                ->setEvent($event)
                ->setCanonical(self::CANONICAL)
                ->setBody("The page id should not be empty", Mime::getHtml())
                ->end();
            return;
        }


        $backlinkPages = MarkupPath::createMarkupFromId($id);
        $html = RelatedTag::renderForPage($backlinkPages);


        $executionContext
            ->response()
            ->setStatus(HttpResponseStatus::ALL_GOOD)
            ->setEvent($event)
            ->setCanonical(self::CANONICAL)
            ->setBody($html, Mime::getHtml())
            ->end();

    }


}



