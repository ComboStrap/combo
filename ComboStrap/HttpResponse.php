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
    public const STATUS_BAD_REQUEST = 400; // 422 ?
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

    private string $body;
    private Mime $mime;


    /**
     * TODO: constructor should be
     */
    private function __construct()
    {
    }

    public static function createForStatus(int $status): HttpResponse
    {
        return (new HttpResponse())
            ->setStatus($status);
    }

    public static function createFromException(\Exception $e): HttpResponse
    {
        $httpResponse = HttpResponse::create();
        try {
            $status = self::getStatusFromException($e);
            $httpResponse->setStatus($status);
        } catch (ExceptionBadArgument $e) {
            $httpResponse->setStatus(HttpResponse::STATUS_INTERNAL_ERROR)
                ->setBody($e->getMessage(), Mime::getText());
        }
        return $httpResponse;
    }

    /**
     * @throws ExceptionBadArgument
     */
    public static function getStatusFromException(\Exception $e): int
    {
        if ($e instanceof ExceptionNotFound || $e instanceof ExceptionNotExists) {
            return HttpResponse::STATUS_NOT_FOUND;
        } elseif ($e instanceof ExceptionBadArgument) {
            return HttpResponse::STATUS_BAD_REQUEST; // bad request
        } elseif ($e instanceof ExceptionBadSyntax) {
            return 415; // unsupported media type
        } elseif ($e instanceof ExceptionBadState || $e instanceof ExceptionInternal) {
            return HttpResponse::STATUS_INTERNAL_ERROR; //
        }
        throw new ExceptionBadArgument("The exception is unknown.");
    }


    public static function create(): HttpResponse
    {
        return new HttpResponse();
    }


    public function setEvent(\Doku_Event $event): HttpResponse
    {
        $this->event = $event;
        return $this;
    }


    public function send()
    {

        if (isset($this->mime)) {
            Http::setMime($this->mime->toString());
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
        if (isset($this->body)) {
            echo $this->body;
        }

        /**
         * Exit
         */
        if (!PluginUtility::isTest()) {
            if ($this->status !== self::STATUS_ALL_GOOD && isset($this->body)) {
                // if this is a 304, there is no body, no message
                LogUtility::log2file("Bad Http Response: $this->status : {$this->getBody()}", LogUtility::LVL_MSG_ERROR, $this->canonical);
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

            if ($testRequest !== null && isset($this->body)) {
                $testRequest->addData(self::EXIT_KEY, $this->body);
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
    public function setBodyAsJsonMessage($messages): HttpResponse
    {
        if (is_array($messages) && sizeof($messages) == 0) {
            $messages = ["No information, no errors"];
        }
        $message = json_encode(["message" => $messages]);
        $this->setBody($message, Mime::getJson());
        return $this;
    }


    public function setBody(string $body, Mime $mime): HttpResponse
    {
        $this->body = $body;
        $this->mime = $mime;
        return $this;
    }

    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }


    /**
     * @throws ExceptionNotFound
     */
    public function getHeaders(string $headerName): array
    {
        $results = array();
        foreach ($this->headers as $header) {
            if (substr($header, 0, strlen($headerName) + 1) == $headerName . ':') {
                $results[] = $header;
            }
        }

        if (count($results) === 0) {
            throw new ExceptionNotFound("No header with the name $headerName");
        }
        return $results;

    }

    /**
     * @throws ExceptionNotFound
     */
    public function getHeader(string $headerName): string
    {
        $headers = $this->getHeaders($headerName);
        return $headers[0];

    }

    /**
     * @throws ExceptionNotFound - if the header was not found
     * @throws ExceptionNotExists - if the header value could not be identified
     */
    public function getHeaderValue(string $headerName): string
    {
        $header = $this->getHeader($headerName);
        $positionDoublePointSeparator = strpos($header, ':');
        if ($positionDoublePointSeparator === false) {
            throw new ExceptionNotExists("No value found");
        }
        return trim(substr($header, $positionDoublePointSeparator + 1));
    }

    public function setHeaders(array $headers): HttpResponse
    {
        $this->headers = $headers;
        return $this;
    }

    /**
     * @throws ExceptionBadSyntax
     */
    public function getBodyAsHtmlDom(): XmlDocument
    {
        return XmlDocument::createHtmlDocFromMarkup($this->getBody());
    }

    public function setStatus(int $status): HttpResponse
    {
        $this->status = $status;
        return $this;
    }


    public function setStatusAndBodyFromException(\Exception $e): HttpResponse
    {

        try {
            $this->setStatus(self::getStatusFromException($e));
        } catch (ExceptionBadArgument $e) {
            $this->setStatus(self::STATUS_INTERNAL_ERROR);
            $this->setBody($e->getMessage(), Mime::getText());
        }
        return $this;
    }


}
