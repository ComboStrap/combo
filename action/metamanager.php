<?php

require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');

use ComboStrap\Alias;
use ComboStrap\Aliases;
use ComboStrap\Analytics;
use ComboStrap\CacheExpirationDate;
use ComboStrap\CacheManager;
use ComboStrap\DatabasePage;
use ComboStrap\DataType;
use ComboStrap\DokuPath;
use ComboStrap\ExceptionCombo;
use ComboStrap\FormMeta;
use ComboStrap\FormMetaField;
use ComboStrap\FormMetaTab;
use ComboStrap\HttpResponse;
use ComboStrap\Identity;
use ComboStrap\Iso8601Date;
use ComboStrap\Json;
use ComboStrap\LdJson;
use ComboStrap\LowQualityPage;
use ComboStrap\Message;
use ComboStrap\Metadata;
use ComboStrap\MetadataDateTime;
use ComboStrap\MetadataJson;
use ComboStrap\MetaManagerMenuItem;
use ComboStrap\Mime;
use ComboStrap\Page;
use ComboStrap\PageImage;
use ComboStrap\PageImages;
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


    const META_MANAGER_CALL_ID = "combo-meta-manager";
    const META_VIEWER_CALL_ID = "combo-meta-viewer";
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
    const TAB_CACHE_VALUE = "cache";

    /**
     * The canonical for the metadata page
     */
    const METADATA_CANONICAL = "metadata";
    /**
     * The canonical for page type
     */
    const PAGE_TYPE_CANONICAL = "page:type";
    const SUCCESS_MESSAGE = "The data were updated without errors.";


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

        $call = $event->data;
        if (!in_array($call, [self::META_MANAGER_CALL_ID, self::META_VIEWER_CALL_ID])) {
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
                ->setEvent($event)
                ->setCanonical(self::CANONICAL)
                ->sendMessage("The page path (id form) is empty");
            return;
        }
        $page = Page::createPageFromId($id);
        if (!$page->exists()) {
            HttpResponse::create(HttpResponse::STATUS_DOES_NOT_EXIST)
                ->setEvent($event)
                ->setCanonical(self::CANONICAL)
                ->sendMessage("The page ($id) does not exist");
            return;
        }

        /**
         * Security
         */
        if (!$page->canBeUpdatedByCurrentUser()) {
            $user = Identity::getUser();
            HttpResponse::create(HttpResponse::STATUS_NOT_AUTHORIZED)
                ->setEvent($event)
                ->setCanonical(self::CANONICAL)
                ->sendMessage("Not Authorized: The user ($user) has not the `write` permission for the page (:$id).");
            return;
        }

        /**
         * Functional code
         */

        $requestMethod = $_SERVER['REQUEST_METHOD'];
        switch ($requestMethod) {
            case 'POST':

                if ($_SERVER["CONTENT_TYPE"] !== "application/json") {
                    /**
                     * We can't set the mime content in a {@link TestRequest}
                     */
                    if (!PluginUtility::isTest()) {
                        HttpResponse::create(HttpResponse::STATUS_UNSUPPORTED_MEDIA_TYPE)
                            ->setEvent($event)
                            ->setCanonical(self::CANONICAL)
                            ->sendMessage("The post content should be in json format");
                        return;
                    }
                }

                /**
                 * We can't simulate a php://input in a {@link TestRequest}
                 * We set therefore the post
                 */
                if (!PluginUtility::isTest()) {
                    $jsonString = file_get_contents('php://input');
                    $_POST = Json::createFromString($jsonString)->toArray();
                }

                if ($call === self::META_MANAGER_CALL_ID) {
                    $this->handleManagerPost($event, $page, $_POST);
                } else {
                    $this->handleViewerPost($event, $page, $_POST);
                }

                return;
            case "GET":

                if ($call === self::META_MANAGER_CALL_ID) {
                    $this->handleManagerGet($event, $page);
                } else {
                    $this->handleViewerGet($event, $page);
                }
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

    /**
     * @param $event
     * @param Page $page
     * @param array $post
     */
    private function handleManagerPost($event, Page $page, array $post)
    {

        /**
         * Boolean form field (default values)
         * are not send back
         */
        $defaultBoolean = [
            Page::CAN_BE_LOW_QUALITY_PAGE_INDICATOR => Page::CAN_BE_LOW_QUALITY_PAGE_DEFAULT,
            action_plugin_combo_qualitymessage::EXECUTE_DYNAMIC_QUALITY_MONITORING_INDICATOR => action_plugin_combo_qualitymessage::EXECUTE_DYNAMIC_QUALITY_MONITORING_DEFAULT
        ];
        $post = array_merge($defaultBoolean, $post);

        $processingMessages = [];

        /**
         * New Metadata processing
         */
        Aliases::createFromPage($page)
            ->setFromFormData($post);
        try {
            PageImages::createFromPage($page)
                ->setFromFormData($post);
        } catch (ExceptionCombo $e) {
            $processingMessages[] = Message::createErrorMessage($e->getMessage())
                ->setCanonical($e->getCanonical());
        }
        try {
            LdJson::createFromPage($page)
                ->setFromFormData($post);
        } catch (ExceptionCombo $e) {
            $processingMessages[] = Message::createErrorMessage($e->getMessage())
                ->setCanonical($e->getCanonical());
        }


        /**
         * When the migration to the new metadata system is done,
         * this unset is no more needed
         */
        unset($post[Aliases::ALIAS_TYPE]);
        unset($post[Aliases::ALIAS_PATH]);
        unset($post[PageImages::IMAGE_PATH]);
        unset($post[PageImages::IMAGE_USAGE]);
        unset($post[LdJson::JSON_LD_META_PROPERTY]);

        /**
         * Old metadata system
         * We upsert as if the data comes from
         * the file system/frontmatter
         * This is the case for all scalar value
         */
        $upsertMessages = $page->upsertMetadataFromAssociativeArray($post, true);
        $processingMessages = array_merge($upsertMessages, $processingMessages);

        $responseMessages = [];
        $responseStatus = HttpResponse::STATUS_ALL_GOOD;
        foreach ($processingMessages as $upsertMessages) {
            $responseMessage = [ucfirst($upsertMessages->getType())];
            $documentationHyperlink = $upsertMessages->getDocumentationHyperLink();
            if ($documentationHyperlink !== null) {
                $responseMessage[] = $documentationHyperlink;
            }
            $responseMessage[] = $upsertMessages->getContent(Mime::PLAIN_TEXT);
            $responseMessages[] = implode(" - ", $responseMessage);
            if ($upsertMessages->getType() === Message::TYPE_ERROR && $responseStatus !== HttpResponse::STATUS_BAD_REQUEST) {
                $responseStatus = HttpResponse::STATUS_BAD_REQUEST;
            }
        }

        if (sizeof($responseMessages) === 0) {
            $responseMessages[] = self::SUCCESS_MESSAGE;
        }

        $frontMatterMessage = syntax_plugin_combo_frontmatter::updateFrontmatter($page);
        $responseMessages[] = $frontMatterMessage->getPlainTextContent();


        /**
         * Response
         */
        HttpResponse::create(HttpResponse::STATUS_ALL_GOOD)
            ->setEvent($event)
            ->sendMessage($responseMessages);


    }

    /**
     * @param Doku_Event $event
     * @param Page $page
     */
    private
    function handleManagerGet(Doku_Event $event, Page $page)
    {
        $formMeta = $this->getFormMetadataForPage($page);
        $payload = json_encode($formMeta->toAssociativeArray());
        HttpResponse::create(HttpResponse::STATUS_ALL_GOOD)
            ->setEvent($event)
            ->send($payload, Mime::JSON);
    }

    /**
     * @param Doku_Event $event
     * @param Page $page
     */
    private function handleViewerGet(Doku_Event $event, Page $page)
    {
        if (!Identity::isManager()) {
            HttpResponse::create(HttpResponse::STATUS_NOT_AUTHORIZED)
                ->setEvent($event)
                ->setCanonical(self::CANONICAL)
                ->sendMessage("Not Authorized (managers only)");
            return;
        }
        $metadata = $page->getMetadatas();
        $persistent = $metadata[Metadata::PERSISTENT_METADATA];
        ksort($persistent);
        $current = $metadata[Metadata::CURRENT_METADATA];
        ksort($current);
        $form = FormMeta::create("raw_metadata")
            ->addField(
                FormMetaField::create(Metadata::PERSISTENT_METADATA)
                    ->setLabel("Persistent Metadata (User Metadata)")
                    ->setTab("persistent")
                    ->setDescription("The persistent metadata contains raw values. They contains the values set by the user and the fixed values such as page id.")
                    ->addValue(json_encode($persistent))
                    ->setType(DataType::JSON_TYPE_VALUE)
            )
            ->addField(FormMetaField::create(Metadata::CURRENT_METADATA)
                ->setLabel("Current (Derived) Metadata")
                ->setTab("current")
                ->setDescription("The current metadata are the derived / calculated / runtime metadata values (extended with the persistent metadata).")
                ->addValue(json_encode($current))
                ->setType(DataType::JSON_TYPE_VALUE)
                ->setMutable(false)
            )
            ->toAssociativeArray();

        HttpResponse::create(HttpResponse::STATUS_ALL_GOOD)
            ->setEvent($event)
            ->setCanonical(self::CANONICAL)
            ->send(json_encode($form), Mime::JSON);

    }

    private function handleViewerPost(Doku_Event $event, Page $page, array $post)
    {

        $meta = $page->getMetadatas();
        /**
         * @var Message[]
         */
        $messages = [];
        /**
         * Only Persistent, current cannot be modified
         */
        $persistentMetadataType = Metadata::PERSISTENT_METADATA;
        $postMeta = json_decode($post[$persistentMetadataType], true);
        if ($postMeta === null) {
            HttpResponse::create(HttpResponse::STATUS_BAD_REQUEST)
                ->setEvent($event)
                ->sendMessage("The metadata $persistentMetadataType should be in json format");
            return;
        }
        $persistentPageMeta = &$meta[$persistentMetadataType];


        $managedMetaMessageSuffix = "is a managed metadata, you need to use the metadata manager to delete it";

        /**
         * Process the actual attribute
         */
        foreach ($persistentPageMeta as $key => $value) {
            $postMetaValue = null;
            if (isset($postMeta[$key])) {
                $postMetaValue = $postMeta[$key];
                unset($postMeta[$key]);
            }

            if ($postMetaValue === null) {
                if (in_array($key, Metadata::FORM_METADATA)) {
                    $messages[] = Message::createInfoMessage("The $persistentMetadataType metadata ($key) $managedMetaMessageSuffix");
                    continue;
                }
                if (in_array($key, Metadata::NOT_MODIFIABLE_PERSISTENT_METADATA)) {
                    $messages[] = Message::createInfoMessage("The $persistentMetadataType metadata ($key) is a internal metadata, you can't delete it");
                    continue;
                }
                unset($persistentPageMeta[$key]);
                $messages[] = Message::createInfoMessage("The $persistentMetadataType metadata ($key) with the value ($value) was deleted");
            } else {
                if ($value !== $postMetaValue) {
                    if (in_array($key, Metadata::FORM_METADATA)) {
                        $messages[] = Message::createInfoMessage("The $persistentMetadataType metadata ($key) $managedMetaMessageSuffix");
                        continue;
                    }
                    if (in_array($key, Metadata::NOT_MODIFIABLE_PERSISTENT_METADATA)) {
                        $messages[] = Message::createInfoMessage("The $persistentMetadataType metadata ($key) is a internal metadata, you can't modify it");
                        continue;
                    }
                    $persistentPageMeta[$key] = $postMetaValue;
                    $messages[] = Message::createInfoMessage("The $persistentMetadataType metadata ($key) was updated to the value ($postMetaValue) - Old value ($value)");
                }
            }
        }
        /**
         * Process the new attribute
         */
        foreach ($postMeta as $key => $value) {
            if (in_array($key, Metadata::FORM_METADATA)) {
                // This meta should be modified via the form
                $messages[] = Message::createInfoMessage("The $persistentMetadataType metadata ($key) can only be added via the meta manager");
                continue;
            }
            if (in_array($key, Metadata::NOT_MODIFIABLE_PERSISTENT_METADATA)) {
                // this meta are not modifiable
                $messages[] = Message::createInfoMessage("The $persistentMetadataType metadata ($key) is a internal metadata, you can't modify it");
                continue;
            }
            $persistentPageMeta[$key] = $value;
            $messages[] = Message::createInfoMessage("The $persistentMetadataType metadata ($key) was created with the value ($value)");
        }

        /**
         * Delete the runtime if present
         * (They were saved in persistent)
         */
        Metadata::deleteIfPresent($persistentPageMeta, Metadata::RUNTIME_META);

        p_save_metadata($page->getDokuwikiId(), $meta);

        if (sizeof($messages) !== 0) {
            $messagesToSend = [];
            foreach ($messages as $message) {
                $messagesToSend[] = $message->getPlainTextContent();
            }
        } else {
            $messagesToSend = "No metadata has been changed.";
        }
        HttpResponse::create(HttpResponse::STATUS_ALL_GOOD)
            ->setEvent($event)
            ->sendMessage($messagesToSend);

    }

    /**
     * @param Page $page
     * @return FormMeta
     */
    static function getFormMetadataForPage(Page $page): FormMeta
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
                ->setCanonical(Analytics::TITLE)
                ->setTab(self::TAB_PAGE_VALUE)
        );

        // H1
        $formMeta->addField(
            FormMetaField::create(Analytics::H1)
                ->addValue($page->getH1(), $page->getDefaultH1())
                ->setTab(self::TAB_PAGE_VALUE)
                ->setCanonical(Analytics::H1)
                ->setDescription("The heading 1 (or H1) is the first heading of your page. It may be used in template to make a difference with the title.")
        );

        // Description
        $formMeta->addField(
            FormMetaField::create(Analytics::DESCRIPTION)
                ->addValue($page->getDescription(), $page->getDescriptionOrElseDokuWiki())
                ->setType(DataType::PARAGRAPH_TYPE_VALUE)
                ->setTab(self::TAB_PAGE_VALUE)
                ->setCanonical(Analytics::DESCRIPTION)
                ->setDescription("The description is a paragraph that describe your page. It's advertised to external application and used in templating.")
        );

        // Keywords


        $formMeta->addField(FormMetaField::create(Page::KEYWORDS_ATTRIBUTE)
            ->addValue(
                action_plugin_combo_metakeywords::toFormValue($page->getKeywords()),
                action_plugin_combo_metakeywords::toFormValue($page->getDefaultKeywords())
            )
            ->setLabel("Keywords")
            ->setMutable(true)
            ->setCanonical(Page::KEYWORDS_ATTRIBUTE)
            ->setTab(self::TAB_PAGE_VALUE)
            ->setDescription("The keywords added to your page (separated by a comma)")
        );

        // Path
        $formMeta->addField(FormMetaField::create(Analytics::PATH)
            ->addValue($page->getPath())
            ->setLabel("Page Path")
            ->setMutable(false)
            ->setCanonical(Analytics::PATH)
            ->setTab(self::TAB_REDIRECTION_VALUE)
            ->setDescription("The path of the page on the file system (in wiki format with the colon `:` as path separator)")
        );

        // Canonical
        $formMeta->addField(
            FormMetaField::create(Analytics::CANONICAL)
                ->addValue($page->getCanonical(), $page->getDefaultCanonical())
                ->setTab(self::TAB_REDIRECTION_VALUE)
                ->setCanonical(Analytics::CANONICAL)
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
                ->setCanonical(Page::SLUG_ATTRIBUTE)
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
                ->setCanonical(Page::LAYOUT_PROPERTY)
                ->setTab(self::TAB_PAGE_VALUE)
                ->setDescription("A layout chooses the layout of your page (such as the slots and placement of the main content)")
        );


        // Modified Date
        $formMeta->addField(FormMetaField::create(Analytics::DATE_MODIFIED)
            ->addValue($page->getModifiedDateAsString())
            ->setMutable(false)
            ->setType(DataType::DATETIME_TYPE_VALUE)
            ->setTab(self::TAB_PAGE_VALUE)
            ->setLabel("Modification Date")
            ->setDescription("The last modification date of the page")
            ->setCanonical(self::METADATA_CANONICAL)
        );

        // Created Date
        $formMeta->addField(FormMetaField::create(Analytics::DATE_CREATED)
            ->addValue($page->getCreatedDateAsString())
            ->setMutable(false)
            ->setType(DataType::DATETIME_TYPE_VALUE)
            ->setTab(self::TAB_PAGE_VALUE)
            ->setCanonical(self::METADATA_CANONICAL)
            ->setLabel("Creation Date")
            ->setDescription("The creation date of the page")
        );


        /**
         * Page Image Properties
         */
        $pageImages = PageImages::createFromPage($page);
        $formMeta->addField($pageImages->toFormField());


        /**
         * Aliases
         */
        $aliases = Aliases::createFromPage($page);
        $formMeta->addField($aliases->toFormField());


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
            ->setType(DataType::DATETIME_TYPE_VALUE)
            ->setTab(self::TAB_TYPE_VALUE)
            ->setCanonical(self::PAGE_TYPE_CANONICAL)
            ->setLabel("Publication Date")
            ->setDescription("The publication date")
        );

        // Start Date
        $formMeta->addField(FormMetaField::create(Analytics::DATE_START)
            ->addValue($page->getStartDateAsString())
            ->setType(DataType::DATETIME_TYPE_VALUE)
            ->setTab(self::TAB_TYPE_VALUE)
            ->setCanonical(Page::EVENT_TYPE)
            ->setLabel("Start Date")
            ->setDescription("The start date of an event")
        );

        // End Date
        $formMeta->addField(FormMetaField::create(Analytics::DATE_END)
            ->addValue($page->getEndDateAsString())
            ->setType(DataType::DATETIME_TYPE_VALUE)
            ->setTab(self::TAB_TYPE_VALUE)
            ->setCanonical(Page::EVENT_TYPE)
            ->setLabel("End Date")
            ->setDescription("The end date of an event")
        );

        // ld-json
        $ldJson = LdJson::createFromPage($page);
        $formFieldLdJson = $ldJson->toFormField();
        $formMeta->addField($formFieldLdJson);


        // Is low quality page
        $formMeta->addField(FormMetaField::create(Page::CAN_BE_LOW_QUALITY_PAGE_INDICATOR)
            // the inverse of the default value is returned if checked - you don't modify a default
            // by default the page can be a low quality
            ->addValue($page->getCanBeOfLowQuality(), false)
            ->setType(DataType::BOOLEAN_TYPE_VALUE)
            ->setTab(self::TAB_QUALITY_VALUE)
            ->setCanonical(LowQualityPage::LOW_QUALITY_PAGE_CANONICAL)
            ->setLabel("Prevent this page to become a low quality page")
            ->setDescription("If checked, this page will never be a low quality page")
        );

        // Quality Monitoring
        $formMeta->addField(FormMetaField::create(action_plugin_combo_qualitymessage::EXECUTE_DYNAMIC_QUALITY_MONITORING_INDICATOR)
            // the inverse of the default value is returned if checked - you don't modify a default
            // by default, the page is monitored
            ->addValue($page->getQualityMonitoringIndicator(), false)
            ->setType(DataType::BOOLEAN_TYPE_VALUE)
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
            ->setType(DataType::DATETIME_TYPE_VALUE)
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

        // Cache cron expiration expression
        $formMeta->addField(FormMetaField::create(CacheManager::META_CACHE_EXPIRATION_FREQUENCY_NAME)
            ->addValue($page->getCacheExpirationFrequency())
            ->setMutable(true)
            ->setTab(self::TAB_CACHE_VALUE)
            ->setCanonical(CacheManager::PAGE_CACHE_EXPIRATION_FREQUENCY_CANONICAL)
            ->setLabel("Cache Expiration Frequency")
            ->setDescription("A page expiration frequency expressed as a cron expression")
        );

        // Cache expiration date
        $cacheExpirationDate = CacheExpirationDate::createForPage($page);
        $formMeta->addField($cacheExpirationDate->toFormField());

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
            )->addTab(
                FormMetaTab::create(self::TAB_CACHE_VALUE)
                    ->setLabel("Cache")
                    ->setWidthLabel(6)
                    ->setWidthField(6)
            );
        return $formMeta;
    }


}
