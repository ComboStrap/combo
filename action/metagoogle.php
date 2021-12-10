<?php

use ComboStrap\LdJson;
use ComboStrap\Page;


require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');


class action_plugin_combo_metagoogle extends DokuWiki_Action_Plugin
{


    const CANONICAL = "google";
    const PUBLISHER = "publisher";

    function __construct()
    {
        // enable direct access to language strings
        // ie $this->lang
        $this->setupLocale();
    }

    public function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'metaGoogleProcessing', array());
    }

    /**
     *
     * @param $event
     */
    function metaGoogleProcessing($event)
    {


        global $ID;
        if (empty($ID)) {
            // $ID is null
            // case on "/lib/exe/mediamanager.php"
            return;
        }
        $page = Page::createPageFromId($ID);
        if (!$page->exists()) {
            return;
        }

        /**
         * No metadata for bars
         */
        if ($page->isSlot()) {
            return;
        }

        $ldJson = LdJson::createForPage($page)
            ->getValueOrDefault();


        /**
         * Publish
         */
        if (!empty($ldJson)) {
            $event->data["script"][] = array(
                "type" => "application/ld+json",
                "_data" => $ldJson,
            );
        }
    }


}
