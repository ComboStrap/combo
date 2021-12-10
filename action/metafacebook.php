<?php

require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');

use ComboStrap\DokuPath;
use ComboStrap\Image;
use ComboStrap\LogUtility;
use ComboStrap\Mime;
use ComboStrap\Page;
use ComboStrap\PageImage;
use ComboStrap\PageType;
use ComboStrap\PluginUtility;
use ComboStrap\Site;
use ComboStrap\StringUtility;




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

        global $ID;
        if (empty($ID)) {
            // $ID is null for media
            return;
        }


        $page = Page::createPageFromId($ID);
        if (!$page->exists()) {
            return;
        }

        /**
         * No social for bars
         */
        if ($page->isSlot()) {
            return;
        }


        /**
         * "og:url" is already created in the {@link action_plugin_combo_metacanonical}
         * "og:description" is already created in the {@link action_plugin_combo_metadescription}
         */
        $facebookMeta = array(
            "og:title" => StringUtility::truncateString($page->getTitleOrDefault(), 70)
        );
        $descriptionOrElseDokuWiki = $page->getDescriptionOrElseDokuWiki();
        if (!empty($descriptionOrElseDokuWiki)) {
            // happens in test with document without content
            $facebookMeta["og:description"] = $descriptionOrElseDokuWiki;
        }

        $title = Site::getTitle();
        if (!empty($title)) {
            $facebookMeta["og:site_name"] = $title;
        }

        /**
         * Type of page
         */
        $pageType = $page->getTypeOrDefault();
        switch ($pageType) {
            case PageType::ARTICLE_TYPE:
                // https://ogp.me/#type_article
                $facebookMeta["article:published_time"] = $page->getPublishedElseCreationTime()->format(DATE_ISO8601);
                $modifiedTime = $page->getModifiedTime();
                if ($modifiedTime != null) {
                    $facebookMeta["article:modified_time"] = $modifiedTime->format(DATE_ISO8601);
                }
                $facebookMeta["og:type"] = $pageType;
                break;
            default:
                // The default facebook value
                $facebookMeta["og:type"] = PageType::WEBSITE_TYPE;
                break;
        }


        /**
         * @var Image[]
         */
        $facebookImages = $page->getImagesOrDefaultForTheFollowingUsages([PageImage::FACEBOOK,PageImage::SOCIAL,PageImage::ALL]);
        if (empty($facebookImages)) {
            $defaultFacebookImage = PluginUtility::getConfValue(self::CONF_DEFAULT_FACEBOOK_IMAGE);
            if (!empty($defaultFacebookImage)) {
                DokuPath::addRootSeparatorIfNotPresent($defaultFacebookImage);
                $image = Image::createImageFromId($defaultFacebookImage);
                if ($image->exists()) {
                    $facebookImages[] = $image;
                } else {
                    if ($defaultFacebookImage != ":logo-facebook.png") {
                        LogUtility::msg("The default facebook image ($defaultFacebookImage) does not exist", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                    }
                }
            }
        }
        if (!empty($facebookImages)) {

            /**
             * One of image/jpeg, image/gif or image/png
             * As stated here: https://developers.facebook.com/docs/sharing/webmasters#images
             **/
            $facebookMime = [Mime::JPEG, Mime::GIF, Mime::PNG];
            foreach ($facebookImages as $facebookImage) {

                if (!in_array($facebookImage->getPath()->getMime()->toString(), $facebookMime)) {
                    continue;
                }

                /** @var Image $facebookImage */
                if (!($facebookImage->isRaster())) {
                    LogUtility::msg("Internal: The image ($facebookImage) is not a raster image and this should not be the case for facebook", LogUtility::LVL_MSG_ERROR);
                    continue;
                }

                if (!$facebookImage->exists()) {
                    LogUtility::msg("The image ($facebookImage) does not exist and was not added", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                } else {

                    $toSmall = false;
                    if ($facebookImage->isAnalyzable()) {

                        // There is a minimum size constraint of 200px by 200px
                        if ($facebookImage->getIntrinsicWidth() < 200) {
                            $toSmall = true;
                        } else {
                            $facebookMeta["og:image:width"] = $facebookImage->getIntrinsicWidth();
                            if ($facebookImage->getIntrinsicHeight() < 200) {
                                $toSmall = true;
                            } else {
                                $facebookMeta["og:image:height"] = $facebookImage->getIntrinsicHeight();
                            }
                        }
                    }

                    if ($toSmall) {
                        $message = "The facebook image ($facebookImage) is too small (" . $facebookImage->getIntrinsicWidth() . " x " . $facebookImage->getIntrinsicHeight() . "). The minimum size constraint is 200px by 200px";
                        if ($facebookImage->getAbsoluteFileSystemPath() != $page->getFirstImage()->getAbsoluteFileSystemPath()) {
                            LogUtility::msg($message, LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                        } else {
                            LogUtility::log2BrowserConsole($message);
                        }
                    }


                    /**
                     * We may don't known the dimensions
                     */
                    if (!$toSmall) {
                        $mime = $facebookImage->getPath()->getMime()->toString();
                        if (!empty($mime)) {
                            $facebookMeta["og:image:type"] = $mime;
                        }
                        $facebookMeta["og:image"] = $facebookImage->getAbsoluteUrl();
                        // One image only
                        break;
                    }
                }

            }
        }


        $facebookMeta["fb:app_id"] = self::FACEBOOK_APP_ID;

        $facebookDefaultLocale = "en_US";
        $locale = $page->getLocale($facebookDefaultLocale);
        $facebookMeta["og:locale"] = $locale;


        /**
         * Add the properties
         */
        foreach ($facebookMeta as $property => $content) {
            $event->data['meta'][] = array("property" => $property, "content" => $content);
        }


    }

}
