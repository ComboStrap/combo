<?php


use ComboStrap\DokuwikiId;
use ComboStrap\ExceptionBadArgument;
use ComboStrap\ExceptionBadSyntax;
use ComboStrap\ExceptionCompile;
use ComboStrap\ExceptionNotFound;
use ComboStrap\ExceptionSqliteNotAvailable;
use ComboStrap\ExecutionContext;
use ComboStrap\FileSystems;
use ComboStrap\HttpResponse;
use ComboStrap\HttpResponseStatus;
use ComboStrap\Identity;
use ComboStrap\LogUtility;
use ComboStrap\MarkupPath;
use ComboStrap\Mime;
use ComboStrap\PageRules;
use ComboStrap\Router;
use ComboStrap\RouterRedirection;
use ComboStrap\RouterRedirectionBuilder;
use ComboStrap\Site;
use ComboStrap\SiteConfig;
use ComboStrap\Sqlite;
use ComboStrap\Web\Url;
use ComboStrap\Web\UrlEndpoint;
use ComboStrap\Web\UrlRewrite;

require_once(__DIR__ . '/../vendor/autoload.php');

/**
 * Class action_plugin_combo_url
 *
 * The actual URL manager
 *
 *
 */
class action_plugin_combo_router extends DokuWiki_Action_Plugin
{

    /**
     * @deprecated
     */
    const URL_MANAGER_ENABLE_CONF = "enableUrlManager";
    const ROUTER_ENABLE_CONF = "enableRouter";


    // Where the target id value comes from


    // The constant parameters

    /** @var string - a name used in log and other places */
    const NAME = 'Url Manager';
    const CANONICAL = 'router';
    const PAGE_404 = "<html lang=\"en\"><body></body></html>";
    const REFRESH_HEADER_NAME = "Refresh";
    const REFRESH_HEADER_PREFIX = self::REFRESH_HEADER_NAME . ': 0;url=';
    const LOCATION_HEADER_PREFIX = HttpResponse::LOCATION_HEADER_NAME . ": ";
    public const URL_MANAGER_NAME = "Router";


    /**
     * @var PageRules
     */
    private $pageRules;


    function __construct()
    {
        // enable direct access to language strings
        // ie $this->lang
        $this->setupLocale();

    }

    /**
     * @param string $refreshHeader
     * @return false|string
     */
    public static function getUrlFromRefresh(string $refreshHeader)
    {
        return substr($refreshHeader, strlen(action_plugin_combo_router::REFRESH_HEADER_PREFIX));
    }

    public static function getUrlFromLocation($refreshHeader)
    {
        return substr($refreshHeader, strlen(action_plugin_combo_router::LOCATION_HEADER_PREFIX));
    }


    /**
     * Determine if the request should be banned based on the id
     *
     * @param string $id
     * @return bool
     *
     * See also {@link https://perishablepress.com/7g-firewall/#features}
     * for blocking rules on http request data such as:
     *   * query_string
     *   * user_agent,
     *   * remote host
     */
    public static function isShadowBanned(string $id): bool
    {
        /**
         * ie
         * wp-json:api:flutter_woo:config_file
         * wp-content:plugins:wpdiscuz:themes:default:style-rtl.css
         * wp-admin
         * 2020:wp-includes:wlwmanifest.xml
         * wp-content:start
         * wp-admin:css:start
         * sito:wp-includes:wlwmanifest.xml
         * site:wp-includes:wlwmanifest.xml
         * cms:wp-includes:wlwmanifest.xml
         * test:wp-includes:wlwmanifest.xml
         * media:wp-includes:wlwmanifest.xml
         * wp2:wp-includes:wlwmanifest.xml
         * 2019:wp-includes:wlwmanifest.xml
         * shop:wp-includes:wlwmanifest.xml
         * wp1:wp-includes:wlwmanifest.xml
         * news:wp-includes:wlwmanifest.xml
         * 2018:wp-includes:wlwmanifest.xml
         */
        if (strpos($id, 'wp-') !== false) {
            return true;
        }

        /**
         * db:oracle:long_or_1_utl_inaddr.get_host_address_chr_33_chr_126_chr_33_chr_65_chr_66_chr_67_chr_49_chr_52_chr_53_chr_90_chr_81_chr_54_chr_50_chr_68_chr_87_chr_81_chr_65_chr_70_chr_80_chr_79_chr_73_chr_89_chr_67_chr_70_chr_68_chr_33_chr_126_chr_33
         * db:oracle:999999.9:union:all:select_null:from_dual
         * db:oracle:999999.9:union:all:select_null:from_dual_and_0_0
         */
        if (preg_match('/_chr_|_0_0/', $id) === 1) {
            return true;
        }


        /**
         * ie
         * git:objects:
         * git:refs:heads:stable
         * git:logs:refs:heads:main
         * git:logs:refs:heads:stable
         * git:hooks:pre-push.sample
         * git:hooks:pre-receive.sample
         */
        if (strpos($id, "git:") === 0) {
            return true;
        }

        return false;

    }

