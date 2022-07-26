<?php

namespace ComboStrap;

/**
 * An execution object permits to get access to environment variable.
 *
 *
 * They may be nested with {@link ExecutionContext::createSubExecutionContext()} this is because Dokuwiki use the global variable
 * ID to get the actual parsed markup.
 *
 * When an execution context has finished, it should be {@link ExecutionContext::close() closed}
 *
 * You can get the actual execution context with {@link ExecutionContext::getActualOrCreateFromEnv()}
 *
 *
 * Same concept than [routing context](https://vertx.io/docs/apidocs/index.html?io/vertx/ext/web/RoutingContext.html)
 * (Not yet fully implemented)
 * ```java
 * if (pet.isPresent())
 *    routingContext
 *     .response()
 *      .setStatusCode(200)
 *       .putHeader(HttpHeaders.CONTENT_TYPE, "application/json")
 *      .end(pet.get().encode()); // (4)
 * else
 *    routingContext.fail(404, new Exception("Pet not found"));
 * }
 * ```
 */
class ExecutionContext
{

    /**
     * Dokuwiki do attribute
     */
    const DO_ATTRIBUTE = "do";

    /**
     * @var ExecutionContext - the sub execution context
     */
    private ExecutionContext $childExecutionContext;

    const CANONICAL = "wikiRequest";

    /**
     * @var array of objects that are scoped to this request
     */
    private array $objects;

    private CacheManager $cacheManager;

    /**
     * A root execution context if any
     * Null because you can unset a static variable
     */
    private static ?ExecutionContext $rootExecutionContext = null;

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


    /**
     * @var string - to check if the requested id was set, if not the running id is set
     */
    private string $wikiId;
    private string $act;
    private Url $url;
    private ExecutionContext $parent;

    /**
     * @var string
     */
    private $capturedRequestId;

    public function __construct(Url $url)
    {

        $this->url = $url;

        /**
         * The requested action
         */
        global $ACT;
        $this->capturedAct = $ACT;
        try {
            $urlAct = $url->getQueryPropertyValue(self::DO_ATTRIBUTE);
        } catch (ExceptionNotFound $e) {
            // the default value
            $urlAct = "show";
        }
        $this->act = $urlAct;
        $ACT = $urlAct;

        /**
         * The requested id
         */
        global $ID;
        $this->capturedGlobalId = $ID;
        try {

            $urlId = $url->getQueryPropertyValue(DokuwikiId::DOKUWIKI_ID_ATTRIBUTE);
            $this->wikiId = $urlId;
            $ID = $urlId;

        } catch (ExceptionNotFound $e) {
            // none
        }

        $this->capturedRequestId = self::getRequestedIdViaGlobalVariables();

    }

    public static function getRootOrCreateFromEnv(): ExecutionContext
    {
        try {
            return self::getRootContext();
        } catch (ExceptionNotFound $e) {
            return self::createRootFromEnvironmentVariable();
        }
    }

    /**
     * @throws ExceptionNotFound
     */
    private static function getRootContext(): ?ExecutionContext
    {
        if (!isset(self::$rootExecutionContext)) {
            throw new ExceptionNotFound("No root context");
        }
        return self::$rootExecutionContext;
    }

    /**
     * @param string $requestedId
     * @param string $requestedAct
     * @return ExecutionContext
     */
    public static function createRootFromWikiId(string $requestedId, string $requestedAct = "show"): ExecutionContext
    {
        if (self::$rootExecutionContext !== null) {
            LogUtility::internalError("The root context should be closed first");
        }
        $url = Url::createEmpty()
            ->setQueryParameter(DokuwikiId::DOKUWIKI_ID_ATTRIBUTE, $requestedId)
            ->setQueryParameter(self::DO_ATTRIBUTE, $requestedAct);
        self::$rootExecutionContext = self::createFromUrl($url);
        return self::$rootExecutionContext;

    }

    private static function createFromUrl(Url $url): ExecutionContext
    {
        return new ExecutionContext($url);
    }

