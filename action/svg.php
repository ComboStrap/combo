<?php


use ComboStrap\Auth;
use ComboStrap\Resources;
use ComboStrap\SvgFile;

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
        $controller->register_hook('FETCH_MEDIA_STATUS', 'BEFORE', $this, 'svg_optimization');


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

    public function svg_optimization(Doku_Event &$event)
    {

        if ($event->data['ext'] != 'svg') return;
        if ($event->data['status'] >= 400) return; // ACLs and precondition checks

        if ($this->getConf(SvgFile::CONF_SVG_OPTIMIZATION_ENABLE, true)) {
            $svgFile = new SvgFile($event->data['file']);
            $file = $svgFile->getOptimizedSvgFile();
            $event->data['file'] = $file;
        }

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
        $isadmin = Auth::isAdmin();
        $isMember = Auth::isMember("@" . self::CONF_SVG_UPLOAD_GROUP_NAME);

        if ($isadmin || $isMember) {
            /**
             * Enhance the svg mime type
             * {@link getMimeTypes()}
             */
            global $config_cascade;
            $svgMimeConf = Resources::getConfResourceDirectory() . "/svg.mime.conf";
            $config_cascade['mime']['local'][] = $svgMimeConf;
        }

    }


}