    /**
     * @param string $id
     * @return bool
     * well-known:traffic-advice = https://github.com/buettner/private-prefetch-proxy/blob/main/traffic-advice.md
     * .well-known/security.txt, id=well-known:security.txt = https://securitytxt.org/
     * well-known:dnt-policy.txt
     */
    public static function isWellKnownFile(string $id): bool
    {
        return strpos($id, "well-known") === 0;
    }


    function register(Doku_Event_Handler $controller)
    {

        if (SiteConfig::getConfValue(self::ROUTER_ENABLE_CONF, 1)) {

            /**
             * This will call the function {@link action_plugin_combo_router::_router()}
             * The event is not DOKUWIKI_STARTED because this is not the first one
             *
             * https://www.dokuwiki.org/devel:event:init_lang_load
             */
            $controller->register_hook('DOKUWIKI_STARTED',
                'BEFORE',
                $this,
                'router',
                array());

            /**
             * Bot Ban functionality
             *
             * Because we make a redirection to the home page, we need to check
             * if the home is readable, for that, the AUTH plugin needs to be initialized
             * That's why we wait
             * https://www.dokuwiki.org/devel:event:dokuwiki_init_done
             *
             * and we can't use
             * https://www.dokuwiki.org/devel:event:init_lang_load
             * because there is no auth setup in {@link auth_aclcheck_cb()}
             * and the the line `if (!$auth instanceof AuthPlugin) return AUTH_NONE;` return none;
             */
            $controller->register_hook('DOKUWIKI_INIT_DONE', 'BEFORE', $this, 'ban', array());

        }


    }

    /**
     *
     * We have created a spacial ban function that is
     * called before the first function
     * {@link action_plugin_combo_metalang::load_lang()}
     * to spare CPU.
     *
     * @param $event
     * @throws Exception
     */
    function ban(&$event)
    {

        $id = Router::getOriginalIdFromRequest();
        if ($id === null) {
            return;
        }
        $page = MarkupPath::createMarkupFromId($id);
        if (FileSystems::exists($page)) {
            return;
        }

        // Well known
        if (self::isWellKnownFile($id)) {
            $redirection = RouterRedirectionBuilder::createFromOrigin(RouterRedirection::TARGET_ORIGIN_WELL_KNOWN)
                ->setType(RouterRedirection::REDIRECT_NOTFOUND_METHOD)
                ->build();
            $this->logRedirection($redirection);
            ExecutionContext::getActualOrCreateFromEnv()
                ->response()
                ->setStatus(HttpResponseStatus::NOT_FOUND)
                ->end();
            return;
        }

        // Shadow banned
        if (self::isShadowBanned($id)) {
            $webSiteHomePage = MarkupPath::createMarkupFromId(Site::getIndexPageName());
            $redirection = RouterRedirectionBuilder::createFromOrigin(RouterRedirection::TARGET_ORIGIN_SHADOW_BANNED)
                ->setType(RouterRedirection::REDIRECT_TRANSPARENT_METHOD)
                ->setTargetMarkupPath($webSiteHomePage)
                ->build();
            $this->executeTransparentRedirect($redirection);
        }

    }

