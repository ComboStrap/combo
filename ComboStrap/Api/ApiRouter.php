<?php

namespace ComboStrap\Api;

use ComboStrap\ExceptionNotFound;
use dokuwiki\Extension\Event;

class ApiRouter
{

    /**
     * @param Event $event
     * @return void
     */
    public static function handle(Event $event)
    {
        $call = $event->data;
        switch ($call) {
            case QualityMessageHandler::CALL_ID:
                QualityMessageHandler::handle($event);
                return;
            default:
                return;
        }


    }

    /**
     * @throws ExceptionNotFound
     */
    public static function getRequestParameter(string $parameter)
    {
        /**
         * Shared check between post and get HTTP method
         */
        if (array_key_exists($parameter, $_GET)) {
            /**
             * May be null value with boolean
             */
            return $_GET[$parameter];
        }

        /**
         * With {@link TestRequest}
         * for instance
         */
        if (array_key_exists($parameter, $_REQUEST)) {
            return $_REQUEST[$parameter];
        }

        global $INPUT;
        if ($INPUT->has($parameter)) {
            return $INPUT->str($parameter);
        }

        if (defined('DOKU_UNITTEST')) {
            global $COMBO;
            if (array_key_exists($parameter, $COMBO)) {
                return $COMBO[$parameter];
            }
        }

        throw new ExceptionNotFound("The parameter ($parameter) was not found for this request");

    }

    public static function hasRequestParameter(string $parameter): bool
    {
        try {
            self::getRequestParameter($parameter);
            return true;
        } catch (ExceptionNotFound $e) {
            return false;
        }
    }
}
