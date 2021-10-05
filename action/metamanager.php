<?php

use ComboStrap\LogUtility;
use ComboStrap\Page;
use ComboStrap\PluginUtility;

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

        /**
         * Shared check between post and get HTTP method
         */
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        if ($requestMethod==="POST") {
            $id = $_POST["id"];
        } else {
            $id = $_GET["id"];
        }
        if (empty($id)) {
            LogUtility::log2file("The page id is empty", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
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

        /**
         * Functional code
         */
        switch ($requestMethod) {
            case 'POST':

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

                /**
                 * Page modification is any
                 */
                $content = $page->getContent();
                $pattern = syntax_plugin_combo_frontmatter::PATTERN;
                $split = preg_split("/($pattern)/ms", $content, 2, PREG_SPLIT_DELIM_CAPTURE);

                /**
                 * The split normally returns an array
                 * where the first element is empty followed by the frontmatter
                 */
                $emptyString = array_shift($split);
                if (!empty($emptyString)) {
                    return;
                }

                $frontMatter = array_shift($split);
                $frontMatterStartTag = syntax_plugin_combo_frontmatter::START_TAG;
                if (!(strpos($frontMatter, $frontMatterStartTag) === 0)) {
                    return;
                }
                $frontMatterMetadata = syntax_plugin_combo_frontmatter::frontMatterMatchToAssociativeArray($frontMatter);
                $frontMatterMetadata = array_merge($frontMatterMetadata, $jsonArray);
                $frontMatterJsonString = json_encode($frontMatterMetadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

                /**
                 * Building the document again
                 */
                $restDocument = "";
                while (($element = array_shift($split)) != null) {
                    $restDocument .= $element;
                }

                /**
                 * Build the new document
                 */
                $frontMatterEndTag = syntax_plugin_combo_frontmatter::END_TAG;
                $newPageContent = <<<EOF
$frontMatterStartTag
$frontMatterJsonString
$frontMatterEndTag$restDocument
EOF;

                $page->upsertContent($newPageContent, "Metadata manager upsert");
                return;
            case "GET":
                header('Content-type: application/json');
                header("Status: 200");
                $metas = $page->getMetadataForRendering();
                echo json_encode($metas);
                return;


        }

    }

}