    public static function createRootFromEnvironmentVariable(): ExecutionContext
    {
        if (self::$rootExecutionContext !== null) {
            LogUtility::internalError("The root context should be closed first");
        }
        $url = Url::createFromGetOrPostGlobalVariable();
        $rootExecutionContext = self::createFromUrl($url);
        self::$rootExecutionContext = $rootExecutionContext;
        return $rootExecutionContext;

    }

    /**
     * @return ExecutionContext - return the actual context or create one from the environment
     */
    public static function getActualOrCreateFromEnv(): ExecutionContext
    {
        try {
            return self::getActualContext();
        } catch (ExceptionNotFound $e) {
            return self::createRootFromEnvironmentVariable();
        }
    }

    public static function reset()
    {
        self::$rootExecutionContext = null;
    }


    /**
     *
     * @throws ExceptionNotFound - if there is no request
     */
    public static function getActualContext(): ExecutionContext
    {


        $actualContext = self::getRootContext();
        while (isset($actualContext->childExecutionContext)) {
            $actualContext = $actualContext->childExecutionContext;
        }
        return $actualContext;
    }


    /**
     * When a request can be a sub-request, you can use this function.
     * If a request is running a sub-request is created otherwise a request is created
     * @param string $runningId
     * @param string|null $runningAct
     * @return ExecutionContext
     */
    public function createSubExecutionContext(string $runningId, ?string $runningAct = "show"): ExecutionContext
    {

        if ($runningAct === null) {
            $runningAct = "show";
        }

        $subExecutionContext = self::createRootFromWikiId($runningId, $runningAct);
        $subExecutionContext->setParent($this);
        $this->childExecutionContext = $subExecutionContext;

        return $subExecutionContext;

    }


    public function __toString()
    {
        return $this->wikiId;
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
    public function setNewRequestedId(string $requestedId): ExecutionContext
    {
        $this->wikiId = $requestedId;
        global $INPUT;
        $INPUT->set("id", $requestedId);
        return $this;
    }

    public function setNewAct(string $string): ExecutionContext
    {
        throw new ExceptionRuntimeInternal("delete");
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
     * @return string - the wiki id of this context
     * @throws ExceptionNotFound
     */
    public function getWikiId(): string
    {
        if (!isset($this->wikiId)) {
            throw new ExceptionNotFound("No id");
        }
        return $this->wikiId;
    }


    public function getAct()
    {
        if (!isset($this->act)) {
            throw new ExceptionRuntimeInternal("No act for this execution");
        }
        return $this->act;
    }


    /**
     * @return void close the execution context
     */
    public function close()
    {

        if (isset($this->parent)) {

            /**
             * Closing a sub-context
             */
            unset($this->parent->childExecutionContext);


        } else {

            /**
             * Closing a parent
             */
            if (isset($this->childExecutionContext)) {
                throw new ExceptionRuntimeInternal("The child context ($this->childExecutionContext) was not closed", self::CANONICAL);
            }


            /**
             * Restore requested id if any
             * (Not sure if it's needed)
             */
            global $INPUT;
            $INPUT->set(DokuWikiId::DOKUWIKI_ID_ATTRIBUTE, $this->capturedRequestId);

            /**
             * Closing the root context
             */
            self::reset();

        }

        /**
         * Environment restoration
         * Execution context, change for now only this
         * global variables
         */
        global $ACT;
        $ACT = $this->getCapturedAct();
        global $ID;
        $ID = $this->getCapturedRunningId();


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

        $root = self::$rootExecutionContext;
        if (!isset($root->cacheManager)) {
            $root->cacheManager = new CacheManager($this);
        }
        return $root->cacheManager;

    }

    public function getRequestedPath(): WikiPath
    {
        return WikiPath::createPagePathFromId($this->getWikiId());
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
        return $this->url;
    }

    public function getCapturedRequestedId()
    {
        return $this->capturedRequestId;
    }

    private function setParent(ExecutionContext $parent)
    {
        $this->parent = $parent;
    }

    public function isRoot(): bool
    {
        if (!isset($this->parent)) {
            return true;
        } else {
            return false;
        }
    }

    public function setConf(string $key, $value): ExecutionContext
    {
        PluginUtility::setConf($key, $value);
        return $this;
    }

    public function getConfValue(string $key, string $default)
    {
        return PluginUtility::getConfValue($key, $default);
    }


}
