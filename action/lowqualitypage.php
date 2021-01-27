<?php


use ComboStrap\LowQualityPage;

require_once(__DIR__ . '/../class/LowQualityPage.php');

/**
 *
 */
class action_plugin_combo_lowqualitypage extends DokuWiki_Action_Plugin
{


    public function register(Doku_Event_Handler $controller)
    {

        if ($this->getConf(LowQualityPage::CONF_LOW_QUALITY_PAGE_PROTECTION_ENABLE)) {

            $securityConf = $this->getConf(LowQualityPage::CONF_LOW_QUALITY_PAGE_PROTECTION_MODE);
            if ($securityConf == LowQualityPage::HIDDEN) {
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
        }

    }

    /**
     * Set a low page has hidden
     * @param $event
     * @param $param
     */
    function handleHiddenCheck(&$event, $param)
    {

        $id = $event->data['id'];
        $user = $event->data['user'];
        if (LowQualityPage::isPageToExclude($id, $user)) {
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
        $user = $event->data['user'];
        if (LowQualityPage::isPageToExclude($id, $user)) {
            $event->result = AUTH_NONE;
        }

    }

    /**
     * @param $event
     * @param $param
     * The autocomplete do a search on page name
     */
    function handleSearchPageLookup(&$event, $param)
    {
        $this->excludeLowQualityPageFromSearch($event);
    }

    /**
     * @param $event
     * @param $param
     * The search page do a search on page name
     */
    function handleSearchFullPage(&$event, $param)
    {

        $this->excludeLowQualityPageFromSearch($event);
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
        $this->excludeLowQualityPageFromSearch($event);
    }

    /**
     * @param $event
     */
    private
    function excludeLowQualityPageFromSearch(&$event)
    {

        foreach (array_keys($event->result) as $idx) {
            if (LowQualityPage::isPageToExclude($idx)) {
                unset($event->result[$idx]);
            }
        }

    }


}
