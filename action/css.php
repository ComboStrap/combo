<?php


use ComboStrap\Api\ApiRouter;
use ComboStrap\ArrayUtility;
use ComboStrap\ExceptionNotFound;
use ComboStrap\ExecutionContext;
use ComboStrap\Identity;
use ComboStrap\PluginUtility;


/**
 * Class action_plugin_combo_css
 * Delete Backend CSS for front-end
 *
 * Bug:
 *   * https://datacadamia.comlighthouse - no interwiki
 *
 * A call to /lib/exe/css.php?t=template&tseed=time()
 *
 *    * t is the template
 *
 *    * tseed is md5 of modified time of the below config file set at {@link tpl_metaheaders()}
 *
 *        * conf/dokuwiki.php
 *        * conf/local.php
 *        * conf/local.protected.php
 *        * conf/tpl/strap/style.ini
 *
 */
class action_plugin_combo_css extends DokuWiki_Action_Plugin
{

    /**
     * If anonymous
     */
    const CONF_ENABLE_MINIMAL_FRONTEND_STYLESHEET = 'enableMinimalFrontEndStylesheet';
    /**
     * If anonymous
     */
    const CONF_DISABLE_DOKUWIKI_STYLESHEET = 'disableDokuwikiStylesheet';

    /**
     * Anonymous or not
     */
    const ANONYMOUS_KEY = 'ano';
    /**
     * Combo theme or not
     */
    const COMBO_THEME_ENABLED_KEY = "combo-theme-enabled";

    /**
     * When anonymous, apply a minimal frontend optimization ?
     * (ie without Jquery used mostly for admin, ...)
     */
    const ANONYMOUS_MINIMAL_FRONT_KEY = "minimal-front";


    /**
     * List of excluded plugin
     */
    const EXCLUDED_PLUGINS = array(
        "acl",
        "authplain",
        "changes",
        "config",
        "extension",
        "info",
        "move",
        "popularity",
        "revert",
        "safefnrecode",
        "searchindex",
        "sqlite",
        "upgrade",
        "usermanager"
    );



