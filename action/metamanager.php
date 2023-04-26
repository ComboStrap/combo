<?php

require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');

use ComboStrap\Api\MetaManagerHandler;
use ComboStrap\DataType;
use ComboStrap\ExceptionCompile;
use ComboStrap\ExecutionContext;
use ComboStrap\Meta\Form\FormMeta;
use ComboStrap\Meta\Form\FormMetaField;
use ComboStrap\HttpResponseStatus;
use ComboStrap\Identity;
use ComboStrap\Json;
use ComboStrap\LowQualityPageOverwrite;
use ComboStrap\MarkupPath;
use ComboStrap\Message;
use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Store\MetadataDokuWikiStore;
use ComboStrap\MetadataFormDataStore;
use ComboStrap\MetadataFrontmatterStore;
use ComboStrap\MetadataStoreTransfer;
use ComboStrap\MetaManagerForm;
use ComboStrap\MetaManagerMenuItem;
use ComboStrap\Mime;
use ComboStrap\PluginUtility;
use ComboStrap\QualityDynamicMonitoringOverwrite;

if (!defined('DOKU_INC')) die();

/**
 *
 *
 */
class action_plugin_combo_metamanager extends DokuWiki_Action_Plugin
{


    public function register(Doku_Event_Handler $controller)
    {

        /**
         * Add a icon in the page tools menu
         * https://www.dokuwiki.org/devel:event:menu_items_assembly
         */
        $controller->register_hook('MENU_ITEMS_ASSEMBLY', 'AFTER', $this, 'addMenuItem');
    }


    public function addMenuItem(Doku_Event $event, $param)
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
        $exists = $INFO['exists'] ?? null;
        if (!$exists) {
            return;
        }
        array_splice($event->data['items'], -1, 0, array(new MetaManagerMenuItem()));

    }


}
