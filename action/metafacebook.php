<?php

use ComboStrap\LogUtility;
use ComboStrap\MetadataUtility;
use ComboStrap\PluginUtility;
use ComboStrap\Page;
use ComboStrap\Site;
use ComboStrap\StringUtility;

if (!defined('DOKU_INC')) die();

require_once(__DIR__ . '/../class/Site.php');

/**
 *
 * For the canonical meta, see {@link action_plugin_combo_metacanonical}
 *
 * Inspiration, reference:
 * https://developers.facebook.com/docs/sharing/webmasters
 * https://github.com/twbs/bootstrap/blob/v4-dev/site/layouts/partials/social.html
 * https://github.com/mprins/dokuwiki-plugin-socialcards/blob/master/action.php
 */
class action_plugin_combo_metafacebook extends DokuWiki_Action_Plugin
{

    const FACEBOOK_APP_ID = "486120022012342";

    /**
     * The image
     */
    const CONF_DEFAULT_FACEBOOK_IMAGE = "defaultFacebookImage";


    const CANONICAL = "facebook";


    function __construct()
    {
        // enable direct access to language strings
        // ie $this->lang
        $this->setupLocale();
    }

    public function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'metaFacebookProcessing', array());
    }

    /**
     *
     * @param $event
     */
    function metaFacebookProcessing($event)
    {


        if ($_SERVER['SCRIPT_NAME'] == "/lib/exe/mediamanager.php") {
            // $ID is null
            return;
        }

        global $ID;
        $page = new Page($ID);

        /**
         * No social for bars
         */
        if ($page->isBar()) {
            return;
        }



        /**
         * "og:url" is already created in the {@link action_plugin_combo_metacanonical}
         * "og:description" is already created in the {@link action_plugin_combo_metadescription}
         */
        $facebookMeta = array(
            "og:title" => StringUtility::truncateString($page->getTitleNotEmpty(), 70),
        );

        $title = Site::getTitle();
        if (!empty($title)) {
            $facebookMeta["og:site_name"] = $title;
        }

        /**
         * Type of page
         */
        $ogType = $page->getType();
        if (!empty($ogType)) {
            $facebookMeta["og:type"] = $ogType;
        } else {
            // The default facebook value
            $facebookMeta["og:type"] = "website";
        }

        /**
         * Image
         */
        $facebookImage = $page->getImage();
        if ($facebookImage == null) {
            $facebookImage = PluginUtility::getConfValue(self::CONF_DEFAULT_FACEBOOK_IMAGE);
        }
        if ($facebookImage != null) {
            $mediaFile = mediaFN($facebookImage);

            if (file_exists($mediaFile)) {
                // based on php getimagesize
                $dimensions = media_image_preview_size($facebookImage, '', false);

                // There is a minimum size constraint of 200px by 200px
                $toSmall = false;
                if (!empty($dimensions)) {
                    list($width, $height) = $dimensions;


                    if ($width < 200) {
                        $toSmall = true;
                    } else {
                        $facebookMeta["og:image:width"] = $width;
                        if ($height < 200) {
                            $toSmall = true;
                        } else {
                            $facebookMeta["og:image:height"] = $height;
                        }
                    }

                    if ($toSmall) {
                        $message = "The facebook image ($facebookImage) is too small ($width x $height). The minimum size constraint is 200px by 200px";
                        if ($facebookImage!=$page->getFirstImage()) {
                            LogUtility::msg($message,LogUtility::LVL_MSG_ERROR,self::CANONICAL);
                        } else {
                            LogUtility::log2BrowserConsole($message);
                        }
                    }

                }

                /**
                 * We may don't known the dimensions
                 */
                if (!$toSmall) {
                    $mime = mimetype($facebookImage);
                    if (!empty($mime)) {
                        $facebookMeta["og:image:type"] = $mime[1];
                    }

                    $facebookImageUrl = ml($facebookImage, '', true, '', true);
                    $facebookMeta["og:image"] = $facebookImageUrl;
                }

            }

        }

        $facebookMeta["fb:app_id"] = self::FACEBOOK_APP_ID;

        $lang = $page->getLang();
        if (!empty($lang)) {

            $country = $page->getCountry();
            if (empty($country)){
                $country = $lang;
            }
            $facebookMeta["og:locale"] = $lang."_".strtoupper($country);

        } else {

            // The Facebook default
            $facebookMeta["og:locale"] = "en_US";

        }

        /**
         * Add the properties
         */
        foreach ($facebookMeta as $property => $content) {
            $event->data['meta'][] = array("property" => $property, "content" => $content);
        }



    }

}
