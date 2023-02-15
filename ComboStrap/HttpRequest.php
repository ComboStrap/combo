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
     * Static data store are with php request scoped
     * but in test, this may bring inconsistency
     */
    public static function purgeStaticDataRequestedScoped()
    {
        // The top context object
        ExecutionContext::setExecutionGlobalVariableToNull();

        /**
         * TODO: They should be incorporated in the root execution context as object
         */
        // global scope
        MetadataDbStore::resetAll();
        // global variable
        global $TOC;
        unset($TOC);

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
     * Set the environment for the {@link PluginUtility::getRequestedWikiId()}
     * @param string $wikiId
     */
    private function setRequestIdEnv(string $wikiId)
    {

        global $INPUT;
        $INPUT->set("id", $wikiId);
        global $INFO;
        if ($INFO !== null) {
            $INFO['id'] = $wikiId;
        }

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


        try {
            $path = $this->url->getPath();
            if (!in_array($path, UrlEndpoint::DOKU_ENDPOINTS)) {
                throw new ExceptionRuntime("The url path is not a doku endpoint path");
            }
        } catch (ExceptionNotFound $e) {
            throw new ExceptionRuntime("The path is mandatory");
        }

        HttpRequest::purgeStaticDataRequestedScoped();

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


        $httpResponse = HttpResponse::createFromDokuWikiResponse($response);

        try {
            /**
             * The get method will delete the env
             * We set it back because this is for now how the
             * {@link HttpRequest::purgeStaticDataRequestedScoped() static component}
             * communicate based on the global requested page id
             */
            $wikiId = $this->url->getPropertyValue(DokuwikiId::DOKUWIKI_ID_ATTRIBUTE);
            $this->setRequestIdEnv($wikiId);
        } catch (ExceptionNotFound $e) {
            // no wiki id
        }

        return $httpResponse;
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


}
