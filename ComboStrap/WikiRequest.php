<?php

namespace ComboStrap;

/**
 * A object that permits to set the global state request data
 *
 *
 *
 * We don't restore the global state because this is useless
 * Dokuwiki does not have the notion of request object, all request data are passed via global state
 *
 *
 *   * Dokuwiki uses {@link Cache::useCache()} needs them also therefore if you split the process in two function: useCache
 * and processing, you need to set and unset each time
 *   * Get function may also needs them if the Object cache depends on them
 *   * ...
 *
 * Before executing any request, this function should be call
 */
class WikiRequest
{

    /**
     * @var array - the actual running request of id and act
     */
    private array $runningIds;

    const CANONICAL = "wikiRequest";

    /**
     * @var array of objects that are scoped to this request
     */
    private array $objects;

    private CacheManager $cacheManager;
    /**
     * This array should have only one value at a time
     * @var WikiRequest[]
     */
    private static array $globalRequests = [];

    /**
     * The id used if
     */
    public const DEFAULT_SLOT_ID_FOR_TEST = "test-slot-id";
    private ?string $capturedGlobalId;
    /**
     * It may be an array when preview/save/cancel
     * @var array|string|null
     */
    private $capturedAct;
    private ?string $capturedRequestedID;

    /**
     * @var string - to check if the requested id was set, if not the running id is set
     */
    private string $requestedId;
    private string $requestedAct;

    public function __construct(string $requestedId, string $requestedAct)
    {
        /**
         * The running fragment
         */
        global $ID;
        $this->capturedGlobalId = $ID;
        $ID = $requestedId;

        /**
         * The requested action
         */
        global $ACT;
        $this->capturedAct = $ACT;
        $ACT = $requestedAct;

        $this->createRunningRequest($requestedId, $requestedAct);

        /**
         * The captured requested Id
         */
        $this->capturedRequestedID = $this->getRequestedIdViaGlobalVariables();

        /**
         * The requested id and act
         */
        $this->requestedId = $requestedId;
        $this->requestedAct = $requestedAct;

    }

    public static function createFromRequestId(string $requestedId, string $requestedAct = "show"): WikiRequest
    {

        $wikiRequest = self::$globalRequests[$requestedId];
        if ($wikiRequest === null) {
            try {
                $actualRequest = self::getGlobalRequest();
                // we throw, we should have no request still open, we don't want any state problem, otherwise data may be messed up
                throw new ExceptionRuntimeInternal("The request ($actualRequest) should be closed before running the new request ($requestedId)", self::CANONICAL);
            } catch (ExceptionNotFound $e) {
                $wikiRequest = new WikiRequest($requestedId, $requestedAct);
                self::setGlobalRequest($wikiRequest);
            }
        }
        return $wikiRequest;
    }

    public static function createFromEnvironmentVariable(): WikiRequest
    {
        global $ACT;
        $act = $ACT;
        if ($ACT === null) {
            $act = "show";
        }
        $requestedId = self::getRequestedIdViaGlobalVariables();
        return self::createFromRequestId($requestedId, $act);
    }

    public static function getOrCreateFromEnv(): WikiRequest
    {
        try {
            return self::get();
        } catch (ExceptionNotFound $e) {
            return self::createFromEnvironmentVariable();
        }
    }

    public static function reset()
    {
        self::operateOnGlobalRequest('reset');
    }


    /**
     *
     * @throws ExceptionNotFound - if there is no request
     */
    public static function get(): WikiRequest
    {
        return self::getGlobalRequest();
    }

    /**
     * When a request can be a sub-request, you can use this function.
     * If a request is running a sub-request is created otherwise a request is created
     * @param string $requestedId
     * @return WikiRequest
     */
    public static function createRequestOrSubRequest(string $requestedId): WikiRequest
    {

        try {
            $wikiRequest = self::getGlobalRequest();
            $wikiRequest->createRunningRequest($requestedId, "show");
            return $wikiRequest;
        } catch (ExceptionNotFound $e) {
            return self::createFromRequestId($requestedId);
        }

    }


