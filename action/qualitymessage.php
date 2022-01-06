<?php

use ComboStrap\AnalyticsDocument;
use ComboStrap\ExceptionCombo;
use ComboStrap\Identity;
use ComboStrap\LogUtility;
use ComboStrap\Message;
use ComboStrap\Mime;
use ComboStrap\Page;
use ComboStrap\PluginUtility;
use ComboStrap\QualityMenuItem;
use ComboStrap\HttpResponse;

if (!defined('DOKU_INC')) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');


require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');

/**
 *
 * Show a quality message
 *
 *
 *
 */
class action_plugin_combo_qualitymessage extends DokuWiki_Action_Plugin
{

    // a class can not start with a number
    const QUALITY_BOX_CLASS = "quality-message";

    /**
     * The quality rules that will not show
     * up in the messages
     */
    const CONF_EXCLUDED_QUALITY_RULES_FROM_DYNAMIC_MONITORING = "excludedQualityRulesFromDynamicMonitoring";
    /**
     * Disable the message totally
     */
    const CONF_DISABLE_QUALITY_MONITORING = "disableDynamicQualityMonitoring";

    const CANONICAL = "quality:dynamic_monitoring";

    const META_MANAGER_CALL_ID = "combo-quality-message";


    function __construct()
    {
        // enable direct access to language strings
        // ie $this->lang
        $this->setupLocale();
    }


    function register(Doku_Event_Handler $controller)
    {


        /**
         * Add a icon in the page tools menu
         * https://www.dokuwiki.org/devel:event:menu_items_assembly
         */
        $controller->register_hook('MENU_ITEMS_ASSEMBLY', 'AFTER', $this, 'addMenuItem');


        /**
         * The ajax api to return data
         */
        $controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE', $this, 'ajaxCall');

    }


    function addMenuItem(Doku_Event $event, $param)
    {

        if (!Identity::isWriter()) {
            return;
        }

        /**
         * The `view` property defines the menu that is currently built
         * https://www.dokuwiki.org/devel:menus
         * If this is not the page menu, return
         */
        if ($event->data['view'] != 'page') return;

        global $INFO;
        if (!$INFO['exists']) {
            return;
        }
        array_splice($event->data['items'], -1, 0, array(new QualityMenuItem()));

    }

