<?php


namespace ComboStrap;


use TestRequest;

class HttpResponse
{
    public const EXIT_KEY = 'exit';


    const STATUS_NOT_FOUND = 404;
    public const STATUS_ALL_GOOD = 200;
    const STATUS_NOT_MODIFIED = 304;
    const STATUS_PERMANENT_REDIRECT = 301;
    public const STATUS_DOES_NOT_EXIST = 404;
    public const STATUS_UNSUPPORTED_MEDIA_TYPE = 415;
    public const STATUS_BAD_REQUEST = 400;
    public const STATUS_INTERNAL_ERROR = 500;
    public const STATUS_NOT_AUTHORIZED = 401;
    const MESSAGE_ATTRIBUTE = "message";

    /**
     * @var int
     */
    private $status;

    private $canonical = "support";
    /**
     * @var \Doku_Event
     */
    private $event;
    /**
     * @var array
     */
    private $headers = [];
    private $msg;


    /**
     * Error constructor.
     */
    public function __construct($status, $msg)
    {
        $this->status = $status;
        $this->msg = $msg;
    }

    public static function create(int $status, string $msg = null): HttpResponse
    {
        return new HttpResponse($status, $msg);
    }


    public function setEvent(\Doku_Event $event): HttpResponse
    {
        $this->event = $event;
        return $this;
    }

    public function send($payload = null, $contentType = null)
    {

        if ($contentType != null) {
            Http::setMime($contentType);
        } else {
            Http::setMime(Mime::PLAIN_TEXT);
        }

        // header should before the status
        // because for instance a `"Location` header changes the status to 302
        foreach ($this->headers as $header) {
            header($header);
        }

        if ($this->status !== null) {
            Http::setStatus($this->status);
        } else {
            $status = Http::getStatus();
            if ($status === null) {
                Http::setStatus(self::STATUS_INTERNAL_ERROR);
                LogUtility::log2file("No status was set for this soft exit, the default was set instead", LogUtility::LVL_MSG_ERROR, $this->canonical);
            }
        }


        /**
         * Payload
         */
        if ($payload !== null) {
            echo $payload;
        }

        /**
         * Exit
         */
        if (!PluginUtility::isTest()) {
            if ($this->status !== self::STATUS_ALL_GOOD && $this->msg !== null) {
                // if this is a 304, there is no body, no message
                LogUtility::log2file("Bad Http Response: $this->status : $this->msg", LogUtility::LVL_MSG_ERROR, $this->canonical);
            }
            exit;
        } else {

            /**
             * Stop the propagation and prevent the default
             */
            if ($this->event !== null) {
                $this->event->stopPropagation();
                $this->event->preventDefault();
            }

            /**
             * Add test info into the request
             */
            $testRequest = TestRequest::getRunning();

            if ($testRequest !== null) {
                $testRequest->addData(self::EXIT_KEY, $payload);
            }

            /**
             * Output buffer
             * Stop the buffer
             * Test request starts a buffer at {@link TestRequest::execute()},
             * it will capture the body until this point
             */
            ob_end_clean();
            /**
             * To avoid phpunit warning `Test code or tested code did not (only) close its own output buffers`
             * and
             * Send the output to the void
             */
            ob_start(function ($value) {
            });

        }
    }

    public function setCanonical($canonical): HttpResponse
    {
        $this->canonical = $canonical;
        return $this;
    }


    public function addHeader(string $header): HttpResponse
    {
        $this->headers[] = $header;
        return $this;
    }

    /**
     * @param string|array $messages
     */
    public function sendMessage($messages)
    {
        if (is_array($messages) && sizeof($messages) == 0) {
            $messages = ["No information, no errors"];
        }
        $message = json_encode(["message" => $messages]);
        $this->send($message, Mime::JSON);

    }

}
