<?php


use ComboStrap\ExceptionNotFound;
use ComboStrap\MarkupPath;
use ComboStrap\Site;

/**
 * Add all canonical HTML metadata
 *
 * In 1.14. we keep the name of the class with canonical to be able to update
 * Above 1.15, in a release branch, you can just modify it
 */
class action_plugin_combo_metacanonical extends DokuWiki_Action_Plugin
{


    const APPLE_MOBILE_WEB_APP_TITLE_META = "apple-mobile-web-app-title";
    const APPLICATION_NAME_META = "application-name";

    /**
     * @throws ExceptionNotFound
     */
    public static function getContextPageForHeadHtmlMeta(): MarkupPath
    {

        try {
            $page = MarkupPath::createFromRequestedPage();
        } catch (ExceptionNotFound $e) {
            // $_SERVER['SCRIPT_NAME']== "/lib/exe/mediamanager.php"
            // $ID is null
            // Admin call for instance
            throw new ExceptionNotFound("No requested page");
        }

        /**
         * No metadata for slot page
         */
        if ($page->isSlot()) {
            throw new ExceptionNotFound("Secondary slot");
        }

        return $page;
    }

    public function register(Doku_Event_Handler $controller)
    {
        /**
         * https://www.dokuwiki.org/devel:event:tpl_metaheader_output
         */
        $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'htmlHeadMetadataProcessing', array());


    }


    function htmlHeadMetadataProcessing($event)
    {

        try {
            $page = self::getContextPageForHeadHtmlMeta();
        } catch (ExceptionNotFound $e) {
            return;
        }


        /**
         * Add the canonical metadata value
         */
        $this->canonicalHeadMetadata($event, $page);
        /**
         * Add the app name value
         */
        $this->appNameMetadata($event, $page);

    }

    /**
     * Dokuwiki has already a canonical methodology
     * https://www.dokuwiki.org/canonical
     *
     */
    private function canonicalHeadMetadata($event, MarkupPath $page)
    {

        /**
         * Where do we pick the canonical URL
         * Canonical from meta
         *
         * FYI: The creation of the link was extracted from
         * {@link wl()} that call {@link idfilter()} that performs just a replacement
         * Calling the wl function will not work because
         * {@link wl()} use the constant DOKU_URL that is set before any test via getBaseURL(true)
         */
        $canonicalUrl = $page->getAbsoluteCanonicalUrl();

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
     * Add the following meta
     * <meta name="apple-mobile-web-app-title" content="appName">
     * <meta name="application-name" content="appName">
     *
     * @param $event
     * @param MarkupPath $page
     * @return void
     */
    private function appNameMetadata($event, MarkupPath $page)
    {
        $applicationName = Site::getName();

        $applicationMetaNameValues = [
            self::APPLE_MOBILE_WEB_APP_TITLE_META,
            self::APPLICATION_NAME_META
        ];
        $metaNameKeyProperty = "name";
        foreach ($applicationMetaNameValues as $applicationNameValue) {

            $appMobileWebAppTitle = array($metaNameKeyProperty => $applicationNameValue, "content" => $applicationName);;
            try {
                $metaKey = $this->getMetaArrayIndex($metaNameKeyProperty, $applicationNameValue, $event->data['meta']);
                // Update
                $event->data['meta'][$metaKey] = $appMobileWebAppTitle;
            } catch (ExceptionNotFound $e) {
                // Add
                $event->data['meta'][] = $appMobileWebAppTitle;
            }
        }


    }

    /**
     * @throws \ComboStrap\ExceptionNotFound
     */
    private function getMetaArrayIndex(string $keyToSearch, string $keyValueToSearch, $metas)
    {
        // Search if the canonical property is already present
        foreach ($metas as $metaKey => $metaValue) {
            if (array_key_exists($keyToSearch, $metaValue)) {
                /**
                 * We may have several properties
                 */
                if ($metaValue[$keyToSearch] == $keyValueToSearch) {
                    return $metaKey;
                }
            }
        }
        throw new ExceptionNotFound("The meta key {$keyToSearch} with the value {$keyValueToSearch} was not found");
    }


}
