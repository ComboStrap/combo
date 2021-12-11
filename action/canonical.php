<?php

use ComboStrap\Canonical;
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

    const CONF_CANONICAL_FOR_GA_PAGE_VIEW = "useCanonicalValueForGoogleAnalyticsPageView";
    const CANONICAL = Canonical::PROPERTY_NAME;


    function __construct()
    {
        // enable direct access to language strings
        // ie $this->lang
        $this->setupLocale();
    }

    public function register(Doku_Event_Handler $controller)
    {

        /**
         * Add canonical to javascript
         */
        $controller->register_hook('TPL_ACT_RENDER', 'BEFORE', $this, 'addCanonicalToJavascript', array());

    }



    /**
     * Add the canonical value to JSON
     * to be able to report only on canonical value and not on path
     * @param $event
     * @noinspection SpellCheckingInspection
     */
    function addCanonicalToJavascript($event)
    {

        global $JSINFO;
        $page = Page::createPageFromRequestedPage();
        if ($page->getCanonical() !== null) {
            $JSINFO[Canonical::PROPERTY_NAME] = $page->getCanonical();
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
