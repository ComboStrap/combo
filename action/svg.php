<?php


require_once(__DIR__ . '/../class/Cache.php');

use ComboStrap\Auth;
use ComboStrap\File;
use ComboStrap\LogUtility;
use ComboStrap\Resources;
use ComboStrap\SvgDocument;
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

    /**
     * @param Doku_Event $event
     * https://www.dokuwiki.org/devel:event:fetch_media_status
     */
    public function svg_optimization(Doku_Event &$event)
    {

        if ($event->data['ext'] != 'svg') return;
        if ($event->data['status'] >= 400) return; // ACLs and precondition checks


        $tagAttributes = TagAttributes::createEmpty();
        $width = $event->data['width'];
        if ($width != 0) {
            $tagAttributes->addComponentAttributeValue("width", $width);
        }
        $height = $event->data['height'];
        if ($height != 0) {
            $tagAttributes->addComponentAttributeValue("height", $height);
        }
        $tagAttributes->addComponentAttributeValue("cache", $event->data['cache']);

        /**
         * Add the extra attributes
         */
        $rev = null;
        foreach ($_REQUEST as $name => $value) {
            switch ($name) {
                case "media":
                case "w":
                case "h":
                case "cache":
                case TagAttributes::BUSTER_KEY:
                case "rev":
                    $rev = $value;
                    break;
                case "tok": // A checker
                    // Nothing to do, we take them
                    break;
                default:
                    if (!empty($value)) {
                        $tagAttributes->addComponentAttributeValue($name, $value);
                    } else {
                        LogUtility::msg("Internal Error: the value of the query name ($name) is empty", LogUtility::LVL_MSG_WARNING, SvgImageLink::CANONICAL);
                    }
            }
        }

        $event->data["mime"] = "image/svg+xml";
        $id = $event->data["media"];
        $svgImageLink = SvgImageLink::createMediaPathFromId($id, $rev, $tagAttributes);
        $event->data['file'] = $svgImageLink->getSvgFile();


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