    public function createRunningRequest(string $runningId, string $runningAct): WikiRequest
    {

        $this->runningIds[] = [$runningId, $runningAct];

        global $ID;
        global $ACT;
        $ID = $runningId;
        $ACT = $runningAct;

        return $this;

    }

    private static function setGlobalRequest(WikiRequest $wikiRequest)
    {
        self::operateOnGlobalRequest('add', $wikiRequest);
    }

    /**
     * A function that permits to debug easily by seeing the operation on the global variable
     * in one place
     * @param string $operation
     * @param WikiRequest|null $wikiRequest
     * @return void
     */
    private static function operateOnGlobalRequest(string $operation, WikiRequest $wikiRequest = null)
    {
        switch ($operation) {
            case 'add':
                self::$globalRequests[$wikiRequest->getRequestedId()] = $wikiRequest;
                break;
            case 'pop':
                unset(self::$globalRequests[$wikiRequest->getRequestedId()]);
                break;
            case 'reset':
                self::$globalRequests = [];
                break;

        }
    }

    /**
     * The actual global request running (there should be only 1 at a time)
     * or not found, if there is any
     * @throws ExceptionNotFound
     */
    private static function getGlobalRequest(): WikiRequest
    {
        $count = count(self::$globalRequests);
        switch ($count) {
            case 0:
                throw new ExceptionNotFound("No actual request");
            case 1:
                return self::$globalRequests[array_key_first(self::$globalRequests)];
            default:
                throw new ExceptionRuntimeInternal("There is more than one global request running ($count)");
        }

    }


    public function __toString()
    {
        return $this->requestedId;
    }


    /**
     * A running id can be a secondary fragment
     * The requested id is the main fragment
     *
     * Note that this should be set only once at the request level
     * (ie in test purpose or the fetcher)
     *
     * @param string $requestedId
     * @return $this
     */
    public function setNewRequestedId(string $requestedId): WikiRequest
    {
        $this->requestedId = $requestedId;
        global $INPUT;
        $INPUT->set("id", $requestedId);
        return $this;
    }

    public function setNewAct(string $string): WikiRequest
    {
        throw new ExceptionRuntimeInternal("delete");
    }

    private function closeRunningRequest($runningIdToClose)
    {
        /** @noinspection PhpUnusedLocalVariableInspection */
        [$actualRunningId, $actualRunningAct] = array_pop($this->runningIds);
        if ($actualRunningId !== $runningIdToClose) {
            throw new ExceptionRuntimeInternal("The actual running id ($actualRunningId) is not the given id to close ($runningIdToClose)", self::CANONICAL);
        }
        try {
            [$previousId, $previousAct] = $this->getLastRunningRequest();
            global $ACT;
            $ACT = $previousAct;
            global $ID;
            $ID = $previousId;
        } catch (ExceptionNotFound $e) {
            global $ACT;
            $ACT = $this->capturedAct;
            global $ID;
            $ID = $this->capturedGlobalId;
        }

    }

    public function getCapturedRequestedId(): string
    {
        return $this->capturedRequestedID;
    }


    private static function getRequestedIdViaGlobalVariables()
    {
        /**
         * {@link getID()} reads the id from the input variable
         *
         * The {@link \action_plugin_combo_lang::load_lang()}
         * set it back right
         */
        global $INPUT;
        $id = $INPUT->str("id");
        if (!empty($id)) {
            return $id;
        }

        /**
         * This should be less used
         * but shows where the requested id is spilled in dokuwiki
         *
         * If the component is in a sidebar, we don't want the ID of the sidebar
         * but the ID of the page.
         */
        global $INFO;
        if ($INFO !== null) {
            $callingId = $INFO['id'];
            if (!empty($callingId)) {
                return $callingId;
            }
        }


        global $ID;
        if ($ID !== null) {
            return $ID;
        }

        /**
         * This is the case with event triggered
         * before DokuWiki such as
         * https://www.dokuwiki.org/devel:event:init_lang_load
         * REQUEST is a mixed of post and get parameters
         */
        global $_REQUEST;
        if (isset($_REQUEST[DokuwikiId::DOKUWIKI_ID_ATTRIBUTE])) {
            return $_REQUEST[DokuwikiId::DOKUWIKI_ID_ATTRIBUTE];
        }

        if (!PluginUtility::isDevOrTest()) {
            // should never happen, we don't throw an exception
            LogUtility::internalError("Internal Error: The requested wiki id could not be determined");
        }

        return self::DEFAULT_SLOT_ID_FOR_TEST;

    }