    /**
     * @param $event Doku_Event
     * @param $param
     * @return void
     */
    function router(Doku_Event &$event, $param)
    {

        /**
         * Just the {@link ExecutionContext::SHOW_ACTION}
         * may be redirected
         */
        $executionContext = ExecutionContext::getActualOrCreateFromEnv();
        if ($executionContext->getExecutingAction() !== ExecutionContext::SHOW_ACTION) {
            return;
        }


        /**
         * Redirect only if the page is not found
         */
        $id = Router::getOriginalIdFromRequest();
        if ($id === null) {
            return;
        }
        $page = MarkupPath::createMarkupFromId($id);
        if (FileSystems::exists($page)) {
            return;
        }


        /**
         * Doku Rewrite is not supported
         */
        $urlRewrite = Site::getUrlRewrite();
        if ($urlRewrite == UrlRewrite::VALUE_DOKU_REWRITE) {
            UrlRewrite::sendErrorMessage();
            return;
        }

        /**
         * Try to find a redirection
         */
        $router = new Router();
        try {
            $redirection = $router->getRedirection();
        } catch (ExceptionSqliteNotAvailable $e) {
            // no Sql Lite
            return;
        } catch (ExceptionNotFound $e) {
            // no redirection
            return;
        } catch (Exception $e) {
            // Error
            LogUtility::error("An unexpected error has occurred while trying to get a redirection", LogUtility::SUPPORT_CANONICAL, $e);
            return;
        }


        /**
         * Special Mode where the redirection is just a change of ACT
         */
        if ($redirection->getOrigin() === Router::GO_TO_EDIT_MODE) {
            global $ACT;
            $ACT = 'edit';
            return;
        }

        /**
         * Other redirections
         */
        switch ($redirection->getType()) {
            case RouterRedirection::REDIRECT_TRANSPARENT_METHOD:
                try {
                    $this->executeTransparentRedirect($redirection);
                } catch (ExceptionCompile $e) {
                    LogUtility::error("Internal Error: A transparent redirect errors has occurred", LogUtility::SUPPORT_CANONICAL, $e);
                }
                return;
            default:
                try {
                    $this->executeHttpRedirect($redirection);
                } catch (ExceptionCompile $e) {
                    LogUtility::error("Internal Error: A http redirect errors has occurred", LogUtility::SUPPORT_CANONICAL, $e);
                }
        }


    }


