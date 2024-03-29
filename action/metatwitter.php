<?php

use ComboStrap\BlockquoteTag;
use ComboStrap\ExceptionBadArgument;
use ComboStrap\ExceptionBadSyntax;
use ComboStrap\ExceptionNotExists;
use ComboStrap\ExecutionContext;
use ComboStrap\FetcherRaster;
use ComboStrap\Meta\Field\TwitterImage;
use ComboStrap\Site;
use ComboStrap\SiteConfig;
use ComboStrap\WikiPath;
use ComboStrap\ExceptionCompile;
use ComboStrap\ExceptionNotFound;
use ComboStrap\IFetcherLocalImage;
use ComboStrap\FileSystems;
use ComboStrap\LogUtility;
use ComboStrap\MarkupPath;
use ComboStrap\PageImageUsage;
use ComboStrap\PluginUtility;
use ComboStrap\ResourceName;
use ComboStrap\StringUtility;


require_once(__DIR__ . '/../ComboStrap/Site.php');

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
     * Don't track
     */
    const CONF_TWITTER_DONT_NOT_TRACK = self::META_DNT;
    const CONF_DONT_NOT_TRACK = self::META_DNT;
    const CONF_ON = "on";
    const CONF_OFF = "off";

    /**
     * The creation ie (combostrap)
     */
    const COMBO_STRAP_TWITTER_HANDLE = "@combostrapweb";
    const COMBO_STRAP_TWITTER_ID = "1283330969332842497";
    const CANONICAL = "twitter";

    const META_CARD = "twitter:card";
    const DEFAULT_IMAGE = ":apple-touch-icon.png";
    const META_DESCRIPTION = "twitter:description";
    const META_IMAGE = "twitter:image";
    const META_TITLE = "twitter:title";
    const META_CREATOR = "twitter:creator";
    const META_CREATOR_ID = "twitter:creator:id";
    const META_SITE = "twitter:site";
    const META_SITE_ID = "twitter:site:id";
    const META_IMAGE_ALT = "twitter:image:alt";
    const META_DNT = "twitter:dnt";
    const META_WIDGET_CSP = "twitter:widgets:csp";
    const META_WIDGETS_THEME = "twitter:widgets:theme";
    const META_WIDGETS_BORDER_COLOR = "twitter:widgets:border-color";


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

        $executionContext = ExecutionContext::getActualOrCreateFromEnv();

        try {
            $templateForWebPage = $executionContext->getExecutingPageTemplate();
            if(!$templateForWebPage->isSocial()){
                return;
            }
            $page = MarkupPath::createPageFromPathObject($templateForWebPage->getRequestedContextPath());
        } catch (ExceptionNotFound $e) {
            return;
        }


        if (!FileSystems::exists($page)) {
            return;
        }

        /**
         * No social for bars
         */
        if ($page->isSlot()) {
            return;
        }


        // https://datacadamia.com/marketing/twitter#html_meta
        // https://developer.twitter.com/en/docs/twitter-for-websites/cards/overview/markup
        // https://cards-dev.twitter.com/validator


        $twitterMeta = array(
            self::META_CARD => "summary",
            self::META_TITLE => StringUtility::truncateString($page->getTitleOrDefault(), 70),
            self::META_CREATOR => self::COMBO_STRAP_TWITTER_HANDLE,
            self::META_CREATOR_ID => self::COMBO_STRAP_TWITTER_ID
        );
        $description = $page->getDescriptionOrElseDokuWiki();
        if (!empty($description)) {
            // happens in test with document without content
            $twitterMeta[self::META_DESCRIPTION] = StringUtility::truncateString($description, 200);
        }


        /**
         * Twitter site
         */
        $siteTwitterHandle = $this->getConf(self::CONF_TWITTER_SITE_HANDLE);
        $siteTwitterId = $this->getConf(self::CONF_TWITTER_SITE_ID);
        if (!empty($siteTwitterHandle)) {
            $twitterMeta[self::META_SITE] = $siteTwitterHandle;

            // Identify the Twitter profile of the page that populates the via property
            // https://developer.twitter.com/en/docs/twitter-for-websites/webpage-properties
            $name = str_replace("@", "", $siteTwitterHandle);
            $event->data['link'][] = array("rel" => "me", "href" => "https://twitter.com/$name");
        }
        if (!empty($siteTwitterId)) {
            $twitterMeta[self::META_SITE_ID] = $siteTwitterId;
        }

        /**
         * Card image
         */
        try {
            $twitterImagePath = TwitterImage::createFromResource($page)->getValueOrDefault();
        } catch (ExceptionNotFound $e) {
            // no twitter image
            return;
        }

        if (!FileSystems::exists($twitterImagePath)) {
            LogUtility::error("The twitter image ($twitterImagePath) does not exists.", self::CANONICAL);
            return;
        }

        try {
            $twitterMeta[self::META_IMAGE] = FetcherRaster::createImageFetchFromPath($twitterImagePath)->getFetchUrl()->toAbsoluteUrlString();
        } catch (ExceptionBadArgument|ExceptionBadSyntax|ExceptionNotExists $e) {
            LogUtility::error("Error with the twitter image url. " . $e->getMessage(), self::CANONICAL, $e);
            return;
        }

        $title = ResourceName::getFromPath($twitterImagePath);
        if (!empty($title)) {
            $twitterMeta[self::META_IMAGE_ALT] = $title;
        }

        /**
         * https://developer.twitter.com/en/docs/twitter-for-websites/webpage-properties
         */
        // don't track
        $twitterMeta[self::META_DNT] = $this->getConf(self::CONF_TWITTER_DONT_NOT_TRACK, self::CONF_ON);
        // turn off csp warning
        $twitterMeta[self::META_WIDGET_CSP] = "on";

        /**
         * Embedded Tweet Theme
         */
        $twitterMeta[self::META_WIDGETS_THEME] = $this->getConf(BlockquoteTag::CONF_TWEET_WIDGETS_THEME, BlockquoteTag::CONF_TWEET_WIDGETS_THEME_DEFAULT);
        $twitterMeta[self::META_WIDGETS_BORDER_COLOR] = $this->getConf(BlockquoteTag::CONF_TWEET_WIDGETS_BORDER, BlockquoteTag::CONF_TWEET_WIDGETS_BORDER_DEFAULT);

        /**
         * Add the properties
         */
        foreach ($twitterMeta as $key => $content) {
            $event->data['meta'][] = array("name" => $key, "content" => $content);
        }


    }

}
