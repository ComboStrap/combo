<?php


use ComboStrap\Api\ApiRouter;
use ComboStrap\Identity;
use ComboStrap\QualityMenuItem;

require_once(__DIR__ . '/../vendor/autoload.php');

/**
 *
 * Show a quality message
 *
 *
 *
 */
class action_plugin_combo_qualitymessage extends DokuWiki_Action_Plugin
{


    function __construct()
    {
        // enable direct access to language strings
        // ie $this->lang
        $this->setupLocale();
    }


    function register(Doku_Event_Handler $controller)
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

        if (!Identity::isWriter()) {
            return;
        }

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
        array_splice($event->data['items'], -1, 0, array(new QualityMenuItem()));

    }

    /**
     * Main function; dispatches the visual comment actions
     * @param Doku_Event $event
     * @param $param
     */
    function ajaxCall(Doku_Event &$event, $param): void
    {
        ApiRouter::handle($event);
    }
}