    /**
     * Redirect to an internal page ie:
     *   * on the same domain
     *   * no HTTP redirect
     *   * id rewrite
     * @param RouterRedirection $redirection - target page id
     * @return void - return true if the user has the permission and that the redirect was done
     * @throws ExceptionCompile
     */
    private
    function executeTransparentRedirect(RouterRedirection $redirection): void
    {
        $markupPath = $redirection->getTargetMarkupPath();
        if ($markupPath === null) {
            throw new ExceptionCompile("A transparent redirect should have a wiki path. Origin {$redirection->getOrigin()}");
        }
        $targetPageId = $redirection->getTargetMarkupPath()->toAbsoluteId();

        // If the user does not have the right to see the target page
        // don't do anything
        if (!(Identity::isReader($targetPageId))) {
            return;
        }

        // Change the id
        global $ID;
        global $INFO;
        $sourceId = $ID;
        $ID = $targetPageId;
        if (isset($_REQUEST["id"])) {
            $_REQUEST["id"] = $targetPageId;
        }
        if (isset($_GET["id"])) {
            $_GET["id"] = $targetPageId;
        }

        /**
         * Refresh the $INFO data
         *
         * the info attributes are used elsewhere
         *   'id': for the sidebar
         *   'exist' : for the meta robot = noindex,follow, see {@link tpl_metaheaders()}
         *   'rev' : for the edit button to be sure that the page is still the same
         */
        $INFO = pageinfo();

        /**
         * Not compatible with
         * https://www.dokuwiki.org/config:send404 is enabled
         *
         * This check happens before that dokuwiki is started
         * and send an header in doku.php
         *
         * We send a warning
         */
        global $conf;
        if ($conf['send404']) {
            LogUtility::msg("The <a href=\"https://www.dokuwiki.org/config:send404\">dokuwiki send404 configuration</a> is on and should be disabled when using the url manager", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
        }

        // Redirection
        $this->logRedirection($redirection);

    }


    /**
     * The general HTTP Redirect method to an internal page
     * where the redirection method decide which type of redirection
     * @throws ExceptionCompile - if any error
     */
    private
    function executeHttpRedirect(RouterRedirection $redirection): void
    {


        // Log the redirections
        $this->logRedirection($redirection);


        $targetUrl = $redirection->getTargetUrl();

        if ($targetUrl !== null) {

            // defend against HTTP Response Splitting
            // https://owasp.org/www-community/attacks/HTTP_Response_Splitting
            $targetUrl = stripctl($targetUrl->toAbsoluteUrlString());


        } else {

            global $ID;

            // if this is search engine redirect
            $url = UrlEndpoint::createDokuUrl();
            switch ($redirection->getOrigin()) {
                case RouterRedirection::TARGET_ORIGIN_SEARCH_ENGINE:
                {
                    $replacementPart = array(':', '_', '-');
                    $query = str_replace($replacementPart, ' ', $ID);
                    $url->setQueryParameter(ExecutionContext::DO_ATTRIBUTE, ExecutionContext::SEARCH_ACTION);
                    $url->setQueryParameter("q", $query);
                    $url->setQueryParameter(DokuwikiId::DOKUWIKI_ID_ATTRIBUTE, $ID);
                    break;
                }
                default:

                    $markupPath = $redirection->getTargetMarkupPath();
                    if ($markupPath == null) {
                        // should not happen (Both may be null but only on edit mode)
                        throw new ExceptionCompile("Internal Error When executing a http redirect, the URL or the wiki page should not be null");
                    }
                    $url->setQueryParameter(DokuwikiId::DOKUWIKI_ID_ATTRIBUTE, $markupPath->toAbsoluteId());


            }

            /**
             * Doing a permanent redirect with a added query string
             * create a new page url on the search engine
             *
             * ie
             * http://host/page
             * is not the same
             * than
             * http://host/page?whatever
             *
             * We can't pass query string otherwise, we get
             * the SEO warning / error
             * `Alternative page with proper canonical tag`
             *
             * Use HTTP X header for debug
             */
            if ($redirection->getType() !== RouterRedirection::REDIRECT_PERMANENT_METHOD) {
                $url->setQueryParameter(action_plugin_combo_routermessage::ORIGIN_PAGE, $ID);
                $url->setQueryParameter(action_plugin_combo_routermessage::ORIGIN_TYPE, $redirection->getOrigin());
            }


            $targetUrl = $url->toAbsoluteUrlString();


        }


        /**
         * Check that we are not redirecting to the same URL
         * to avoid the TOO_MANY_REDIRECT error
         */
        $requestURL = Url::createFromString($_SERVER['REQUEST_URI'])->toAbsoluteUrlString();
        if ($requestURL === $targetUrl) {
            throw new ExceptionCompile("A redirection should not redirect to the requested URL. Redirection Origin: {$redirection->getOrigin()}, Redirection URL:{$targetUrl} ");
        }

        /**
         * The dokuwiki function {@link send_redirect()}
         * set the `Location header` and in php, the header function
         * in this case change the status code to 302 Arghhhh.
         * The code below is adapted from this function {@link send_redirect()}
         */
        global $MSG; // are there any undisplayed messages? keep them in session for display
        if (isset($MSG) && count($MSG) && !defined('NOSESSION')) {
            //reopen session, store data and close session again
            @session_start();
            $_SESSION[DOKU_COOKIE]['msg'] = $MSG;
        }
        session_write_close(); // always close the session

        switch ($redirection->getType()) {

            case RouterRedirection::REDIRECT_PERMANENT_METHOD:
                ExecutionContext::getActualOrCreateFromEnv()
                    ->response()
                    ->setStatus(HttpResponseStatus::PERMANENT_REDIRECT)
                    ->addHeader(self::LOCATION_HEADER_PREFIX . $targetUrl)
                    ->end();
                return;

            case RouterRedirection::REDIRECT_NOTFOUND_METHOD:

                // Empty 404 body to not get the standard 404 page of the browser
                // but a blank page to avoid a sort of FOUC.
                // ie the user see a page briefly
                ExecutionContext::getActualOrCreateFromEnv()
                    ->response()
                    ->setStatus(HttpResponseStatus::NOT_FOUND)
                    ->addHeader(self::REFRESH_HEADER_PREFIX . $targetUrl)
                    ->setBody(self::PAGE_404, Mime::getHtml())
                    ->end();
                return;

            default:
                throw new ExceptionCompile("The type ({$redirection->getType()}) is not an http redirection");

        }


    }


    /**
     *
     *   * For a conf file, it will update the Redirection Action Data as Referrer, Count Of Redirection, Redirection Date
     *   * For a SQlite database, it will add a row into the log
     *
     * @param string $sourcePageId
     * @param $targetPageId
     * @param $algorithmic
     * @param $method - http or rewrite
     */
    function logRedirection(RouterRedirection $redirection)
    {
        global $ID;

        $row = array(
            "TIMESTAMP" => date("c"),
            "SOURCE" => $ID,
            "TARGET" => $redirection->getTargetAsString(),
            "REFERRER" => $_SERVER['HTTP_REFERER'] ?? null,
            "TYPE" => $redirection->getOrigin(),
            "METHOD" => $redirection->getType()
        );
        try {
            $request = Sqlite::createOrGetBackendSqlite()
                ->createRequest()
                ->setTableRow('redirections_log', $row);
        } catch (ExceptionSqliteNotAvailable $e) {
            return;
        }
        try {
            $request
                ->execute();
        } catch (ExceptionCompile $e) {
            LogUtility::msg("Redirection Log Insert Error. {$e->getMessage()}");
        } finally {
            $request->close();
        }


    }


}
