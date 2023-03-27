<?php


require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');

use ComboStrap\DirectoryLayout;
use ComboStrap\Identity;


/**
 * Class action_plugin_combo_svg
 * Returned an svg optimized version
 */
class action_plugin_combo_svg extends DokuWiki_Action_Plugin
{


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
        $isMember = Identity::isMember("@" . Identity::CONF_DESIGNER_GROUP_NAME);

        if ($isAdmin || $isMember) {
            /**
             * Enhance the svg mime type
             * {@link getMimeTypes()}
             */
            global $config_cascade;
            $svgMimeConf = DirectoryLayout::getComboResourcesDirectory()->resolve("conf")->resolve("svg.mime.conf")->toAbsoluteId();
            $config_cascade['mime']['local'][] = $svgMimeConf;
        }

    }


}
