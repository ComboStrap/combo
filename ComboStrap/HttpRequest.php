<?php

namespace ComboStrap;

use http\Env\Request;
use http\Exception\RuntimeException;

/**
 * A request to the application
 * It's now a wrapper around {@link \TestRequest}
 */
class HttpRequest
{

    const CANONICAL = "httpRequest";
    private bool $withTestRequest = true;

    private Url $url;
    private HttpResponse $response;


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
        MetadataDokuWikiStore::resetAll();
        MetadataDbStore::resetAll();
        // request scope
        IdManager::reset();
        // global variable
        global $TOC;
        unset($TOC);

    }


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

    private static function createRequest(Url $url): HttpRequest
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

    public function fetch(): HttpResponse
    {

        if (!$this->withTestRequest) {
            throw new RuntimeException("Real HTTP fetch not yet implemented, only test fetch");
        }

        $query = $this->url->getQueryProperties();

        HttpRequest::purgeStaticDataRequestedScoped();

        $testRequest = new \TestRequest();
        $response = $testRequest->get($query);

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
}
