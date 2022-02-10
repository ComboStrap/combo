<?php

use ComboStrap\LdJson;
use ComboStrap\Page;
use ComboStrap\PluginUtility;


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


        if(!PluginUtility::isRenderingRequestedPageProcess()){
            return;
        }

        $page = Page::createPageFromRequestedPage();
        $ldJson = LdJson::createForPage($page)
            ->getLdJsonMergedWithDefault();

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
