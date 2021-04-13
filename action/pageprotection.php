<?php


use ComboStrap\Auth;
use ComboStrap\DokuPath;
use ComboStrap\LowQualityPage;
use ComboStrap\Page;
use ComboStrap\PageProtection;
use ComboStrap\StringUtility;

require_once(__DIR__ . '/../class/LowQualityPage.php');
require_once(__DIR__ . '/../class/PageProtection.php');
require_once(__DIR__ . '/../class/DokuPath.php');

/**
 *
 */
class action_plugin_combo_pageprotection extends DokuWiki_Action_Plugin
{


    public function register(Doku_Event_Handler $controller)
    {


        $securityConf = $this->getConf(PageProtection::CONF_PAGE_PROTECTION_MODE);
        if (empty($securityConf)) {
            $securityConf = $this->getConf(LowQualityPage::CONF_LOW_QUALITY_PAGE_PROTECTION_MODE);
        }
        if ($securityConf == PageProtection::CONF_VALUE_HIDDEN) {
            /**
             * https://www.dokuwiki.org/devel:event:pageutils_id_hidepage
             */
            $controller->register_hook('PAGEUTILS_ID_HIDEPAGE', 'BEFORE', $this, 'handleHiddenCheck', array());
        } else {
            /**
             * https://www.dokuwiki.org/devel:event:auth_acl_check
             */
            $controller->register_hook('AUTH_ACL_CHECK', 'AFTER', $this, 'handleAclCheck', array());
        }

        /**
         * https://www.dokuwiki.org/devel:event:search_query_pagelookup
         */
        $controller->register_hook('SEARCH_QUERY_PAGELOOKUP', 'AFTER', $this, 'handleSearchPageLookup', array());

        /**
         * https://www.dokuwiki.org/devel:event:search_query_fullpage
         */
        $controller->register_hook('SEARCH_QUERY_FULLPAGE', 'AFTER', $this, 'handleSearchFullPage', array());
        /**
         * https://www.dokuwiki.org/devel:event:feed_data_process
         */
        $controller->register_hook('FEED_DATA_PROCESS', 'AFTER', $this, 'handleRssFeed', array());

        /**
         * Add logged in
         */
        $controller->register_hook('DOKUWIKI_STARTED', 'AFTER', $this, 'handleAnonymousJsIndicator');


    }

    /**
     * Set a low page has hidden
     * @param $event
     * @param $param
     */
    function handleHiddenCheck(&$event, $param)
    {

        $id = $event->data['id'];
        $page = new Page($id);

        if ($page->isProtected()) {
            $event->data['hidden'] = true;
        }

    }

    /**
     * Make the authorization to NONE for low page
     * @param $event
     * @param $param
     */
    function handleAclCheck(&$event, $param)
    {

        $id = $event->data['id'];

        /**
         * When deleting a media, we get this id value
         * This is not a page
         */
        if (StringUtility::endWiths($id, "*")) {

            if ($_SERVER['SCRIPT_NAME'] == "/lib/exe/mediamanager.php") {
                return;
            }

        }

        $dokuPath = DokuPath::createFromId($id);
        if ($dokuPath->isPage()) {
            /**
             * ACL ID have the root form
             */
            $cleanId = cleanID($id);
            /**
             * It should be only a page
             * https://www.dokuwiki.org/devel:event:auth_acl_check
             */
            $user = $event->data['user'];
            $page = new Page($cleanId);
            if ($page->isProtected($user)) {
                $event->result = AUTH_NONE;
            }
        }

    }

    /**
     * @param $event
     * @param $param
     * The autocomplete do a search on page name
     */
    function handleSearchPageLookup(&$event, $param)
    {
        $this->excludePageFromSearch($event);
    }

    /**
     * @param $event
     * @param $param
     * The search page do a search on page name
     */
    function handleSearchFullPage(&$event, $param)
    {

        $this->excludePageFromSearch($event);
    }

    /**
     *
     * @param $event
     * @param $param
     * The Rss
     * https://www.dokuwiki.org/syndication
     * Example
     * https://example.com/feed.php?type=rss2&num=5
     */
    function handleRssFeed(&$event, $param)
    {
        $this->excludePageFromSearch($event);
    }

    /**
     * @param $event
     */
    private
    function excludePageFromSearch(&$event)
    {

        $result = $event->result;
        /**
         * The value is always an array
         * but as we got this error:
         * ```
         * array_keys() expects parameter 1 to be array
         * ```
         */
        if (is_array($result)) {
            foreach (array_keys($result) as $idx) {
                $page = new Page($idx);
                if ($page->isProtected()) {
                    unset($result[$idx]);
                }
            }
        }

    }

    function handleAnonymousJsIndicator(&$event, $param)
    {

        global $JSINFO;
        $JSINFO[PageProtection::JS_IS_PUBLIC_NAVIGATION_INDICATOR] = !Auth::isLoggedIn();


    }

}
