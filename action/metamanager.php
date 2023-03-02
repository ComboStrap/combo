<?php

require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');

use ComboStrap\DataType;
use ComboStrap\ExceptionCompile;
use ComboStrap\ExecutionContext;
use ComboStrap\FormMeta;
use ComboStrap\FormMetaField;
use ComboStrap\HttpResponse;
use ComboStrap\HttpResponseStatus;
use ComboStrap\Identity;
use ComboStrap\Json;
use ComboStrap\LowQualityPageOverwrite;
use ComboStrap\Message;
use ComboStrap\Metadata;
use ComboStrap\MetadataDokuWikiStore;
use ComboStrap\MetadataFormDataStore;
use ComboStrap\MetadataFrontmatterStore;
use ComboStrap\MetadataStoreTransfer;
use ComboStrap\MetaManagerForm;
use ComboStrap\MetaManagerMenuItem;
use ComboStrap\Mime;
use ComboStrap\MarkupPath;
use ComboStrap\PluginUtility;
use ComboStrap\QualityDynamicMonitoringOverwrite;

if (!defined('DOKU_INC')) die();

/**
 *
 * Save metadata that were send by ajax
 */
class action_plugin_combo_metamanager extends DokuWiki_Action_Plugin
{


    const META_MANAGER_CALL_ID = "combo-meta-manager";
    const META_VIEWER_CALL_ID = "combo-meta-viewer";

    const CANONICAL = "meta-manager";


    /**
     * The canonical for the metadata page
     */
    const METADATA_CANONICAL = "metadata";

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
        $executionContext = ExecutionContext::getActualOrCreateFromEnv();
        if (empty($id)) {
            $executionContext
                ->response()
                ->setStatus(HttpResponseStatus::BAD_REQUEST)
                ->setEvent($event)
                ->setCanonical(self::CANONICAL)
                ->setBodyAsJsonMessage("The page path (id form) is empty")
                ->end();
            return;
        }
        $page = MarkupPath::createMarkupFromId($id);
        if (!$page->exists()) {
            $executionContext->response()
                ->setStatus(HttpResponseStatus::DOES_NOT_EXIST)
                ->setEvent($event)
                ->setCanonical(self::CANONICAL)
                ->setBodyAsJsonMessage("The page ($id) does not exist")
                ->end();
            return;
        }

