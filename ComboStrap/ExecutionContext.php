<?php

namespace ComboStrap;


use http\Exception\RuntimeException;

/**
 * An execution object permits to get access to environment variable.
 *
 *
 * They may be nested with {@link ExecutionContext::startSubExecutionEnv()} this is because Dokuwiki use the global variable
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


    const CANONICAL = "execution-context";
    const SHOW_ACTION =  "show";

    /**
     * @var array of objects that are scoped to this request
     */
    private array $runtimeVariables;

    private CacheManager $cacheManager;

    /**
     * A root execution context if any
     * Null because you can unset a static variable
     */
    private static ?ExecutionContext $executionContext = null;

    /**
     * Dokuwiki set an other global ID when running the parser
     * @var array keep track of the previous running envs to restore them
     */
    private array $previousRunningEnvs = [];
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
    private string $requestedWikiId;
    private string $act;
    private Url $url;


    /**
     * @var string
     */
    private $capturedRequestId;
    private array $capturedConf;
    private bool $isConsoleOn = false;

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
            $this->requestedWikiId = $urlId;
            $ID = $urlId;

        } catch (ExceptionNotFound $e) {
            // none
        }

        $this->capturedRequestId = self::getRequestedIdViaGlobalVariables();


    }



    /**
     * @throws ExceptionNotFound
     */
    public static function getContext(): ?ExecutionContext
    {
        if (!isset(self::$executionContext)) {
            throw new ExceptionNotFound("No root context");
        }
        return self::$executionContext;
    }

    /**
     * @param string $requestedId
     * @param string $requestedAct
     * @return ExecutionContext
     */
    public static function createFromWikiId(string $requestedId, string $requestedAct = "show"): ExecutionContext
    {
        if (self::$executionContext !== null) {
            LogUtility::internalError("The root context should be closed first");
        }
        self::$executionContext = self::createFromWikiId($requestedId, $requestedAct);
        return self::$executionContext;

    }

    private static function createFromUrl(Url $url): ExecutionContext
    {
        return new ExecutionContext($url);
    }

    public static function createFromEnvironmentVariable(): ExecutionContext
    {

        $url = Url::createFromGetOrPostGlobalVariable();
        return self::createRootFromUrl($url);

    }

    private static function createRootFromUrl(Url $url): ExecutionContext
    {
        if (self::$executionContext !== null) {
            LogUtility::internalError("The root context should be closed first");
        }
        $rootExecutionContext = self::createFromUrl($url)
            ->captureRootEnv();
        self::$executionContext = $rootExecutionContext;
        return $rootExecutionContext;

    }

    /**
     * @return ExecutionContext - return the actual context or create one from the environment
     */
    public static function getActualOrCreateFromEnv(): ExecutionContext
    {
        try {
            return self::getContext();
        } catch (ExceptionNotFound $e) {
            return self::createFromEnvironmentVariable();
        }
    }


    public static function setExecutionGlobalVariableToNull()
    {
        self::$executionContext = null;
    }



    /**
     * Dokuwiki uses global variable to advertise an other parse environment
     * That's fucked up but needs to live with it.
     *
     * Because we support also other template than the output {@link FetcherPage}, we need to give back this
     * global variable also. We can't therefore create a stack of environment because if this is Dokuwiki that
     * starts the parse, there is no sub environment created
     *
     * This utility function makes it clear and permits to set back the env with {@link ExecutionContext::closeSubExecutionEnv()}
     *
     * @param string $runningId
     * @param string|null $runningAct - when we run dynamic rendering, the act is set to advertise it (ie {@link MarkupDynamicRender::DYNAMIC_RENDERING}
     * @return ExecutionContext
     */
    public function startSubExecutionEnv(string $runningId, string $runningAct = 'show'): ExecutionContext
    {


        global $ID;
        global $ACT;

        $this->previousRunningEnvs[] = [$ID, $ACT];
        $ID = $runningId;
        $ACT = $runningAct;

        return $this;

    }

    public function closeSubExecutionEnv(): ExecutionContext
    {
        [$previousId, $previousAct] = array_pop($this->previousRunningEnvs);
        global $ID;
        global $ACT;
        $ID = $previousId;
        $ACT = $previousAct;
        return $this;
    }

    /**
     * @throws ExceptionNotFound
     */
    public function getExecutingWikiId(): string
    {
        global $ID;
        if ($ID === null) {
            throw new ExceptionNotFound("No executing id was found");
        }
        return $ID;
    }


    public function __toString()
    {
        return $this->requestedWikiId;
    }


    /**
     * A running id can be a secondary fragment
     * The requested id is the main fragment id (found in the URL)
     *
     * Note that this should be set only once at the request level
     * (ie in test purpose or the fetcher)
     *
     * @param string $requestedId
     * @return $this
     */
    public function setNewRequestedId(string $requestedId): ExecutionContext
    {
        $this->requestedWikiId = $requestedId;
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
    public function getRequestedWikiId(): string
    {
        if (!isset($this->requestedWikiId)) {
            throw new ExceptionNotFound("No id");
        }
        return $this->requestedWikiId;
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

        if (count($this->previousRunningEnvs) > 0) {
            throw new ExceptionRuntimeInternal("All sub execution environment were not closed");
        }
        $this->restoreRootEnv();

        /**
         * Log utility is not yet a conf
         */
        LogUtility::setTestExceptionLevelToDefault();

        /**
         * Restore requested id if any
         * (Not sure if it's needed)
         */
        global $INPUT;
        $INPUT->set(DokuWikiId::DOKUWIKI_ID_ATTRIBUTE, $this->capturedRequestId);

        /**
         * Environment restoration
         * Execution context, change for now only this
         * global variables
         */
        global $ACT;
        $ACT = $this->getCapturedAct();
        global $ID;
        $ID = $this->getCapturedRunningId();

        /**
         * Deleting
         */
        self::setExecutionGlobalVariableToNull();


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

        $root = self::$executionContext;
        if (!isset($root->cacheManager)) {
            $root->cacheManager = new CacheManager($this);
        }
        return $root->cacheManager;

    }

    public function getRequestedPath(): WikiPath
    {
        return WikiPath::createPagePathFromId($this->getRequestedWikiId());
    }

    /**
     * @throws ExceptionNotFound
     */
    public function &getRuntimeObject(string $objectIdentifier)
    {
        if (isset($this->runtimeVariables[$objectIdentifier])) {
            return $this->runtimeVariables[$objectIdentifier];
        }
        throw new ExceptionNotFound("No object $objectIdentifier found");
    }

    public function setRuntimeObject($objectIdentifier, &$object): ExecutionContext
    {
        $this->runtimeVariables[$objectIdentifier] = &$object;
        return $this;
    }

    public function getUrl(): Url
    {
        return $this->url;
    }

    public function getCapturedRequestedId()
    {
        return $this->capturedRequestId;
    }


    /**
     * @param string $key
     * @param $value
     * @param string $namespace - if null, stored in the global conf namespace
     * @return $this
     */
    public function setConf(string $key, $value, string $namespace = 'plugin'): ExecutionContext
    {
        global $conf;
        if ($namespace !== null) {
            $conf[$namespace][PluginUtility::PLUGIN_BASE_NAME][$key] = $value;
        } else {
            $conf[$key] = $value;
        }
        return $this;
    }

    public function getConfValue(string $key, string $default)
    {
        return PluginUtility::getConfValue($key, $default);
    }

    public function setRuntimeBoolean(string $key, bool $b): ExecutionContext
    {
        $this->runtimeVariables[$key] = $b;
        return $this;
    }

    /**
     * @throws ExceptionNotFound
     */
    public function getRuntimeBoolean(string $name): bool
    {
        $var = $this->runtimeVariables[$name];
        if (!isset($var)) {
            throw new ExceptionNotFound("No $name runtime env was found");
        }
        return DataType::toBoolean($var);
    }

    public function setCacheXhtmlOn(): ExecutionContext
    {
        Site::setCacheXhtmlOn();
        return $this;
    }

    public function setConsoleOn(): ExecutionContext
    {
        $this->isConsoleOn = true;
        return $this;
    }

    public function setConsoleOff(): ExecutionContext
    {
        $this->isConsoleOn = false;
        return $this;
    }

    public function setDisablePageFetcher(): ExecutionContext
    {
        $this->setConf(FetcherPage::CONF_ENABLE_AS_SHOW_ACTION, 0);
        return $this;
    }

    /**
     * Capture the environment to be able to restore it close
     * @return ExecutionContext
     */
    private function captureRootEnv(): ExecutionContext
    {
        global $conf;
        $this->capturedConf = $conf;
        return $this;
    }

    /**
     * Restore the configuration
     * @return void
     */
    private function restoreRootEnv()
    {
        global $conf;
        $conf = $this->capturedConf;
    }

    public function isConsoleOn(): bool
    {
        return $this->isConsoleOn;
    }

    public function isPageFetcherEnabledAsShowAction(): bool
    {
        // the non strict equality is needed, we get a string for an unknown reason
        return $this->getConfValue(FetcherPage::CONF_ENABLE_AS_SHOW_ACTION, FetcherPage::CONF_ENABLE_AS_SHOW_ACTION_DEFAULT) == 1;
    }

    public function setEnablePageFetcherAsShowAction(): ExecutionContext
    {
        $this->getConfValue(FetcherPage::CONF_ENABLE_AS_SHOW_ACTION, 1);
        return $this;
    }

    /**
     * Dokuwiki handler name
     * @return array|mixed|string
     */
    public function getExecutingAction()
    {
        global $ACT;
        return $ACT;
    }

    public function setLogExceptionToError(): ExecutionContext
    {
        LogUtility::setTestExceptionLevelToError();
        return $this;
    }


}
