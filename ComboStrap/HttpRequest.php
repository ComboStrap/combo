<?php

namespace ComboStrap;


/**
 * A request to the application
 * It's now a wrapper around {@link \TestRequest}
 */
class HttpRequest
{

    const CANONICAL = "httpRequest";
    const POST = "post";
    const GET = "get";
    private bool $withTestRequest = true;

    private Url $url;
    private HttpResponse $response;
    private string $method = self::GET;
    private bool $asAdmin = false;
    private array $postData = [];


    public function __construct(Url $url)
    {
        $this->url = $url;


    }


    /**
     * @param string $wikiId
     * @return HttpResponse
     * With the path uri: '/doku.php'
     */
    public static function fetchXhtmlPageResponse(string $wikiId): HttpResponse
    {

        try {
            $url = FetcherPage::createPageFetcherFromId($wikiId)
                ->getFetchUrl();
        } catch (ExceptionNotFound $e) {
            throw new ExceptionRuntimeInternal("Cannot create the fetch url", self::CANONICAL, 1, $e);
        }

        return HttpRequest::createRequest($url)
            ->withTestRequest()
            ->fetch();

    }


    private function withTestRequest(): HttpRequest
    {
        $this->withTestRequest = true;
        return $this;
    }

    public static function createRequest(Url $url): HttpRequest
    {
        return new HttpRequest($url);
    }


    /**
     * @param array $data - data post body as if it was from a form
     * @return $this
     */
    public function post(array $data = array()): HttpRequest
    {
        $this->method = self::POST;
        $this->postData = $data;
        return $this;
    }


    public function fetch(): HttpResponse
    {
        if (!$this->withTestRequest) {

            throw new ExceptionRuntime("Real HTTP fetch not yet implemented, only test fetch");

        } else {

            /**
             * Close the previous executing environment if any
             */
            ExecutionContext::getActualOrCreateFromEnv()->close();

        }


        try {
            $path = $this->url->getPath();
            if (!in_array($path, UrlEndpoint::DOKU_ENDPOINTS)) {
                throw new ExceptionRuntime("The url path is not a doku endpoint path");
            }
        } catch (ExceptionNotFound $e) {
            throw new ExceptionRuntime("The path is mandatory");
        }

        $testRequest = new \TestRequest();

        if ($this->asAdmin) {
            Identity::becomeSuperUser($testRequest);
        }

        switch ($this->method) {
            case self::GET:
                $query = $this->url->getQueryProperties();
                $response = $testRequest->get($query, $path);
                break;
            case self::POST:
                $query = $this->url->getQueryProperties();
                foreach ($query as $queryKey => $queryValue) {
                    $testRequest->setGet($queryKey, $queryValue);
                }
                $response = $testRequest->post($this->postData, $path);
                break;
            default:
                throw new ExceptionRuntime("The method ({$this->method}) is not implemented");
        }


        return HttpResponse::createFromDokuWikiResponse($response);
    }

    public function asAdmin(): HttpRequest
    {
        $this->asAdmin = true;
        return $this;
    }

    public function get(): HttpRequest
    {
        $this->method = self::GET;
        return $this;
    }

    public function fetchAndExcuteBodyAsHtml(int $waitTimeInSecondToComplete = 0): HttpResponse
    {
        return $this->fetch()->executeBodyAsHtmlPage($waitTimeInSecondToComplete);
    }


}