    /**
     * Main function; dispatches the visual comment actions
     * @param   $event Doku_Event
     */
    function ajaxCall(&$event, $param): void
    {
        $call = $event->data;
        if ($call != self::META_MANAGER_CALL_ID) {
            return;
        }
        //no other ajax call handlers needed
        $event->stopPropagation();
        $event->preventDefault();

        /**
         * Shared check between post and get HTTP method
         */
        $id = $_GET["id"];
        if ($id === null) {
            /**
             * With {@link TestRequest}
             * for instance
             */
            $id = $_REQUEST["id"];
        }

        if (empty($id)) {
            HttpResponse::create(HttpResponse::STATUS_BAD_REQUEST)
                ->setEvent($event)
                ->setCanonical(self::CANONICAL)
                ->send("The page id should not be empty", Mime::HTML);
            return;
        }

        /**
         * Quality is just for the writers
         */
        if (!Identity::isWriter($id)) {
            HttpResponse::create(HttpResponse::STATUS_NOT_AUTHORIZED)
                ->setEvent($event)
                ->send("Quality is only for writer", Mime::HTML);
            return;
        }


        $page = Page::createPageFromId($id);

        if ($page->isSlot()) {
            HttpResponse::create(HttpResponse::STATUS_BAD_REQUEST)
                ->setEvent($event)
                ->setCanonical(self::CANONICAL)
                ->send("A has no quality metrics", Mime::HTML);
            return;
        }

        if (!$page->isDynamicQualityMonitored()) {
            HttpResponse::create(HttpResponse::STATUS_ALL_GOOD)
                ->setEvent($event)
                ->setCanonical(self::CANONICAL)
                ->send("This page is not quality monitored. Change the configuration if you want to.", Mime::HTML);
            return;
        }

        if (!$page->exists()) {
            HttpResponse::create(HttpResponse::STATUS_ALL_GOOD)
                ->setEvent($event)
                ->setCanonical(self::CANONICAL)
                ->send("The page does not exist", Mime::HTML);
            return;
        }


        try {
            $analyticsArray = $page->getAnalyticsDocument()->getJson()->toArray();
        } catch (ExceptionCombo $e) {
            HttpResponse::create(HttpResponse::STATUS_INTERNAL_ERROR)
                ->setEvent($event)
                ->setCanonical(self::CANONICAL)
                ->send("Error while trying to read the JSON analytics document. {$e->getMessage()}", Mime::HTML);
            return;
        }

        $rules = $analyticsArray[AnalyticsDocument::QUALITY][AnalyticsDocument::RULES];


        /**
         * We may got null
         * array_key_exists() expects parameter 2 to be array,
         * null given in /opt/www/datacadamia.com/lib/plugins/combo/action/qualitymessage.php on line 113
         */
        if ($rules == null) {
            HttpResponse::create(HttpResponse::STATUS_ALL_GOOD)
                ->setEvent($event)
                ->setCanonical(self::CANONICAL)
                ->send("No rules found in the analytics document", Mime::HTML);
            return;
        }

        /**
         * If there is no info, nothing to show
         */
        if (!array_key_exists(AnalyticsDocument::INFO, $rules)) {
            HttpResponse::create(HttpResponse::STATUS_ALL_GOOD)
                ->setEvent($event)
                ->setCanonical(self::CANONICAL)
                ->send("No quality rules information to show", Mime::HTML);
            return;
        }

        /**
         * The error info
         */
        $qualityInfoRules = $rules[AnalyticsDocument::INFO];

        /**
         * Excluding the excluded rules
         */
        global $conf;
        $excludedRulesConf = $conf['plugin'][PluginUtility::PLUGIN_BASE_NAME][self::CONF_EXCLUDED_QUALITY_RULES_FROM_DYNAMIC_MONITORING];
        $excludedRules = preg_split("/,/", $excludedRulesConf);
        foreach ($excludedRules as $excludedRule) {
            if (array_key_exists($excludedRule, $qualityInfoRules)) {
                unset($qualityInfoRules[$excludedRule]);
            }
        }

        if (sizeof($qualityInfoRules) <= 0) {
            HttpResponse::create(HttpResponse::STATUS_ALL_GOOD)
                ->setEvent($event)
                ->setCanonical(self::CANONICAL)
                ->send("No quality rules information to show", Mime::HTML);
            return;
        }

        $qualityScore = $analyticsArray[AnalyticsDocument::QUALITY][renderer_plugin_combo_analytics::SCORING][renderer_plugin_combo_analytics::SCORE];
        $message = Message::createInfoMessage()
            ->addHtmlContent("<p>Well played, you got a " . PluginUtility::getDocumentationHyperLink("quality:score", "quality score") . " of {$qualityScore} !</p>");

        $lowQuality = $analyticsArray[AnalyticsDocument::QUALITY][AnalyticsDocument::LOW];
        if ($lowQuality) {

            $mandatoryFailedRules = $analyticsArray[AnalyticsDocument::QUALITY][AnalyticsDocument::FAILED_MANDATORY_RULES];
            $rulesUrl = PluginUtility::getDocumentationHyperLink("quality:rule", "rules");
            $lqPageUrl = PluginUtility::getDocumentationHyperLink("low_quality_page", "low quality page");
            $message->addHtmlContent("<div class='alert alert-info'>This is a {$lqPageUrl} because it has failed the following mandatory {$rulesUrl}:");
            $message->addHtmlContent("<ul style='margin-bottom: 0'>");
            /**
             * A low quality page should have
             * failed mandatory rules
             * but due to the asycn nature, sometimes
             * we don't have an array
             */
            if (is_array($mandatoryFailedRules)) {
                foreach ($mandatoryFailedRules as $mandatoryFailedRule) {
                    $message->addHtmlContent("<li>" . PluginUtility::getDocumentationHyperLink("quality:rule#list", $mandatoryFailedRule) . "</li>");
                }
            }
            $message->addHtmlContent("</ul>");
            $message->addHtmlContent("</div>");
        }
        $message->addHtmlContent("<p>You can still win a couple of points.</p>");
        $message->addHtmlContent("<ul>");
        foreach ($qualityInfoRules as $qualityRule => $qualityInfo) {
            $message->addHtmlContent("<li>$qualityInfo</li>");
        }
        $message->addHtmlContent("</ul>");

        $htmlBox = $message->setCanonical("quality:dynamic_monitoring")
            ->setSignatureName("Quality Dynamic Monitoring Feature")
            ->setClass(self::QUALITY_BOX_CLASS)
            ->toHtmlBox();

        HttpResponse::create(HttpResponse::STATUS_ALL_GOOD)
            ->setEvent($event)
            ->send($htmlBox, Mime::HTML);

    }
}
