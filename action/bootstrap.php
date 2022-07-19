<?php
/**
 * DokuWiki Plugin Js Action
 *
 */

use ComboStrap\Bootstrap;
use ComboStrap\LogUtility;
use ComboStrap\PluginUtility;
use ComboStrap\Site;

if (!defined('DOKU_INC')) die();

/**
 * Adding bootstrap if not present
 *
 * This is a little bit silly but make the experience better
 *
 * With the default dokuwiki, they set a color on a:link and a:visited
 * which conflicts immediately
 *
 */
class action_plugin_combo_bootstrap extends DokuWiki_Action_Plugin
{

    const BOOTSTRAP_JAVASCRIPT_BUNDLE_COMBO_ID = "bootstrap-javascript-bundle-combo";
    const BOOTSTRAP_STYLESHEET_COMBO_ID = "bootstrap-stylesheet-combo";
    public const CONF_PRELOAD_CSS = "preloadCss";
    /**
     * Jquery UI
     */
    public const CONF_JQUERY_DOKU = 'jQueryDoku';
    public const CONF_USE_CDN = "useCDN";
    /**
     * Disable the javascript of Dokuwiki
     * if public
     * https://combostrap.com/frontend/optimization
     */
    public const CONF_DISABLE_BACKEND_JAVASCRIPT = "disableBackendJavascript";

    /**
     * @param Doku_Event $event
     * @param $param
     * Function that handle the META HEADER event
     *   * It will add the Bootstrap Js and CSS
     *   * Make all script and resources defer
     * @throws Exception
     * @noinspection PhpUnused
     */
    public static function handle_bootstrap(Doku_Event &$event, $param)
    {


        $newHeaderTypes = array();
        $bootstrapHeaders = Bootstrap::getBootstrapMetaHeaders();
        $eventHeaderTypes = $event->data;
        foreach ($eventHeaderTypes as $headerType => $headerData) {
            switch ($headerType) {

                case "link":
                    // index, rss, manifest, search, alternate, stylesheet
                    // delete edit
                    $bootstrapCss = $bootstrapHeaders[$headerType]['css'];
                    $headerData[] = $bootstrapCss;

                    // preload all CSS is an heresy as it creates a FOUC (Flash of non-styled element)
                    // but we know it only now and this is it
                    $cssPreloadConf = tpl_getConf(self::CONF_PRELOAD_CSS);
                    $newLinkData = array();
                    foreach ($headerData as $linkData) {
                        switch ($linkData['rel']) {
                            case 'edit':
                                break;
                            case 'preload':
                                /**
                                 * Preload can be set at the array level with the critical attribute
                                 * If the preload attribute is present
                                 * We get that for instance for css animation style sheet
                                 * that are not needed for rendering
                                 */
                                if (isset($linkData["as"])) {
                                    if ($linkData["as"] === "style") {
                                        $newLinkData[] = self::captureStylePreloadingAndTransformToPreloadCssTag($linkData);
                                        continue 2;
                                    }
                                }
                                $newLinkData[] = $linkData;
                                break;
                            case 'stylesheet':
                                if ($cssPreloadConf) {
                                    $newLinkData[] = self::captureStylePreloadingAndTransformToPreloadCssTag($linkData);
                                    continue 2;
                                }
                                $newLinkData[] = $linkData;
                                break;
                            default:
                                $newLinkData[] = $linkData;
                                break;
                        }
                    }

                    $newHeaderTypes[$headerType] = $newLinkData;
                    break;

                case "script":

                    /**
                     * Do we delete the dokuwiki javascript ?
                     */
                    $scriptToDeletes = [];
                    if (empty($_SERVER['REMOTE_USER']) && tpl_getConf(self::CONF_DISABLE_BACKEND_JAVASCRIPT, 0)) {
                        $scriptToDeletes = [
                            //'JSINFO', Don't delete Jsinfo !! It contains metadata information (that is used to get context)
                            'js.php'
                        ];
                        if (Bootstrap::getBootStrapMajorVersion() == "5") {
                            // bs 5 does not depends on jquery
                            $scriptToDeletes[] = "jquery.php";
                        }
                    }

                    /**
                     * The new script array
                     */
                    $newScriptData = array();
                    // A variable to hold the Jquery scripts
                    // jquery-migrate, jquery, jquery-ui ou jquery.php
                    // see https://www.dokuwiki.org/config:jquerycdn
                    $jqueryDokuScripts = array();
                    foreach ($headerData as $scriptData) {

                        foreach ($scriptToDeletes as $scriptToDelete) {
                            if (isset($scriptData["_data"]) && !empty($scriptData["_data"])) {
                                $haystack = $scriptData["_data"];
                            } else {
                                $haystack = $scriptData["src"];
                            }
                            if (preg_match("/$scriptToDelete/i", $haystack)) {
                                continue 2;
                            }
                        }

                        $critical = false;
                        if (isset($scriptData["critical"])) {
                            $critical = $scriptData["critical"];
                            unset($scriptData["critical"]);
                        }

                        // defer is only for external resource
                        // if this is not, this is illegal
                        if (isset($scriptData["src"])) {
                            if (!$critical) {
                                $scriptData['defer'] = null;
                            }
                        }

                        if (isset($scriptData["type"])) {
                            $type = strtolower($scriptData["type"]);
                            if ($type == "text/javascript") {
                                unset($scriptData["type"]);
                            }
                        }

                        // The charset attribute on the script element is obsolete.
                        if (isset($scriptData["charset"])) {
                            unset($scriptData["charset"]);
                        }

                        // Jquery ?
                        $jqueryFound = false;
                        // script may also be just an online script without the src attribute
                        if (array_key_exists('src', $scriptData)) {
                            $jqueryFound = strpos($scriptData['src'], 'jquery');
                        }
                        if ($jqueryFound === false) {
                            $newScriptData[] = $scriptData;
                        } else {
                            $jqueryDokuScripts[] = $scriptData;
                        }

                    }

                    // Add Jquery at the beginning
                    $boostrapMajorVersion = Bootstrap::getBootStrapMajorVersion();
                    if ($boostrapMajorVersion == "4") {
                        if (
                            empty($_SERVER['REMOTE_USER'])
                            && tpl_getConf(self::CONF_JQUERY_DOKU) == 0
                        ) {
                            // We take the Jquery of Bootstrap
                            $newScriptData = array_merge($bootstrapHeaders[$headerType], $newScriptData);
                        } else {
                            // Logged in
                            // We take the Jqueries of doku and we add Bootstrap
                            $newScriptData = array_merge($jqueryDokuScripts, $newScriptData); // js
                            // We had popper of Bootstrap
                            $newScriptData[] = $bootstrapHeaders[$headerType]['popper'];
                            // We had the js of Bootstrap
                            $newScriptData[] = $bootstrapHeaders[$headerType]['js'];
                        }
                    } else {

                        // There is no JQuery in 5
                        // We had the js of Bootstrap and popper
                        // Add Jquery before the js.php
                        $newScriptData = array_merge($jqueryDokuScripts, $newScriptData); // js
                        // Then add at the top of the top (first of the first) bootstrap
                        // Why ? Because Jquery should be last to be able to see the missing icon
                        // https://stackoverflow.com/questions/17367736/jquery-ui-dialog-missing-close-icon
                        $bootstrap[] = $bootstrapHeaders[$headerType]['popper'];
                        $bootstrap[] = $bootstrapHeaders[$headerType]['js'];
                        $newScriptData = array_merge($bootstrap, $newScriptData);

                    }


                    $newHeaderTypes[$headerType] = $newScriptData;
                    break;
                case "meta":
                    $newHeaderData = array();
                    foreach ($headerData as $metaData) {
                        // Content should never be null
                        // Name may change
                        // https://www.w3.org/TR/html4/struct/global.html#edef-META
                        if (!key_exists("content", $metaData)) {
                            $message = "Strap - The head meta (" . print_r($metaData, true) . ") does not have a content property";
                            LogUtility::error($message);
                        } else {
                            $content = $metaData["content"];
                            if (empty($content)) {
                                $messageEmpty = "the below head meta has an empty content property (" . print_r($metaData, true) . ")";
                                LogUtility::error($messageEmpty);
                            } else {
                                $newHeaderData[] = $metaData;
                            }
                        }
                    }
                    $newHeaderTypes[$headerType] = $newHeaderData;
                    break;
                case "noscript": // https://github.com/ComboStrap/dokuwiki-plugin-gtm/blob/master/action.php#L32
                case "style":
                    $newHeaderTypes[$headerType] = $headerData;
                    break;
                default:
                    $message = "Strap - The header type ($headerType) is unknown and was not controlled.";
                    $newHeaderTypes[$headerType] = $headerData;
                    LogUtility::error($message);

            }
        }

        $event->data = $newHeaderTypes;


    }

