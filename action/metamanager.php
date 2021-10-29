<?php

use ComboStrap\Analytics;
use ComboStrap\DatabasePage;
use ComboStrap\FormField;
use ComboStrap\Http;
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

    const VALUES_ATTRIBUTE = "values";
    const NAME_ATTRIBUTE = "name";
    //data type
    const BOOLEAN_TYPE_VALUE = "boolean";
    const WIDTH_ATTRIBUTE = "width"; // width of the label / element

    const COLUMNS_ATTRIBUTE = "columns";
    const TAB_TYPE_VALUE = "type";
    const TAB_QUALITY_VALUE = "quality";
    const TAB_PAGE_VALUE = "page";
    const TAB_LANGUAGE_VALUE = "language";
    const TAB_INTEGRATION_VALUE = "integration";
    const TAB_IMAGE_VALUE = "image";
    const TAB_REDIRECTION_VALUE = "redirection";

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

                $fields = [];

                /**
                 * The old viewer meta panel
                 */
                $type = $_GET["type"];
                if ($type === "viewer") {
                    if (!Identity::isManager()) {
                        Http::setStatus(401);
                        $fields = ["message" => "Not Authorized (managers only)"];
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
                        $fields = array_merge($metasPersistent, $metasCurrent);
                        ksort($fields);
                        Http::setStatus(200);
                    }
                    header('Content-type: application/json');
                    echo json_encode($fields);
                    return;
                }

                /**
                 * The manager
                 */
                // Name
                $fields[] = FormField::create(Analytics::NAME)
                    ->setMutable(true)
                    ->setTab(self::TAB_PAGE_VALUE)
                    ->setLabel("Name")
                    ->setCanonical(Analytics::NAME)
                    ->setDescription("The page name is the shortest page description. It should be at maximum a couple of words long. It's used mainly in navigation components.")
                    ->addValue($page->getPageName(), $page->getDefaultPageName())
                    ->toAssociativeArray();

                // Title (title of a component is an heading)
                $fields[] = FormField::create(Analytics::TITLE)
                    ->setLabel("Title")
                    ->setDescription("The page title is a description advertised to external application such as search engine and browser.")
                    ->addValue($page->getTitle(), $page->getDefaultTitle())
                    ->setTab(self::TAB_PAGE_VALUE)
                    ->toAssociativeArray();

                // H1
                $fields[] = FormField::create(Analytics::H1)
                    ->addValue($page->getH1(), $page->getDefaultH1())
                    ->setTab(self::TAB_PAGE_VALUE)
                    ->setDescription("The heading 1 (or H1) is the first heading of your page. It may be used in template to make a difference with the title.")
                    ->toAssociativeArray();

                // Description
                $fields[] = FormField::create(Analytics::DESCRIPTION)
                    ->addValue($page->getDescription(), $page->getDescriptionOrElseDokuWiki())
                    ->setType(FormField::PARAGRAPH_TYPE_VALUE)
                    ->setTab(self::TAB_PAGE_VALUE)
                    ->setDescription("The description is a paragraph that describe your page. It's advertised to external application and used in templating.")
                    ->toAssociativeArray();

                // Canonical
                $fields[] = FormField::create(Analytics::CANONICAL)
                    ->addValue($page->getCanonical(),$page->getDefaultCanonical())
                    ->setTab(self::TAB_REDIRECTION_VALUE)
                    ->setDescription("The canonical creates a link that identifies your page uniquely by name")
                    ->toAssociativeArray();

                // Layout
                $fields[] = FormField::create(Page::LAYOUT_PROPERTY)
                    ->addValue( $page->getLayout(), $page->getDefaultLayout())
                    ->setDomainValues($page->getLayoutValues())
                    ->setTab(self::TAB_PAGE_VALUE)
                    ->setDescription("A layout chooses the layout of your page (such as the slots and placement of the main content)")
                    ->toAssociativeArray();


                // Modified Date
                $modifiedDate[FormField::VALUE_ATTRIBUTE] = $page->getModifiedDateAsString();
                $modifiedDate[FormField::MUTABLE_ATTRIBUTE] = false;
                $modifiedDate[FormField::DATA_TYPE_ATTRIBUTE] = FormField::DATETIME_TYPE_VALUE;
                $modifiedDate[FormField::TAB_ATTRIBUTE] = self::TAB_PAGE_VALUE;
                $modifiedDate[FormField::HYPERLINK_ATTRIBUTE] = PluginUtility::getDocumentationHyperLink(
                    self::METADATA_CANONICAL,
                    "Modification Date",
                    false,
                    "The last modification date of the page"
                );
                $modifiedDate[self::NAME_ATTRIBUTE] = Analytics::DATE_MODIFIED;
                $fields[] = $modifiedDate;

                // Created Date
                $dateCreated[FormField::VALUE_ATTRIBUTE] = $page->getCreatedDateAsString();
                $dateCreated[FormField::MUTABLE_ATTRIBUTE] = false;
                $dateCreated[FormField::DATA_TYPE_ATTRIBUTE] = FormField::DATETIME_TYPE_VALUE;
                $dateCreated[FormField::TAB_ATTRIBUTE] = self::TAB_PAGE_VALUE;
                $dateCreated[FormField::HYPERLINK_ATTRIBUTE] = PluginUtility::getDocumentationHyperLink(
                    self::METADATA_CANONICAL,
                    "Creation Date",
                    false,
                    "The creation date of the page"
                );
                $dateCreated[self::NAME_ATTRIBUTE] = Analytics::DATE_CREATED;
                $fields[] = $dateCreated;


                // Path
                $metasPath[FormField::VALUE_ATTRIBUTE] = $page->getPath();
                $metasPath[FormField::MUTABLE_ATTRIBUTE] = false;
                $metasPath[FormField::TAB_ATTRIBUTE] = self::TAB_PAGE_VALUE;
                $metasPath[FormField::HYPERLINK_ATTRIBUTE] = PluginUtility::getDocumentationHyperLink(
                    Analytics::PATH,
                    "Path",
                    false,
                    "The path of the page on the file system (in wiki format with the colon `:` as path separator)"
                );
                $metasPath[self::NAME_ATTRIBUTE] = Analytics::PATH;
                $fields[] = $metasPath;

                // Image
                $pageImages = [];
                $pageImages[FormField::DATA_TYPE_ATTRIBUTE] = FormField::TABULAR_TYPE_VALUE;
                $pageImages[self::COLUMNS_ATTRIBUTE] = [];

                //
                $pageImagePath[FormField::MUTABLE_ATTRIBUTE] = true;
                $pageImagePath[FormField::HYPERLINK_ATTRIBUTE] = PluginUtility::getDocumentationHyperLink(
                    syntax_plugin_combo_pageimage::CANONICAL,
                    "Path",
                    false,
                    "The path of the image"
                );
                $metadataImagePathName = "image-path";
                $pageImagePath[self::NAME_ATTRIBUTE] = $metadataImagePathName;
                $pageImages[self::COLUMNS_ATTRIBUTE][] = $pageImagePath;

                // Usage
                $metadataImageLabelName = "image-usage";
                $pageImageTag[self::WIDTH_ATTRIBUTE] = 8;
                $pageImageTag[self::NAME_ATTRIBUTE] = $metadataImageLabelName;
                $pageImageTag[FormField::MUTABLE_ATTRIBUTE] = true;
                $pageImageTag[FormField::DOMAIN_VALUES_ATTRIBUTE] = PageImage::getUsageValues();
                $pageImageTag[FormField::LABEL_ATTRIBUTE] = "Image Usage";
                $pageImageTag[self::WIDTH_ATTRIBUTE] = 4;
                $pageImageTag[FormField::DEFAULT_VALUE_ATTRIBUTE] = PageImage::getDefaultUsages();
                $pageImageTag[FormField::HYPERLINK_ATTRIBUTE] = PluginUtility::getDocumentationHyperLink(
                    syntax_plugin_combo_pageimage::CANONICAL,
                    "Usages",
                    false,
                    "The possible usages of the image"
                );
                $pageImages[self::COLUMNS_ATTRIBUTE][] = $pageImageTag;

                $pageImages[FormField::LABEL_ATTRIBUTE] = "Images";
                $pageImages[FormField::TAB_ATTRIBUTE] = self::TAB_IMAGE_VALUE;
                $pageImages[FormField::HYPERLINK_ATTRIBUTE] = PluginUtility::getDocumentationHyperLink(
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
                     * Image
                     */
                    $pageImagePath = [];
                    if ($pageImage != null) {
                        $pageImagePath[FormField::VALUE_ATTRIBUTE] = $pageImage->getImage()->getDokuPath()->getPath();
                    }
                    if ($i == 0 && $pageImageDefault !== null) {
                        $pageImagePath[FormField::DEFAULT_VALUE_ATTRIBUTE] = $pageImageDefault->getImage()->getDokuPath()->getPath();
                    }
                    $pageImageRow[] = $pageImagePath;

                    /**
                     * Label
                     */
                    $pageImageTag = [];
                    if ($pageImage != null) {
                        $pageImageTag[FormField::VALUE_ATTRIBUTE] = $pageImage->getUsages();
                    }
                    $pageImageTag[FormField::DEFAULT_VALUE_ATTRIBUTE] = PageImage::getDefaultUsages();
                    if ($i == 0 && $pageImageDefault !== null) {
                        $pageImageTag[FormField::DEFAULT_VALUE_ATTRIBUTE] = $pageImageDefault->getDefaultUsages();
                    }
                    $pageImageRow[] = $pageImageTag;

                    /**
                     * Add the row
                     */
                    $pageImageRows[] = $pageImageRow;

                }
                $pageImages[self::VALUES_ATTRIBUTE] = $pageImageRows;
                $fields[] = $pageImages;

                /**
                 * Aliases
                 */
                $aliasesValues = $page->getAliases();
                $aliasUrl = PluginUtility::getDocumentationHyperLink(
                    Page::ALIAS_ATTRIBUTE,
                    "Page Aliases",
                    false,
                    "Aliases that will redirect to this page."
                );
                $alias[self::NAME_ATTRIBUTE] = Page::ALIAS_ATTRIBUTE;
                $alias[FormField::TAB_ATTRIBUTE] = self::TAB_REDIRECTION_VALUE;
                $alias[FormField::DATA_TYPE_ATTRIBUTE] = FormField::TABULAR_TYPE_VALUE;

                $alias[FormField::MUTABLE_ATTRIBUTE] = true;
                $alias[FormField::HYPERLINK_ATTRIBUTE] = $aliasUrl;
                if (sizeof($aliasesValues) === 0) {
                    $aliasesValues = ["None"];
                }
                $alias[FormField::VALUE_ATTRIBUTE] = $aliasesValues;
                $fields[] = $alias;


                // Page Type
                $metasPageType[FormField::VALUE_ATTRIBUTE] = $page->getType();
                $metasPageType[FormField::DEFAULT_VALUE_ATTRIBUTE] = $page->getDefaultType();
                $metasPageType[FormField::MUTABLE_ATTRIBUTE] = true;
                $metasPageType[FormField::DOMAIN_VALUES_ATTRIBUTE] = $page->getTypeValues();
                $metasPageType[FormField::TAB_ATTRIBUTE] = self::TAB_TYPE_VALUE;
                $metasPageType[FormField::HYPERLINK_ATTRIBUTE] = PluginUtility::getDocumentationHyperLink(
                    self::PAGE_TYPE_CANONICAL,
                    "Page Type",
                    false,
                    "The type of page"
                );
                $metasPageType[self::NAME_ATTRIBUTE] = Page::TYPE_META_PROPERTY;
                $fields[] = $metasPageType;


                // Published Date
                $publishedDate[FormField::VALUE_ATTRIBUTE] = $page->getPublishedTimeAsString();
                $publishedDate[FormField::DEFAULT_VALUE_ATTRIBUTE] = $page->getCreatedDateAsString();
                $publishedDate[FormField::MUTABLE_ATTRIBUTE] = true;
                $publishedDate[FormField::DATA_TYPE_ATTRIBUTE] = FormField::DATETIME_TYPE_VALUE;
                $publishedDate[FormField::TAB_ATTRIBUTE] = self::TAB_TYPE_VALUE;
                $publishedDate[FormField::HYPERLINK_ATTRIBUTE] = PluginUtility::getDocumentationHyperLink(
                    self::PAGE_TYPE_CANONICAL,
                    "Publication Date",
                    false,
                    "The publication date"
                );
                $publishedDate[self::NAME_ATTRIBUTE] = Publication::DATE_PUBLISHED;
                $fields[] = $publishedDate;

                // Start Date
                $startDate[FormField::VALUE_ATTRIBUTE] = $page->getStartDate();
                $startDate[FormField::MUTABLE_ATTRIBUTE] = true;
                $startDate[FormField::DATA_TYPE_ATTRIBUTE] = FormField::DATETIME_TYPE_VALUE;
                $startDate[FormField::TAB_ATTRIBUTE] = self::TAB_TYPE_VALUE;
                $startDate[FormField::HYPERLINK_ATTRIBUTE] = PluginUtility::getDocumentationHyperLink(
                    Page::EVENT_TYPE,
                    "Start Date",
                    false,
                    "The start date of an event"
                );
                $startDate[self::NAME_ATTRIBUTE] = Analytics::DATE_START;
                $fields[] = $startDate;

                // End Date
                $endDate[FormField::VALUE_ATTRIBUTE] = $page->getEndDate();
                $endDate[FormField::MUTABLE_ATTRIBUTE] = true;
                $endDate[FormField::DATA_TYPE_ATTRIBUTE] = FormField::DATETIME_TYPE_VALUE;
                $endDate[FormField::TAB_ATTRIBUTE] = self::TAB_TYPE_VALUE;
                $endDate[FormField::HYPERLINK_ATTRIBUTE] = PluginUtility::getDocumentationHyperLink(
                    Page::EVENT_TYPE,
                    "End Date",
                    false,
                    "The end date of an event"
                );
                $endDate[self::NAME_ATTRIBUTE] = Analytics::DATE_END;
                $fields[] = $endDate;


                // ld-json
                $ldJson[FormField::VALUE_ATTRIBUTE] = $page->getLdJson();
                $ldJson[FormField::MUTABLE_ATTRIBUTE] = true;
                $ldJson[FormField::DEFAULT_VALUE_ATTRIBUTE] = "Enter a json-ld value";
                $ldJson[FormField::DATA_TYPE_ATTRIBUTE] = FormField::PARAGRAPH_TYPE_VALUE;
                $ldJson[FormField::TAB_ATTRIBUTE] = self::TAB_TYPE_VALUE;
                $ldJson[FormField::HYPERLINK_ATTRIBUTE] = PluginUtility::getDocumentationHyperLink(
                    action_plugin_combo_metagoogle::CANONICAL,
                    "Json-ld",
                    false,
                    "Advanced Page metadata definition with the json-ld format"
                );
                $ldJson[self::NAME_ATTRIBUTE] = action_plugin_combo_metagoogle::JSON_LD_META_PROPERTY;
                $fields[] = $ldJson;

                // Is low quality page
                $lowQualityIndicator = $page->getLowQualityIndicator();
                $isLowQualityPage[FormField::VALUE_ATTRIBUTE] = $lowQualityIndicator;
                $isLowQualityPage[FormField::MUTABLE_ATTRIBUTE] = true;
                $isLowQualityPage[FormField::DEFAULT_VALUE_ATTRIBUTE] = false; // the value returned if checked
                $isLowQualityPage[FormField::DATA_TYPE_ATTRIBUTE] = self::BOOLEAN_TYPE_VALUE;
                $isLowQualityPage[FormField::TAB_ATTRIBUTE] = self::TAB_QUALITY_VALUE;
                $isLowQualityPage[FormField::HYPERLINK_ATTRIBUTE] = PluginUtility::getDocumentationHyperLink(
                    LowQualityPage::LOW_QUALITY_PAGE_CANONICAL,
                    "Prevent this page to become a low quality page",
                    false,
                    "If checked, this page will never be a low quality page"
                );
                $isLowQualityPage[self::NAME_ATTRIBUTE] = Page::LOW_QUALITY_PAGE_INDICATOR;
                $fields[] = $isLowQualityPage;

                // Quality Monitoring
                $isQualityMonitoringOn[FormField::VALUE_ATTRIBUTE] = $page->isQualityMonitored();
                $isQualityMonitoringOn[FormField::MUTABLE_ATTRIBUTE] = true;
                $isQualityMonitoringOn[FormField::DEFAULT_VALUE_ATTRIBUTE] = false; // the value returned if checked
                $isQualityMonitoringOn[FormField::DATA_TYPE_ATTRIBUTE] = self::BOOLEAN_TYPE_VALUE;
                $isQualityMonitoringOn[FormField::TAB_ATTRIBUTE] = self::TAB_QUALITY_VALUE;
                $isQualityMonitoringOn[FormField::HYPERLINK_ATTRIBUTE] = PluginUtility::getDocumentationHyperLink(
                    "quality:dynamic_monitoring",
                    "Disable the quality message of this page",
                    false,
                    "If checked, the quality message will not be shown for the page."
                );
                $isQualityMonitoringOn[self::NAME_ATTRIBUTE] = action_plugin_combo_qualitymessage::DISABLE_INDICATOR;
                $fields[] = $isQualityMonitoringOn;


                // Locale
                $locale[FormField::VALUE_ATTRIBUTE] = $page->getLocale();
                $locale[FormField::MUTABLE_ATTRIBUTE] = false;
                $locale[FormField::DEFAULT_VALUE_ATTRIBUTE] = Site::getLocale();
                $locale[FormField::TAB_ATTRIBUTE] = self::TAB_LANGUAGE_VALUE;
                $locale[FormField::HYPERLINK_ATTRIBUTE] = PluginUtility::getDocumentationHyperLink(
                    "locale",
                    "Locale",
                    false,
                    "The locale define the language and the formatting of numbers and time for the page. It's generated from the language and region metadata."
                );
                $locale[self::NAME_ATTRIBUTE] = "locale";
                $fields[] = $locale;

                // Lang
                $lang[FormField::VALUE_ATTRIBUTE] = $page->getLang();
                $lang[FormField::MUTABLE_ATTRIBUTE] = true;
                $lang[FormField::DEFAULT_VALUE_ATTRIBUTE] = Site::getLang();
                $lang[FormField::TAB_ATTRIBUTE] = self::TAB_LANGUAGE_VALUE;
                $lang[FormField::HYPERLINK_ATTRIBUTE] = PluginUtility::getDocumentationHyperLink(
                    Page::LANG_META_PROPERTY,
                    "Language",
                    false,
                    "The language of the page"
                );
                $lang[self::NAME_ATTRIBUTE] = Page::LANG_META_PROPERTY;
                $fields[] = $lang;

                // Country
                $region[FormField::VALUE_ATTRIBUTE] = $page->getLocaleRegion();
                $region[FormField::MUTABLE_ATTRIBUTE] = true;
                $region[FormField::DEFAULT_VALUE_ATTRIBUTE] = Site::getLanguageRegion();
                $region[FormField::TAB_ATTRIBUTE] = self::TAB_LANGUAGE_VALUE;
                $region[FormField::HYPERLINK_ATTRIBUTE] = PluginUtility::getDocumentationHyperLink(
                    Page::REGION_META_PROPERTY,
                    "Region",
                    false,
                    "The region of the language"
                );
                $region[self::NAME_ATTRIBUTE] = Page::REGION_META_PROPERTY;
                $fields[] = $region;

                // database replication Date
                $replicationDateValue = $page->getDatabasePage()->getReplicationDate();
                $replicationDate[FormField::VALUE_ATTRIBUTE] = $replicationDateValue != null ? $replicationDateValue->format(Iso8601Date::getFormat()) : null;
                $replicationDate[FormField::MUTABLE_ATTRIBUTE] = false;
                $replicationDate[FormField::DATA_TYPE_ATTRIBUTE] = FormField::DATETIME_TYPE_VALUE;
                $replicationDate[FormField::TAB_ATTRIBUTE] = self::TAB_INTEGRATION_VALUE;
                $replicationDate[FormField::HYPERLINK_ATTRIBUTE] = PluginUtility::getDocumentationHyperLink(
                    DatabasePage::REPLICATION_CANONICAL,
                    "Database Replication Date",
                    false,
                    "The last date of database replication"
                );
                $replicationDate[self::NAME_ATTRIBUTE] = DatabasePage::DATE_REPLICATION;
                $fields[] = $replicationDate;

                // UUID
                $metasUuid[FormField::VALUE_ATTRIBUTE] = $page->getPageId();
                $metasUuid[FormField::MUTABLE_ATTRIBUTE] = false;
                $metasUuid[FormField::TAB_ATTRIBUTE] = self::TAB_INTEGRATION_VALUE;
                $metasUuid[FormField::HYPERLINK_ATTRIBUTE] = PluginUtility::getDocumentationHyperLink(
                    Page::PAGE_ID_ATTRIBUTE,
                    "UUID",
                    false,
                    "UUID is the Universally Unique IDentifier of the page used in replication (between database or installation)"
                );
                $metasUuid[self::NAME_ATTRIBUTE] = Page::PAGE_ID_ATTRIBUTE;
                $fields[] = $metasUuid;

                /**
                 * Tabs (for whatever reason, javascript keep the order of the properties
                 * and therefore the order of the tabs)
                 */
                $ui = [
                    "tabs" => [
                        self::TAB_PAGE_VALUE => [
                            FormField::LABEL_ATTRIBUTE => "Page",
                            "grid" => [3, 9]
                        ],
                        self::TAB_TYPE_VALUE => [
                            FormField::LABEL_ATTRIBUTE => "Page Type",
                            "grid" => [3, 9]
                        ],
                        self::TAB_REDIRECTION_VALUE => [
                            FormField::LABEL_ATTRIBUTE => "Redirection",
                            "grid" => [3, 9]
                        ],
                        self::TAB_IMAGE_VALUE => [
                            FormField::LABEL_ATTRIBUTE => "Image",
                            "grid" => [12]
                        ],
                        self::TAB_QUALITY_VALUE => [
                            FormField::LABEL_ATTRIBUTE => "Quality",
                            "grid" => [6, 6]
                        ],
                        self::TAB_LANGUAGE_VALUE => [
                            FormField::LABEL_ATTRIBUTE => "Language",
                            "grid" => [2, 10]
                        ],
                        self::TAB_INTEGRATION_VALUE => [
                            FormField::LABEL_ATTRIBUTE => "Integration",
                            "grid" => [4, 8]
                        ],
                    ],
                    "layout" => [
                        "type" => "nav-tabs",//ie nav-tabs versus list-group: https://getbootstrap.com/docs/5.0/components/list-group/#javascript-behavior
                        "direction" => "horizontal",
                        "grid" => [12]
                    ]
                ];
                $forms = [
                    "ui" => $ui,
                    "fields" => $fields
                ];
                header('Content-type: application/json');
                header("Status: 200");
                echo json_encode($forms);
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
