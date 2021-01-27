<?php

use ComboStrap\MetadataUtility;
use ComboStrap\PluginUtility;
use ComboStrap\UrlCanonical;

if (!defined('DOKU_INC')) die();

/**
 *
 *
 *   * The name of the file should be the last name of the class
 *   * There should be only one name
 */
class action_plugin_combo_metacanonical extends DokuWiki_Action_Plugin
{

    /**
     * The conf
     */
    const CANONICAL_LAST_NAMES_COUNT_CONF = 'MinimalNamesCountForAutomaticCanonical';


    function __construct()
    {
        // enable direct access to language strings
        // ie $this->lang
        $this->setupLocale();
    }

    public function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'metaCanonicalProcessing', array());
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
        global $conf;

        /**
         * Split the id by :
         */
        $names = preg_split("/:/", $ID);
        $namesLength = sizeOf($names);

        /**
         * No canonical for bars
         */
        $bars = array($conf['sidebar']);
        $strapTemplateName = 'strap';
        if ($conf['template'] === $strapTemplateName) {
            $bars[] = $conf['tpl'][$strapTemplateName]['headerbar'];
            $bars[] = $conf['tpl'][$strapTemplateName]['footerbar'];
            $bars[] = $conf['tpl'][$strapTemplateName]['sidekickbar'];
        }
        if (in_array($names[$namesLength - 1], $bars)) {
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

        $canonical = MetadataUtility::getMeta(UrlCanonical::CANONICAL_PROPERTY);

        /**
         * The last part of the id as canonical
         */
        // How many last parts are taken into account in the canonical processing (2 by default)
        $canonicalLastNamesCount = $this->getConf(self::CANONICAL_LAST_NAMES_COUNT_CONF, 0);
        if ($canonical == null && $canonicalLastNamesCount > 0) {
            /**
             * Takes the last names part
             */
            if ($namesLength > $canonicalLastNamesCount) {
                $names = array_slice($names, $namesLength - $canonicalLastNamesCount);
            }
            /**
             * If this is a start page, delete the name
             * ie javascript:start will become javascript
             */
            if ($names[$namesLength - 1] == $conf['start']) {
                $names = array_slice($names, 0, $namesLength - 1);
            }
            $canonical = implode(":", $names);
            p_set_metadata($ID, array(UrlCanonical::CANONICAL_PROPERTY => $canonical));
        }

        $canonicalUrl = UrlCanonical::getUrl($canonical);

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
        foreach ($event->data['meta'] as $key => $meta) {
            if ($meta["property"] == $canonicalPropertyKey) {
                $canonicalOgKeyKey = $key;
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

}
