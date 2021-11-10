<?php

use ComboStrap\DokuPath;
use ComboStrap\Page;
use ComboStrap\PluginUtility;

if (!defined('DOKU_INC')) die();

/**
 *
 *
 */
class action_plugin_combo_canonical extends DokuWiki_Action_Plugin
{

    /**
     * The conf
     */
    const CONF_CANONICAL_LAST_NAMES_COUNT = 'MinimalNamesCountForAutomaticCanonical';
    const CONF_CANONICAL_FOR_GA_PAGE_VIEW = "useCanonicalValueForGoogleAnalyticsPageView";
    const CANONICAL = "canonical";


    function __construct()
    {
        // enable direct access to language strings
        // ie $this->lang
        $this->setupLocale();
    }

    public function register(Doku_Event_Handler $controller)
    {
        /**
         * https://www.dokuwiki.org/devel:event:tpl_metaheader_output
         */
        $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'metaCanonicalProcessing', array());

        /**
         * Add canonical to javascript
         */
        $controller->register_hook('TPL_ACT_RENDER', 'BEFORE', $this, 'addCanonicalToJavascript', array());

    }

    /**
     * Dokuwiki has already a canonical methodology
     * https://www.dokuwiki.org/canonical
     *
     * @param $event
     */
    function metaCanonicalProcessing($event)
    {

        global $ID;
        if (empty($ID)) {
            // $_SERVER['SCRIPT_NAME']== "/lib/exe/mediamanager.php"
            // $ID is null
            return;
        }

        $page = Page::createPageFromId($ID);

        /**
         * No canonical for slot page
         */
        if ($page->isSlot()) {
            return;
        }

        /**
         * Where do we pick the canonical URL
         */
        /**
         * Canonical from meta
         *
         * FYI: The creation of the link was extracted from
         * {@link wl()} that call {@link idfilter()} that performs just a replacement
         * Calling the wl function will not work because
         * {@link wl()} use the constant DOKU_URL that is set before any test via getBaseURL(true)
         */


        $canonicalUrl = $page->getCanonicalUrl();

        /**
         * Replace the meta entry
         *
         * First search the key of the meta array
         */
        $canonicalKey = "";
        $canonicalRelArray = array("rel" => "canonical", "href" => $canonicalUrl);
        foreach ($event->data['link'] as $key => $link) {
            if ($link["rel"] == "canonical") {
                $canonicalKey = $key;
            }
        }
        if ($canonicalKey != "") {
            // Update
            $event->data['link'][$canonicalKey] = $canonicalRelArray;
        } else {
            // Add
            $event->data['link'][] = $canonicalRelArray;
        }

        /**
         * Add the Og canonical meta
         * https://developers.facebook.com/docs/sharing/webmasters/getting-started/versioned-link/
         */
        $canonicalOgKeyKey = "";
        $canonicalPropertyKey = "og:url";
        $canonicalOgArray = array("property" => $canonicalPropertyKey, "content" => $canonicalUrl);
        // Search if the canonical property is already present
        foreach ($event->data['meta'] as $key => $meta) {
            if (array_key_exists("property", $meta)) {
                /**
                 * We may have several properties
                 */
                if ($meta["property"] == $canonicalPropertyKey) {
                    $canonicalOgKeyKey = $key;
                }
            }
        }
        if ($canonicalOgKeyKey != "") {
            // Update
            $event->data['meta'][$canonicalOgKeyKey] = $canonicalOgArray;
        } else {
            // Add
            $event->data['meta'][] = $canonicalOgArray;
        }

    }

    /**
     * Add the canonical value to JSON
     * @param $event
     * @noinspection SpellCheckingInspection
     */
    function addCanonicalToJavascript($event)
    {

        global $JSINFO;
        $page = Page::createPageFromRequestedPage();
        if ($page->getCanonical() != null) {
            $JSINFO[Page::CANONICAL_PROPERTY] = $page->getCanonical();
            if (isset($JSINFO["ga"]) && PluginUtility::getConfValue(self::CONF_CANONICAL_FOR_GA_PAGE_VIEW, 1)) {
                //
                // The path portion of a URL. This value should start with a slash (/) character.
                // As said here
                // https://developers.google.com/analytics/devguides/collection/analyticsjs/pages#pageview_fields
                //
                //
                // For the modification instructions
                // https://developers.google.com/analytics/devguides/collection/analyticsjs/pages#pageview_fields
                $pageViewCanonical = str_replace(DokuPath::PATH_SEPARATOR, "/", $page->getCanonical());
                if ($pageViewCanonical[0] != "/") {
                    $pageViewCanonical = "/$pageViewCanonical";
                }
                $JSINFO["ga"]["pageview"] = $pageViewCanonical;
            }
        }
    }

}
