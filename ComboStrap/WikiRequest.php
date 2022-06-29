<?php

namespace ComboStrap;

class WikiRequest
{


    private ?string $previousGlobalId;
    private ?string $previousAct;
    private ?string $previousRequestedID;

    /**
     * @var string - to check if the requested id was set, if not the running id is set
     */
    private string $requestedId;

    public function __construct()
    {
        /**
         * The running fragment
         */
        global $ID;
        $this->previousGlobalId = $ID;

        /**
         * The requested action
         */
        global $ACT;
        $this->previousAct = $ACT;

        /**
         * The requested Page
         */
        $this->previousRequestedID = PluginUtility::getRequestedWikiId();

    }

    public static function create(): WikiRequest
    {
        return new WikiRequest();
    }

    public function setRunningId(string $runningId): WikiRequest
    {
        global $ID;
        $ID = $runningId;
        if (!isset($this->requestedId)) {
            $this->setGlobalInputRequestedId($runningId);
        }
        return $this;
    }

    public function setRequestedId(string $requestedId): WikiRequest
    {
        $this->requestedId = $requestedId;
        $this->setGlobalInputRequestedId($requestedId);
        return $this;
    }

    public function setAct(string $string): WikiRequest
    {
        global $ACT;
        $ACT = $string;
        return $this;
    }

    public function resetEnvironment()
    {

        global $ACT;
        $ACT = $this->previousAct;
        global $ID;
        $ID = $this->previousGlobalId;
        global $INPUT;
        $INPUT->set(DokuWikiId::DOKUWIKI_ID_ATTRIBUTE, $this->previousRequestedID);

    }

    public function getPreviousRequestedId(): string
    {
        return $this->previousRequestedID;
    }

    /**
     * To be able to set the requested id from the running and requested method
     * @param string $requestedId
     * @return void
     */
    private function setGlobalInputRequestedId(string $requestedId)
    {
        global $INPUT;
        $INPUT->set("id", $requestedId);
    }

}