        /**
         * Security
         */
        if (!$page->canBeUpdatedByCurrentUser()) {
            $user = Identity::getUser();
            $executionContext->response()
                ->setStatus(HttpResponseStatus::NOT_AUTHORIZED)
                ->setEvent($event)
                ->setCanonical(self::CANONICAL)
                ->setBodyAsJsonMessage("Not Authorized: The user ($user) has not the `write` permission for the page (:$id).")
                ->end();
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
                        $executionContext
                            ->response()->setStatus(HttpResponseStatus::UNSUPPORTED_MEDIA_TYPE)
                            ->setEvent($event)
                            ->setCanonical(self::CANONICAL)
                            ->setBodyAsJsonMessage("The post content should be in json format")
                            ->end();
                        return;
                    }
                }

                /**
                 * We can't simulate a php://input in a {@link TestRequest}
                 * We set therefore the post
                 */
                if (!PluginUtility::isTest()) {
                    $jsonString = file_get_contents('php://input');
                    try {
                        $_POST = Json::createFromString($jsonString)->toArray();
                    } catch (ExceptionCompile $e) {
                        $executionContext
                            ->response()
                            ->setStatus(HttpResponseStatus::BAD_REQUEST)
                            ->setEvent($event)
                            ->setCanonical(self::CANONICAL)
                            ->setBodyAsJsonMessage("The json payload could not decoded. Error: {$e->getMessage()}")
                            ->end();
                        return;
                    }
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
     * @param MarkupPath $page
     * @param array $post
     */
    private function handleManagerPost($event, MarkupPath $page, array $post)
    {

        $formStore = MetadataFormDataStore::getOrCreateFromResource($page, $post);
        $targetStore = MetadataDokuWikiStore::getOrCreateFromResource($page);

        /**
         * Boolean form field (default values)
         * are not send back by the HTML form
         */
        $defaultBooleanMetadata = [
            LowQualityPageOverwrite::PROPERTY_NAME,
            QualityDynamicMonitoringOverwrite::PROPERTY_NAME
        ];
        $defaultBoolean = [];
        foreach ($defaultBooleanMetadata as $booleanMeta) {
            $metadata = Metadata::getForName($booleanMeta)
                ->setResource($page)
                ->setReadStore($formStore)
                ->setWriteStore($targetStore);
            $defaultBoolean[$metadata::getName()] = $metadata->toStoreDefaultValue();
        }
        $post = array_merge($defaultBoolean, $post);

        /**
         * Processing
         */
        $transfer = MetadataStoreTransfer::createForPage($page)
            ->fromStore($formStore)
            ->toStore($targetStore)
            ->process($post);
        $processingMessages = $transfer->getMessages();


        $responseMessages = [];
        $responseStatus = HttpResponseStatus::ALL_GOOD;
        foreach ($processingMessages as $upsertMessages) {
            $responseMessage = [ucfirst($upsertMessages->getType())];
            $documentationHyperlink = $upsertMessages->getDocumentationHyperLink();
            if ($documentationHyperlink !== null) {
                $responseMessage[] = $documentationHyperlink;
            }
            $responseMessage[] = $upsertMessages->getContent(Mime::PLAIN_TEXT);
            $responseMessages[] = implode(" - ", $responseMessage);
            if ($upsertMessages->getType() === Message::TYPE_ERROR && $responseStatus !== HttpResponseStatus::BAD_REQUEST) {
                $responseStatus = HttpResponseStatus::BAD_REQUEST;
            }
        }

        if (sizeof($responseMessages) === 0) {
            $responseMessages[] = self::SUCCESS_MESSAGE;
        }

        try {
            $frontMatterMessage = MetadataFrontmatterStore::createFromPage($page)
                ->sync();
            $responseMessages[] = $frontMatterMessage->getPlainTextContent();
        } catch (ExceptionCompile $e) {
            $responseMessages[] = $e->getMessage();
        }


        /**
         * Response
         */
        ExecutionContext::getActualOrCreateFromEnv()
            ->response()
            ->setStatus(HttpResponseStatus::ALL_GOOD)
            ->setEvent($event)
            ->setBodyAsJsonMessage($responseMessages)
            ->end();


    }

    /**
     * @param Doku_Event $event
     * @param MarkupPath $page
     */
    private
    function handleManagerGet(Doku_Event $event, MarkupPath $page)
    {
        $formMeta = MetaManagerForm::createForPage($page)->toFormMeta();
        $payload = json_encode($formMeta->toAssociativeArray());
        ExecutionContext::getActualOrCreateFromEnv()
            ->response()
            ->setStatus(HttpResponseStatus::ALL_GOOD)
            ->setEvent($event)
            ->setBody($payload, Mime::getJson())
            ->end();
    }

    /**
     * @param Doku_Event $event
     * @param MarkupPath $page
     */
    private function handleViewerGet(Doku_Event $event, MarkupPath $page)
    {
        if (!Identity::isManager()) {
            ExecutionContext::getActualOrCreateFromEnv()
                ->response()
                ->setStatus(HttpResponseStatus::NOT_AUTHORIZED)
                ->setEvent($event)
                ->setCanonical(self::CANONICAL)
                ->setBodyAsJsonMessage("Not Authorized (managers only)")
                ->end();
            return;
        }
        $metadata = MetadataDokuWikiStore::getOrCreateFromResource($page)->getDataCurrentAndPersistent();
        $persistent = $metadata[MetadataDokuWikiStore::PERSISTENT_METADATA];
        ksort($persistent);
        $current = $metadata[MetadataDokuWikiStore::CURRENT_METADATA];
        ksort($current);
        $form = FormMeta::create("raw_metadata")
            ->addField(
                FormMetaField::create(MetadataDokuWikiStore::PERSISTENT_METADATA, DataType::JSON_TYPE_VALUE)
                    ->setLabel("Persistent Metadata (User Metadata)")
                    ->setTab("persistent")
                    ->setDescription("The persistent metadata contains raw values. They contains the values set by the user and the fixed values such as page id.")
                    ->addValue(json_encode($persistent))
            )
            ->addField(FormMetaField::create(MetadataDokuWikiStore::CURRENT_METADATA, DataType::JSON_TYPE_VALUE)
                ->setLabel("Current (Derived) Metadata")
                ->setTab("current")
                ->setDescription("The current metadata are the derived / calculated / runtime metadata values (extended with the persistent metadata).")
                ->addValue(json_encode($current))
                ->setMutable(false)
            )
            ->toAssociativeArray();

        ExecutionContext::getActualOrCreateFromEnv()
            ->response()
            ->setStatus(HttpResponseStatus::ALL_GOOD)
            ->setEvent($event)
            ->setCanonical(self::CANONICAL)
            ->setBody(json_encode($form), Mime::getJson())
            ->end();

    }

    private function handleViewerPost(Doku_Event $event, MarkupPath $page, array $post)
    {

        $metadataStore = MetadataDokuWikiStore::getOrCreateFromResource($page);
        $actualMeta = $metadataStore->getDataCurrentAndPersistent();

        /**
         * @var Message[]
         */
        $messages = [];
        /**
         * Technically, persistent is a copy of persistent data
         * but on the ui for now, only persistent data can be modified
         */
        $postMeta = json_decode($post[MetadataDokuWikiStore::PERSISTENT_METADATA], true);
        if ($postMeta === null) {
            ExecutionContext::getActualOrCreateFromEnv()
                ->response()
                ->setStatus(HttpResponseStatus::BAD_REQUEST)
                ->setEvent($event)
                ->setBodyAsJsonMessage("The metadata should be in json format")
                ->end();
            return;
        }


        $managedMetaMessageSuffix = "is a managed metadata, you need to use the metadata manager to delete it";

        /**
         * Process the actual attribute
         * We loop only over the persistent metadata
         * that are the one that we want change
         */
        $persistentMetadata = $actualMeta[MetadataDokuWikiStore::PERSISTENT_METADATA];
        foreach ($persistentMetadata as $key => $value) {

            $postMetaValue = null;
            if (isset($postMeta[$key])) {
                $postMetaValue = $postMeta[$key];
                unset($postMeta[$key]);
            }

            if ($postMetaValue === null) {
                if (in_array($key, Metadata::MUTABLE_METADATA)) {
                    $messages[] = Message::createInfoMessage("The metadata ($key) $managedMetaMessageSuffix");
                    continue;
                }
                if (in_array($key, Metadata::NOT_MODIFIABLE_PERSISTENT_METADATA)) {
                    $messages[] = Message::createInfoMessage("The metadata ($key) is a internal metadata, you can't delete it");
                    continue;
                }
                unset($actualMeta[MetadataDokuWikiStore::CURRENT_METADATA][$key]);
                unset($actualMeta[MetadataDokuWikiStore::PERSISTENT_METADATA][$key]);
                $messages[] = Message::createInfoMessage("The metadata ($key) with the value ($value) was deleted");
            } else {
                if ($value !== $postMetaValue) {
                    if (in_array($key, Metadata::MUTABLE_METADATA)) {
                        $messages[] = Message::createInfoMessage("The metadata ($key) $managedMetaMessageSuffix");
                        continue;
                    }
                    if (in_array($key, Metadata::NOT_MODIFIABLE_PERSISTENT_METADATA)) {
                        $messages[] = Message::createInfoMessage("The metadata ($key) is a internal metadata, you can't modify it");
                        continue;
                    }
                    $actualMeta[MetadataDokuWikiStore::CURRENT_METADATA][$key] = $postMetaValue;
                    $actualMeta[MetadataDokuWikiStore::PERSISTENT_METADATA][$key] = $postMetaValue;
                    $messages[] = Message::createInfoMessage("The metadata ($key) was updated to the value ($postMetaValue) - Old value ($value)");
                }
            }
        }
        /**
         * Process the new attribute
         */
        foreach ($postMeta as $key => $value) {
            if (in_array($key, Metadata::MUTABLE_METADATA)) {
                // This meta should be modified via the form
                $messages[] = Message::createInfoMessage("The metadata ($key) can only be added via the meta manager");
                continue;
            }
            if (in_array($key, Metadata::NOT_MODIFIABLE_PERSISTENT_METADATA)) {
                // this meta are not modifiable
                $messages[] = Message::createInfoMessage("The metadata ($key) is a internal metadata, you can't modify it");
                continue;
            }
            $actualMeta[MetadataDokuWikiStore::CURRENT_METADATA][$key] = $value;
            $actualMeta[MetadataDokuWikiStore::PERSISTENT_METADATA][$key] = $value;
            $messages[] = Message::createInfoMessage("The metadata ($key) was created with the value ($value)");
        }


        p_save_metadata($page->getWikiId(), $actualMeta);

        if (sizeof($messages) !== 0) {
            $messagesToSend = [];
            foreach ($messages as $message) {
                $messagesToSend[] = $message->getPlainTextContent();
            }
        } else {
            $messagesToSend = "No metadata has been changed.";
        }
        ExecutionContext::getActualOrCreateFromEnv()
            ->response()
            ->setStatus(HttpResponseStatus::ALL_GOOD)
            ->setEvent($event)
            ->setBodyAsJsonMessage($messagesToSend)
            ->end();

    }


}
