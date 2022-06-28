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
        // global scope
        MetadataDokuWikiStore::resetAll();
        MetadataDbStore::resetAll();
        // request scope
        CacheManager::reset();
        SnippetManager::reset();
        IdManager::reset();

    }


    public static function fetchPageFragmentAsXhtml(string $wikiId): HttpResponse
    {

        $url = FetcherPageFragment::createPageFragmentFetcherFromId($wikiId)
            ->setRequestedMimeToXhtml()
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

        $query = $this->url->getQuery();
        $testRequest = new \TestRequest();
        $response = $testRequest->get($query);

        $statusCode = $response->getStatusCode();
        if ($statusCode === null) {
            $statusCode = HttpResponse::STATUS_ALL_GOOD;
        }
        $httpResponse = HttpResponse::create($statusCode)
            ->setBody($response->getContent());

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
