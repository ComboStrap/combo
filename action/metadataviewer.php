<?php

use ComboStrap\Identity;
use ComboStrap\MetadataMenuItem;
use ComboStrap\MetadataUtility;

if (!defined('DOKU_INC')) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');

require_once (__DIR__.'/../ComboStrap/PluginUtility.php');


/**
 *
 * To show a message after redirection or rewriting
 *
 *
 *
 */
class action_plugin_combo_metadataviewer extends DokuWiki_Action_Plugin
{


    function __construct()
    {
        // enable direct access to language strings
        // ie $this->lang
        $this->setupLocale();
    }


    function register(Doku_Event_Handler $controller)
    {

        /* This will call the function _displayMetaMessage */
        $controller->register_hook('TPL_ACT_RENDER', 'BEFORE', $this, '_displayMetaViewer', array());

        /**
         * Add a icon in the page tools menu
         * https://www.dokuwiki.org/devel:event:menu_items_assembly
         */
        $controller->register_hook('MENU_ITEMS_ASSEMBLY', 'AFTER', $this, 'handle_rail_bar');

    }


    /**
     * Main function; dispatches the visual comment actions
     * @param   $event Doku_Event
     * @param $param
     */
    function _displayMetaViewer(&$event, $param)
    {

        if ($this->getConf(MetadataUtility::CONF_ENABLE_WHEN_EDITING) && ($event->data == 'edit' || $event->data == 'preview')) {

            print MetadataUtility::getHtmlMetadataBox($this);

        }

    }

    public function handle_rail_bar(Doku_Event $event, $param)
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
        array_splice($event->data['items'], -1, 0, array(new MetadataMenuItem()));

    }


}
