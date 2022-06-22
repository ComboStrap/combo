<?php


require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');

use ComboStrap\Dimension;
use ComboStrap\ExceptionCompile;
use ComboStrap\Fetcher;
use ComboStrap\FetcherAbs;
use ComboStrap\FetcherLocalPath;
use ComboStrap\Identity;
use ComboStrap\FetchCache;
use ComboStrap\DokuPath;
use ComboStrap\FetcherTraitImage;
use ComboStrap\FetcherSvg;
use ComboStrap\MediaLink;
use ComboStrap\LogUtility;
use ComboStrap\MediaMarkup;
use ComboStrap\PluginUtility;
use ComboStrap\Site;
use ComboStrap\SvgImageLink;
use ComboStrap\TagAttributes;

if (!defined('DOKU_INC')) exit;
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');

/**
 * Class action_plugin_combo_svg
 * Returned an svg optimized version
 */
class action_plugin_combo_svg extends DokuWiki_Action_Plugin
{


    const CONF_SVG_UPLOAD_GROUP_NAME = "svgUploadGroupName";

    public function register(Doku_Event_Handler $controller)
    {


        /**
         * Hack the upload is done via the ajax.php file
         * {@link media_upload()}
         */
        $controller->register_hook('AUTH_ACL_CHECK', 'BEFORE', $this, 'svg_mime');

        /**
         * When the parsing of a page starts
         */
        $controller->register_hook('PARSER_WIKITEXT_PREPROCESS', 'BEFORE', $this, 'svg_mime');


    }


    /**
     * @param Doku_Event $event
     * {@link media_save} is checking the authorized mime type
     * Svg is not by default, we add it here if the user is admin or
     * in a specified group
     */
    public function svg_mime(Doku_Event &$event)
    {

        self::allowSvgIfAuthorized();

    }

    /**
     *
     */
    public static function allowSvgIfAuthorized()
    {
        $isAdmin = Identity::isAdmin();
        $isMember = Identity::isMember("@" . self::CONF_SVG_UPLOAD_GROUP_NAME);

        if ($isAdmin || $isMember) {
            /**
             * Enhance the svg mime type
             * {@link getMimeTypes()}
             */
            global $config_cascade;
            $svgMimeConf = Site::getComboResourcesDirectory()->resolve("conf")->resolve("svg.mime.conf")->toPathString();
            $config_cascade['mime']['local'][] = $svgMimeConf;
        }

    }


}
