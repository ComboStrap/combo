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
        $id = $_GET[$parameter];
        if ($id !== null) {
            return $id;
        }
        /**
         * With {@link TestRequest}
         * for instance
         */
        $id = $_REQUEST[$parameter];
        if ($id !== null) {
            return $id;
        }

        throw new ExceptionNotFound("The parameter ($parameter) was not found for this request");

    }
}
