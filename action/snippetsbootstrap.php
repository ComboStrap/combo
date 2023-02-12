<?php
/**
 * DokuWiki Plugin Js Action
 *
 */

use ComboStrap\Bootstrap;
use ComboStrap\ExceptionNotFound;
use ComboStrap\ExecutionContext;
use ComboStrap\Identity;
use ComboStrap\LogUtility;
use ComboStrap\PageTemplate;
use ComboStrap\PluginUtility;
use ComboStrap\Site;

if (!defined('DOKU_INC')) die();

/**
 *
 *
 */
class action_plugin_combo_snippetsbootstrap extends DokuWiki_Action_Plugin
{


    public const CONF_PRELOAD_CSS = "preloadCss";
    /**
     * Use the Jquery of Dokuwiki and not of Bootstrap
     */
    public const CONF_JQUERY_DOKU = 'jQueryDoku';
    public const CONF_JQUERY_DOKU_DEFAULT = 0;

    /**
     * Disable the javascript of Dokuwiki
     * if public
     * https://combostrap.com/frontend/optimization
     */
    public const CONF_DISABLE_BACKEND_JAVASCRIPT = "disableBackendJavascript";

    /**
     * This is so a bad practice, default to no
     * but fun to watch
     */
    const CONF_PRELOAD_CSS_DEFAULT = 0;
    const JQUERY_CANONICAL = "jquery";
    const FRONT_END_OPTIMIZATION_CANONICAL = 'frontend:optimization';


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
        $bootstrap = Bootstrap::getFromContext();
        $bootStrapMajorVersion = $bootstrap->getMajorVersion();
        $eventHeaderTypes = $event->data;
        foreach ($eventHeaderTypes as $headTagName => $headTagsAsArray) {
            switch ($headTagName) {

                case "link":
                    /**
                     * Link tag processing
                     * ie index, rss, manifest, search, alternate, stylesheet
                     */
                    $headTagsAsArray[] = $bootstrap->getCssSnippet()->toDokuWikiArray();

                    // preload all CSS is an heresy as it creates a FOUC (Flash of non-styled element)
                    // but we know it only now and this is fun to experience for the user
                    $cssPreloadConf = Site::getConfValue(self::CONF_PRELOAD_CSS, self::CONF_PRELOAD_CSS_DEFAULT);
                    $newLinkData = array();
                    foreach ($headTagsAsArray as $linkData) {
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

                    $newHeaderTypes[$headTagName] = $newLinkData;
                    break;

                case "script":

                    /**
                     * Script processing
                     *
                     * Do we delete the dokuwiki javascript ?
                     */
                    $scriptToDeletes = [];
                    $disableBackend = Site::getConfValue(self::CONF_DISABLE_BACKEND_JAVASCRIPT, 0);
                    if (!Identity::isLoggedIn() && $disableBackend) {
                        $scriptToDeletes = [
                            //'JSINFO', Don't delete Jsinfo !! It contains metadata information (that is used to get context)
                            'js.php'
                        ];
                        if ($bootStrapMajorVersion == "5") {
                            // bs 5 does not depends on jquery
                            $scriptToDeletes[] = "jquery.php";
                        }
                    }

                    /**
                     * The new script array
                     * that will replace the actual
                     */
                    $newScriptTagAsArray = array();
                    /**
                     * Scan:
                     *   * Capture the Dokuwiki Jquery Tags
                     *   * Delete for optimization if needed
                     *
                     * @var array A variable to hold the Jquery scripts
                     * jquery-migrate, jquery, jquery-ui ou jquery.php
                     * see https://www.dokuwiki.org/config:jquerycdn
                     */
                    $jqueryDokuScriptsTagsAsArray = array();
                    foreach ($headTagsAsArray as $scriptData) {

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
                            $newScriptTagAsArray[] = $scriptData;
                        } else {
                            $jqueryDokuScriptsTagsAsArray[] = $scriptData;
                        }

                    }

                    /**
                     * Add Bootstrap scripts
                     * At the top of the queue
                     */
                    if ($bootStrapMajorVersion === "4") {
                        $useJqueryDoku = Site::getConfValue(self::CONF_JQUERY_DOKU, self::CONF_JQUERY_DOKU_DEFAULT);
                        if (
                            !Identity::isLoggedIn()
                            && $useJqueryDoku === 0
                        ) {
                            /**
                             * We take the Javascript of Bootstrap
                             * (Jquery and others)
                             */
                            $boostrapSnippetsAsArray = [];
                            foreach ($bootstrap->getJsSnippets() as $snippet) {
                                $boostrapSnippetsAsArray[] = $snippet->toDokuWikiArray();
                            }
                            /**
                             * At the top of the queue
                             */
                            $newScriptTagAsArray = array_merge($boostrapSnippetsAsArray, $newScriptTagAsArray);
                        } else {
                            // Logged in
                            // We take the Jqueries of doku and we add Bootstrap
                            $newScriptTagAsArray = array_merge($jqueryDokuScriptsTagsAsArray, $newScriptTagAsArray); // js
                            // We had popper of Bootstrap
                            $newScriptTagAsArray[] = $bootstrap->getPopperSnippet()->toDokuWikiArray();
                            // We had the js of Bootstrap
                            $newScriptTagAsArray[] = $bootstrap->getBootstrapJsSnippet()->toDokuWikiArray();
                        }
                    } else {

                        // There is no JQuery in 5
                        // We had the js of Bootstrap and popper
                        // Add Jquery before the js.php
                        $newScriptTagAsArray = array_merge($jqueryDokuScriptsTagsAsArray, $newScriptTagAsArray); // js
                        // Then add at the top of the top (first of the first) bootstrap
                        // Why ? Because Jquery should be last to be able to see the missing icon
                        // https://stackoverflow.com/questions/17367736/jquery-ui-dialog-missing-close-icon
                        $bootstrapTagArray[] = $bootstrap->getPopperSnippet()->toDokuWikiArray();
                        $bootstrapTagArray[] = $bootstrap->getBootstrapJsSnippet()->toDokuWikiArray();
                        $newScriptTagAsArray = array_merge($bootstrapTagArray, $newScriptTagAsArray);

                    }

                    $newHeaderTypes[$headTagName] = $newScriptTagAsArray;
                    break;
                case "meta":
                    $newHeaderData = array();
                    foreach ($headTagsAsArray as $metaData) {
                        // Content should never be null
                        // Name may change
                        // https://www.w3.org/TR/html4/struct/global.html#edef-META
                        if (!key_exists("content", $metaData)) {
                            $message = "The head meta (" . print_r($metaData, true) . ") does not have a content property";
                            LogUtility::error($message, self::FRONT_END_OPTIMIZATION_CANONICAL);
                        } else {
                            $content = $metaData["content"];
                            if (empty($content)) {
                                $messageEmpty = "the below head meta has an empty content property (" . print_r($metaData, true) . ")";
                                LogUtility::error($messageEmpty, self::FRONT_END_OPTIMIZATION_CANONICAL);
                            } else {
                                $newHeaderData[] = $metaData;
                            }
                        }
                    }
                    $newHeaderTypes[$headTagName] = $newHeaderData;
                    break;
                case "noscript": // https://github.com/ComboStrap/dokuwiki-plugin-gtm/blob/master/action.php#L32
                case "style":
                    $newHeaderTypes[$headTagName] = $headTagsAsArray;
                    break;
                default:
                    $message = "The header type ($headTagName) is unknown and was not controlled.";
                    $newHeaderTypes[$headTagName] = $headTagsAsArray;
                    LogUtility::error($message, self::FRONT_END_OPTIMIZATION_CANONICAL);

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
        $executionContext = ExecutionContext::getActualOrCreateFromEnv();
        try {
            $preloadedCss = &$executionContext->getRuntimeObject(PageTemplate::PRELOAD_TAG);
        } catch (ExceptionNotFound $e) {
            $preloadedCss = [];
            $executionContext->setRuntimeObject(PageTemplate::PRELOAD_TAG,$preloadedCss);
        }
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

