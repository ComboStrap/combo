<?php

use ComboStrap\Analytics;
use ComboStrap\Auth;
use ComboStrap\LogUtility;
use ComboStrap\Note;
use ComboStrap\Page;
use ComboStrap\PagesIndex;
use ComboStrap\PluginUtility;

if (!defined('DOKU_INC')) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');


require_once(__DIR__ . '/../class/Page.php');
require_once(__DIR__ . '/../class/Note.php');

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
            if (!Auth::isWriter()) {
                return;
            }

            $note = $this->getQualityNote(PluginUtility::getPageId(), $this);
            if ($note != null) {
                ptln($note->getHtml());
            }
        }

    }

    /**
     * @param $pageId
     * @param $plugin - Plugin
     * @return Note|null
     */
    static public function getQualityNote($pageId, $plugin)
    {
        $page = new Page($pageId);

        if ($page->isBar()) {
            return null;
        }
        if ($page->existInFs()) {
            $analytics = $page->getAnalyticsFromFs();
            $rules = $analytics[Analytics::QUALITY][Analytics::RULES];

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

                $qualityScore = $analytics[Analytics::QUALITY][renderer_plugin_combo_analytics::SCORING][renderer_plugin_combo_analytics::SCORE];
                $message = new Note($plugin);
                $message->addContent("<p>Well played, you got a " . PluginUtility::getUrl("quality:score", "quality score") . " of {$qualityScore} !</p>");
                if ($page->isLowQualityPage()) {
                    $analytics = $page->getAnalyticsFromFs(true);
                    $mandatoryFailedRules = $analytics[Analytics::QUALITY][Analytics::FAILED_MANDATORY_RULES];
                    $rulesUrl = PluginUtility::getUrl("quality:rule", "rules");
                    $lqPageUrl = PluginUtility::getUrl("low_quality_page", "low quality page");
                    $message->addContent("<div class='alert alert-info'>This is a {$lqPageUrl} because it has failed the following mandatory {$rulesUrl}:");
                    $message->addContent("<ul style='margin-bottom: 0'>");
                    foreach ($mandatoryFailedRules as $mandatoryFailedRule) {
                        $message->addContent("<li>{$mandatoryFailedRule}</li>");
                    }
                    $message->addContent("</ul>");
                    $message->addContent("</div>");
                }
                $message->addContent("<p>You can still win a couple of points.</p>");
                $message->addContent("<ul>");
                foreach ($qualityInfoRules as $qualityRule => $qualityInfo) {
                    $message->addContent("<li>");
                    $message->addContent($qualityInfo);
                    $message->addContent("</li>");
                }
                $message->addContent("</ul>");

                $message->setSignatureCanonical("quality:dynamic_monitoring");
                $message->setSignatureName("Quality Dynamic Monitoring Feature");
                $message->setType(Note::TYPE_CLASSIC);
                $message->setClass(self::QUALITY_BOX_CLASS);
                return $message;


            }
        }
        return null;
    }


}
