<?php

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
    const CONF_TWITTER_SITE_IMAGE = "twitterSiteImage";

    /**
     * The creation ie (combostrap)
     */
    const COMBO_STRAP_TWITTER_HANDLE = "@combostrapweb";
    const COMBO_STRAP_TWITTER_ID = "1283330969332842497";


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




        // https://datacadamia.com/marketing/twitter#html_meta
        // https://developer.twitter.com/en/docs/twitter-for-websites/cards/overview/markup
        // https://cards-dev.twitter.com/validator

        $twitterMeta = array(
            "twitter:card" => "summary",
            "twitter:title" => StringUtility::truncateString($page->getTitleNotEmpty(),70),
            "twitter:description" => StringUtility::truncateString($page->getDescription(),200),
            "twitter:creator"=> self::COMBO_STRAP_TWITTER_HANDLE,
            "twitter:creator:id"=> self::COMBO_STRAP_TWITTER_ID
        );


        /**
         * Twitter site
         */
        $siteTwitterHandle = PluginUtility::getConfValue(self::CONF_TWITTER_SITE_HANDLE);
        $siteTwitterId = PluginUtility::getConfValue(self::CONF_TWITTER_SITE_ID);
        if ($siteTwitterHandle !=null){
            $twitterMeta["twitter:site"] = $siteTwitterHandle;
            $twitterMeta["twitter:site:id"] = $siteTwitterId;
        }

        /**
         * Card image
         */
        $twitterImage = PluginUtility::getConfValue(self::CONF_TWITTER_SITE_IMAGE);
        if ($twitterImage != null) {
            $mediaFile = mediaFN($twitterImage);

            if (file_exists($mediaFile)) {
                $twitterImageUrl = ml($twitterImage, '', true, '', true);
                $twitterMeta["twitter:image"] = $twitterImageUrl;
                $twitterMeta["twitter:image:alt"] = "Logo of the website";
            };

        }

        /**
         * Add the properties
         */
        foreach ($twitterMeta as $key => $content) {
            $event->data['meta'][] = array("name" => $key, "content" => $content);
        }

    }

}
