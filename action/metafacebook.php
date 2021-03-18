<?php

use ComboStrap\Image;
use ComboStrap\LogUtility;
use ComboStrap\MetadataUtility;
use ComboStrap\PluginUtility;
use ComboStrap\Page;
use ComboStrap\Site;
use ComboStrap\StringUtility;

if (!defined('DOKU_INC')) die();

require_once(__DIR__ . '/../class/Site.php');
require_once(__DIR__ . '/../class/Image.php');

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
            "og:description" => $page->getDescription(),
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
            $facebookMeta["og:type"] = Page::WEBSITE_TYPE;
        }

        if ($ogType == Page::ARTICLE_TYPE) {
            // https://ogp.me/#type_article
            $facebookMeta["article:published_time"] = date("c", $page->getPublishedElseCreationTimeStamp());
            $facebookMeta["article:modified_time"] = date("c", $page->getModifiedTimestamp());
        }

        /**
         * @var Image[]
         */
        $facebookImages = $page->getImageSet();
        if (empty($facebookImages)) {
            $defaultFacebookImage = cleanID(PluginUtility::getConfValue(self::CONF_DEFAULT_FACEBOOK_IMAGE));
            if (!empty($defaultFacebookImage)) {
                $image = new Image($defaultFacebookImage);
                if ($image->exists()) {
                    $facebookImages[] = $image;
                } else {
                    if ($defaultFacebookImage != "logo-facebook.png") {
                        LogUtility::msg("The default facebook image ($defaultFacebookImage) does not exist", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                    }
                }


            }
        }
        if (!empty($facebookImages)) {
            foreach ($facebookImages as $facebookImage) {

                if (!$facebookImage->exists()) {
                    LogUtility::msg("The image ($facebookImage) does not exist and was not added", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                } else {

                    $toSmall = false;
                    if ($facebookImage->isAnalyzable()) {

                        // There is a minimum size constraint of 200px by 200px
                        if ($facebookImage->getWidth() < 200) {
                            $toSmall = true;
                        } else {
                            $facebookMeta["og:image:width"] = $facebookImage->getWidth();
                            if ($facebookImage->getHeight() < 200) {
                                $toSmall = true;
                            } else {
                                $facebookMeta["og:image:height"] = $facebookImage->getHeight();
                            }
                        }
                    }

                    if ($toSmall) {
                        $message = "The facebook image ($facebookImage) is too small (" . $facebookImage->getWidth() . " x " . $facebookImage->getHeight() . "). The minimum size constraint is 200px by 200px";
                        if ($facebookImage->getId() != $page->getFirstImage()->getId()) {
                            LogUtility::msg($message, LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                        } else {
                            LogUtility::log2BrowserConsole($message);
                        }
                    }


                    /**
                     * We may don't known the dimensions
                     */
                    if (!$toSmall) {
                        $mime = $facebookImage->getMime();
                        if (!empty($mime)) {
                            $facebookMeta["og:image:type"] = $mime[1];
                        }
                        $facebookMeta["og:image"] = $facebookImage->getUrl();
                        // One image only
                        break;
                    }
                }

            }
        }


        $facebookMeta["fb:app_id"] = self::FACEBOOK_APP_ID;

        $lang = $page->getLang();
        if (!empty($lang)) {

            $country = $page->getCountry();
            if (empty($country)) {
                $country = $lang;
            }
            $facebookMeta["og:locale"] = $lang . "_" . strtoupper($country);

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
