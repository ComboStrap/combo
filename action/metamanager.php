<?php

require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');

use ComboStrap\Alias;
use ComboStrap\Analytics;
use ComboStrap\DatabasePage;
use ComboStrap\DokuPath;
use ComboStrap\FormMeta;
use ComboStrap\FormMetaField;
use ComboStrap\FormMetaTab;
use ComboStrap\Http;
use ComboStrap\HttpResponse;
use ComboStrap\Identity;
use ComboStrap\Iso8601Date;
use ComboStrap\LogUtility;
use ComboStrap\LowQualityPage;
use ComboStrap\MetaManagerMenuItem;
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
        if ($id === null) {
            /**
             * With {@link TestRequest}
             * for instance
             */
            $id = $_REQUEST["id"];
        }

        if (empty($id)) {
            HttpResponse::create(HttpResponse::STATUS_BAD_REQUEST)
                ->setMessage("The page path (id form) is empty")
                ->setEvent($event)
                ->setCanonical(self::CANONICAL)
                ->send();
            return;
        }
        $page = Page::createPageFromId($id);
        if (!$page->exists()) {
            HttpResponse::create(HttpResponse::STATUS_DOES_NOT_EXIST)
                ->setMessage("The page ($id) does not exist")
                ->setCanonical(self::CANONICAL)
                ->send();
            return;
        }

        /**
         * Security
         */
        if (!$page->canBeUpdatedByCurrentUser()) {
            $user = Identity::getUser();
            HttpResponse::create(HttpResponse::STATUS_NOT_AUTHORIZED)
                ->setMessage("Not Authorized: The user ($user) has not the `write` permission for the page (:$id).")
                ->setEvent($event)
                ->setCanonical(self::CANONICAL)
                ->send();
            return;
        }

        /**
         * Functional code
         */
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        switch ($requestMethod) {
            case 'POST':

                $this->handlePost($event, $page);

                return;
            case "GET":

                /**
                 * The old viewer meta panel
                 */
                $type = $_GET["type"];
                if ($type === "viewer") {
                    if (!Identity::isManager()) {
                        HttpResponse::create(HttpResponse::STATUS_NOT_AUTHORIZED)
                            ->setMessage("Not Authorized (managers only)")
                            ->setEvent($event)
                            ->setCanonical(self::CANONICAL)
                            ->send();
                        return;

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
                        HttpResponse::create(HttpResponse::STATUS_ALL_GOOD)
                            ->setEvent($event)
                            ->setCanonical(self::CANONICAL)
                            ->send($fields);
                        return;
                    }

                }

                $this->handleGetFormMeta($page);
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
        array_splice($event->data['items'], -1, 0, array(new MetaManagerMenuItem()));

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

    /**
     * @param $event
     * @param Page $page
     */
    private function handlePost($event, Page $page)
    {
        if ($_SERVER["CONTENT_TYPE"] !== "application/json") {
            /**
             * We can't set the mime content in a {@link TestRequest}
             */
            if (!PluginUtility::isTest()) {
                HttpResponse::create(HttpResponse::STATUS_UNSUPPORTED_MEDIA_TYPE)
                    ->setEvent($event)
                    ->setCanonical(self::CANONICAL)
                    ->send("The post content should be in json format");
                return;
            }
        }

        /**
         * We can't simulate a php://input in a {@link TestRequest}
         * We set therefore the post
         */
        if (!PluginUtility::isTest()) {
            $jsonString = file_get_contents('php://input');
            $_POST = \ComboStrap\Json::createFromString($jsonString)->toArray();
        }

        $modifications = [];
        $errors = [];
        foreach ($_POST as $name => $value) {
            $name = strtolower($name);
            switch ($name) {
                case Page::NAME_PROPERTY:
                    if ($value != $page->getPageName()) {
                        $page->setPageName($value);
                    }
                    continue 2;
                default:
                    $oldValue = $page->getMetadata($name);
                    if ($oldValue !== $value) {
                        //$page->setMetadata($name, $value);
                        $errors[] = "The metadata ($name) is not managed but was saved with the value ($value)";
                    }
                    continue 2;
            }
        }
        $page->getDatabasePage()->replicateMetaAttributes();

        HttpResponse::create(HttpResponse::STATUS_ALL_GOOD)
            ->sendMessage($errors);


    }

    /**
     * @param Page $page
     */
    private
    function handleGetFormMeta(Page $page)
    {
        $formMeta = FormMeta::create($page->getDokuwikiId())
            ->setType(FormMeta::FORM_NAV_TABS_TYPE);

        /**
         * The manager
         */
        // Name
        $formMeta->addField(
            FormMetaField::create(Analytics::NAME)
                ->setMutable(true)
                ->setTab(self::TAB_PAGE_VALUE)
                ->setLabel("Name")
                ->setCanonical(Analytics::NAME)
                ->setDescription("The page name is the shortest page description. It should be at maximum a couple of words long. It's used mainly in navigation components.")
                ->addValue($page->getPageName(), $page->getDefaultPageName())
        );

        // Title (title of a component is an heading)
        $formMeta->addField(
            FormMetaField::create(Analytics::TITLE)
                ->setLabel("Title")
                ->setDescription("The page title is a description advertised to external application such as search engine and browser.")
                ->addValue($page->getTitle(), $page->getDefaultTitle())
                ->setTab(self::TAB_PAGE_VALUE)
        );

        // H1
        $formMeta->addField(
            FormMetaField::create(Analytics::H1)
                ->addValue($page->getH1(), $page->getDefaultH1())
                ->setTab(self::TAB_PAGE_VALUE)
                ->setDescription("The heading 1 (or H1) is the first heading of your page. It may be used in template to make a difference with the title.")
        );

        // Description
        $formMeta->addField(
            FormMetaField::create(Analytics::DESCRIPTION)
                ->addValue($page->getDescription(), $page->getDescriptionOrElseDokuWiki())
                ->setType(FormMetaField::PARAGRAPH_TYPE_VALUE)
                ->setTab(self::TAB_PAGE_VALUE)
                ->setDescription("The description is a paragraph that describe your page. It's advertised to external application and used in templating.")
        );

        // Path
        $formMeta->addField(FormMetaField::create(Analytics::PATH)
            ->addValue($page->getPath())
            ->setLabel("Page Path")
            ->setMutable(false)
            ->setTab(self::TAB_REDIRECTION_VALUE)
            ->setDescription("The path of the page on the file system (in wiki format with the colon `:` as path separator)")
        );

        // Canonical
        $formMeta->addField(
            FormMetaField::create(Analytics::CANONICAL)
                ->addValue($page->getCanonical(), $page->getDefaultCanonical())
                ->setTab(self::TAB_REDIRECTION_VALUE)
                ->setLabel("Canonical Path")
                ->setDescription("The canonical path is a short unique path for the page (used in named permalink)")
        );

        // Slug
        $defaultSlug = $page->getDefaultSlug();
        if (!empty($defaultSlug)) {
            $defaultSlug = DokuPath::toSlugPath($defaultSlug);
        }
        $formMeta->addField(
            FormMetaField::create(Page::SLUG_ATTRIBUTE)
                ->addValue($page->getSlug(), $defaultSlug)
                ->setLabel("Slug Path")
                ->setTab(self::TAB_REDIRECTION_VALUE)
                ->setDescription("The slug is used in the url of the page (if chosen)")
        );

        $formMeta->addField(
            FormMetaField::create("url-path")
                ->addValue($page->getUrlPath())
                ->setTab(self::TAB_REDIRECTION_VALUE)
                ->setMutable(false)
                ->setCanonical("page:url")
                ->setLabel("Url Path")
                ->setDescription("The path used in the page url")
        );

        // Layout
        $formMeta->addField(
            FormMetaField::create(Page::LAYOUT_PROPERTY)
                ->addValue($page->getLayout(), $page->getDefaultLayout())
                ->setDomainValues($page->getLayoutValues())
                ->setTab(self::TAB_PAGE_VALUE)
                ->setDescription("A layout chooses the layout of your page (such as the slots and placement of the main content)")
        );


        // Modified Date
        $formMeta->addField(FormMetaField::create(Analytics::DATE_MODIFIED)
            ->addValue($page->getModifiedDateAsString())
            ->setMutable(false)
            ->setType(FormMetaField::DATETIME_TYPE_VALUE)
            ->setTab(self::TAB_PAGE_VALUE)
            ->setLabel("Modification Date")
            ->setDescription("The last modification date of the page")
            ->setCanonical(self::METADATA_CANONICAL)
        );

        // Created Date
        $formMeta->addField(FormMetaField::create(Analytics::DATE_CREATED)
            ->addValue($page->getCreatedDateAsString())
            ->setMutable(false)
            ->setType(FormMetaField::DATETIME_TYPE_VALUE)
            ->setTab(self::TAB_PAGE_VALUE)
            ->setCanonical(self::METADATA_CANONICAL)
            ->setLabel("Creation Date")
            ->setDescription("The creation date of the page")
        );


        /**
         * Page Image Properties
         */
        $pageImagePath = FormMetaField::create("image-path")
            ->setLabel("Path")
            ->setCanonical(syntax_plugin_combo_pageimage::CANONICAL)
            ->setDescription("The path of the image")
            ->setWidth(8);
        $pageImageUsage = FormMetaField::create("image-usage")
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
        $formMeta->addField(FormMetaField::create("page-image")
            ->setType(FormMetaField::TABULAR_TYPE_VALUE)
            ->setLabel("Page Images")
            ->setTab(self::TAB_IMAGE_VALUE)
            ->setDescription("The illustrative images of the page")
            ->addColumn($pageImagePath)
            ->addColumn($pageImageUsage)
        );


        /**
         * Aliases
         */
        $aliasPath = FormMetaField::create("alias-path")
            ->setCanonical(Alias::CANONICAL)
            ->setLabel("Alias Path")
            ->setDescription("The path of the alias");
        $aliasType = FormMetaField::create("alias-type")
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

        $formMeta->addField(FormMetaField::create(Page::ALIAS_ATTRIBUTE)
            ->setLabel("Page Aliases")
            ->setDescription("Aliases that will redirect to this page.")
            ->setTab(self::TAB_REDIRECTION_VALUE)
            ->setType(FormMetaField::TABULAR_TYPE_VALUE)
            ->addColumn($aliasPath)
            ->addColumn($aliasType)
        );


        // Page Type
        $formMeta->addField(FormMetaField::create(Page::TYPE_META_PROPERTY)
            ->addValue($page->getType(), $page->getDefaultType())
            ->setDomainValues($page->getTypeValues())
            ->setTab(self::TAB_TYPE_VALUE)
            ->setCanonical(self::PAGE_TYPE_CANONICAL)
            ->setLabel("Page Type")
            ->setDescription("The type of page")
        );

        // Published Date
        $formMeta->addField(FormMetaField::create(Publication::DATE_PUBLISHED)
            ->addValue($page->getPublishedTimeAsString(), $page->getCreatedDateAsString())
            ->setType(FormMetaField::DATETIME_TYPE_VALUE)
            ->setTab(self::TAB_TYPE_VALUE)
            ->setCanonical(self::PAGE_TYPE_CANONICAL)
            ->setLabel("Publication Date")
            ->setDescription("The publication date")
        );

        // Start Date
        $formMeta->addField(FormMetaField::create(Analytics::DATE_START)
            ->addValue($page->getStartDate())
            ->setType(FormMetaField::DATETIME_TYPE_VALUE)
            ->setTab(self::TAB_TYPE_VALUE)
            ->setCanonical(Page::EVENT_TYPE)
            ->setLabel("Start Date")
            ->setDescription("The start date of an event")
        );

        // End Date
        $formMeta->addField(FormMetaField::create(Analytics::DATE_END)
            ->addValue($page->getEndDate())
            ->setType(FormMetaField::DATETIME_TYPE_VALUE)
            ->setTab(self::TAB_TYPE_VALUE)
            ->setCanonical(Page::EVENT_TYPE)
            ->setLabel("End Date")
            ->setDescription("The end date of an event")
        );

        // ld-json
        $formMeta->addField(FormMetaField::create(action_plugin_combo_metagoogle::JSON_LD_META_PROPERTY)
            ->addValue($page->getLdJson(), "Enter a json-ld value")
            ->setType(FormMetaField::PARAGRAPH_TYPE_VALUE)
            ->setTab(self::TAB_TYPE_VALUE)
            ->setCanonical(action_plugin_combo_metagoogle::CANONICAL)
            ->setLabel("Json-ld")
            ->setDescription("Advanced Page metadata definition with the json-ld format")
        );


        // Is low quality page
        $formMeta->addField(FormMetaField::create(Page::LOW_QUALITY_PAGE_INDICATOR)
            ->addValue($page->getLowQualityIndicator(), false) // the default value is the value returned if checked)
            ->setType(FormMetaField::BOOLEAN_TYPE_VALUE)
            ->setTab(self::TAB_QUALITY_VALUE)
            ->setCanonical(LowQualityPage::LOW_QUALITY_PAGE_CANONICAL)
            ->setLabel("Prevent this page to become a low quality page")
            ->setDescription("If checked, this page will never be a low quality page")
        );

        // Quality Monitoring
        $formMeta->addField(FormMetaField::create(action_plugin_combo_qualitymessage::DYNAMIC_QUALITY_MONITORING_INDICATOR)
            ->addValue($page->getDynamicQualityIndicatorOrDefault(), false) // the default value is returned if checked
            ->setType(FormMetaField::BOOLEAN_TYPE_VALUE)
            ->setTab(self::TAB_QUALITY_VALUE)
            ->setCanonical(action_plugin_combo_qualitymessage::CANONICAL)
            ->setLabel("Disable the quality message of this page")
            ->setDescription("If checked, the quality message will not be shown for the page.")
        );

        // Locale
        $formMeta->addField(FormMetaField::create("locale")
            ->addValue($page->getLocale(), Site::getLocale())
            ->setMutable(false)
            ->setTab(self::TAB_LANGUAGE_VALUE)
            ->setCanonical("locale")
            ->setLabel("Locale")
            ->setDescription("The locale define the language and the formatting of numbers and time for the page. It's generated from the language and region metadata.")
        );

        // Lang
        $formMeta->addField(FormMetaField::create(Page::LANG_META_PROPERTY)
            ->addValue($page->getLang(), Site::getLang())
            ->setTab(self::TAB_LANGUAGE_VALUE)
            ->setCanonical(Page::LANG_META_PROPERTY)
            ->setLabel("Language")
            ->setDescription("The language of the page")
        );

        // Country
        $formMeta->addField(FormMetaField::create(Page::REGION_META_PROPERTY)
            ->addValue($page->getLocaleRegion(), Site::getLanguageRegion())
            ->setTab(self::TAB_LANGUAGE_VALUE)
            ->setLabel("Region")
            ->setDescription("The region of the language")
        );

        // database replication Date
        $replicationDate = $page->getDatabasePage()->getReplicationDate();
        $formMeta->addField(FormMetaField::create(DatabasePage::DATE_REPLICATION)
            ->addValue($replicationDate != null ? $replicationDate->format(Iso8601Date::getFormat()) : null)
            ->setMutable(false)
            ->setType(FormMetaField::DATETIME_TYPE_VALUE)
            ->setTab(self::TAB_INTEGRATION_VALUE)
            ->setCanonical(DatabasePage::REPLICATION_CANONICAL)
            ->setLabel("Database Replication Date")
            ->setDescription("The last date of database replication")
        );

        // Page Id
        $pageId = $page->getPageId();
        $formMeta->addField(FormMetaField::create(Page::PAGE_ID_ATTRIBUTE)
            ->addValue($pageId)
            ->setMutable(false)
            ->setTab(self::TAB_INTEGRATION_VALUE)
            ->setCanonical(Page::PAGE_ID_ATTRIBUTE)
            ->setLabel("Page Id")
            ->setDescription("An unique identifier for the page")
        );

        /**
         * Tabs (for whatever reason, javascript keep the order of the properties
         * and therefore the order of the tabs)
         */
        $formMeta
            ->addTab(
                FormMetaTab::create(self::TAB_PAGE_VALUE)
                    ->setLabel("Page")
                    ->setWidthLabel(3)
                    ->setWidthField(9)
            )
            ->addTab(
                FormMetaTab::create(self::TAB_TYPE_VALUE)
                    ->setLabel("Page Type")
                    ->setWidthLabel(3)
                    ->setWidthField(9)
            )
            ->addTab(
                FormMetaTab::create(self::TAB_REDIRECTION_VALUE)
                    ->setLabel("Redirection")
                    ->setWidthLabel(3)
                    ->setWidthField(9)
            )
            ->addTab(
                FormMetaTab::create(self::TAB_IMAGE_VALUE)
                    ->setLabel("Image")
                    ->setWidthField(12)
            )
            ->addTab(
                FormMetaTab::create(self::TAB_QUALITY_VALUE)
                    ->setLabel("Quality")
                    ->setWidthLabel(6)
                    ->setWidthField(6)
            )->addTab(
                FormMetaTab::create(self::TAB_LANGUAGE_VALUE)
                    ->setLabel("Language")
                    ->setWidthLabel(2)
                    ->setWidthField(10)
            )->addTab(
                FormMetaTab::create(self::TAB_INTEGRATION_VALUE)
                    ->setLabel("Integration")
                    ->setWidthLabel(4)
                    ->setWidthField(8)
            );


        Http::setJsonMime();
        Http::setStatus(200);
        echo json_encode($formMeta->toAssociativeArray());
    }


}
