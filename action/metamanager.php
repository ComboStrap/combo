<?php

use ComboStrap\Analytics;
use ComboStrap\DatabasePage;
use ComboStrap\Identity;
use ComboStrap\Iso8601Date;
use ComboStrap\LogUtility;
use ComboStrap\LowQualityPage;
use ComboStrap\MetadataMenuItem;
use ComboStrap\Page;
use ComboStrap\PageImage;
use ComboStrap\PluginUtility;
use ComboStrap\Publication;
use ComboStrap\Site;

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
    const VALUES_ATTRIBUTE = "values";
    const MUTABLE_ATTRIBUTE = "mutable";
    const NAME_ATTRIBUTE = "name";
    const DOMAIN_VALUES_ATTRIBUTE = "domain-values";
    const DATA_TYPE_ATTRIBUTE = "type"; //data type
    const DATETIME_TYPE_VALUE = "datetime";
    const PARAGRAPH_TYPE_VALUE = "paragraph";
    const BOOLEAN_TYPE_VALUE = "boolean";
    const LABEL_URL_ATTRIBUTE = "url";
    const TABULAR_TYPE_ATTRIBUTE = "tabular";
    const LABEL_ATTRIBUTE = "label";

    /**
     * The tabs attribute and value
     */
    const TAB_ATTRIBUTE = "tab";
    const COLUMNS_ATTRIBUTE = "columns";
    const TAB_TYPE_VALUE = "Page Type";
    const TAB_QUALITY_VALUE = "Quality";
    const TAB_PAGE_VALUE = "Page";
    const TAB_LANGUAGE_VALUE = "Language";
    const TAB_REPLICATION_VALUE = "Replication";
    const TAB_IMAGE_VALUE = "Image";

    /**
     * The canonical for the metadata page
     */
    const METADATA_CANONICAL = "metadata";
    /**
     * The canonical for page type
     */
    const PAGE_TYPE_CANONICAL = "page:type";



    public function register(Doku_Event_Handler $controller)
    {

        /**
         * The ajax api to return data
         */
        $controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE', $this, '_ajax_call');

        /**
         * Add a icon in the page tools menu
         * https://www.dokuwiki.org/devel:event:menu_items_assembly
         */
        $controller->register_hook('MENU_ITEMS_ASSEMBLY', 'AFTER', $this, 'handle_rail_bar');
    }

    /**
     * Handle Metadata HTTP ajax requests
     * @param $event Doku_Event
     *
     *
     * https://www.dokuwiki.org/devel:plugin_programming_tips#handle_json_ajax_request
     *
     * CSRF checks are only for logged in users
     * This is public ({@link getSecurityToken()}
     */
    function _ajax_call(Doku_Event &$event): void
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
        $id = $_GET["id"];

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
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        switch ($requestMethod) {
            case 'POST':

                $jsonString = $_POST["json"];
//                if (empty($jsonString)) {
//                    LogUtility::log2file("The json object is missing", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
//                    header("Status: 400");
//                    return;
//                }
//
//                $jsonArray = \ComboStrap\Json::createFromString($jsonString)->toArray();
//                if ($jsonArray === null) {
//                    header("Status: 400");
//                    LogUtility::log2file("The json received is not conform ($jsonString)", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
//                    return;
//                }
//
//                /**
//                 * Upsert metadata after the page content modification
//                 * to not trigger another modification because of the replication date
//                 */
//                $page->upsertMetadata($jsonArray);

                header("Status: 200");

                return;
            case "GET":

                $metas = [];

                /**
                 * The old viewer meta panel
                 */
                $type = $_GET["type"];
                if ($type === "viewer") {
                    if (!Identity::isManager()) {
                        header("Status: 401");
                        $metas = ["message" => "Not Authorized (managers only)"];
                    } else {
                        $metadata = p_read_metadata($id);
                        $metasPersistent = $metadata['persistent'];
                        $metasCurrent = $metadata['current'];
                        /**
                         * toc is in the current meta data's, we place it then as high priority
                         * if it does not work, we need to implement a recursive merge
                         * because the {@link array_merge_recursive()} just add the values
                         * (we got them the same value twice)
                         */
                        $metas = array_merge($metasPersistent, $metasCurrent);
                        ksort($metas);
                        header("Status: 200");
                    }
                    header('Content-type: application/json');
                    echo json_encode($metas);
                    return;
                }

                /**
                 * The manager
                 */
                // Canonical
                $metasCanonical[self::VALUE_ATTRIBUTE] = $page->getCanonical();
                $metasCanonical[self::DEFAULT_VALUE_ATTRIBUTE] = $page->getDefaultCanonical();
                $metasCanonical[self::MUTABLE_ATTRIBUTE] = true;
                $metasCanonical[self::TAB_ATTRIBUTE] = self::TAB_PAGE_VALUE;
                $metasCanonical[self::LABEL_URL_ATTRIBUTE] = PluginUtility::getDocumentationUrl(
                    Analytics::CANONICAL,
                    "Canonical",
                    false,
                    "The canonical (also known as slug) creates a permanent link."
                );
                $metasCanonical[self::NAME_ATTRIBUTE] = Analytics::CANONICAL;
                $metas[] = $metasCanonical;

                // Name
                $metasName[self::VALUE_ATTRIBUTE] = $page->getPageName();
                $metasName[self::DEFAULT_VALUE_ATTRIBUTE] = $page->getDefaultPageName();
                $metasName[self::MUTABLE_ATTRIBUTE] = true;
                $metasName[self::TAB_ATTRIBUTE] = self::TAB_PAGE_VALUE;
                $metasName[self::LABEL_URL_ATTRIBUTE] = PluginUtility::getDocumentationUrl(
                    Analytics::NAME,
                    "Name",
                    false,
                    "The page name is the shortest page description. It should be at maximum a couple of words long. It's used mainly in navigation components."
                );
                $metasName[self::NAME_ATTRIBUTE] = Analytics::NAME;
                $metas[] = $metasName;

                // Title (title of a component is an heading)
                $metasTitle[self::VALUE_ATTRIBUTE] = $page->getTitle();
                $metasTitle[self::DEFAULT_VALUE_ATTRIBUTE] = $page->getDefaultTitle();
                $metasTitle[self::MUTABLE_ATTRIBUTE] = true;
                $metasTitle[self::TAB_ATTRIBUTE] = self::TAB_PAGE_VALUE;
                $metasTitle[self::LABEL_URL_ATTRIBUTE] = PluginUtility::getDocumentationUrl(
                    Analytics::TITLE,
                    "Title",
                    false,
                    "The page title is a description advertised to external application such as search engine and browser."
                );
                $metasTitle[self::NAME_ATTRIBUTE] = Analytics::TITLE;
                $metas[] = $metasTitle;

                // H1
                $metasH1Value[self::VALUE_ATTRIBUTE] = $page->getH1();
                $metasH1Value[self::DEFAULT_VALUE_ATTRIBUTE] = $page->getDefaultH1();
                $metasH1Value[self::MUTABLE_ATTRIBUTE] = true;
                $metasH1Value[self::TAB_ATTRIBUTE] = self::TAB_PAGE_VALUE;
                $metasH1Value[self::LABEL_URL_ATTRIBUTE] = PluginUtility::getDocumentationUrl(
                    Analytics::H1,
                    "H1",
                    false,
                    "The heading 1 (or H1) is the first heading of your page. It may be used in template to make a difference with the title."
                );
                $metasH1Value[self::NAME_ATTRIBUTE] = Analytics::H1;
                $metas[] = $metasH1Value;

                // Description
                $metasDescription[self::VALUE_ATTRIBUTE] = $page->getDescription();
                $metasDescription[self::DEFAULT_VALUE_ATTRIBUTE] = $page->getDescriptionOrElseDokuWiki();
                $metasDescription[self::MUTABLE_ATTRIBUTE] = true;
                $metasDescription[self::DATA_TYPE_ATTRIBUTE] = self::PARAGRAPH_TYPE_VALUE;
                $metasDescription[self::TAB_ATTRIBUTE] = self::TAB_PAGE_VALUE;
                $metasDescription[self::LABEL_URL_ATTRIBUTE] = PluginUtility::getDocumentationUrl(
                    Analytics::DESCRIPTION,
                    "Description",
                    false,
                    "The description is a paragraph that describe your page. It's advertised to external application and used in templating."
                );
                $metasDescription[self::NAME_ATTRIBUTE] = Analytics::DESCRIPTION;
                $metas[] = $metasDescription;

                // Layout
                $layout[self::VALUE_ATTRIBUTE] = $page->getLayout();
                $layout[self::MUTABLE_ATTRIBUTE] = true;
                $layout[self::DEFAULT_VALUE_ATTRIBUTE] = $page->getDefaultLayout();
                $layout[self::DOMAIN_VALUES_ATTRIBUTE] = $page->getLayoutValues();
                $layout[self::TAB_ATTRIBUTE] = self::TAB_PAGE_VALUE;
                $layout[self::LABEL_URL_ATTRIBUTE] = PluginUtility::getDocumentationUrl(
                    Page::LAYOUT_PROPERTY,
                    "Layout",
                    false,
                    "A layout chooses the layout of your page (such as the slots and placement of the main content)"
                );
                $layout[self::NAME_ATTRIBUTE] = Page::LAYOUT_PROPERTY;
                $metas[] = $layout;


                // Modified Date
                $modifiedDate[self::VALUE_ATTRIBUTE] = $page->getModifiedDateAsString();
                $modifiedDate[self::MUTABLE_ATTRIBUTE] = false;
                $modifiedDate[self::DATA_TYPE_ATTRIBUTE] = self::DATETIME_TYPE_VALUE;
                $modifiedDate[self::TAB_ATTRIBUTE] = self::TAB_PAGE_VALUE;
                $modifiedDate[self::LABEL_URL_ATTRIBUTE] = PluginUtility::getDocumentationUrl(
                    self::METADATA_CANONICAL,
                    "Modification Date",
                    false,
                    "The last modification date of the page"
                );
                $modifiedDate[self::NAME_ATTRIBUTE] = Analytics::DATE_MODIFIED;
                $metas[] = $modifiedDate;

                // Created Date
                $dateCreated[self::VALUE_ATTRIBUTE] = $page->getCreatedDateAsString();
                $dateCreated[self::MUTABLE_ATTRIBUTE] = false;
                $dateCreated[self::DATA_TYPE_ATTRIBUTE] = self::DATETIME_TYPE_VALUE;
                $dateCreated[self::TAB_ATTRIBUTE] = self::TAB_PAGE_VALUE;
                $dateCreated[self::LABEL_URL_ATTRIBUTE] = PluginUtility::getDocumentationUrl(
                    self::METADATA_CANONICAL,
                    "Creation Date",
                    false,
                    "The creation date of the page"
                );
                $dateCreated[self::NAME_ATTRIBUTE] = Analytics::DATE_CREATED;
                $metas[] = $dateCreated;


                // Path
                $metasPath[self::VALUE_ATTRIBUTE] = $page->getPath();
                $metasPath[self::MUTABLE_ATTRIBUTE] = false;
                $metasPath[self::TAB_ATTRIBUTE] = self::TAB_PAGE_VALUE;
                $metasPath[self::LABEL_URL_ATTRIBUTE] = PluginUtility::getDocumentationUrl(
                    Analytics::PATH,
                    "Path",
                    false,
                    "The path of the page on the file system (in wiki format with the colon `:` as path separator)"
                );
                $metasPath[self::NAME_ATTRIBUTE] = Analytics::PATH;
                $metas[] = $metasPath;

                // Image
                $pageImages = [];
                $pageImages[self::DATA_TYPE_ATTRIBUTE] = self::TABULAR_TYPE_ATTRIBUTE;
                $pageImages[self::COLUMNS_ATTRIBUTE] = [];
                $pageImageTag[self::MUTABLE_ATTRIBUTE] = true;
                $pageImageTag[self::DOMAIN_VALUES_ATTRIBUTE] = PageImage::getTagValues();
                $pageImageTag[self::LABEL_ATTRIBUTE] = "Image Tag";
                $pageImageTag[self::LABEL_URL_ATTRIBUTE] = PluginUtility::getDocumentationUrl(
                    syntax_plugin_combo_pageimage::CANONICAL,
                    "Image Tag",
                    false,
                    "The tag of the image"
                );
                $metadataImageLabelName = "image-tag";
                $pageImageTag[self::NAME_ATTRIBUTE] = $metadataImageLabelName;
                $pageImages[self::COLUMNS_ATTRIBUTE][] = $pageImageTag;
                $pageImagePath[self::MUTABLE_ATTRIBUTE] = true;
                $pageImagePath[self::LABEL_URL_ATTRIBUTE] = PluginUtility::getDocumentationUrl(
                    syntax_plugin_combo_pageimage::CANONICAL,
                    "Image Path",
                    false,
                    "The path of the image"
                );
                $metadataImagePathName = "image-path";
                $pageImagePath[self::NAME_ATTRIBUTE] = $metadataImagePathName;
                $pageImages[self::COLUMNS_ATTRIBUTE][] = $pageImagePath;
                $pageImages[self::TAB_ATTRIBUTE] = self::TAB_IMAGE_VALUE;
                $pageImages[self::LABEL_URL_ATTRIBUTE] = PluginUtility::getDocumentationUrl(
                    syntax_plugin_combo_pageimage::CANONICAL,
                    "Page Images",
                    false,
                    "The illustrative images of the page"
                );


                /**
                 * @var PageImage $pageImage
                 */
                $pageImagesObjects = $page->getPageImagesObject();
                $pageImageDefault = $page->getDefaultPageImageObject();
                $pageImageRows = [];
                for ($i = 0; $i < 5; $i++) {

                    $pageImage = null;
                    $pageImageRow = [];
                    if (isset($pageImagesObjects[$i])) {
                        $pageImage = $pageImagesObjects[$i];
                    }
                    /**
                     * Label
                     */
                    $pageImageTag = [];
                    if ($pageImage != null) {
                        $pageImageTag[self::VALUE_ATTRIBUTE] = $pageImage->getTag();
                    }
                    $pageImageTag[self::DEFAULT_VALUE_ATTRIBUTE] = PageImage::getDefaultTag();
                    if ($i == 0 && $pageImageDefault !== null) {
                        $pageImageTag[self::DEFAULT_VALUE_ATTRIBUTE] = $pageImageDefault->getDefaultTag();
                    }
                    $pageImageRow[] = $pageImageTag;

                    /**
                     * Image
                     */
                    $pageImagePath = [];
                    if ($pageImage != null) {
                        $pageImagePath[self::VALUE_ATTRIBUTE] = $pageImage->getImage()->getPath();
                    }
                    if ($i == 0 && $pageImageDefault !== null) {
                        $pageImagePath[self::DEFAULT_VALUE_ATTRIBUTE] = $pageImageDefault->getImage()->getPath();
                    }
                    $pageImageRow[] = $pageImagePath;

                    /**
                     * Add the row
                     */
                    $pageImageRows[] = $pageImageRow;

                }
                $pageImages[self::VALUES_ATTRIBUTE] = $pageImageRows;
                $metas[] = $pageImages;


                // Page Type
                $metasPageType[self::VALUE_ATTRIBUTE] = $page->getType();
                $metasPageType[self::DEFAULT_VALUE_ATTRIBUTE] = $page->getDefaultType();
                $metasPageType[self::MUTABLE_ATTRIBUTE] = true;
                $metasPageType[self::DOMAIN_VALUES_ATTRIBUTE] = $page->getTypeValues();
                $metasPageType[self::TAB_ATTRIBUTE] = self::TAB_TYPE_VALUE;
                $metasPageType[self::LABEL_URL_ATTRIBUTE] = PluginUtility::getDocumentationUrl(
                    self::PAGE_TYPE_CANONICAL,
                    "Page Type",
                    false,
                    "The type of page"
                );
                $metasPageType[self::NAME_ATTRIBUTE] = Page::TYPE_META_PROPERTY;
                $metas[] = $metasPageType;


                // Published Date
                $publishedDate[self::VALUE_ATTRIBUTE] = $page->getPublishedTimeAsString();
                $publishedDate[self::DEFAULT_VALUE_ATTRIBUTE] = $page->getCreatedDateAsString();
                $publishedDate[self::MUTABLE_ATTRIBUTE] = true;
                $publishedDate[self::DATA_TYPE_ATTRIBUTE] = self::DATETIME_TYPE_VALUE;
                $publishedDate[self::TAB_ATTRIBUTE] = self::TAB_TYPE_VALUE;
                $publishedDate[self::LABEL_URL_ATTRIBUTE] = PluginUtility::getDocumentationUrl(
                    self::PAGE_TYPE_CANONICAL,
                    "Publication Date",
                    false,
                    "The publication date"
                );
                $publishedDate[self::NAME_ATTRIBUTE] = Publication::DATE_PUBLISHED;
                $metas[] = $publishedDate;

                // Start Date
                $startDate[self::VALUE_ATTRIBUTE] = $page->getStartDate();
                $startDate[self::MUTABLE_ATTRIBUTE] = true;
                $startDate[self::DATA_TYPE_ATTRIBUTE] = self::DATETIME_TYPE_VALUE;
                $startDate[self::TAB_ATTRIBUTE] = self::TAB_TYPE_VALUE;
                $startDate[self::LABEL_URL_ATTRIBUTE] = PluginUtility::getDocumentationUrl(
                    Page::EVENT_TYPE,
                    "Start Date",
                    false,
                    "The start date of an event"
                );
                $startDate[self::NAME_ATTRIBUTE] = Analytics::DATE_START;
                $metas[] = $startDate;

                // End Date
                $endDate[self::VALUE_ATTRIBUTE] = $page->getEndDate();
                $endDate[self::MUTABLE_ATTRIBUTE] = true;
                $endDate[self::DATA_TYPE_ATTRIBUTE] = self::DATETIME_TYPE_VALUE;
                $endDate[self::TAB_ATTRIBUTE] = self::TAB_TYPE_VALUE;
                $endDate[self::LABEL_URL_ATTRIBUTE] = PluginUtility::getDocumentationUrl(
                    Page::EVENT_TYPE,
                    "End Date",
                    false,
                    "The end date of an event"
                );
                $endDate[self::NAME_ATTRIBUTE] = Analytics::DATE_END;
                $metas[] = $endDate;


                // ld-json
                $ldJson[self::VALUE_ATTRIBUTE] = $page->getLdJson();
                $ldJson[self::MUTABLE_ATTRIBUTE] = true;
                $ldJson[self::DEFAULT_VALUE_ATTRIBUTE] = "Enter a json-ld value";
                $ldJson[self::DATA_TYPE_ATTRIBUTE] = self::PARAGRAPH_TYPE_VALUE;
                $ldJson[self::TAB_ATTRIBUTE] = self::TAB_TYPE_VALUE;
                $ldJson[self::LABEL_URL_ATTRIBUTE] = PluginUtility::getDocumentationUrl(
                    action_plugin_combo_metagoogle::CANONICAL,
                    "Json-ld",
                    false,
                    "Advanced Page metadata definition with the json-ld format"
                );
                $ldJson[self::NAME_ATTRIBUTE] = action_plugin_combo_metagoogle::JSON_LD_META_PROPERTY;
                $metas[] = $ldJson;

                // Is low quality page
                $isLowQualityPage[self::VALUE_ATTRIBUTE] = $page->getLowQualityIndicator();
                $isLowQualityPage[self::MUTABLE_ATTRIBUTE] = true;
                $isLowQualityPage[self::DEFAULT_VALUE_ATTRIBUTE] = $page->getDefaultLowQualityIndicator();
                $isLowQualityPage[self::DATA_TYPE_ATTRIBUTE] = self::BOOLEAN_TYPE_VALUE;
                $isLowQualityPage[self::TAB_ATTRIBUTE] = self::TAB_QUALITY_VALUE;
                $isLowQualityPage[self::LABEL_URL_ATTRIBUTE] = PluginUtility::getDocumentationUrl(
                    LowQualityPage::LOW_QUALITY_PAGE_CANONICAL,
                    "Low Quality Page Indicator",
                    false,
                    "If checked, the page will be tagged as a low quality page"
                );
                $isLowQualityPage[self::NAME_ATTRIBUTE] = Page::LOW_QUALITY_PAGE_INDICATOR;
                $metas[] = $isLowQualityPage;

                // Quality Monitoring
                $isQualityMonitoringOn[self::VALUE_ATTRIBUTE] = $page->isQualityMonitored();
                $isQualityMonitoringOn[self::MUTABLE_ATTRIBUTE] = true;
                $isQualityMonitoringOn[self::DEFAULT_VALUE_ATTRIBUTE] = !$this->getConf(action_plugin_combo_qualitymessage::CONF_DISABLE_QUALITY_MONITORING);
                $isQualityMonitoringOn[self::DATA_TYPE_ATTRIBUTE] = self::BOOLEAN_TYPE_VALUE;
                $isQualityMonitoringOn[self::TAB_ATTRIBUTE] = self::TAB_QUALITY_VALUE;
                $isQualityMonitoringOn[self::LABEL_URL_ATTRIBUTE] = PluginUtility::getDocumentationUrl(
                    "quality:dynamic_monitoring",
                    "Dynamic Quality Monitoring",
                    false,
                    "If checked, the quality message will be shown for the page."
                );
                $isQualityMonitoringOn[self::NAME_ATTRIBUTE] = action_plugin_combo_qualitymessage::DISABLE_INDICATOR;
                $metas[] = $isQualityMonitoringOn;


                // Locale
                $locale[self::VALUE_ATTRIBUTE] = $page->getLocale();
                $locale[self::MUTABLE_ATTRIBUTE] = false;
                $locale[self::DEFAULT_VALUE_ATTRIBUTE] = Site::getLocale();
                $locale[self::TAB_ATTRIBUTE] = self::TAB_LANGUAGE_VALUE;
                $locale[self::LABEL_URL_ATTRIBUTE] = PluginUtility::getDocumentationUrl(
                    "locale",
                    "Locale",
                    false,
                    "The locale define the language and the formatting of numbers and time for the page. It's generated from the language and region metadata."
                );
                $locale[self::NAME_ATTRIBUTE] = "locale";
                $metas[] = $locale;

                // Lang
                $lang[self::VALUE_ATTRIBUTE] = $page->getLang();
                $lang[self::MUTABLE_ATTRIBUTE] = true;
                $lang[self::DEFAULT_VALUE_ATTRIBUTE] = Site::getLang();
                $lang[self::TAB_ATTRIBUTE] = self::TAB_LANGUAGE_VALUE;
                $lang[self::LABEL_URL_ATTRIBUTE] = PluginUtility::getDocumentationUrl(
                    Page::LANG_META_PROPERTY,
                    "Language",
                    false,
                    "The language of the page"
                );
                $lang[self::NAME_ATTRIBUTE] = Page::LANG_META_PROPERTY;
                $metas[] = $lang;

                // Country
                $region[self::VALUE_ATTRIBUTE] = $page->getLocaleRegion();
                $region[self::MUTABLE_ATTRIBUTE] = true;
                $region[self::DEFAULT_VALUE_ATTRIBUTE] = Site::getLanguageRegion();
                $region[self::TAB_ATTRIBUTE] = self::TAB_LANGUAGE_VALUE;
                $region[self::LABEL_URL_ATTRIBUTE] = PluginUtility::getDocumentationUrl(
                    Page::REGION_META_PROPERTY,
                    "Region",
                    false,
                    "The region of the language"
                );
                $region[self::NAME_ATTRIBUTE] = Page::REGION_META_PROPERTY;
                $metas[] = $region;

                // database replication Date
                $replicationDateValue = $page->getDatabasePage()->getReplicationDate();
                $replicationDate[self::VALUE_ATTRIBUTE] = $replicationDateValue != null ? $replicationDateValue->format(Iso8601Date::getFormat()) : null;
                $replicationDate[self::MUTABLE_ATTRIBUTE] = false;
                $replicationDate[self::DATA_TYPE_ATTRIBUTE] = self::DATETIME_TYPE_VALUE;
                $replicationDate[self::TAB_ATTRIBUTE] = self::TAB_REPLICATION_VALUE;
                $replicationDate[self::LABEL_URL_ATTRIBUTE] = PluginUtility::getDocumentationUrl(
                    DatabasePage::REPLICATION_CANONICAL,
                    "Replication Date",
                    false,
                    "The last date of database replication"
                );
                $replicationDate[self::NAME_ATTRIBUTE] = DatabasePage::DATE_REPLICATION;
                $metas[] = $replicationDate;

                // UUID
                $metasUuid[self::VALUE_ATTRIBUTE] = $page->getUuid();
                $metasUuid[self::MUTABLE_ATTRIBUTE] = false;
                $metasUuid[self::TAB_ATTRIBUTE] = self::TAB_REPLICATION_VALUE;
                $metasUuid[self::LABEL_URL_ATTRIBUTE] = PluginUtility::getDocumentationUrl(
                    Page::UUID_ATTRIBUTE,
                    "UUID",
                    false,
                    "UUID is the Universally Unique IDentifier of the page used in replication (between database or installation)"
                );
                $metasUuid[self::NAME_ATTRIBUTE] = Page::UUID_ATTRIBUTE;
                $metas[] = $metasUuid;

                header('Content-type: application/json');
                header("Status: 200");
                echo json_encode($metas);
                return;


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

    function updateFrontmatter($page, $jsonArray)
    {
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
    }


}
