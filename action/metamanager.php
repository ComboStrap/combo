<?php

use ComboStrap\LogUtility;
use ComboStrap\Page;

if (!defined('DOKU_INC')) die();

/**
 *
 * Save metadata that were send by ajax
 */
class action_plugin_combo_metamanager extends DokuWiki_Action_Plugin
{


    const CALL_ID = "combo-meta-manager";
    const JSON_PARAM = "json";
    const CANONICAL = "meta-manager";

    public function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE', $this, '_ajax_call');
    }

    /**
     * handle ajax requests
     * @param $event Doku_Event
     *
     * {@link html_show()}
     *
     * https://www.dokuwiki.org/devel:plugin_programming_tips#handle_json_ajax_request
     *
     * CSRF checks are only for logged in users
     * This is public ({@link getSecurityToken()}
     */
    function _ajax_call(&$event)
    {

        if ($event->data !== self::CALL_ID) {
            return;
        }
        //no other ajax call handlers needed
        $event->stopPropagation();
        $event->preventDefault();

        $id = $_POST["id"];
        if (empty($id)) {
            LogUtility::log2file("The page ($id) is mandatory", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
            header("Status: 400");
            return;
        }
        $page = Page::createPageFromId($id);
        if (!$page->exists()) {
            LogUtility::log2file("The page ($id) does not exist", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
            header("Status: 404");
            return;
        }

        /**
         * Security
         */
        if (!$page->canBeUpdatedByCurrentUser()) {
            LogUtility::log2file("Not authorized ($id)", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
            header("Status: 401");
            return;
        }

        $jsonString = $_POST["json"];
        if (empty($jsonString)) {
            LogUtility::log2file("The json object is missing", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
            header("Status: 400");
            return;
        }


        $jsonArray = \ComboStrap\Json::createFromString($jsonString)->toArray();
        if ($jsonArray === null) {
            header("Status: 400");
            LogUtility::log2file("The json received is not conform ($jsonString)", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
            return;
        }

        $page->upsertMetadata($jsonArray);

        header("Status: 200");


    }

}
