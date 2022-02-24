<?php

require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');


use ComboStrap\AliasType;
use ComboStrap\DatabasePageRow;
use ComboStrap\DokuPath;
use ComboStrap\ExceptionCombo;
use ComboStrap\HttpResponse;
use ComboStrap\Identity;
use ComboStrap\LogUtility;
use ComboStrap\Mime;
use ComboStrap\Page;
use ComboStrap\PageId;
use ComboStrap\PageRules;
use ComboStrap\PageUrlPath;
use ComboStrap\PageUrlType;
use ComboStrap\PluginUtility;
use ComboStrap\Site;
use ComboStrap\Sqlite;
use ComboStrap\Url;
use ComboStrap\UrlManagerBestEndPage;


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

    // The redirect type
    const REDIRECT_TRANSPARENT_METHOD = 'transparent'; // was (Id)
    // For permanent, see https://developers.google.com/search/docs/advanced/crawling/301-redirects
    const REDIRECT_PERMANENT_METHOD = 'permanent'; // was `Http` (301)
    const REDIRECT_NOTFOUND_METHOD = "notfound"; // 404 (See other) (when best page name is calculated)

    public const PERMANENT_REDIRECT_CANONICAL = "permanent:redirect";

    // Where the target id value comes from
    const TARGET_ORIGIN_WELL_KNOWN = 'well-known';
    const TARGET_ORIGIN_PAGE_RULES = 'pageRules';
    /**
     * Named Permalink (canonical)
     */
    const TARGET_ORIGIN_CANONICAL = 'canonical';
    const TARGET_ORIGIN_ALIAS = 'alias';
    /**
     * Identifier Permalink (full page id)
     */
    const TARGET_ORIGIN_PERMALINK = "permalink";
    /**
     * Extended Permalink (abbreviated page id at the end)
     */
    const TARGET_ORIGIN_PERMALINK_EXTENDED = "extendedPermalink";
    const TARGET_ORIGIN_START_PAGE = 'startPage';
    const TARGET_ORIGIN_BEST_PAGE_NAME = 'bestPageName';
    const TARGET_ORIGIN_BEST_NAMESPACE = 'bestNamespace';
    const TARGET_ORIGIN_SEARCH_ENGINE = 'searchEngine';
    const TARGET_ORIGIN_BEST_END_PAGE_NAME = 'bestEndPageName';
    const TARGET_ORIGIN_SHADOW_BANNED = "shadowBanned";


    // The constant parameters
    const GO_TO_SEARCH_ENGINE = 'GoToSearchEngine';
    const GO_TO_BEST_NAMESPACE = 'GoToBestNamespace';
    const GO_TO_BEST_PAGE_NAME = 'GoToBestPageName';
    const GO_TO_BEST_END_PAGE_NAME = 'GoToBestEndPageName';
    const GO_TO_NS_START_PAGE = 'GoToNsStartPage';
    const GO_TO_EDIT_MODE = 'GoToEditMode';
    const NOTHING = 'Nothing';

    /** @var string - a name used in log and other places */
    const NAME = 'Url Manager';
    const CANONICAL = 'router';
    const PAGE_404 = "<html lang=\"en\"><body></body></html>";
    const REFRESH_HEADER_NAME = "Refresh";
    const REFRESH_HEADER_PREFIX = self::REFRESH_HEADER_NAME . ': 0;url=';
    const LOCATION_HEADER_NAME = "Location";
    const LOCATION_HEADER_PREFIX = self::LOCATION_HEADER_NAME . ": ";
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
     * @param $refreshHeader
     * @return false|string
     */
    public static function getUrlFromRefresh($refreshHeader)
    {
        return substr($refreshHeader, strlen(action_plugin_combo_router::REFRESH_HEADER_PREFIX));
    }

    public static function getUrlFromLocation($refreshHeader)
    {
        return substr($refreshHeader, strlen(action_plugin_combo_router::LOCATION_HEADER_PREFIX));
    }

    /**
     * @return array|mixed|string|string[]
     *
     * Unfortunately, DOKUWIKI_STARTED is not the first event
     * The id may have been changed by
     * {@link action_plugin_combo_metalang::load_lang()}
     * function, that's why we have this function
     * to get the original requested id
     */
    private static function getOriginalIdFromRequest()
    {
        $originalId = $_GET["id"];
        return str_replace("/", DokuPath::PATH_SEPARATOR, $originalId);
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

        if (PluginUtility::getConfValue(self::ROUTER_ENABLE_CONF, 1)) {
            /**
             * This will call the function {@link action_plugin_combo_router::_router()}
             * The event is not DOKUWIKI_STARTED because this is not the first one
             *
             * https://www.dokuwiki.org/devel:event:init_lang_load
             */
            $controller->register_hook('DOKUWIKI_STARTED',
                'AFTER',
                $this,
                'router',
                array());

            /**
             * This is the real first call of Dokuwiki
             * Unfortunately, it does not create the environment
             * We just ban to spare server resources
             *
             * https://www.dokuwiki.org/devel:event:init_lang_load
             */
            $controller->register_hook('INIT_LANG_LOAD', 'BEFORE', $this, 'ban', array());

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

        $id = self::getOriginalIdFromRequest();
        $page = Page::createPageFromId($id);
        if (!$page->exists()) {
            // Well known
            if (self::isWellKnownFile($id)) {
                $this->logRedirection($id, "", self::TARGET_ORIGIN_WELL_KNOWN, self::REDIRECT_NOTFOUND_METHOD);
                HttpResponse::create(HttpResponse::STATUS_NOT_FOUND)
                    ->send();
                return;
            }

            // Shadow banned
            if (self::isShadowBanned($id)) {
                $webSiteHomePage = Site::getHomePageName();
                $this->executeTransparentRedirect($webSiteHomePage, self::TARGET_ORIGIN_SHADOW_BANNED);
            }
        }
    }

    /**
     * @param $event Doku_Event
     * @param $param
     * @return void
     * @throws Exception
     */
    function router(&$event, $param)
    {

        global $ACT;
        if ($ACT !== 'show') return;


        global $ID;

        /**
         * Without SQLite, this module does not work further
         */
        $sqlite = Sqlite::createOrGetSqlite();
        if ($sqlite == null) {
            return;
        } else {
            $this->pageRules = new PageRules();
        }

        /**
         * Unfortunately, DOKUWIKI_STARTED is not the first event
         * The id may have been changed by
         * {@link action_plugin_combo_metalang::load_lang()}
         * function, that's why we check against the {@link $_REQUEST}
         * and not the global ID
         */
        $originalId = self::getOriginalIdFromRequest();

        /**
         * Page is an existing id ?
         */
        $targetPage = Page::createPageFromId($ID);
        if ($targetPage->exists()) {

            /**
             * If this is not the root home page
             * and if the canonical id is the not the same,
             * and if this is not a historical page (revision)
             * redirect
             */
            if (
                $originalId !== $targetPage->getUrlId() // The id may have been changed
                && $ID != Site::getHomePageName()
                && !isset($_REQUEST["rev"])
            ) {
                /**
                 * TODO: When saving for the first time, the page is not stored in the database
                 *   but that's not the case actually
                 */
                if ($targetPage->getDatabasePage()->exists()) {
                    $this->executePermanentRedirect(
                        $targetPage->getCanonicalUrl([], true),
                        self::TARGET_ORIGIN_PERMALINK_EXTENDED
                    );
                }
            }
            return;
        }


        $identifier = $ID;


        /**
         * Page Id Website / root Permalink ?
         */
        $shortPageId = PageUrlPath::getShortEncodedPageIdFromUrlId($targetPage->getPath()->getLastName());
        if ($shortPageId !== null) {
            $pageId = PageUrlPath::decodePageId($shortPageId);
            if ($targetPage->getParentPage() === null && $pageId !== null) {
                $page = DatabasePageRow::createFromPageId($pageId)->getPage();
                if ($page !== null && $page->exists()) {
                    $this->executePermanentRedirect(
                        $page->getCanonicalUrl([], true),
                        self::TARGET_ORIGIN_PERMALINK
                    );
                }
            }

            /**
             * Page Id Abbr ?
             * {@link PageUrlType::CONF_CANONICAL_URL_TYPE}
             */
            if (
                $pageId !== null
            ) {
                $page = DatabasePageRow::createFromPageIdAbbr($pageId)->getPage();
                if ($page === null) {
                    // or the length of the abbr has changed
                    $databasePage = new DatabasePageRow();
                    $row = $databasePage->getDatabaseRowFromAttribute("substr(" . PageId::PROPERTY_NAME . ", 1, " . strlen($pageId) . ")", $pageId);
                    if ($row !== null) {
                        $databasePage->setRow($row);
                        $page = $databasePage->getPage();
                    }
                }
                if ($page !== null && $page->exists()) {
                    /**
                     * If the url canonical id has changed, we show it
                     * to the writer by performing a permanent redirect
                     */
                    if ($identifier != $page->getUrlId()) {
                        // Google asks for a redirect
                        // https://developers.google.com/search/docs/advanced/crawling/301-redirects
                        // People access your site through several different URLs.
                        // If, for example, your home page can be reached in multiple ways
                        // (for instance, http://example.com/home, http://home.example.com, or http://www.example.com),
                        // it's a good idea to pick one of those URLs as your preferred (canonical) destination,
                        // and use redirects to send traffic from the other URLs to your preferred URL.
                        $this->executePermanentRedirect(
                            $page->getCanonicalUrl([], true),
                            self::TARGET_ORIGIN_PERMALINK_EXTENDED
                        );
                        return;
                    }

                    $this->executeTransparentRedirect($page->getDokuwikiId(), self::TARGET_ORIGIN_PERMALINK_EXTENDED);
                    return;

                }
                // permanent url not yet in the database
                // Other permanent such as permanent canonical ?
                // We let the process go with the new identifier

            }

        }

        // Global variable needed in the process
        global $conf;

        /**
         * Identifier is a Canonical ?
         */
        $databasePage = DatabasePageRow::createFromCanonical($identifier);
        $targetPage = $databasePage->getPage();
        if ($targetPage !== null && $targetPage->exists()) {
            /**
             * Does the canonical url is canonical name based
             * ie {@link  PageUrlType::CONF_VALUE_CANONICAL_PATH}
             */
            if ($targetPage->getUrlId() === $identifier) {
                $res = $this->executeTransparentRedirect(
                    $targetPage->getDokuwikiId(),
                    self::TARGET_ORIGIN_CANONICAL
                );
            } else {
                $res = $this->executePermanentRedirect(
                    $targetPage->getDokuwikiId(), // not the url because, it allows to add url query redirection property
                    self::TARGET_ORIGIN_CANONICAL
                );
            }
            if ($res) {
                return;
            }
        }

        /**
         * Identifier is an alias
         */
        $targetPage = DatabasePageRow::createFromAlias($identifier)->getPage();
        if (
            $targetPage !== null
            && $targetPage->exists()
            // The build alias is the file system metadata alias
            // it may be null if the replication in the database was not successful
            && $targetPage->getBuildAlias() !== null
        ) {
            $buildAlias = $targetPage->getBuildAlias();
            switch ($buildAlias->getType()) {
                case AliasType::REDIRECT:
                    $res = $this->executePermanentRedirect($targetPage->getCanonicalUrl([], true), self::TARGET_ORIGIN_ALIAS);
                    if ($res) {
                        return;
                    }
                    break;
                case AliasType::SYNONYM:
                    $res = $this->executeTransparentRedirect($targetPage->getDokuwikiId(), self::TARGET_ORIGIN_ALIAS);
                    if ($res) {
                        return;
                    }
                    break;
                default:
                    LogUtility::msg("The alias type ({$buildAlias->getType()}) is unknown. A permanent redirect was performed for the alias $identifier");
                    $res = $this->executePermanentRedirect($targetPage->getCanonicalUrl([], true), self::TARGET_ORIGIN_ALIAS);
                    if ($res) {
                        return;
                    }
                    break;
            }
        }


        // If there is a redirection defined in the page rules
        $result = $this->processingPageRules();
        if ($result) {
            // A redirection has occurred
            // finish the process
            return;
        }

        /**
         *
         * There was no redirection found, redirect to edit mode if writer
         *
         */
        if (Identity::isWriter() && $this->getConf(self::GO_TO_EDIT_MODE) == 1) {

            $this->gotToEditMode($event);
            // Stop here
            return;

        }

        /*
         *  We are still a reader, the redirection does not exist the user is not allowed to edit the page (public of other)
         */
        if ($this->getConf('ActionReaderFirst') == self::NOTHING) {
            return;
        }

        // We are reader and their is no redirection set, we apply the algorithm
        $readerAlgorithms = array();
        $readerAlgorithms[0] = $this->getConf('ActionReaderFirst');
        $readerAlgorithms[1] = $this->getConf('ActionReaderSecond');
        $readerAlgorithms[2] = $this->getConf('ActionReaderThird');

        while (
            ($algorithm = array_shift($readerAlgorithms)) != null
        ) {

            switch ($algorithm) {

                case self::NOTHING:
                    return;

                case self::GO_TO_BEST_END_PAGE_NAME:

                    list($targetPage, $method) = UrlManagerBestEndPage::process($identifier);
                    if ($targetPage != null) {
                        $res = false;
                        switch ($method) {
                            case self::REDIRECT_PERMANENT_METHOD:
                                $res = $this->executePermanentRedirect($targetPage, self::TARGET_ORIGIN_BEST_END_PAGE_NAME);
                                break;
                            case self::REDIRECT_NOTFOUND_METHOD:
                                $res = $this->performNotFoundRedirect($targetPage, self::TARGET_ORIGIN_BEST_END_PAGE_NAME);
                                break;
                            default:
                                LogUtility::msg("This redirection method ($method) was not expected for the redirection algorithm ($algorithm)");
                        }
                        if ($res) {
                            // Redirection has succeeded
                            return;
                        }
                    }
                    break;

                case self::GO_TO_NS_START_PAGE:

                    // Start page with the conf['start'] parameter
                    $startPage = getNS($identifier) . ':' . $conf['start'];
                    if (page_exists($startPage)) {
                        $res = $this->performNotFoundRedirect($startPage, self::TARGET_ORIGIN_START_PAGE);
                        if ($res) {
                            return;
                        }
                    }

                    // Start page with the same name than the namespace
                    $startPage = getNS($identifier) . ':' . curNS($identifier);
                    if (page_exists($startPage)) {
                        $res = $this->performNotFoundRedirect($startPage, self::TARGET_ORIGIN_START_PAGE);
                        if ($res) {
                            return;
                        }
                    }
                    break;

                case self::GO_TO_BEST_PAGE_NAME:

                    $bestPageId = null;

                    $bestPage = $this->getBestPage($identifier);
                    $bestPageId = $bestPage['id'];
                    $scorePageName = $bestPage['score'];

                    // Get Score from a Namespace
                    $bestNamespace = $this->scoreBestNamespace($identifier);
                    $bestNamespaceId = $bestNamespace['namespace'];
                    $namespaceScore = $bestNamespace['score'];

                    // Compare the two score
                    if ($scorePageName > 0 or $namespaceScore > 0) {
                        if ($scorePageName > $namespaceScore) {
                            $this->performNotFoundRedirect($bestPageId, self::TARGET_ORIGIN_BEST_PAGE_NAME);
                        } else {
                            $this->performNotFoundRedirect($bestNamespaceId, self::TARGET_ORIGIN_BEST_PAGE_NAME);
                        }
                        return;
                    }
                    break;

                case self::GO_TO_BEST_NAMESPACE:

                    $scoreNamespace = $this->scoreBestNamespace($identifier);
                    $bestNamespaceId = $scoreNamespace['namespace'];
                    $score = $scoreNamespace['score'];

                    if ($score > 0) {
                        $this->performNotFoundRedirect($bestNamespaceId, self::TARGET_ORIGIN_BEST_NAMESPACE);
                        return;
                    }
                    break;

                case self::GO_TO_SEARCH_ENGINE:

                    $this->redirectToSearchEngine();

                    return;
                    break;

                // End Switch Action
            }

            // End While Action
        }


    }


    /**
     * getBestNamespace
     * Return a list with 'BestNamespaceId Score'
     * @param $id
     * @return array
     */
    private
    function scoreBestNamespace($id)
    {

        global $conf;

        // Parameters
        $pageNameSpace = getNS($id);

        // If the page has an existing namespace start page take it, other search other namespace
        $startPageNameSpace = $pageNameSpace . ":";
        $dateAt = '';
        // $startPageNameSpace will get a full path (ie with start or the namespace
        resolve_pageid($pageNameSpace, $startPageNameSpace, $exists, $dateAt, true);
        if (page_exists($startPageNameSpace)) {
            $nameSpaces = array($startPageNameSpace);
        } else {
            $nameSpaces = ft_pageLookup($conf['start']);
        }

        // Parameters and search the best namespace
        $pathNames = explode(':', $pageNameSpace);
        $bestNbWordFound = 0;
        $bestNamespaceId = '';
        foreach ($nameSpaces as $nameSpace) {

            $nbWordFound = 0;
            foreach ($pathNames as $pathName) {
                if (strlen($pathName) > 2) {
                    $nbWordFound = $nbWordFound + substr_count($nameSpace, $pathName);
                }
            }
            if ($nbWordFound > $bestNbWordFound) {
                // Take only the smallest namespace
                if (strlen($nameSpace) < strlen($bestNamespaceId) or $nbWordFound > $bestNbWordFound) {
                    $bestNbWordFound = $nbWordFound;
                    $bestNamespaceId = $nameSpace;
                }
            }
        }

        $startPageFactor = $this->getConf('WeightFactorForStartPage');
        $nameSpaceFactor = $this->getConf('WeightFactorForSameNamespace');
        if ($bestNbWordFound > 0) {
            $bestNamespaceScore = $bestNbWordFound * $nameSpaceFactor + $startPageFactor;
        } else {
            $bestNamespaceScore = 0;
        }


        return array(
            'namespace' => $bestNamespaceId,
            'score' => $bestNamespaceScore
        );

    }

    /**
     * @param $event
     */
    private
    function gotToEditMode(&$event)
    {
        global $ACT;
        $ACT = 'edit';

    }


    /**
     * Redirect to an internal page ie:
     *   * on the same domain
     *   * no HTTP redirect
     *   * id rewrite
     * @param string $targetPageId - target page id
     * @param string $targetOriginId - the source of the target (redirect)
     * @return bool - return true if the user has the permission and that the redirect was done
     * @throws Exception
     */
    private
    function executeTransparentRedirect(string $targetPageId, string $targetOriginId): bool
    {
        /**
         * Because we set the ID globally for the ID redirect
         * we make sure that this is not a {@link Page}
         * object otherwise we got an error in the {@link \ComboStrap\AnalyticsMenuItem}
         * because the constructor takes it {@link \dokuwiki\Menu\Item\AbstractItem}
         */
        if (is_object($targetPageId)) {
            $class = get_class($targetPageId);
            LogUtility::msg("The parameters targetPageId ($targetPageId) is an object of the class ($class) and it should be a page id");
        }

        if (is_object($targetOriginId)) {
            $class = get_class($targetOriginId);
            LogUtility::msg("The parameters targetOriginId ($targetOriginId) is an object of the class ($class) and it should be a page id");
        }

        // If the user does not have the right to see the target page
        // don't do anything
        if (!(Identity::isReader($targetPageId))) {
            return false;
        }

        // Change the id
        global $ID;
        global $INFO;
        $sourceId = $ID;
        $ID = $targetPageId;

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
        if ($conf['send404'] == true) {
            LogUtility::msg("The <a href=\"https://www.dokuwiki.org/config:send404\">dokuwiki send404 configuration</a> is on and should be disabled when using the url manager", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
        }

        // Redirection
        $this->logRedirection($sourceId, $targetPageId, $targetOriginId, self::REDIRECT_TRANSPARENT_METHOD);

        return true;

    }

    private function executePermanentRedirect(string $target, $targetOrigin): bool
    {
        return $this->executeHttpRedirect($target, $targetOrigin, self::REDIRECT_PERMANENT_METHOD);
    }

    /**
     * The general HTTP Redirect method to an internal page
     * where the redirection method decide which type of redirection
     * @param string $target - a dokuwiki id or an url
     * @param string $targetOrigin - the origin of the target (the algorithm used to get the target origin)
     * @param string $method - the redirection method
     */
    private
    function executeHttpRedirect(string $target, string $targetOrigin, string $method): bool
    {

        global $ID;


        // Log the redirections
        $this->logRedirection($ID, $target, $targetOrigin, $method);


        // An external url ?
        $isValid = Url::isValid($target);
        // If there is a bug in the isValid function for an internal url
        // We get a loop.
        // The Url becomes the id, the id is unknown and we do a redirect again
        //
        // We check then if the target starts with the base url
        // if this is the case, it's valid
        if (!$isValid && strpos($target, DOKU_URL) === 0) {
            $isValid = true;
        }
        if ($isValid) {

            // defend against HTTP Response Splitting
            // https://owasp.org/www-community/attacks/HTTP_Response_Splitting
            $targetUrl = stripctl($target);

        } else {


            // Explode the page ID and the anchor (#)
            $link = explode('#', $target, 2);

            // Query String to pass the message
            $urlParams = [];
            if ($targetOrigin != self::TARGET_ORIGIN_PERMALINK) {
                $urlParams = array(
                    action_plugin_combo_routermessage::ORIGIN_PAGE => $ID,
                    action_plugin_combo_routermessage::ORIGIN_TYPE => $targetOrigin
                );
            }

            // if this is search engine redirect
            if ($targetOrigin == self::TARGET_ORIGIN_SEARCH_ENGINE) {
                $replacementPart = array(':', '_', '-');
                $query = str_replace($replacementPart, ' ', $ID);
                $urlParams["do"] = "search";
                $urlParams["q"] = $query;
            }

            $targetUrl = wl($link[0], $urlParams, true, '&');
            // %3A back to :
            $targetUrl = str_replace("%3A", ":", $targetUrl);
            if ($link[1]) {
                $targetUrl .= '#' . rawurlencode($link[1]);
            }

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

        switch ($method) {
            case self::REDIRECT_PERMANENT_METHOD:
                HttpResponse::create(HttpResponse::STATUS_PERMANENT_REDIRECT)
                    ->addHeader(self::LOCATION_HEADER_PREFIX . $targetUrl)
                    ->send();
                return true;
            case self::REDIRECT_NOTFOUND_METHOD:

                // Empty 404 body to not get the standard 404 page of the browser
                // but a blank page to avoid a sort of FOUC.
                // ie the user see a page briefly
                HttpResponse::create(HttpResponse::STATUS_NOT_FOUND)
                    ->addHeader(self::REFRESH_HEADER_PREFIX . $targetUrl)
                    ->send(self::PAGE_404, Mime::HTML);
                return true;

            default:
                LogUtility::msg("The method ($method) is not an http redirection");
                return false;
        }


    }

    /**
     * @param $id
     * @return array
     */
    private
    function getBestPage($id): array
    {

        // The return parameters
        $bestPageId = null;
        $scorePageName = null;

        // Get Score from a page
        $pageName = noNS($id);
        $pagesWithSameName = ft_pageLookup($pageName);
        if (count($pagesWithSameName) > 0) {

            // Search same namespace in the page found than in the Id page asked.
            $bestNbWordFound = 0;


            $wordsInPageSourceId = explode(':', $id);
            foreach ($pagesWithSameName as $targetPageId => $title) {

                // Nb of word found in the target page id
                // that are in the source page id
                $nbWordFound = 0;
                foreach ($wordsInPageSourceId as $word) {
                    $nbWordFound = $nbWordFound + substr_count($targetPageId, $word);
                }

                if ($bestPageId == null) {

                    $bestNbWordFound = $nbWordFound;
                    $bestPageId = $targetPageId;

                } else {

                    if ($nbWordFound >= $bestNbWordFound && strlen($bestPageId) > strlen($targetPageId)) {

                        $bestNbWordFound = $nbWordFound;
                        $bestPageId = $targetPageId;

                    }

                }

            }
            $scorePageName = $this->getConf('WeightFactorForSamePageName') + ($bestNbWordFound - 1) * $this->getConf('WeightFactorForSameNamespace');
            return array(
                'id' => $bestPageId,
                'score' => $scorePageName);
        }
        return array(
            'id' => $bestPageId,
            'score' => $scorePageName
        );

    }


    /**
     * Redirect to the search engine
     */
    private
    function redirectToSearchEngine()
    {

        global $ID;
        $this->performNotFoundRedirect($ID, self::TARGET_ORIGIN_SEARCH_ENGINE);

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
    function logRedirection(string $sourcePageId, $targetPageId, $algorithmic, $method)
    {

        $row = array(
            "TIMESTAMP" => date("c"),
            "SOURCE" => $sourcePageId,
            "TARGET" => $targetPageId,
            "REFERRER" => $_SERVER['HTTP_REFERER'],
            "TYPE" => $algorithmic,
            "METHOD" => $method
        );
        $request = Sqlite::createOrGetBackendSqlite()
            ->createRequest()
            ->setTableRow('redirections_log', $row);
        try {
            $request
                ->execute();
        } catch (ExceptionCombo $e) {
            LogUtility::msg("Redirection Log Insert Error. {$e->getMessage()}");
        } finally {
            $request->close();
        }


    }

    /**
     * This function check if there is a redirection declared
     * in the redirection table
     * @return bool - true if a rewrite or redirection occurs
     * @throws Exception
     */
    private function processingPageRules(): bool
    {
        global $ID;

        $calculatedTarget = null;
        $ruleMatcher = null; // Used in a warning message if the target page does not exist
        // Known redirection in the table
        // Get the page from redirection data
        $rules = $this->pageRules->getRules();
        foreach ($rules as $rule) {

            $ruleMatcher = strtolower($rule[PageRules::MATCHER_NAME]);
            $ruleTarget = $rule[PageRules::TARGET_NAME];

            // Glob to Rexgexp
            $regexpPattern = '/' . str_replace("*", "(.*)", $ruleMatcher) . '/';

            // Match ?
            // https://www.php.net/manual/en/function.preg-match.php
            $pregMatchResult = @preg_match($regexpPattern, $ID, $matches);
            if ($pregMatchResult === false) {
                // The `if` to take into account this problem
                // PHP Warning:  preg_match(): Unknown modifier 'd' in /opt/www/datacadamia.com/lib/plugins/combo/action/router.php on line 972
                LogUtility::log2file("processing Page Rules An error occurred with the pattern ($regexpPattern)", LogUtility::LVL_MSG_WARNING);
                return false;
            }
            if ($pregMatchResult) {
                $calculatedTarget = $ruleTarget;
                foreach ($matches as $key => $match) {
                    if ($key == 0) {
                        continue;
                    } else {
                        $calculatedTarget = str_replace('$' . $key, $match, $calculatedTarget);
                    }
                }
                break;
            }
        }

        if ($calculatedTarget == null) {
            return false;
        }

        // If this is an external redirect (other domain)
        if (Url::isValid($calculatedTarget)) {

            $this->executeHttpRedirect($calculatedTarget, self::TARGET_ORIGIN_PAGE_RULES, self::REDIRECT_PERMANENT_METHOD);
            return true;

        }

        // If the page exist
        if (page_exists($calculatedTarget)) {

            // This is DokuWiki Id and should always be lowercase
            // The page rule may have change that
            $calculatedTarget = strtolower($calculatedTarget);
            $res = $this->executeTransparentRedirect($calculatedTarget, self::TARGET_ORIGIN_PAGE_RULES);
            if ($res) {
                return true;
            } else {
                return false;
            }

        } else {

            LogUtility::msg("The calculated target page ($calculatedTarget) (for the non-existing page `$ID` with the matcher `$ruleMatcher`) does not exist", LogUtility::LVL_MSG_ERROR);
            return false;

        }

    }

    private function performNotFoundRedirect(string $targetId, string $origin): bool
    {
        return $this->executeHttpRedirect($targetId, $origin, self::REDIRECT_NOTFOUND_METHOD);
    }


}
