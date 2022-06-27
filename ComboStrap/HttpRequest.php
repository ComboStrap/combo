<?php

namespace ComboStrap;

class HttpRequest
{
    private \TestRequest $request;
    private \TestResponse $response;


    public function __construct()
    {
        $this->request = new \TestRequest();
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

    public static function create(): HttpRequest
    {
        return new HttpRequest();
    }

    public function getDokuwikiTestRequest(): \TestRequest
    {
        return $this->request;
    }

    public function executeForWikiId(string $wikiId): HttpRequest
    {
        $this->response = $this->request->get(["id"=>$wikiId]);
        /**
         * The get method will delete the env
         * We set it back because this is for now how the
         * {@link HttpRequest::purgeStaticDataRequestedScoped() static component}
         * communicate based on the global requested page id
         */
        $this->setRequestIdEnv($wikiId);
        return $this;
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
}
