<?php

require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');

use ComboStrap\Alias;
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
    // width of the label / element

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
                    ->addValue($page->getCanonical(), $page->getDefaultCanonical())
                    ->setTab(self::TAB_REDIRECTION_VALUE)
                    ->setDescription("The canonical creates a link that identifies your page uniquely by name")
                    ->toAssociativeArray();

                // Layout
                $fields[] = FormField::create(Page::LAYOUT_PROPERTY)
                    ->addValue($page->getLayout(), $page->getDefaultLayout())
                    ->setDomainValues($page->getLayoutValues())
                    ->setTab(self::TAB_PAGE_VALUE)
                    ->setDescription("A layout chooses the layout of your page (such as the slots and placement of the main content)")
                    ->toAssociativeArray();


                // Modified Date
                $fields[] = FormField::create(Analytics::DATE_MODIFIED)
                    ->addValue($page->getModifiedDateAsString())
                    ->setMutable(false)
                    ->setType(FormField::DATETIME_TYPE_VALUE)
                    ->setTab(self::TAB_PAGE_VALUE)
                    ->setLabel("Modification Date")
                    ->setDescription("The last modification date of the page")
                    ->setCanonical(self::METADATA_CANONICAL)
                    ->toAssociativeArray();

                // Created Date
                $fields[] = FormField::create(Analytics::DATE_CREATED)
                    ->addValue($page->getCreatedDateAsString())
                    ->setMutable(false)
                    ->setType(FormField::DATETIME_TYPE_VALUE)
                    ->setTab(self::TAB_PAGE_VALUE)
                    ->setCanonical(self::METADATA_CANONICAL)
                    ->setLabel("Creation Date")
                    ->setDescription("The creation date of the page")
                    ->toAssociativeArray();

                // Path
                $fields[] = FormField::create(Analytics::PATH)
                    ->addValue($page->getPath())
                    ->setMutable(false)
                    ->setTab(self::TAB_PAGE_VALUE)
                    ->setDescription("The path of the page on the file system (in wiki format with the colon `:` as path separator)")
                    ->toAssociativeArray();


                /**
                 * Page Image Properties
                 */
                $pageImagePath = FormField::create("image-path")
                    ->setLabel("Path")
                    ->setCanonical(syntax_plugin_combo_pageimage::CANONICAL)
                    ->setDescription("The path of the image")
                    ->setWidth(8);
                $pageImageUsage = FormField::create("image-usage")
                    ->setLabel("Usages")
                    ->setCanonical(syntax_plugin_combo_pageimage::CANONICAL)
                    ->setDomainValues(PageImage::getUsageValues())
                    ->setWidth(4)
                    ->setDescription("The possible usages of the image");
                $pageImagesObjects = $page->getPageImagesObject();
                $pageImageDefault = $page->getDefaultPageImageObject();
                for ($i = 0; $i < 5; $i++) {

                    $pageImage = null;
                    if (isset($pageImagesObjects[$i])) {
                        $pageImage = $pageImagesObjects[$i];
                    }

                    /**
                     * Image
                     */
                    $pageImagePathValue = null;
                    $pageImagePathDefaultValue = null;
                    $pageImagePathUsage = null;
                    if ($pageImage != null) {
                        $pageImagePathValue = $pageImage->getImage()->getDokuPath()->getPath();
                        $pageImagePathUsage = $pageImage->getUsage();
                    }
                    if ($i == 0 && $pageImageDefault !== null) {
                        $pageImagePathDefaultValue = $pageImageDefault->getImage()->getDokuPath()->getPath();
                    }
                    $pageImagePath->addValue($pageImagePathValue, $pageImagePathDefaultValue);
                    $pageImageUsage->addValue($pageImagePathUsage, PageImage::getDefaultUsage());

                }

                // Image
                $fields[] = FormField::create("page-image")
                    ->setType(FormField::TABULAR_TYPE_VALUE)
                    ->setLabel("Page Images")
                    ->setTab(self::TAB_IMAGE_VALUE)
                    ->setDescription("The illustrative images of the page")
                    ->addColumn($pageImagePath)
                    ->addColumn($pageImageUsage)
                    ->toAssociativeArray();


                /**
                 * Aliases
                 */
                $aliasPath = FormField::create("alias-path")
                    ->setCanonical(Alias::CANONICAL)
                    ->setLabel("Alias Path")
                    ->setDescription("The path of the alias");
                $aliasType = FormField::create("alias-type")
                    ->setCanonical(Alias::CANONICAL)
                    ->setLabel("Alias Type")
                    ->setDescription("The type of the alias")
                    ->setDomainValues(Alias::getPossibleTypesValues());


                $aliasesValues = $page->getAliases();
                if (sizeof($aliasesValues) === 0) {
                    $aliasPath->addValue(null);
                    $aliasType->addValue(null, Alias::getDefaultType());
                } else {
                    foreach ($aliasesValues as $alias) {
                        $aliasPath->addValue($alias->getPath());
                        $aliasType->addValue($alias->getType(), Alias::getDefaultType());
                    }
                }

                $fields[] = FormField::create(Page::ALIAS_ATTRIBUTE)
                    ->setLabel("Page Aliases")
                    ->setDescription("Aliases that will redirect to this page.")
                    ->setTab(self::TAB_REDIRECTION_VALUE)
                    ->setType(FormField::TABULAR_TYPE_VALUE)
                    ->addColumn($aliasPath)
                    ->addColumn($aliasType)
                    ->toAssociativeArray();


                // Page Type
                $fields[] = FormField::create(Page::TYPE_META_PROPERTY)
                    ->addValue($page->getType(), $page->getDefaultType())
                    ->setDomainValues($page->getTypeValues())
                    ->setTab(self::TAB_TYPE_VALUE)
                    ->setCanonical(self::PAGE_TYPE_CANONICAL)
                    ->setLabel("Page Type")
                    ->setDescription("The type of page")
                    ->toAssociativeArray();

                // Published Date
                $fields[] = FormField::create(Publication::DATE_PUBLISHED)
                    ->addValue($page->getPublishedTimeAsString(), $page->getCreatedDateAsString())
                    ->setType(FormField::DATETIME_TYPE_VALUE)
                    ->setTab(self::TAB_TYPE_VALUE)
                    ->setCanonical(self::PAGE_TYPE_CANONICAL)
                    ->setLabel("Publication Date")
                    ->setDescription("The publication date")
                    ->toAssociativeArray();

                // Start Date
                $fields[] = FormField::create(Analytics::DATE_START)
                    ->addValue($page->getStartDate())
                    ->setType(FormField::DATETIME_TYPE_VALUE)
                    ->setTab(self::TAB_TYPE_VALUE)
                    ->setCanonical(Page::EVENT_TYPE)
                    ->setLabel("Start Date")
                    ->setDescription("The start date of an event");

                // End Date
                $fields[] = FormField::create(Analytics::DATE_END)
                    ->addValue($page->getEndDate())
                    ->setTab(FormField::DATETIME_TYPE_VALUE)
                    ->setTab(self::TAB_TYPE_VALUE)
                    ->setCanonical(Page::EVENT_TYPE)
                    ->setLabel("End Date")
                    ->setDescription("The end date of an event")
                    ->toAssociativeArray();

                // ld-json
                $fields[] = FormField::create(action_plugin_combo_metagoogle::JSON_LD_META_PROPERTY)
                    ->addValue($page->getLdJson(), "Enter a json-ld value")
                    ->setType(FormField::PARAGRAPH_TYPE_VALUE)
                    ->setTab(self::TAB_TYPE_VALUE)
                    ->setCanonical(action_plugin_combo_metagoogle::CANONICAL)
                    ->setLabel("Json-ld")
                    ->setDescription("Advanced Page metadata definition with the json-ld format")
                    ->toAssociativeArray();


                // Is low quality page
                $fields[] = FormField::create(Page::LOW_QUALITY_PAGE_INDICATOR)
                    ->addValue($page->getLowQualityIndicator(), false) // the default value is the value returned if checked)
                    ->setType(self::BOOLEAN_TYPE_VALUE)
                    ->setTab(self::TAB_QUALITY_VALUE)
                    ->setCanonical(LowQualityPage::LOW_QUALITY_PAGE_CANONICAL)
                    ->setLabel("Prevent this page to become a low quality page")
                    ->setDescription("If checked, this page will never be a low quality page")
                    ->toAssociativeArray();

                // Quality Monitoring
                $fields[] = FormField::create(action_plugin_combo_qualitymessage::DYNAMIC_QUALITY_MONITORING_INDICATOR)
                    ->addValue($page->isQualityMonitored(), false) // the default value is returned if checked
                    ->setType(self::BOOLEAN_TYPE_VALUE)
                    ->setTab(self::TAB_QUALITY_VALUE)
                    ->setCanonical(action_plugin_combo_qualitymessage::CANONICAL)
                    ->setLabel("Disable the quality message of this page")
                    ->setDescription("If checked, the quality message will not be shown for the page.")
                    ->toAssociativeArray();

                // Locale
                $fields[] = FormField::create("locale")
                    ->addValue($page->getLocale(), Site::getLocale())
                    ->setMutable(false)
                    ->setTab(self::TAB_LANGUAGE_VALUE)
                    ->setCanonical("locale")
                    ->setLabel("Locale")
                    ->setDescription("The locale define the language and the formatting of numbers and time for the page. It's generated from the language and region metadata.")
                    ->toAssociativeArray();

                // Lang
                $fields[] = FormField::create(Page::LANG_META_PROPERTY)
                    ->addValue($page->getLang(), Site::getLang())
                    ->setTab(self::TAB_LANGUAGE_VALUE)
                    ->setCanonical(Page::LANG_META_PROPERTY)
                    ->setLabel("Language")
                    ->setDescription("The language of the page")
                    ->toAssociativeArray();

                // Country
                $fields[] = FormField::create(Page::REGION_META_PROPERTY)
                    ->addValue($page->getLocaleRegion(), Site::getLanguageRegion())
                    ->setTab(self::TAB_LANGUAGE_VALUE)
                    ->setLabel("Region")
                    ->setDescription("The region of the language")
                    ->toAssociativeArray();

                // database replication Date
                $replicationDate = $page->getDatabasePage()->getReplicationDate();
                $fields[] = FormField::create(DatabasePage::DATE_REPLICATION)
                    ->addValue($replicationDate != null ? $replicationDate->format(Iso8601Date::getFormat()) : null)
                    ->setMutable(false)
                    ->setType(FormField::DATETIME_TYPE_VALUE)
                    ->setTab(self::TAB_INTEGRATION_VALUE)
                    ->setCanonical(DatabasePage::REPLICATION_CANONICAL)
                    ->setLabel("Database Replication Date")
                    ->setDescription("The last date of database replication")
                    ->toAssociativeArray();

                // Page Id
                $fields[] = FormField::create(Page::PAGE_ID_ATTRIBUTE)
                    ->addValue($page->getPageId())
                    ->setMutable(false)
                    ->setTab(self::TAB_INTEGRATION_VALUE)
                    ->setCanonical(Page::PAGE_ID_ATTRIBUTE)
                    ->setLabel("Page Id")
                    ->setDescription("An unique identifier for the page")
                    ->toAssociativeArray();

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