    /**
     * Registers a callback function for a given event
     *
     * @param Doku_Event_Handler $controller DokuWiki's event controller object
     * @return void
     *
     * To fire this event
     *   * Ctrl+Shift+R to disable browser cache
     *
     */
    public function register(Doku_Event_Handler $controller)
    {

        $requestScript = PluginUtility::getRequestScript();
        switch ($requestScript) {
            case "css.php":
                /**
                 * The process follows the following steps:
                 *     * With CSS_STYLES_INCLUDED, you choose the file that you want
                 *     * then with CSS_CACHE_USE, you can change the cache key name
                 */
                $controller->register_hook('CSS_STYLES_INCLUDED', 'BEFORE', $this, 'handle_front_css_styles');
                $controller->register_hook('CSS_CACHE_USE', 'BEFORE', $this, 'handle_css_cache');
                break;
            case "doku.php":
                /**
                 * Add property to the css URL to create multiple CSS file:
                 *   * public/private (anonymous/loggedIn)
                 */
                $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'handle_css_metaheader');
                break;
        }


    }

    /**
     * @param Doku_Event $event
     * @param $param
     *
     * Add query parameter to the CSS header call. ie
     * <link rel="preload" href="/lib/exe/css.php?t=template&tseed=8e31090353c8fcf80aa6ff0ea9bf3746" as="style">
     * to indicate if the page that calls the css is from a user that is logged in or not:
     *   * public vs private
     *   * ie frontend vs backend
     */
    public function handle_css_metaheader(Doku_Event &$event, $param)
    {

        $executionContext = ExecutionContext::getActualOrCreateFromEnv();
        $config = $executionContext->getConfig();
        $disableDokuwikiStylesheetConf = $config->getBooleanValue(self::CONF_DISABLE_DOKUWIKI_STYLESHEET, false);
        $isExecutingTheme = $executionContext->isExecutingPageTemplate();

        $disableDokuwikiStylesheet = $disableDokuwikiStylesheetConf && $isExecutingTheme;


        $links = &$event->data['link'];
        foreach ($links as $key => &$link) {

            $pos = strpos($link['href'], 'css.php');
            if ($pos === false) {
                continue;
            }

            if (Identity::isAnonymous()) {

                if ($disableDokuwikiStylesheet) {
                    unset($links[$key]);
                    return;
                }

                $link['href'] .= '&' . self::ANONYMOUS_KEY;
                $isEnabledMinimalFrontEnd = ExecutionContext::getActualOrCreateFromEnv()
                    ->getConfig()
                    ->getBooleanValue(self::CONF_ENABLE_MINIMAL_FRONTEND_STYLESHEET, 1);

                if($isEnabledMinimalFrontEnd){
                    $link['href'] .= '&' . self::ANONYMOUS_MINIMAL_FRONT_KEY;
                }
            }

            if ($executionContext->isExecutingPageTemplate()) {
                $link['href'] .= '&' . self::COMBO_THEME_ENABLED_KEY;
            }



        }

    }

    /**
     *
     * @param Doku_Event $event event object by reference
     * @param mixed $param [the parameters passed as fifth argument to register_hook() when this
     *                           handler was registered]
     * @return void
     *
     * Change the key of the cache.
     *
     * The default key can be seen in the {@link css_out()} function
     * when a new cache is created (ie new cache(key,ext)
     *
     * This is only called when this is a front call, see {@link register()}
     *
     * @see <a href="https://github.com/i-net-software/dokuwiki-plugin-lightweightcss/blob/master/action.php#L122">Credits</a>
     */
    public function handle_css_cache(Doku_Event &$event, $param)
    {

        /**
         * Add Anonymous and comboTheme in the cache key
         * if present
         */
        $keys = [self::ANONYMOUS_KEY, self::COMBO_THEME_ENABLED_KEY, self::ANONYMOUS_MINIMAL_FRONT_KEY];
        $foundKeys = [];
        foreach ($keys as $key) {
            if (ApiRouter::hasRequestParameter($key)) {
                $foundKeys[] = $key;
            }
        }
        if (empty($foundKeys)) {
            return;
        }

        /**
         * Add Anonymous and comboTheme in the cache key
         * if present
         */
        $event->data->key .= implode('.', $foundKeys);
        $event->data->cache = getCacheName($event->data->key, $event->data->ext);


    }

    /**
     * Handle the front CSS script list. The script would be fit to do even more stuff / types
     * but handles only admin and default currently.
     *
     * @param Doku_Event $event event object by reference
     * @param mixed $param [the parameters passed as fifth argument to register_hook() when this
     *                           handler was registered]
     * @return void
     */
    public function handle_front_css_styles(Doku_Event &$event, $param)
    {

        $isAnonymous = ApiRouter::hasRequestParameter(self::ANONYMOUS_KEY);
        $isThemeEnabled = ApiRouter::hasRequestParameter(self::COMBO_THEME_ENABLED_KEY);
        $isMinimalFrontEnd = ApiRouter::hasRequestParameter(self::ANONYMOUS_MINIMAL_FRONT_KEY);
        if (!$isAnonymous && !$isThemeEnabled) {
            return;
        }



        /**
         * There is one call by:
         *   * mediatype (ie screen, all, print, speech)
         *   * and one call for the dokuwiki default
         */
        switch ($event->data['mediatype']) {

            case 'print':
            case 'screen':
            case 'all':
                $filteredDataFiles = array();
                $files = $event->data['files'];
                foreach ($files as $file => $fileDirectory) {

                    // template style
                    if ($isThemeEnabled && strpos($fileDirectory, 'lib/tpl')) {
                        continue;
                    }

                    // Lib styles
                    if (($isThemeEnabled || $isMinimalFrontEnd) && strpos($fileDirectory, 'lib/styles')) {
                        // Geshi (syntax highlighting) and basic style of doku, we don't keep.
                        continue;
                    }

                    // No Css from lib scripts
                    // Jquery is here
                    if (($isThemeEnabled || $isMinimalFrontEnd) && $isAnonymous && strpos($fileDirectory, 'lib/scripts')) {
                        // Jquery is needed for admin (not anonymous)
                        // scripts\jquery\jquery-ui-theme\smoothness.css
                        continue;
                    }

                    if (($isThemeEnabled || $isMinimalFrontEnd)) {
                        // Excluded
                        $isExcluded = false;
                        foreach (self::EXCLUDED_PLUGINS as $plugin) {
                            if (strpos($file, 'lib/plugins/' . $plugin)) {
                                $isExcluded = true;
                                break;
                            }
                        }
                        if (!$isExcluded) {
                            $filteredDataFiles[$file] = $fileDirectory;
                        }
                    }
                }

                $event->data['files'] = $filteredDataFiles;

                break;

            case 'speech':
                if (!PluginUtility::isTest()) {
                    $event->preventDefault();
                }
                break;
            case 'DW_DEFAULT':
                // Interwiki styles are here, we keep (in the lib/css.php file)
                break;

        }
    }


}


