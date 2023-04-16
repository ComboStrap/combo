<?php

namespace ComboStrap\Api;

use ComboStrap\ExceptionNotFound;
use ComboStrap\ExecutionContext;
use ComboStrap\HttpResponseStatus;
use ComboStrap\Identity;
use ComboStrap\Mime;
use ComboStrap\QualityTag;
use ComboStrap\WikiPath;
use dokuwiki\Extension\Event;

/**
 * Return the quality report in HTML
 */
class QualityMessageHandler
{

    public const CALL_ID = "combo-quality-message";
    public const CANONICAL = "quality:dynamic_monitoring";

    /**
     * Disable the message totally
     */
    public const CONF_DISABLE_QUALITY_MONITORING = "disableDynamicQualityMonitoring";
    /**
     * The quality rules that will not show
     * up in the messages
     */
    public const CONF_EXCLUDED_QUALITY_RULES_FROM_DYNAMIC_MONITORING = "excludedQualityRulesFromDynamicMonitoring";


    public static function handle(Event $event)
    {

        // no other ajax call handlers needed
        $event->stopPropagation();
        $event->preventDefault();

        $executingContext = ExecutionContext::getActualOrCreateFromEnv();

        /**
         * Shared check between post and get HTTP method
         */
        /**
         * Shared check between post and get HTTP method
         */
        try {
            $id = ApiRouter::getRequestParameter("id");
        } catch (ExceptionNotFound $e) {
            $executingContext->response()
                ->setStatus(HttpResponseStatus::BAD_REQUEST)
                ->setEvent($event)
                ->setCanonical(QualityMessageHandler::CANONICAL)
                ->setBody("The page id should not be empty", Mime::getHtml())
                ->end();
            return;
        }

        /**
         * Quality is just for the writers
         */
        if (!Identity::isWriter($id)) {
            $executingContext->response()
                ->setStatus(HttpResponseStatus::NOT_AUTHORIZED)
                ->setEvent($event)
                ->setBody("Quality is only for writer", Mime::getHtml())
                ->end();
            return;
        }


        $markupPath = WikiPath::createMarkupPathFromId($id);
        $message = QualityTag::createQualityReport($markupPath);
        $status = $message->getStatus();


        $executingContext->response()
            ->setStatus($status)
            ->setEvent($event)
            ->setCanonical(QualityMessageHandler::CANONICAL)
            ->setBody($message->getContent(), Mime::getHtml())
            ->end();
    }
}
