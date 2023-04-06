<?php

use ComboStrap\ExceptionNotFound;
use ComboStrap\ExecutionContext;
use ComboStrap\FileSystems;
use ComboStrap\LdJson;
use ComboStrap\LogUtility;
use ComboStrap\MarkupPath;
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


        try {
            $templateForWebPage = ExecutionContext::getActualOrCreateFromEnv()->getExecutingPageTemplate();
        } catch (ExceptionNotFound $e) {
            return;
        }

        if (!$templateForWebPage->isSocial()) {
            return;
        }


        try {
            $requestedPath = $templateForWebPage->getRequestedContextPath();
        } catch (ExceptionNotFound $e) {
            LogUtility::internalError("Because the template is social, it should be at minima a path request");
            return;
        }


        $page = MarkupPath::createPageFromPathObject($requestedPath);
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
