<?php

use ComboStrap\Analytics;
use ComboStrap\LogUtility;
use ComboStrap\Page;
use ComboStrap\PluginUtility;
use ComboStrap\Publication;

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

    /**
     * The JSON attribute for each parameter
     */
    const VALUE_ATTRIBUTE = "value";
    const DEFAULT_VALUE_ATTRIBUTE = "default";
    const MUTABLE_ATTRIBUTE = "mutable";
    const VALUES_ATTRIBUTE = "values";
    const TYPE_ATTRIBUTE = self::TAB_TYPE_VALUE;
    const DATETIME_TYPE_VALUE = "datetime";
    const PARAGRAPH_TYPE_VALUE = "paragraph";
    const BOOLEAN_TYPE_VALUE = "boolean";
    const TAB_ATTRIBUTE = "tab";
    const TAB_TYPE_VALUE = "type";
    const TAB_QUALITY_VALUE = "quality";
    const CANONICAL_ATTRIBUTE = "canonical";

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
        if ($requestMethod === "POST") {
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


                /**
                 * Page modification if any
                 */
                $content = $page->getContent();
                $frontMatterStartTag = syntax_plugin_combo_frontmatter::START_TAG;
                if (strpos($content, $frontMatterStartTag) === 0) {

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
                }


                /**
                 * Upsert metadata after the page content modification
                 * to not trigger another modification because of the replication date
                 */
                $page->upsertMetadata($jsonArray);

                header("Status: 200");

                return;
            case "GET":
                header('Content-type: application/json');
                header("Status: 200");
                $metas = [];


                // Canonical
                $metasCanonical[self::VALUE_ATTRIBUTE] = $page->getCanonical();
                $metasCanonical[self::DEFAULT_VALUE_ATTRIBUTE] = $page->getDefaultCanonical();
                $metasCanonical[self::MUTABLE_ATTRIBUTE] = true;
                $metasCanonical[self::TAB_ATTRIBUTE] = "page";
                $metasCanonical[self::CANONICAL_ATTRIBUTE] = Analytics::CANONICAL;
                $metas[Analytics::CANONICAL] = $metasCanonical;

                // Name
                $metasName[self::VALUE_ATTRIBUTE] = $page->getPageName();
                $metasName[self::DEFAULT_VALUE_ATTRIBUTE] = $page->getDefaultPageName();
                $metasName[self::MUTABLE_ATTRIBUTE] = true;
                $metasName[self::TAB_ATTRIBUTE] = "page";
                $metasName[self::CANONICAL_ATTRIBUTE] = Analytics::NAME;
                $metas[Analytics::NAME] = $metasName;

                // Title
                $metasTitle[self::VALUE_ATTRIBUTE] = $page->getTitle();
                $metasTitle[self::DEFAULT_VALUE_ATTRIBUTE] = $page->getDefaultTitle();
                $metasTitle[self::MUTABLE_ATTRIBUTE] = true;
                $metasTitle[self::TAB_ATTRIBUTE] = "page";
                $metasTitle[self::CANONICAL_ATTRIBUTE] = Analytics::TITLE; // title of a component is an heading
                $metas[Analytics::TITLE] = $metasTitle;

                // H1
                $metasH1Value[self::VALUE_ATTRIBUTE] = $page->getH1();
                $metasH1Value[self::DEFAULT_VALUE_ATTRIBUTE] = $page->getDefaultH1();
                $metasH1Value[self::MUTABLE_ATTRIBUTE] = true;
                $metasH1Value[self::TAB_ATTRIBUTE] = "page";
                $metasH1Value[self::CANONICAL_ATTRIBUTE] = Analytics::H1;
                $metas[Analytics::H1] = $metasH1Value;

                // Description
                $metasDescription[self::VALUE_ATTRIBUTE] = $page->getDescription();
                $metasDescription[self::DEFAULT_VALUE_ATTRIBUTE] = $page->getDescriptionOrElseDokuWiki();
                $metasDescription[self::MUTABLE_ATTRIBUTE] = true;
                $metasDescription[self::TYPE_ATTRIBUTE] = self::PARAGRAPH_TYPE_VALUE;
                $metasDescription[self::TAB_ATTRIBUTE] = "page";
                $metasDescription[self::CANONICAL_ATTRIBUTE] = Analytics::DESCRIPTION;
                $metas[Analytics::DESCRIPTION] = $metasDescription;

                // Layout
                $layout[self::VALUE_ATTRIBUTE] = $page->getLayout();
                $layout[self::MUTABLE_ATTRIBUTE] = true;
                $layout[self::DEFAULT_VALUE_ATTRIBUTE] = $page->getDefaultLayout();
                $layout[self::VALUES_ATTRIBUTE] = $page->getLayoutValues();
                $layout[self::TAB_ATTRIBUTE] = "page";
                $layout[self::CANONICAL_ATTRIBUTE] = Page::LAYOUT_PROPERTY;
                $metas[Page::LAYOUT_PROPERTY] = $layout;


                // Modified Date
                $modifiedCreated[self::VALUE_ATTRIBUTE] = $page->getModifiedDateAsString();
                $modifiedCreated[self::MUTABLE_ATTRIBUTE] = false;
                $modifiedCreated[self::TYPE_ATTRIBUTE] = self::DATETIME_TYPE_VALUE;
                $modifiedCreated[self::TAB_ATTRIBUTE] = "page";
                $modifiedCreated[self::CANONICAL_ATTRIBUTE] = Page::CANONICAL_VALUE;
                $metas[Analytics::DATE_MODIFIED] = $modifiedCreated;

                // Created Date
                $dateCreated[self::VALUE_ATTRIBUTE] = $page->getCreatedDateAsString();
                $dateCreated[self::MUTABLE_ATTRIBUTE] = false;
                $dateCreated[self::TYPE_ATTRIBUTE] = self::DATETIME_TYPE_VALUE;
                $dateCreated[self::TAB_ATTRIBUTE] = "page";
                $metas[Analytics::DATE_CREATED] = $dateCreated;


                // UUID
                $metasUuid[self::VALUE_ATTRIBUTE] = $page->getUuid();
                $metasUuid[self::MUTABLE_ATTRIBUTE] = false;
                $metasUuid[self::TAB_ATTRIBUTE] = "page";
                $metas[Page::UUID_ATTRIBUTE] = $metasUuid;

                // Path
                $metasPath[self::VALUE_ATTRIBUTE] = $page->getPath();
                $metasPath[self::MUTABLE_ATTRIBUTE] = false;
                $metasPath[self::TAB_ATTRIBUTE] = "page";
                $metas[Analytics::PATH] = $metasPath;

                // Page Type
                $metasPageType[self::VALUE_ATTRIBUTE] = $page->getType();
                $metasPageType[self::DEFAULT_VALUE_ATTRIBUTE] = $page->getDefaultType();
                $metasPageType[self::MUTABLE_ATTRIBUTE] = true;
                $metasPageType[self::VALUES_ATTRIBUTE] = $page->getTypeValues();
                $metasPageType[self::TAB_ATTRIBUTE] = self::TAB_TYPE_VALUE;
                $metas[Page::TYPE_META_PROPERTY] = $metasPageType;

                // Published Date
                $publishedDate[self::VALUE_ATTRIBUTE] = $page->getPublishedTimeAsString();
                $publishedDate[self::DEFAULT_VALUE_ATTRIBUTE] = $page->getCreatedDateAsString();
                $publishedDate[self::MUTABLE_ATTRIBUTE] = true;
                $publishedDate[self::TYPE_ATTRIBUTE] = self::DATETIME_TYPE_VALUE;
                $publishedDate[self::TAB_ATTRIBUTE] = self::TAB_TYPE_VALUE;
                $metas[Publication::DATE_PUBLISHED] = $publishedDate;

                // Start Date
                $startDate[self::VALUE_ATTRIBUTE] = $page->getStartDate();
                $startDate[self::MUTABLE_ATTRIBUTE] = true;
                $startDate[self::TYPE_ATTRIBUTE] = self::DATETIME_TYPE_VALUE;
                $startDate[self::TAB_ATTRIBUTE] = self::TAB_TYPE_VALUE;
                $metas[Analytics::DATE_START] = $startDate;

                // End Date
                $endDate[self::VALUE_ATTRIBUTE] = $page->getEndDate();
                $endDate[self::MUTABLE_ATTRIBUTE] = true;
                $endDate[self::TYPE_ATTRIBUTE] = self::DATETIME_TYPE_VALUE;
                $endDate[self::TAB_ATTRIBUTE] = self::TAB_TYPE_VALUE;
                $metas[Analytics::DATE_END] = $endDate;


                // Is low quality page
                $isLowQualityPage[self::VALUE_ATTRIBUTE] = $page->getLowQualityIndicator();
                $isLowQualityPage[self::MUTABLE_ATTRIBUTE] = true;
                $isLowQualityPage[self::DEFAULT_VALUE_ATTRIBUTE] = $page->getDefaultLowQualityIndicator();
                $isLowQualityPage[self::TYPE_ATTRIBUTE] = self::BOOLEAN_TYPE_VALUE;
                $isLowQualityPage[self::TAB_ATTRIBUTE] = self::TAB_QUALITY_VALUE;
                $metas[Page::LOW_QUALITY_PAGE_INDICATOR] = $isLowQualityPage;

                // Quality Monitoring
                $isQualityMonitoringOn[self::VALUE_ATTRIBUTE] = $page->isQualityMonitored();
                $isQualityMonitoringOn[self::MUTABLE_ATTRIBUTE] = true;
                $isQualityMonitoringOn[self::DEFAULT_VALUE_ATTRIBUTE] = !$this->getConf(action_plugin_combo_qualitymessage::CONF_DISABLE_QUALITY_MONITORING);
                $isQualityMonitoringOn[self::TYPE_ATTRIBUTE] = self::BOOLEAN_TYPE_VALUE;
                $isQualityMonitoringOn[self::TAB_ATTRIBUTE] = self::TAB_QUALITY_VALUE;
                $metas[action_plugin_combo_qualitymessage::DISABLE_INDICATOR] = $isQualityMonitoringOn;


                echo json_encode($metas);
                return;


        }

    }


}
