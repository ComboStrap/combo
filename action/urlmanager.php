<?php

require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');


use ComboStrap\DatabasePage;
use ComboStrap\Http;
use ComboStrap\Identity;
use ComboStrap\LinkUtility;
use ComboStrap\LogUtility;
use ComboStrap\PageRules;
use ComboStrap\PluginUtility;
use ComboStrap\Site;
use ComboStrap\Sqlite;
use ComboStrap\Page;
use ComboStrap\UrlManagerBestEndPage;
use ComboStrap\Url;


/**
 * Class action_plugin_combo_url
 *
 * The actual URL manager
 *
 *
 */
class action_plugin_combo_urlmanager extends DokuWiki_Action_Plugin
{

    const URL_MANAGER_ENABLE_CONF = "enableUrlManager";

    // The redirect type
    const REDIRECT_TRANSPARENT_METHOD = 'transparent'; // was (Id)
    // For permanent, see https://developers.google.com/search/docs/advanced/crawling/301-redirects
    const REDIRECT_PERMANENT_METHOD = 'permanent'; // was `Http` (301)
    const REDIRECT_NOTFOUND_METHOD = "notfound"; // 404 (See other) (when best page name is calculated)

    public const PERMANENT_REDIRECT_CANONICAL = "permanent:redirect";

