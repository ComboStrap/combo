<?php


namespace ComboStrap;


use TestRequest;

class HttpResponse
{
    public const EXIT_KEY = 'exit';


    const MESSAGE_ATTRIBUTE = "message";
    const CANONICAL = "http:response";

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
    private bool $hasEnded = false;


    /**
     * TODO: constructor should be
     */
    private function __construct()
    {
    }

    /**
     * @param int $status
     * @return HttpResponse
     * @deprecated use the {@link ExecutionContext::response()} instead
     * to access the response
     */
    public static function createForStatus(int $status): HttpResponse
    {
        return (new HttpResponse())
            ->setStatus($status);
    }

    public static function createFromException(\Exception $e): HttpResponse
    {
        if (PluginUtility::isDevOrTest()) {
            throw new ExceptionRuntimeInternal($e, self::CANONICAL, 1, $e);
        }
        $httpResponse = HttpResponse::create();
        $message = "<p>{$e->getMessage()}</p>";
        try {
            $status = self::getStatusFromException($e);
            $httpResponse->setStatus($status);
        } catch (ExceptionBadArgument $e) {
            $httpResponse->setStatus(HttpResponseStatus::INTERNAL_ERROR);
            $message = "<p>{$e->getMessage()}</p>$message";
        }
        $httpResponse->setBody($message, Mime::getHtml());
        return $httpResponse;
    }

    /**
     * @throws ExceptionBadArgument
     */
    public static function getStatusFromException(\Exception $e): int
    {
        if ($e instanceof ExceptionNotFound || $e instanceof ExceptionNotExists) {
            return HttpResponseStatus::NOT_FOUND;
        } elseif ($e instanceof ExceptionBadArgument) {
            return HttpResponseStatus::BAD_REQUEST; // bad request
        } elseif ($e instanceof ExceptionBadSyntax) {
            return 415; // unsupported media type
        } elseif ($e instanceof ExceptionBadState || $e instanceof ExceptionInternal) {
            return HttpResponseStatus::INTERNAL_ERROR; //
        }
        throw new ExceptionBadArgument("The exception is unknown.");
    }


    public static function create(): HttpResponse
    {
        return new HttpResponse();
    }

    public static function createFromDokuWikiResponse(\TestResponse $response): HttpResponse
    {
        $statusCode = $response->getStatusCode();
        if ($statusCode === null) {
            $statusCode = HttpResponseStatus::ALL_GOOD;
        }
        $contentType = $response->getHeader("Content-Type");
        $mime = Mime::create($contentType);
        return HttpResponse::createForStatus($statusCode)
            ->setBody($response->getContent(), $mime)
            ->setHeaders($response->getHeaders());
    }


    public function setEvent(\Doku_Event $event): HttpResponse
    {
        $this->event = $event;
        return $this;
    }


    public function end()
    {

        $this->hasEnded = true;

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
                Http::setStatus(HttpResponseStatus::INTERNAL_ERROR);
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
            if ($this->status !== HttpResponseStatus::ALL_GOOD && isset($this->body)) {
                // if this is a 304, there is no body, no message
                LogUtility::log2file("Bad Http Response: $this->status : {$this->getBody()}", LogUtility::LVL_MSG_ERROR, $this->canonical);
            }
            exit;
        } else {

            /**
             * We try to set the dokuwiki processing
             * but it does not work every time
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

            /**
             * In dev or test, we don't exit to get the data, the code execution will come here then
             * but {@link act_dispatch() Act dispatch} calls always the template,
             * We create a fake empty template
             */
            global $conf;
            $template = "combo_test";
            $conf['template'] = $template;
            $main = LocalPath::createFromPathString(DOKU_INC . "lib/tpl/$template/main.php");
            FileSystems::setContent($main, "");


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
     */
    public function getHeaders(string $headerName): array
    {

        return Http::getHeadersForName($headerName, $this->headers);

    }

    /**
     * @throws ExceptionNotFound
     */
    public function getHeader(string $headerName): string
    {
        $headers = $this->getHeaders($headerName);
        if(count($headers)==0){
            throw new ExceptionNotFound("No header found for the name $headerName");
        }
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
            $this->setStatus(HttpResponseStatus::INTERNAL_ERROR);
            $this->setBody($e->getMessage(), Mime::getText());
        }
        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function hasEnded(): bool
    {
        return $this->hasEnded;
    }


}
