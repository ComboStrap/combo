<?php


require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');

use ComboStrap\DirectoryLayout;
use ComboStrap\Site;

if (!defined('DOKU_INC')) exit;
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');

/**
 * Add the txt type has an authorized type
 */
class action_plugin_combo_txt extends DokuWiki_Action_Plugin
{




    public function register(Doku_Event_Handler $controller)
    {



        /**
         * Hack the upload done via the ajax.php file
         * {@link media_upload()}
         */
        $controller->register_hook('AUTH_ACL_CHECK', 'BEFORE', $this, 'txt_mime');

        /**
         * When the parsing of a page starts
         */
        $controller->register_hook('PARSER_WIKITEXT_PREPROCESS', 'BEFORE', $this, 'txt_mime');

    }


    /**
     * @param Doku_Event $event
     * {@link media_save} is checking the authorized mime type
     * Txt is not by default, we add it here if the user is admin or
     * in a specified group
     */
    public function txt_mime(Doku_Event &$event)
    {

        /**
         * Enhance the txt mime type
         * {@link getMimeTypes()}
         */
        global $config_cascade;
        $svgMimeConf = DirectoryLayout::getComboResourcesDirectory()->resolve("conf")->resolve("txt.mime.conf")->toAbsoluteString();
        $config_cascade['mime']['local'][] = $svgMimeConf;

    }



}