    /**
     * @param $linkData - an array of link style sheet data
     * @return array - the array with the preload attributes
     */
    public static function captureStylePreloadingAndTransformToPreloadCssTag($linkData): array
    {
        /**
         * Save the stylesheet to load it at the end
         */
        global $preloadedCss;
        $preloadedCss[] = $linkData;

        /**
         * Modify the actual tag data
         * Change the loading mechanism to preload
         */
        $linkData['rel'] = 'preload';
        $linkData['as'] = 'style';
        return $linkData;
    }

    /**
     * Add the preloaded CSS resources
     * at the end
     */
    public static function addPreloadedResources()
    {
        // For the preload if any
        global $preloadedCss;
        //
        // Note: Adding this css in an animationFrame
        // such as https://github.com/jakearchibald/svgomg/blob/master/src/index.html#L183
        // would be difficult to test
        if (isset($preloadedCss)) {
            foreach ($preloadedCss as $link) {
                $htmlLink = '<link rel="stylesheet" href="' . $link['href'] . '" ';
                if ($link['crossorigin'] != "") {
                    $htmlLink .= ' crossorigin="' . $link['crossorigin'] . '" ';
                }
                if (!empty($link['class'])) {
                    $htmlLink .= ' class="' . $link['class'] . '" ';
                }
                // No integrity here
                $htmlLink .= '>';
                ptln($htmlLink);
            }
            /**
             * Reset
             * Needed in test when we start two requests
             */
            $preloadedCss = [];
        }

    }


    /**
     * Registers our handler for the MANIFEST_SEND event
     * https://www.dokuwiki.org/devel:event:js_script_list
     * manipulate the list of JavaScripts that will be concatenated
     * @param Doku_Event_Handler $controller
     */
    public function register(Doku_Event_Handler $controller)
    {

        $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'handle_bootstrap');


    }


}

