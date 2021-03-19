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

/**
 *
 * For the canonical meta, see {@link action_plugin_combo_metacanonical}
 * https://github.com/twbs/bootstrap/blob/v4-dev/site/layouts/partials/social.html
 *
 * TODO: https://developer.twitter.com/en/docs/twitter-for-websites/embedded-tweets/overview
 */
class action_plugin_combo_metatwitter extends DokuWiki_Action_Plugin
{


    /**
     * The handle name
     */
    const CONF_TWITTER_SITE_HANDLE = "twitterSiteHandle";
    /**
     * The handle id
     */
    const CONF_TWITTER_SITE_ID = "twitterSiteId";
    /**
     * The image
     */
    const CONF_DEFAULT_TWITTER_IMAGE = "defaultTwitterImage";

    /**
     * Don't track
     */
    const CONF_TWITTER_DONT_NOT_TRACK = "twitter:dnt";
    const CONF_DONT_NOT_TRACK = "twitter:dnt";
    const CONF_ON = "on";
    const CONF_OFF = "off";

    /**
     * The creation ie (combostrap)
     */
    const COMBO_STRAP_TWITTER_HANDLE = "@combostrapweb";
    const COMBO_STRAP_TWITTER_ID = "1283330969332842497";
    const CANONICAL = "twitter";



    function __construct()
    {
        // enable direct access to language strings
        // ie $this->lang
        $this->setupLocale();
    }

    public function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'metaTwitterProcessing', array());
    }

    /**
     *
     * @param $event
     */
    function metaTwitterProcessing($event)
    {

        global $ID;
        if (empty($ID)) {
            // $ID is null for media
            return;
        }


        $page = new Page($ID);

        /**
         * No social for bars
         */
        if ($page->isBar()) {
            return;
        }


        // https://datacadamia.com/marketing/twitter#html_meta
        // https://developer.twitter.com/en/docs/twitter-for-websites/cards/overview/markup
        // https://cards-dev.twitter.com/validator

        $twitterMeta = array(
            "twitter:card" => "summary",
            "twitter:title" => StringUtility::truncateString($page->getTitleNotEmpty(), 70),
            "twitter:description" => StringUtility::truncateString($page->getDescription(), 200),
            "twitter:creator" => self::COMBO_STRAP_TWITTER_HANDLE,
            "twitter:creator:id" => self::COMBO_STRAP_TWITTER_ID
        );


        /**
         * Twitter site
         */
        $siteTwitterHandle = PluginUtility::getConfValue(self::CONF_TWITTER_SITE_HANDLE);
        $siteTwitterId = PluginUtility::getConfValue(self::CONF_TWITTER_SITE_ID);
        if (!empty($siteTwitterHandle)) {
            $twitterMeta["twitter:site"] = $siteTwitterHandle;

            // Identify the Twitter profile of the page that populates the via property
            // https://developer.twitter.com/en/docs/twitter-for-websites/webpage-properties
            $name = substr($siteTwitterHandle,"@","");
            $event->data['link'][] = array("rel" => "me", "href" => "https://twitter.com/$name");
        }
        if (!empty($siteTwitterId)) {
            $twitterMeta["twitter:site:id"] = $siteTwitterId;
        }

        /**
         * Card image
         */
        $twitterImages = $page->getImageSet();
        if (empty($twitterImages)) {
            $defaultImageIdConf = cleanID(PluginUtility::getConfValue(self::CONF_DEFAULT_TWITTER_IMAGE));
            if (!empty($defaultImageIdConf)) {
                $twitterImage = new Image($defaultImageIdConf);
                if ($twitterImage->exists()) {
                    $twitterImages[] = $twitterImage;
                } else {
                    if ($defaultImageIdConf != "apple-touch-icon.png") {
                        LogUtility::msg("The default twitter image ($defaultImageIdConf) does not exist", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                    }
                }
            }

        }
        if (!empty($twitterImages)) {
            foreach ($twitterImages as $twitterImage) {
                if ($twitterImage->exists()) {
                    $twitterMeta["twitter:image"] = $twitterImage->getUrl();
                    if (!empty($twitterImage->getAlt())) {
                        $twitterMeta["twitter:image:alt"] = $twitterImage->getAlt();
                    }
                    // One image only
                    break;
                }
            }
        }

        /**
         * https://developer.twitter.com/en/docs/twitter-for-websites/webpage-properties
         */
        // don;t track
        $twitterMeta["twitter:dnt"]=PluginUtility::getConfValue(self::CONF_TWITTER_DONT_NOT_TRACK);
        // turn off csp warning
        $twitterMeta["twitter:widgets:csp"]="on";

        /**
         * Embedded Tweet Theme
         */

        $twitterMeta["twitter:widgets:theme"]=PluginUtility::getConfValue(syntax_plugin_combo_blockquote::CONF_TWEET_WIDGETS_THEME);
        $twitterMeta["twitter:widgets:border-color"]=PluginUtility::getConfValue(syntax_plugin_combo_blockquote::CONF_TWEET_WIDGETS_BORDER);

        /**
         * Add the properties
         */
        foreach ($twitterMeta as $key => $content) {
            $event->data['meta'][] = array("name" => $key, "content" => $content);
        }



    }

}
