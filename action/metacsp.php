<?php

use ComboStrap\Site;
use ComboStrap\StringUtility;

if (!defined('DOKU_INC')) die();

/**
 *
 * Adding security directive
 *
 */
class action_plugin_combo_metacsp extends DokuWiki_Action_Plugin
{


    function __construct()
    {
        // enable direct access to language strings
        // ie $this->lang
        $this->setupLocale();
    }

    public function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'metaCsp', array());
    }

    /**
     * Dokuwiki has already a canonical methodology
     * https://www.dokuwiki.org/canonical
     *
     * @param $event
     */
    function metaCsp($event)
    {


        /**
         * HTML meta directives
         */
        $directives = [
            'block-all-mixed-content', // no http, https
        ];

        // Search if the CSP property is already present
        $cspKey = null;
        foreach ($event->data['meta'] as $key => $meta) {
            if (isset($meta["http-equiv"])) {
                if ($meta["http-equiv"] == "Content-Security-Policy") {
                    $cspKey = $key;
                }
            }
        }
        if ($cspKey != null) {
            $actualDirectives = StringUtility::explodeAndTrim($event->data['meta'][$cspKey]["content"], ",");
            $directives = array_merge($actualDirectives, $directives);
            $event->data['meta'][$cspKey] = [
                "http-equiv" => "Content-Security-Policy",
                "content" => join(", ", $directives)
            ];
        } else {
            $event->data['meta'][] = [
                "http-equiv" => "Content-Security-Policy",
                "content" => join(",", $directives)
            ];
        }

        /**
         * Http header CSP directives
         */
        $httpHeaderReferer = $_SERVER['HTTP_REFERER'];
        $httpDirectives = [];
        if (strpos($httpHeaderReferer, Site::getUrl()) === false) {
            // not same origin
            $httpDirectives = [
                "Content-Security-Policy: frame-ancestors 'none'", // the page cannot be used in a iframe (clickjacking),
                "X-Frame-Options: deny" // the page cannot be used in a iframe (clickjacking) - deprecated for frame ancestores
            ];
        }
        foreach ($httpDirectives as $httpDirective) {
            header($httpDirective);
        }

    }

}
