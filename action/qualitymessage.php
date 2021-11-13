<?php

use ComboStrap\Analytics;
use ComboStrap\Identity;
use ComboStrap\Message;
use ComboStrap\Page;
use ComboStrap\PluginUtility;

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

    /**
     * Key in the frontmatter that disable the message
     */
    const DYNAMIC_QUALITY_MONITORING_INDICATOR = "dynamic_quality_monitoring";
    const CANONICAL = "quality:dynamic_monitoring";


    function __construct()
    {
        // enable direct access to language strings
        // ie $this->lang
        $this->setupLocale();
    }


    function register(Doku_Event_Handler $controller)
    {


        $controller->register_hook(
            'TPL_ACT_RENDER',
            'BEFORE',
            $this,
            '_displayQualityMessage',
            array()
        );


    }


    /**
     * Main function; dispatches the visual comment actions
     * @param   $event Doku_Event
     */
    function _displayQualityMessage(&$event, $param)
    {
        if ($event->data == 'show') {

            /**
             * Quality is just for the writers
             */
            if (!Identity::isWriter()) {
                return;
            }

            $htmlNote = $this->createQualityNote($this);
            if ($htmlNote != null) {
                ptln($htmlNote);
            }
        }

    }

    /**
     * @param $plugin - Plugin
     * @return string|null
     */
    static public function createQualityNote($plugin): ?string
    {
        $page = Page::createPageFromRequestedPage();

        if ($page->isSlot()) {
            return null;
        }

        if (!$page->isDynamicQualityMonitored()) {
            return null;
        }

        if ($page->exists()) {

            $analyticsArray = $page->getAnalytics()->getData()->toArray();
            $rules = $analyticsArray[Analytics::QUALITY][Analytics::RULES];


            /**
             * We may got null
             * array_key_exists() expects parameter 2 to be array,
             * null given in /opt/www/datacadamia.com/lib/plugins/combo/action/qualitymessage.php on line 113
             */
            if ($rules == null) {
                return null;
            }

            /**
             * If there is no info, nothing to show
             */
            if (!array_key_exists(Analytics::INFO, $rules)) {
                return null;
            }

            /**
             * The error info
             */
            $qualityInfoRules = $rules[Analytics::INFO];

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

            if (sizeof($qualityInfoRules) > 0) {

                $qualityScore = $analyticsArray[Analytics::QUALITY][renderer_plugin_combo_analytics::SCORING][renderer_plugin_combo_analytics::SCORE];
                $message = Message::createInfoMessage()
                    ->setPlugin($plugin)
                    ->addHtmlContent("<p>Well played, you got a " . PluginUtility::getDocumentationHyperLink("quality:score", "quality score") . " of {$qualityScore} !</p>");

                if ($page->isLowQualityPage()) {
                    $analyticsArray = $page->getAnalytics()->getData()->toArray();
                    $mandatoryFailedRules = $analyticsArray[Analytics::QUALITY][Analytics::FAILED_MANDATORY_RULES];
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

                return $message->setCanonical("quality:dynamic_monitoring")
                    ->setSignatureName("Quality Dynamic Monitoring Feature")
                    ->setClass(self::QUALITY_BOX_CLASS)
                    ->toHtmlBox();

            }
        }
        return null;
    }


}
