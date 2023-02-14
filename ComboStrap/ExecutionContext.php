<?php

namespace ComboStrap;


use action_plugin_combo_docustom;
use dokuwiki\Extension\PluginTrait;
use TestRequest;


/**
 * An execution object permits to get access to environment variable.
 *
 * Note that normally every page has a page context
 * meaning that you can go from an admin page to show the page.
 *
 *
 * When an execution context has finished, it should be {@link ExecutionContext::close() closed}
 * or destroyed
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

    /**
     * All action (handler)
     * That's what you will found in the `do` parameters
     */
    const SHOW_ACTION = "show";
    const EDIT_ACTION = "edit";
    const PREVIEW_ACTION = "preview";
    const ADMIN_ACTION = "admin";
    const DRAFT_ACTION = "draft";
    const SEARCH_ACTION = "search";
    const LOGIN_ACTION = "login";
    const SAVE_ACTION = "save";
    const DRAFT_DEL_ACTION = "draftdel";
    const REDIRECT_ACTION = "redirect";

    // private actions does not render a page to be indexed
    // by a search engine (ie no redirect)
    const PRIVATES_ACTION_NO_REDIRECT = [
        self::EDIT_ACTION,
        self::PREVIEW_ACTION,
        self::ADMIN_ACTION,
        self::DRAFT_ACTION,
        self::DRAFT_DEL_ACTION,
        self::SEARCH_ACTION,
        self::LOGIN_ACTION,
        self::SAVE_ACTION,
        self::REDIRECT_ACTION,
        self::REGISTER_ACTION,
        self::RESEND_PWD_ACTION,
        self::PROFILE_ACTION,
    ];
    const REGISTER_ACTION = "register";
    const RESEND_PWD_ACTION = "resendpwd";
    const PROFILE_ACTION = "profile";


    /**
     * @var array - the configuration value to restore
     *
     * Note we can't capture the whole global $conf
     * because the configuration are loaded at runtime via {@link PluginTrait::loadConfig()}
     *
     * Meaning that the configuration environment at the start is not fully loaded
     * and does not represent the environment totally
     *
     * We capture then the change and restore them at the end
     */
    private array $configurationValuesToRestore = [];

    /**
     * @var array of objects that are scoped to this request
     */
    private array $executionScopedVariables = [];

    private CacheManager $cacheManager;

    /**
     * A root execution context if any
     * Null because you can unset a static variable
     */
    private static ?ExecutionContext $executionContext = null;

    /**
     * The id used if
     * @deprecated to delete
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

    private Url $url;


    /**
     * @var string
     */
    private $capturedRequestId;
    private array $capturedConf;
    private bool $isConsoleOn = false;
    private HttpResponse $response;
    private IFetcher $executingMainFetcher;

    /**
     * @var FetcherMarkup  - the fetcher markup actually runnning
     */
    private FetcherMarkup $executingFetcherMarkup;

    /**
     * @var WikiPath the {@link self::getContextPath()} when no context could be determined
     */
    private WikiPath $defaultContextPath;
    /**
     * @var mixed|string
     */
    private $oldAct;
    private string $oldId;
    private PageTemplate $executingPageTemplate;

    public function __construct(Url $url)
    {

        $this->url = $url;

        $this->response = HttpResponse::createFromExecutionContext($this);

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
     * Utility class to set the requested id (used only in test,
     * normally the environment is set from global PHP environment variable
     * that get the HTTP request
     * @param string $requestedId
     * @return ExecutionContext
     * @deprecated use {@link self::setDefaultContextPath()} if you want to set a context path
     * without using a {@link PageTemplate} or {@link FetcherMarkup}
     */
    public static function getOrCreateFromRequestedWikiId(string $requestedId): ExecutionContext
    {

        return self::getActualOrCreateFromEnv()
            ->setDefaultContextPath(WikiPath::createMarkupPathFromId($requestedId));

    }


    public static function createFromEnvironmentVariable(): ExecutionContext
    {

        $url = Url::createFromGetOrPostGlobalVariable();
        return self::createFromUrl($url);

    }

    private static function createFromUrl(Url $url): ExecutionContext
    {
        if (self::$executionContext !== null) {
            LogUtility::internalError("The root context should be closed first");
        }
        $rootExecutionContext = (new ExecutionContext($url));
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

    /**
     * Return the actual context path
     */
    public function getContextNamespacePath(): WikiPath
    {
        $requestedPath = $this->getContextPath();
        try {
            return $requestedPath->getParent();
        } catch (ExceptionNotFound $e) {
            // root
            return $requestedPath;
        }

    }


    public static function setExecutionGlobalVariableToNull()
    {
        self::$executionContext = null;
    }


    /**
     * @throws ExceptionNotFound
     */
    public function getExecutingWikiId(): string
    {
        global $ID;
        if (empty($ID)) {
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
     * Note that
     */
    public function getRequestedWikiId(): string
    {
        if (!isset($this->requestedWikiId)) {
            throw new ExceptionNotFound("No requested id was found in the context");
        }
        return $this->requestedWikiId;
    }


    /**
     * @return void close the execution context
     */
    public function close()
    {

        $this->restoreEnv();

        unset($this->executingMainFetcher);
        unset($this->executingFetcherMarkup);
        unset($this->executionScopedVariables);
        unset($this->cacheManager);

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
        return $this->getContextPath();
    }

    /**
     * @throws ExceptionNotFound
     */
    public function &getRuntimeObject(string $objectIdentifier)
    {
        if (isset($this->executionScopedVariables[$objectIdentifier])) {
            return $this->executionScopedVariables[$objectIdentifier];
        }
        throw new ExceptionNotFound("No object $objectIdentifier found");
    }

    public function setRuntimeObject($objectIdentifier, &$object): ExecutionContext
    {
        $this->executionScopedVariables[$objectIdentifier] = &$object;
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
     * @param string|null $pluginNamespace - if null, stored in the global conf namespace
     * @return $this
     */
    public function setConf(string $key, $value, ?string $pluginNamespace = PluginUtility::PLUGIN_BASE_NAME): ExecutionContext
    {
        /**
         * Environment within dokuwiki is a global variable
         *
         * We set it the global variable
         *
         * but we capture it {@link ExecutionContext::$capturedConf}
         * to restore it when the execution context os {@link ExecutionContext::close()}
         */
        $globalKey = "$pluginNamespace:$key";
        if (!isset($this->configurationValuesToRestore[$globalKey])) {
            $oldValue = Site::getConfValue($key, $value, $pluginNamespace);
            $this->configurationValuesToRestore[$globalKey] = $oldValue;
        }
        Site::setConf($key, $value, $pluginNamespace);
        return $this;
    }

    public function getConfValue(string $key, string $default = null)
    {
        return Site::getConfValue($key, $default);
    }

    public function setRuntimeBoolean(string $key, bool $b): ExecutionContext
    {
        $this->executionScopedVariables[$key] = $b;
        return $this;
    }

    /**
     * @throws ExceptionNotFound
     */
    public function getRuntimeBoolean(string $name): bool
    {
        $var = $this->executionScopedVariables[$name];
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

    /**
     *
     * @return $this
     */
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

    public function setDisableTemplating(): ExecutionContext
    {
        $this->setConf(action_plugin_combo_docustom::CONF_ENABLE_FRONT_SYSTEM, 0);
        return $this;
    }


    /**
     * Restore the configuration
     * @return void
     */
    private function restoreEnv()
    {

        foreach ($this->configurationValuesToRestore as $guid => $value) {
            [$plugin, $confKey] = explode(":", $guid);
            Site::setConf($confKey, $value, $plugin);
        }
    }

    public function isConsoleOn(): bool
    {
        return $this->isConsoleOn;
    }

    public function isPageFetcherEnabledAsShowAction(): bool
    {
        // the non strict equality is needed, we get a string for an unknown reason
        return $this->getConfValue(action_plugin_combo_docustom::CONF_ENABLE_FRONT_SYSTEM, action_plugin_combo_docustom::CONF_ENABLE_FRONT_SYSTEM_DEFAULT) == 1;
    }

    public function setEnablePageFetcherAsShowAction(): ExecutionContext
    {
        $this->getConfValue(action_plugin_combo_docustom::CONF_ENABLE_FRONT_SYSTEM, 1);
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

    public function getSnippetSystem(): SnippetSystem
    {
        return SnippetSystem::getFromContext();
    }

    /**
     * @return bool - does the action create a publication (render a page)
     */
    public function isPublicationAction(): bool
    {

        try {
            $this->getRequestedWikiId();
        } catch (ExceptionNotFound $e) {
            return false;
        }

        $act = $this->getExecutingAction();
        if (in_array($act, self::PRIVATES_ACTION_NO_REDIRECT)) {
            return false;
        }

        return true;

    }

    public function setEnableSectionEditing(): ExecutionContext
    {
        $this->setConf('maxseclevel', 999, null);
        return $this;
    }

    public function setCanonicalUrlType(string $value): ExecutionContext
    {
        $this->setConf(PageUrlType::CONF_CANONICAL_URL_TYPE, $value);
        return $this;
    }

    public function setUseHeadingAsTitle(): ExecutionContext
    {
        // https://www.dokuwiki.org/config:useheading
        $this->setConf('useheading', 1, null);
        return $this;
    }

    public function response(): HttpResponse
    {
        return $this->response;
    }

    private function setExecutingId(string $executingId): ExecutionContext
    {
        global $ID;
        $ID = $executingId;
        return $this;
    }

    public function setConfGlobal(string $key, string $value): ExecutionContext
    {
        $this->setConf($key, $value, null);
        return $this;
    }

    /**
     * @return bool - if this execution is a test running
     */
    public function isTestRun(): bool
    {
        /**
         * Test Requested is loaded only in a test run
         * Does not exist in a normal installation
         * and is not found, triggering an exception
         */
        if (class_exists('TestRequest')) {
            $testRequest = TestRequest::getRunning();
            return $testRequest !== null;
        }
        return false;

    }

    private function setExecutingAction(?string $runningAct): ExecutionContext
    {
        global $ACT;
        $ACT = $runningAct;
        return $this;
    }


    /**
     * Set the main fetcher, the entry point of the request (ie the url of the browser)
     * that will return a string
     * @throws ExceptionBadArgument
     * @throws ExceptionInternal
     * @throws ExceptionNotFound
     */
    public function createStringMainFetcherFromRequestedUrl(Url $fetchUrl): IFetcherString
    {
        $this->executingMainFetcher = FetcherSystem::createFetcherStringFromUrl($fetchUrl);
        return $this->executingMainFetcher;
    }


    /**
     * Set the main fetcher (with the url of the browser)
     * that will return a path (image, ...)
     * @throws ExceptionBadArgument
     * @throws ExceptionInternal
     * @throws ExceptionNotFound
     */
    public function createPathMainFetcherFromUrl(Url $fetchUrl): IFetcherPath
    {
        $this->executingMainFetcher = FetcherSystem::createPathFetcherFromUrl($fetchUrl);
        return $this->executingMainFetcher;
    }

    public function closeMainExecutingFetcher(): ExecutionContext
    {
        unset($this->executingMainFetcher);
        return $this;
    }

    /**
     * This function sets the markup running context object globally,
     * so that code may access it via this global variable
     * (Fighting dokuwiki global scope)
     * @param FetcherMarkup $fetcherMarkup
     * @return $this
     */
    public function setExecutingFetcherMarkup(FetcherMarkup $fetcherMarkup): ExecutionContext
    {
        if (isset($this->executingFetcherMarkup)) {
            throw new ExceptionRuntimeInternal("Two fetcher markups cannot run at the same time");
        }

        /**
         * Act
         */
        $this->oldAct = $this->getExecutingAction();
        if ($fetcherMarkup->isMarkupStringExecution()) {
            $runningAct = FetcherMarkup::MARKUP_DYNAMIC_EXECUTION_NAME;
            $this->setExecutingAction($runningAct);
        }

        /**
         * Id
         */
        try {
            $this->oldId = $this->getExecutingWikiId();
        } catch (ExceptionNotFound $e) {
            $this->oldId = null;
        }
        try {

            $executingPath = $fetcherMarkup->getRequestedExecutingPath();
            $executingId = $executingPath->toQualifiedId();
            $this->setExecutingId($executingId);

            /**
             * Fragment run
             */
            if ($fetcherMarkup->isFragmentExecution()) {
                global $INFO;
                $contextPath = $fetcherMarkup->getRequestedContextPath();
                $INFO['id'] = $contextPath->getWikiId();
            }

        } catch (ExceptionNotFound $e) {
            // no executing path dynamic markup execution
        }

        $this->executingFetcherMarkup = $fetcherMarkup;
        return $this;
    }

    public function closeRunningFetcherMarkup(): ExecutionContext
    {
        unset($this->executingFetcherMarkup);
        $this->setExecutingAction($this->oldAct);
        unset($this->oldAct);
        $this->setExecutingId($this->oldId);
        unset($this->oldId);
        global $INFO;
        unset($INFO['id']);
        return $this;
    }


    /**
     * @throws ExceptionNotFound - if there is no fetcher markup execution running
     */
    public function getExecutingFetcherMarkup(): FetcherMarkup
    {
        if (isset($this->executingFetcherMarkup)) {
            return $this->executingFetcherMarkup;
        }
        throw new ExceptionNotFound("No markup execution running");
    }

    /**
     * This function sets the default context path.
     *
     * Mostly used in test, to determine relative path
     * when testing {@link LinkMarkup} and {@link WikiPath}
     * and not use a {@link FetcherMarkup}
     *
     * @param WikiPath $contextPath - a markup file context path used (not a namespace)
     * @return $this
     */
    public function setDefaultContextPath(WikiPath $contextPath): ExecutionContext
    {
        $this->defaultContextPath = $contextPath;
        if (FileSystems::isDirectory($this->defaultContextPath)) {
            /**
             * Not a directory.
             *
             * If the link or path is the empty path, the path is not the directory
             * but the actual markup
             */
            throw new ExceptionRuntimeInternal("The path ($contextPath) should not be a namespace path");
        }
        return $this;
    }


    /**
     * @return WikiPath - the context path is a markup file that gives context.
     * Ie this is the equivalent of the current directory.
     * When a link/path is empty or relative, the program will check for the context path
     * to calculate the absolute path
     */
    public function getContextPath(): WikiPath
    {
        try {
            /**
             * Do we have a template page executing ?
             */
            return $this->getExecutingPageTemplate()
                ->getRequestedContextPath();
        } catch (ExceptionNotFound $e) {
            try {
                /**
                 * Do we a fetcher markup running ?
                 */
                return $this
                    ->getExecutingFetcherMarkup()
                    ->getRequestedContextPath();
            } catch (ExceptionNotFound $e) {

                /**
                 * Nope ? This is a dokuwiki run (admin page, ...)
                 */
                return $this->getDefaultContextPath();

            }

        }


    }

    public function getDefaultContextPath(): WikiPath
    {
        if (isset($this->defaultContextPath)) {
            return $this->defaultContextPath;
        }
        // in a admin or dynamic rendering
        // dokuwiki may have set a $ID
        global $ID;
        if (isset($ID) && $ID !== self::DEFAULT_SLOT_ID_FOR_TEST) {
            return WikiPath::createMarkupPathFromId($ID);
        }
        return WikiPath::createRootNamespacePathOnMarkupDrive()->resolve(Site::getIndexPageName() . "." . WikiPath::MARKUP_DEFAULT_TXT_EXTENSION);


    }

    /**
     * The page global context object
     * @throws ExceptionNotFound
     */
    public function getExecutingPageTemplate(): PageTemplate
    {
        if (isset($this->executingPageTemplate)) {
            return $this->executingPageTemplate;
        }
        throw new ExceptionNotFound("No page template execution running");
    }

    /**
     * Set the page template that is executing.
     * It's the context object for all page related
     * (mostly header event)
     * @param PageTemplate $pageTemplate
     * @return $this
     */
    public function setExecutingPageTemplate(PageTemplate $pageTemplate): ExecutionContext
    {
        $this->executingPageTemplate = $pageTemplate;
        return $this;
    }

    public function closeExecutingPageTemplate(PageTemplate $pageTemplate): ExecutionContext
    {
        $this->executingPageTemplate = $pageTemplate;
        return $this;
    }

}
