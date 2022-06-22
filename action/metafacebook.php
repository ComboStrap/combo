<?php

require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');

use ComboStrap\DokuPath;
use ComboStrap\ExceptionBadArgument;
use ComboStrap\ExceptionBadSyntax;
use ComboStrap\ExceptionCompile;
use ComboStrap\ExceptionNotExists;
use ComboStrap\ExceptionNotFound;
use ComboStrap\FetcherLocalImage;
use ComboStrap\FetcherTraitImage;
use ComboStrap\FileSystems;
use ComboStrap\LogUtility;
use ComboStrap\Mime;
use ComboStrap\Page;
use ComboStrap\PageImageUsage;
use ComboStrap\PageType;
use ComboStrap\PluginUtility;
use ComboStrap\Site;
use ComboStrap\StringUtility;


/**
 *
 * Implementation of the ogp protocol
 *
 * followed by:
 *   * Facebook: https://developers.facebook.com/docs/sharing/webmasters
 *   * Linkedin:  https://www.linkedin.com/help/linkedin/answer/a521928/making-your-website-shareable-on-linkedin?lang=en
 *
 * For the canonical meta, see {@link action_plugin_combo_metacanonical}
 *
 * Inspiration, reference:
 * https://github.com/twbs/bootstrap/blob/v4-dev/site/layouts/partials/social.html
 * https://github.com/mprins/dokuwiki-plugin-socialcards/blob/master/action.php
 *
 *
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
        if ($page->isSecondarySlot()) {
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
                try {
                    $facebookMeta["article:published_time"] = $page->getPublishedElseCreationTime()->format(DATE_ISO8601);
                } catch (ExceptionNotFound $e) {
                    // Internal error, the page should exist
                    LogUtility::error("Internal Error: We were unable to define the publication date for the page ($page)", self::CANONICAL);

                }
                try {
                    $modifiedTime = $page->getModifiedTimeOrDefault();
                    $facebookMeta["article:modified_time"] = $modifiedTime->format(DATE_ISO8601);
                } catch (ExceptionNotFound $e) {
                    // Internal error, the page should exist
                    LogUtility::error("Internal Error: We were unable to define the modification date for the page ($page)", self::CANONICAL);
                }


                $facebookMeta["og:type"] = $pageType;
                break;
            default:
                // The default facebook value
                $facebookMeta["og:type"] = PageType::WEBSITE_TYPE;
                break;
        }


        /**
         * @var FetcherTraitImage[]
         */
        $facebookImages = $page->getImagesForTheFollowingUsages([PageImageUsage::FACEBOOK, PageImageUsage::SOCIAL, PageImageUsage::ALL]);
        if (empty($facebookImages)) {
            $defaultFacebookImage = PluginUtility::getConfValue(self::CONF_DEFAULT_FACEBOOK_IMAGE);
            if (!empty($defaultFacebookImage)) {
                $dokuPath = DokuPath::createMediaPathFromId($defaultFacebookImage);
                if (FileSystems::exists($dokuPath)) {
                    try {
                        $facebookImages[] = FetcherLocalImage::createImageFetchFromPath($dokuPath);
                    } catch (ExceptionCompile $e) {
                        LogUtility::error("We were unable to add the default facebook image ($defaultFacebookImage) because of the following error: {$e->getMessage()}", self::CANONICAL);
                    }
                } else {
                    if ($defaultFacebookImage != ":logo-facebook.png") {
                        LogUtility::error("The default facebook image ($defaultFacebookImage) does not exist", self::CANONICAL);
                    }
                }
            }
        }
        if (!empty($facebookImages)) {

            /**
             * One of image/jpeg, image/gif or image/png
             * As stated here: https://developers.facebook.com/docs/sharing/webmasters#images
             **/
            $facebookMimes = [Mime::JPEG, Mime::GIF, Mime::PNG];
            foreach ($facebookImages as $facebookImage) {

                $path = $facebookImage->getOriginalPath();
                if (!FileSystems::exists($path)) {
                    LogUtility::error("The image ($path) does not exist and was not added", self::CANONICAL);
                    continue;
                }
                try {
                    $mime = FileSystems::getMime($path);
                } catch (ExceptionNotFound $e) {
                    LogUtility::internalError($e->getMessage());
                    continue;
                }
                if (!in_array($mime->toString(), $facebookMimes)) {
                    continue;
                }

                $toSmall = false;

                // There is a minimum size constraint of 200px by 200px
                // The try is in case we can't get the width and height
                try {
                    $intrinsicWidth = $facebookImage->getIntrinsicWidth();
                    $intrinsicHeight = $facebookImage->getIntrinsicHeight();
                } catch (ExceptionCompile $e) {
                    LogUtility::error("No image was added for facebook. Error while retrieving the dimension of the image: {$e->getMessage()}", self::CANONICAL);
                    break;
                }

                if ($intrinsicWidth < 200) {
                    $toSmall = true;
                } else {
                    $facebookMeta["og:image:width"] = $intrinsicWidth;
                    if ($intrinsicHeight < 200) {
                        $toSmall = true;
                    } else {
                        $facebookMeta["og:image:height"] = $intrinsicHeight;
                    }
                }

                if ($toSmall) {
                    $message = "The facebook image ($facebookImage) is too small (" . $intrinsicWidth . " x " . $intrinsicHeight . "). The minimum size constraint is 200px by 200px";
                    try {
                        $firstImagePath = $page->getFirstImage()->getOriginalPath();

                        if (
                            $path->toAbsolutePath()->toPathString()
                            !==
                            $firstImagePath->toAbsolutePath()->toPathString()
                        ) {
                            // specified image
                            LogUtility::error($message, self::CANONICAL);
                        } else {
                            // first image
                            LogUtility::log2BrowserConsole($message);
                        }
                    } catch (ExceptionNotFound $e) {
                        LogUtility::error($message, self::CANONICAL);
                    }
                }


                /**
                 * We may don't known the dimensions
                 */
                if (!$toSmall) {
                    $facebookMeta["og:image:type"] = $mime->toString();
                    try {
                        $facebookMeta["og:image"] = $facebookImage->getFetchUrl()->toAbsoluteUrlString();
                    } catch (ExceptionCompile $e) {
                        // Oeps
                        LogUtility::internalError("Og Image could not be added. Error: {$e->getMessage()}", self::CANONICAL);
                    }
                    // One image only
                    break;
                }

            }
        }


        $facebookMeta["fb:app_id"] = self::FACEBOOK_APP_ID;

        // FYI default if not set by facebook: "en_US"
        $locale = ComboStrap\Locale::createForPage($page, "_")->getValueOrDefault();
        $facebookMeta["og:locale"] = $locale;


        /**
         * Add the properties
         */
        foreach ($facebookMeta as $property => $content) {
            $event->data['meta'][] = array("property" => $property, "content" => $content);
        }


    }

}
