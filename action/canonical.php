<?php

use ComboStrap\Canonical;
use ComboStrap\Site;
use ComboStrap\SiteConfig;
use ComboStrap\WikiPath;
use ComboStrap\ExceptionNotFound;
use ComboStrap\MarkupPath;
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
         * The {@link jsinfo()} is called in the {@link tpl_metaheaders()}
         * 'TPL_METAHEADER_OUTPUT' event has already the script with the JSINFO
         * 'TPL_ACT_RENDER' is triggered just before
         */
        $controller->register_hook('TPL_ACT_RENDER', 'AFTER', $this, 'addCanonicalToJavascript', array());

    }


    /**
     * Add the canonical value to JSON
     * to be able to report only on canonical value and not on path
     * @param $event
     * @noinspection SpellCheckingInspection
     */
    function addCanonicalToJavascript($event)
    {

        try {
            $page = MarkupPath::createFromRequestedPage();
        } catch (ExceptionNotFound $e) {
            return;
        }
        global $JSINFO;
        try {
            $canonical = $page->getCanonical()->toAbsoluteId();
            $JSINFO[Canonical::PROPERTY_NAME] = $canonical;
        } catch (ExceptionNotFound $e) {
            return;
        }

        if (isset($JSINFO["ga"]) && SiteConfig::getConfValue(self::CONF_CANONICAL_FOR_GA_PAGE_VIEW, 1)) {
            //
            // The path portion of a URL. This value should start with a slash (/) character.
            // As said here
            // https://developers.google.com/analytics/devguides/collection/analyticsjs/pages#pageview_fields
            //
            //
            // For the modification instructions
            // https://developers.google.com/analytics/devguides/collection/analyticsjs/pages#pageview_fields
            $pageViewCanonical = str_replace(WikiPath::NAMESPACE_SEPARATOR_DOUBLE_POINT, "/", $canonical);
            if ($pageViewCanonical[0] != "/") {
                $pageViewCanonical = "/$pageViewCanonical";
            }
            $JSINFO["ga"]["pageview"] = $pageViewCanonical;
        }

    }

}
