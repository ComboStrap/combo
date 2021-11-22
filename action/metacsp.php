<?php

use ComboStrap\LogUtility;
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

        $controller->register_hook('DOKUWIKI_STARTED', 'BEFORE', $this, 'httpHeaderCsp', array());
        $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'htmlMetaCsp', array());

    }

    /**
     * Dokuwiki has already a canonical methodology
     * https://www.dokuwiki.org/canonical
     *
     * @param $event
     */
    function htmlMetaCsp($event)
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
                if ($meta["http-equiv"] == "content-security-policy") {
                    $cspKey = $key;
                }
            }
        }
        if ($cspKey != null) {
            $actualDirectives = StringUtility::explodeAndTrim($event->data['meta'][$cspKey]["content"], ",");
            $directives = array_merge($actualDirectives, $directives);
            $event->data['meta'][$cspKey] = [
                "http-equiv" => "content-security-policy",
                "content" => join(", ", $directives)
            ];
        } else {
            $event->data['meta'][] = [
                "http-equiv" => "content-security-policy",
                "content" => join(",", $directives)
            ];
        }

    }

    function httpHeaderCsp($event)
    {
        /**
         * Http header CSP directives
         */
        $httpHeaderReferer = $_SERVER['HTTP_REFERER'];
        $httpDirectives = [];
        if (strpos($httpHeaderReferer, Site::getBaseUrl()) === false) {
            // not same origin
            $httpDirectives = [
                // the page cannot be used in a iframe (clickjacking),
                "content-security-policy: frame-ancestors 'none'",
                // the page cannot be used in a iframe (clickjacking) - deprecated for frame ancestores
                "X-Frame-Options: SAMEORIGIN",
                // stops a browser from trying to MIME-sniff the content type and forces it to stick with the declared content-type
                "X-Content-Type-Options: nosniff",
                // sends the origin if cross origin otherwise the full refer for same origin
                "Referrer-Policy: strict-origin-when-cross-origin",
                // controls DNS prefetching, allowing browsers to proactively perform domain name resolution on external links, images, CSS, JavaScript, and more. This prefetching is performed in the background, so the DNS is more likely to be resolved by the time the referenced items are needed. This reduces latency when the user clicks a link.
                "X-DNS-Prefetch-Control: on",
                // This header stops pages from loading when they detect reflected cross-site scripting (XSS) attacks. Although this protection is not necessary when sites implement a strong Content-Security-Policy disabling the use of inline JavaScript ('unsafe-inline'), it can still provide protection for older web browsers that don't support CSP.
                "X-XSS-Protection: 1; mode=block"
            ];
        }
        if (!headers_sent()) {
            foreach ($httpDirectives as $httpDirective) {
                header($httpDirective);
            }
        } else {
            LogUtility::msg("HTTP Headers have already ben sent. We couldn't add the CSP security header", LogUtility::LVL_MSG_WARNING,"security");
        }
    }

}
