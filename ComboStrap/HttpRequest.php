<?php

namespace ComboStrap;


use dokuwiki\ActionRouter;

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


        $url = FetcherPage::createPageFetcherFromId($wikiId)
            ->getFetchUrl();


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

        }

        if (ExecutionContext::getActualOrCreateFromEnv()->response()->hasEnded()) {
            /**
             * As of today, the execution context is responsible to
             * send back the response
             * (There is no routing context, therefore for
             * each http request, a new context needs to be created)
             */
            throw new ExceptionRuntimeInternal("The execution context has ended, you should reopen another one");
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
        $testRequest->setServer('REQUEST_TIME', time());

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
