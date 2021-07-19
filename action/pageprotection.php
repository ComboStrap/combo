<?php


use ComboStrap\Identity;
use ComboStrap\DokuPath;
use ComboStrap\LatePublication;
use ComboStrap\LowQualityPage;
use ComboStrap\Page;
use ComboStrap\PageProtection;
use ComboStrap\Publication;
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


        /**
         * https://www.dokuwiki.org/devel:event:pageutils_id_hidepage
         */
        $controller->register_hook('PAGEUTILS_ID_HIDEPAGE', 'BEFORE', $this, 'handleHiddenCheck', array());

        /**
         * https://www.dokuwiki.org/devel:event:auth_acl_check
         */
        $controller->register_hook('AUTH_ACL_CHECK', 'AFTER', $this, 'handleAclCheck', array());

        /**
         * https://www.dokuwiki.org/devel:event:sitemap_generate
         */
        $controller->register_hook('SITEMAP_GENERATE', 'AFTER', $this, 'handleSiteMapGenerate', array());

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
     * Set page has hidden
     * @param $event
     * @param $param
     */
    function handleHiddenCheck(&$event, $param)
    {

        /**
         * Only for public
         */
        if (Identity::isLoggedIn()) {
            return;
        }

        $id = $event->data['id'];
        if ($id == null) {
            /**
             * Happens in test when rendering
             * with instructions only
             */
            return;
        }
        $page = new Page($id);

        if ($page->isLowQualityPage()) {
            if (LowQualityPage::getLowQualityProtectionMode() == PageProtection::CONF_VALUE_HIDDEN) {
                $event->data['hidden'] = true;
                return;
            }
        }
        if ($page->isLatePublication()) {
            if (Publication::getLatePublicationProtectionMode() == PageProtection::CONF_VALUE_HIDDEN) {
                $event->data['hidden'] = true;
            }

        }

    }

    /**
     *
     * https://www.dokuwiki.org/devel:event:auth_acl_check
     * @param $event
     * @param $param
     */
    function handleAclCheck(&$event, $param)
    {
        /**
         * Only for public
         *
         * Note: user is also
         * to be found at
         * $user = $event->data['user'];
         */
        if (Identity::isLoggedIn()) {
            return;
        }

        /**
         * Are we on a page script
         */
        $imageScript = ["/lib/exe/mediamanager.php", "/lib/exe/detail.php"];
        if (in_array($_SERVER['SCRIPT_NAME'], $imageScript)) {
            // id may be null or end with a star
            // this is not a image
            return;
        }

        $id = $event->data['id'];

        $dokuPath = DokuPath::createUnknownFromId($id);
        if ($dokuPath->isPage()) {

            /**
             * It should be only a page
             * https://www.dokuwiki.org/devel:event:auth_acl_check
             */
            $page = new Page($id);

            if ($page->isLowQualityPage()) {
                if ($this->getConf(LowQualityPage::CONF_LOW_QUALITY_PAGE_PROTECTION_ENABLE, true)) {
                    $securityConf = $this->getConf(LowQualityPage::CONF_LOW_QUALITY_PAGE_PROTECTION_MODE, PageProtection::CONF_VALUE_ACL);
                    if ($securityConf == PageProtection::CONF_VALUE_ACL) {
                        $event->result = AUTH_NONE;
                        return;
                    }
                }
            }
            if ($page->isLatePublication()) {
                if ($this->getConf(Publication::CONF_LATE_PUBLICATION_PROTECTION_ENABLE, true)) {
                    $securityConf = $this->getConf(Publication::CONF_LATE_PUBLICATION_PROTECTION_MODE, PageProtection::CONF_VALUE_ACL);
                    if ($securityConf == PageProtection::CONF_VALUE_ACL) {
                        $event->result = AUTH_NONE;
                        return;
                    }
                }
            }

        }

    }

    function handleSiteMapGenerate(&$event, $param)
    {


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
                if ($page->isLowQualityPage()) {
                    $securityConf = $this->getConf(LowQualityPage::CONF_LOW_QUALITY_PAGE_PROTECTION_MODE);
                    if (in_array($securityConf, [PageProtection::CONF_VALUE_ACL, PageProtection::CONF_VALUE_HIDDEN])) {
                        $event->result = AUTH_NONE;
                        return;
                    }
                }
                if ($page->isLatePublication()) {
                    $securityConf = $this->getConf(Publication::CONF_LATE_PUBLICATION_PROTECTION_MODE);
                    if (in_array($securityConf, [PageProtection::CONF_VALUE_ACL, PageProtection::CONF_VALUE_HIDDEN])) {
                        $event->result = AUTH_NONE;
                        return;
                    }
                }
            }
        }

    }

    function handleAnonymousJsIndicator(&$event, $param)
    {

        global $JSINFO;
        $JSINFO[PageProtection::JS_IS_PUBLIC_NAVIGATION_INDICATOR] = !Identity::isLoggedIn();


    }

}