    /**
     * @return string
     */
    public function getRequestedId(): string
    {
        return $this->requestedId;
    }

    public function getActualAct()
    {
        /** @noinspection PhpUnusedLocalVariableInspection */
        [$id, $act] = $this->getLastRunningRequest();
        return $act;
    }

    public function getActualRunningId(): string
    {
        /** @noinspection PhpUnusedLocalVariableInspection */
        [$id, $act] = $this->getLastRunningRequest();
        return $id;

    }

    /**
     * @param $requestId - control parameter, the request id to close (it should be the current running id or the top request otherwise a error is thrown)
     * @return void close the running id on the stack of running request
     *
     */
    public function close(string $requestId)
    {
        $runningRequestsCount = count($this->runningIds);
        switch ($runningRequestsCount) {
            case 0:
                throw new ExceptionRuntimeInternal("There should be at minimal one running id (ie the requested id)");
            case 1:
                $this->closeRequest($requestId);
                break;
            default:
                $this->closeRunningRequest($requestId);

        }

    }

    private function closeRequest($requestToClose)
    {

        $this->closeRunningRequest($requestToClose);
        if (count($this->runningIds) > 0) {
            $runningIdsNotClosed = implode(", ", $this->runningIds);
            throw new ExceptionRuntimeInternal("The running ids needs to be close before closing the request. Running Id not close ($runningIdsNotClosed)");
        }

        if ($this->requestedId !== $requestToClose) {
            throw new ExceptionRuntimeInternal("The requested id ($this->requestedId) is not the id to close ($requestToClose)");
        }

        // restore state
        global $INPUT;
        $INPUT->set(DokuWikiId::DOKUWIKI_ID_ATTRIBUTE, $this->capturedRequestedID);
        global $ID;
        $ID = $this->capturedGlobalId;
        global $ACT;
        $ACT = $this->capturedAct;

        // delete static object
        self::operateOnGlobalRequest('pop', $this);
    }

    /**
     * @throws ExceptionNotFound
     */
    private function getLastRunningRequest()
    {
        $lastKey = array_key_last($this->runningIds);
        if ($lastKey === null) {
            throw new ExceptionNotFound("No running requests was found");
        }
        return $this->runningIds[$lastKey];
    }

    /**
     * @return string
     */
    public function getRequestedAct(): string
    {
        return $this->requestedAct;
    }

    public function getCapturedRunningId(): ?string
    {
        return $this->capturedGlobalId;
    }

    public function getCapturedAct()
    {
        return $this->capturedAct;
    }

    public function getCacheManager(): CacheManager
    {
        if (!isset($this->cacheManager)) {
            $this->cacheManager = new CacheManager($this);
        }
        return $this->cacheManager;


    }

    public function getRequestedPath(): WikiPath
    {
        return WikiPath::createPagePathFromId($this->getRequestedId());
    }

    /**
     * @throws ExceptionNotFound
     */
    public function &getObject(string $objectIdentifier)
    {
        if (isset($this->objects[$objectIdentifier])) {
            return $this->objects[$objectIdentifier];
        }
        throw new ExceptionNotFound("No object $objectIdentifier found");
    }

    public function setObject($objectIdentifier, &$object)
    {
        $this->objects[$objectIdentifier] = &$object;
    }

    public function getUrl(): Url
    {
        try {
            return Url::createFromGetOrPostGlobalVariable();
        } catch (ExceptionBadArgument $e) {
            throw new ExceptionRuntimeInternal("Error while creating the request url");
        }
    }


}
