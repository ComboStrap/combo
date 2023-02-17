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

    /**
     * private actions does not render a page to be indexed
     * by a search engine (ie no redirect)
     * May be easier, if not `show`, not public
     */
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
     * @var array of objects that are scoped to this request
     */
    private array $executionScopedVariables = [];

    private CacheManager $cacheManager;

    private Site $app;

    /**
     * A root execution context if any
     * Null because you can unset a static variable
     */
    private static ?ExecutionContext $executionContext = null;

    /**
     * The id used if
     * @deprecated to delete
     */
    public const DEFAULT_SLOT_ID_FOR_TEST = "start";
    private ?string $capturedGlobalId;
    /**
     * It may be an array when preview/save/cancel
     * @var array|string|null
     */
    private $capturedAct;


    private Url $url;


    private HttpResponse $response;

    /**
     * @var IFetcher - the fetcher that takes into account the HTTP request
     */
    private IFetcher $executingMainFetcher;

    /**
     * @var array - a stack of:
     *   * markup handler executing (ie handler that is taking a markup (file, string) and making it a HTML, pdf, ...)
     *   * and old global environement, $executingId, $contextExecutingId, $act
     *
     * This fetcher is called by the main fetcher or by the {@link self::setExecutingMarkupHandler()}
     */
    private array $executingMarkupHandlerStack = [];

    /**
     * @var PageTemplate - the page template fetcher running (when a fetcher creates a page, it would uses this fetcher)
     * This class is called by the main fetcher to create a page
     */
    private PageTemplate $executingPageTemplate;



    public function __construct()
    {

        $this->url = Url::createFromGetOrPostGlobalVariable();

        $this->response = HttpResponse::createFromExecutionContext($this);

        /**
         * The requested action
         */
        global $ACT;
        $this->capturedAct = $ACT;
        try {
            $urlAct = $this->url->getQueryPropertyValue(self::DO_ATTRIBUTE);
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

            $urlId = $this->url->getQueryPropertyValue(DokuwikiId::DOKUWIKI_ID_ATTRIBUTE);
            if (is_array($urlId)) {
                /**
                 * hack because the dokuwiki request global object as `ID` and `id` as array
                 * but our own {@link Url object} don't allow that and makes an array instead
                 * We don't use this data anyway, anymore ...
                 */
                $urlId = $urlId[0];
            }
            $ID = $urlId;

        } catch (ExceptionNotFound $e) {
            // none
        }


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


    /**
     * @return ExecutionContext
     * @deprecated uses {@link self::createBlank()} instead
     */
    public static function createFromEnvironmentVariable(): ExecutionContext
    {
        return self::createBlank();
    }


    public static function createBlank(): ExecutionContext
    {

        if (self::$executionContext !== null) {
            LogUtility::internalError("The previous root context should be closed first");
        }
        $rootExecutionContext = (new ExecutionContext());
        self::$executionContext = $rootExecutionContext;
        return $rootExecutionContext;

    }

    /**
     * @return ExecutionContext - return the actual context or create a new one from the environment
     */
    public static function getActualOrCreateFromEnv(): ExecutionContext
    {
        try {
            return self::getContext();
        } catch (ExceptionNotFound $e) {
            return self::createBlank();
        }
    }

    /**
     * We create the id manager in the execution
     * context
     * (because in case a user choose to not use templating, the {@link FetcherMarkup}
     * is not available)
     * And all dynamic component such as {@link \syntax_plugin_combo_dropdown} would not
     * work anymore.
     *
     * @return IdManager
     */
    public function getIdManager(): IdManager
    {
        if (!isset($this->idManager)) {
            $this->idManager = new IdManager($this);
        }
        return $this->idManager;
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


    /**
     * @return void close the execution context
     */
    public function close()
    {

        $this->getApp()->getConfig()->restoreConfigState();

        unset($this->executingMainFetcher);
        unset($this->executingMarkupHandlerStack);
        unset($this->executionScopedVariables);
        unset($this->cacheManager);
        unset($this->idManager);

        /** global dokuwiki messages variable */
        global $MSG;
        unset($MSG);


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

    /**
     *
     * @throws ExceptionNotFound - no page rendering requested
     *
     */
    public function getRequestedPath(): WikiPath
    {
        /**
         * Do we have a template page executing ?
         */
        try {
            return $this->getExecutingPageTemplate()
                ->getRequestedContextPath();
        } catch (ExceptionNotFound $e) {
            try {
                /**
                 * Case when the main handler
                 * run the main content before
                 * to inject it in the template page
                 * {@link PageTemplate::generateAndGetPageHtmlAsString()}
                 */
                return $this->getExecutingMarkupHandler()
                    ->getRequestedContextPath();
            } catch (ExceptionNotFound $e) {
                // not a template engine running
                global $ACT;
                if ($ACT !== ExecutionContext::SHOW_ACTION || $ACT !== FetcherMarkup::MARKUP_DYNAMIC_EXECUTION_NAME) {
                    throw new ExceptionNotFound("No page is rendering");
                }

                global $INPUT;
                $inputId = $INPUT->str("id");
                if (!empty($inputId)) {
                    return WikiPath::createMarkupPathFromId($inputId);
                }

                global $ID;
                if (!empty($ID)) {
                    return WikiPath::createMarkupPathFromId($ID);
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
                        return WikiPath::createMarkupPathFromId($callingId);
                    }
                }

                /**
                 * This is the case with event triggered
                 * before DokuWiki such as
                 * https://www.dokuwiki.org/devel:event:init_lang_load
                 * REQUEST is a mixed of post and get parameters
                 */
                global $_REQUEST;
                if (isset($_REQUEST[DokuwikiId::DOKUWIKI_ID_ATTRIBUTE])) {
                    $requestId = $_REQUEST[DokuwikiId::DOKUWIKI_ID_ATTRIBUTE];
                    if (!empty($requestId)) {
                        return WikiPath::createMarkupPathFromId($requestId);
                    }
                }

                throw new ExceptionRuntimeInternal("In a show action, the requested id should not be empty.");
            }

        }

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


    /**
     * @param string $key
     * @param $value
     * @param string|null $pluginNamespace - if null, stored in the global conf namespace
     * @return $this
     *
     */
    public function setConf(string $key, $value, ?string $pluginNamespace = PluginUtility::PLUGIN_BASE_NAME): ExecutionContext
    {
        $this->getApp()->getConfig()->setConf($key, $value, $pluginNamespace);
        return $this;
    }

    /**
     * @param string $key
     * @param string|null $default
     * @return mixed|null
     * @deprecated use
     */
    public function getConfValue(string $key, string $default = null)
    {
        return $this->getApp()->getConfig()->getValue($key, $default);
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

    /**
     * @return $this
     * @deprecated uses {@link SiteConfig::setCacheXhtmlOn()}
     */
    public function setCacheXhtmlOn(): ExecutionContext
    {
        $this->getApp()->getConfig()->setCacheXhtmlOn();
        return $this;
    }

    /**
     *
     * @return $this
     * @deprecated use the {@link SiteConfig::setConsoleOn} instead
     */
    public function setConsoleOn(): ExecutionContext
    {
        $this->getApp()->getConfig()->setCacheXhtmlOn();
        return $this;
    }

    public function setConsoleOff(): ExecutionContext
    {
        $this->getConfig()->setConsoleOff();
        return $this;
    }

    /**
     * @return $this
     * @deprecated use {@link SiteConfig::setDisableTemplating()}
     */
    public function setDisableTemplating(): ExecutionContext
    {
        $this->getApp()->getConfig()->setDisableTemplating();
        return $this;
    }


    /**
     * @return bool
     * @deprecated use the {@link SiteConfig::isConsoleOn()} instead
     */
    public function isConsoleOn(): bool
    {
        return $this->getApp()->getConfig()->isConsoleOn();
    }


    public function setEnablePageFetcherAsShowAction(): ExecutionContext
    {
        $this->getConfValue(SiteConfig::CONF_ENABLE_TEMPLATE_SYSTEM, 1);
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
        $this->getConfig()->setLogExceptionToError();
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

    /**
     * @param string|null $executingId
     * @return void
     */
    private function setExecutingId(?string $executingId): void
    {
        global $ID;
        if ($executingId !== null) {
            $executingId = WikiPath::toDokuWikiId($executingId);
        }
        $ID = $executingId;
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
     * @param FetcherMarkup $markupHandler
     * @return $this
     */
    public function setExecutingMarkupHandler(FetcherMarkup $markupHandler): ExecutionContext
    {

        if (count($this->executingMarkupHandlerStack) >= 1 && !$markupHandler->isStringExecution()) {
            /**
             * A markup handler for a file can call a handler with a string
             * (example: {@link webcode can launch a sub-one
             */
            throw new ExceptionRuntimeInternal("Only one path markup handler can run at the same time");
        }

        /**
         * Act
         */
        $oldAct = $this->getExecutingAction();
        if ($markupHandler->isStringExecution()) {
            $runningAct = FetcherMarkup::MARKUP_DYNAMIC_EXECUTION_NAME;
            $this->setExecutingAction($runningAct);
        }

        /**
         * Id
         */
        try {
            $oldExecutingId = $this->getExecutingWikiId();
        } catch (ExceptionNotFound $e) {
            $oldExecutingId = null;
        }
        try {

            $executingPath = $markupHandler->getRequestedExecutingPath();
            $executingId = $executingPath->toQualifiedId();
            $this->setExecutingId($executingId);
        } catch (ExceptionNotFound $e) {
            // no executing path dynamic markup execution
        }

        /**
         * Fragment run
         */
        global $INFO;
        $oldContextId = $INFO['id'];
        if ($markupHandler->isFragmentExecution()) {
            $contextPath = $markupHandler->getRequestedContextPath();
            $INFO['id'] = $contextPath->getWikiId();
        }

        $this->executingMarkupHandlerStack[] = [$markupHandler, $oldExecutingId, $oldContextId, $oldAct];
        return $this;
    }

    public function closeExecutingMarkupHandler(): ExecutionContext
    {
        /** @noinspection PhpUnusedLocalVariableInspection */
        [$markupHandler, $oldExecutingId, $oldContextId, $oldAct] = array_pop($this->executingMarkupHandlerStack);

        $this
            ->setExecutingAction($oldAct)
            ->setExecutingId($oldExecutingId);

        global $INFO;
        if ($oldExecutingId === null) {
            unset($INFO['id']);
        } else {
            $INFO['id'] = $oldContextId;
        }
        return $this;
    }


    /**
     * @throws ExceptionNotFound - if there is no markup handler execution running
     */
    public function getExecutingMarkupHandler(): FetcherMarkup
    {
        $count = count($this->executingMarkupHandlerStack);
        if ($count >= 1) {
            return $this->executingMarkupHandlerStack[$count - 1][0];
        }
        throw new ExceptionNotFound("No markup handler running");
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
     * @deprecated
     */
    public function setDefaultContextPath(WikiPath $contextPath): ExecutionContext
    {
        $this->getConfig()->setDefaultContextPath($contextPath);
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
                    ->getExecutingMarkupHandler()
                    ->getRequestedContextPath();
            } catch (ExceptionNotFound $e) {

                /**
                 * Nope ? This is a dokuwiki run (admin page, ...)
                 */
                return $this->getDefaultContextPath();

            }

        }


    }


    /**
     * @return WikiPath
     * @deprecated uses {@link SiteConfig::getDefaultContextPath()}
     */
    public function getDefaultContextPath(): WikiPath
    {
        return $this->getConfig()->getDefaultContextPath();
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

    public function closeExecutingPageTemplate(): ExecutionContext
    {
        unset($this->executingPageTemplate);
        return $this;
    }

    public function getApp(): Site
    {
        if (isset($this->app)) {
            return $this->app;
        }
        $this->app = new Site($this);
        return $this->app;
    }

    /**
     * @return SiteConfig short utility function to get access to the global app config
     */
    public function getConfig(): SiteConfig
    {
        return $this->getApp()->getConfig();
    }

    /**
     * @throws ExceptionNotFound - when there is no executing id
     */
    public function getExecutingWikiPath(): WikiPath
    {
        try {
            return $this->getExecutingMarkupHandler()
                ->getRequestedExecutingPath()
                ->toWikiPath();
        } catch (ExceptionCast|ExceptionNotFound $e) {
            // Execution without templating (ie without fetcher markup)
            return WikiPath::createMarkupPathFromId($this->getExecutingWikiId());
        }

    }


}