    // Where the target id value comes from
    const TARGET_ORIGIN_WELL_KNOWN = 'well-known';
    const TARGET_ORIGIN_PAGE_RULES = 'pageRules';
    const TARGET_ORIGIN_CANONICAL = 'canonical';
    const TARGET_ORIGIN_ALIAS = 'alias';
    const TARGET_ORIGIN_PERMALINK = "permalink";
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
    const CANONICAL = 'url/manager';
    const PAGE_404 = "<html lang=\"en\"><body></body></html>";
    const REFRESH_HEADER_PREFIX = 'Refresh: 0;url=';
    const LOCATION_HEADER_PREFIX = "Location: ";


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
        return substr($refreshHeader, strlen(action_plugin_combo_urlmanager::REFRESH_HEADER_PREFIX));
    }

    public static function getUrlFromLocation($refreshHeader)
    {
        return substr($refreshHeader, strlen(action_plugin_combo_urlmanager::LOCATION_HEADER_PREFIX));
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

        if (PluginUtility::getConfValue(self::URL_MANAGER_ENABLE_CONF, 1)) {
            /* This will call the function _handle404 */
            $controller->register_hook('DOKUWIKI_STARTED',
                'AFTER',
                $this,
                '_handle404',
                array());
        }

    }

    /**
     * Verify if there is a 404
     * Inspiration comes from <a href="https://github.com/splitbrain/dokuwiki-plugin-notfound/blob/master/action.php">Not Found Plugin</a>
     * @param $event Doku_Event
     * @param $param
     * @return false|void
     * @throws Exception
     */
    function _handle404(&$event, $param)
    {

        global $ID;

        /**
         * Without SQLite, this module does not work further
         */
        $sqlite = Sqlite::getSqlite();
        if ($sqlite == null) {
            return;
        } else {
            $this->pageRules = new PageRules();
        }

        /**
         * Page is an existing id ?
         */
        $targetPage = Page::createPageFromId($ID);
        if ($targetPage->exists()) {
            /**
             * If this is not the root home page
             * and if the canonical id is the not the same,
             * redirect
             */
            if ($ID !== $targetPage->getCanonicalId() && $ID != Site::getHomePageName()) {
                $this->executePermanentRedirect($targetPage->getCanonicalUrl(), self::TARGET_ORIGIN_PERMALINK_EXTENDED);
            }
            return;
        }


        global $ACT;
        if ($ACT != 'show') return;

        $identifier = $ID;

        // Well known
        if (self::isWellKnownFile($identifier)) {
            echo self::PAGE_404;
            Http::setStatus(404);
            $this->logRedirection($ID, "", self::TARGET_ORIGIN_WELL_KNOWN, self::REDIRECT_NOTFOUND_METHOD);
            exit();
        }

        // Shadow banned
        if (self::isShadowBanned($identifier)) {
            $webSiteHomePage = Site::getHomePageName();
            $this->executeTransparentRedirect($webSiteHomePage, self::TARGET_ORIGIN_SHADOW_BANNED);
        }

        /**
         * Page Id
         * {@link Page::CONF_CANONICAL_URL_TYPE}
         */

        $pageIdAbbr = Page::decodePageId($targetPage->getDokuPathName());
        if (
            $pageIdAbbr != null
        ) {
            $page = DatabasePage::createFromPageIdAbbr($pageIdAbbr)->getPage();
            if ($page !== null && $page->exists()) {
                /**
                 * If the url canonical id has changed, we show it
                 * to the writer by performing a permanent redirect
                 */
                if ($identifier != $page->getCanonicalId()) {
                    // Google asks for a redirect
                    // https://developers.google.com/search/docs/advanced/crawling/301-redirects
                    // People access your site through several different URLs.
                    // If, for example, your home page can be reached in multiple ways
                    // (for instance, http://example.com/home, http://home.example.com, or http://www.example.com),
                    // it's a good idea to pick one of those URLs as your preferred (canonical) destination,
                    // and use redirects to send traffic from the other URLs to your preferred URL.
                    $this->executePermanentRedirect($page->getCanonicalUrl(), self::TARGET_ORIGIN_PERMALINK_EXTENDED);
                    return;
                }
                $this->executeTransparentRedirect($page->getDokuwikiId(), self::TARGET_ORIGIN_PERMALINK_EXTENDED);
                return;

            }
            // permanent url not yet in the database

            // permanent id test
            $identifier = $targetPage->getParentId();
            $permanentIdPage = Page::createPageFromId($identifier);
            if ($permanentIdPage->exists()) {
                $this->executeTransparentRedirect($permanentIdPage->getDokuwikiId(), self::TARGET_ORIGIN_PERMALINK_EXTENDED);
                return;
            }

            // Other permanent such as permanent canonical ?
            // We let the process go with the new identifier


        }

        // Global variable needed in the process
        global $conf;

        /**
         * Identifier is a Canonical ?
         */
        $targetPage = Page::createPageFromCanonical($identifier);
        if ($targetPage !== null && $targetPage->exists()) {
            $res = $this->executeTransparentRedirect($targetPage->getDokuwikiId(), self::TARGET_ORIGIN_CANONICAL);
            if ($res) {
                return;
            }
        }

        /**
         * Identifier is an alias
         */
        $targetPage = Page::createPageFromAlias($identifier);
        if ($targetPage !== null && $targetPage->exists()) {
            $res = $this->executePermanentRedirect($targetPage->getCanonicalUrl(), self::TARGET_ORIGIN_ALIAS);
            if ($res) {
                return;
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
        global $ID;
        global $conf;


        global $ACT;
        $ACT = 'edit';

        // If this is a side bar no message.
        // There is always other page with the same name
        $pageName = noNS($ID);
        if ($pageName != $conf['sidebar']) {

            action_plugin_combo_urlmessage::notify($ID, self::GO_TO_EDIT_MODE);

        }


    }


    /**
     * Redirect to an internal page ie:
     *   * on the same domain
     *   * no HTTP redirect
     *   * id rewrite
     * @param string $targetPageId - target page id or an URL
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
        // Change the info id for the sidebar
        $INFO['id'] = $targetPageId;
        /**
         * otherwise there is:
         *   * a meta robot = noindex,follow
         * See {@link tpl_metaheaders()}
         */
        $INFO['exists'] = true;

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
     * An HTTP Redirect to an internal page, no external resources
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
        if (Url::isValidURL($target)) {

            // defend against HTTP Response Splitting
            // https://owasp.org/www-community/attacks/HTTP_Response_Splitting
            $targetUrl = stripctl($target);

        } else {

            // Notify
            // Message can be shown because this is not an external URL
            action_plugin_combo_urlmessage::notify($ID, $targetOrigin);

            // Explode the page ID and the anchor (#)
            $link = explode('#', $target, 2);

            // Query String to pass the message
            $urlParams = [];
            if ($targetOrigin != self::TARGET_ORIGIN_PERMALINK) {
                $urlParams = array(
                    action_plugin_combo_urlmessage::ORIGIN_PAGE => $ID,
                    action_plugin_combo_urlmessage::ORIGIN_TYPE => $targetOrigin
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
            if ($link[1]) {
                $targetUrl .= '#' . rawurlencode($link[1]);
            }

        }


        switch ($method) {
            case self::REDIRECT_PERMANENT_METHOD:
                // header location should before the status
                // because it changes it to 302
                header(self::LOCATION_HEADER_PREFIX . $targetUrl);
                Http::setStatus(301);
                break;
            case self::REDIRECT_NOTFOUND_METHOD:
                // Empty 404 body to not get the standard 404 page of the browser
                // but a blank page to avoid a sort of FOUC.
                // ie the user see a page briefly
                echo self::PAGE_404;
                header(self::REFRESH_HEADER_PREFIX . $targetUrl);
                Http::setStatus(404);
                break;
            default:
                LogUtility::msg("The method ($method) is not an http redirection");
                header('Location: ' . $targetUrl);
                Http::setStatus(302);
                break;
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


        /**
         * Exit
         */
        PluginUtility::softExit("Http Redirect executed");
        return true;

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
        $sqlite = Sqlite::getSqlite();
        $res = $sqlite->storeEntry('redirections_log', $row);

        if (!$res) {
            LogUtility::msg("An error occurred");
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
            if (preg_match($regexpPattern, $ID, $matches)) {
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
        if (Url::isValidURL($calculatedTarget)) {

            $this->executeHttpRedirect($calculatedTarget, self::TARGET_ORIGIN_PAGE_RULES, true);
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
