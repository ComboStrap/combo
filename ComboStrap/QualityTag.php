<?php

namespace ComboStrap;

use ComboStrap\Api\QualityMessageHandler;
use renderer_plugin_combo_analytics;

class QualityTag
{

    public const MARKUP_TAG = "quality";
    const CANONICAL = ":dynamic-quality-monitoring";

    /**
     * @param WikiPath $wikiPath
     * @return Message
     */
    public static function createQualityReport(WikiPath $wikiPath): Message
    {

        if (!FileSystems::exists($wikiPath)) {
            return Message::createInfoMessage("The resource ($wikiPath) does not exist, no quality report can be computed.");
        }

        try {
            $path = MarkupPath::createPageFromPathObject($wikiPath)->fetchAnalyticsPath();
            $analyticsArray = Json::createFromPath($path)->toArray();
        } catch (ExceptionCompile $e) {
            return Message::createErrorMessage("Error while trying to read the JSON analytics document. {$e->getMessage()}")
                ->setStatus(HttpResponseStatus::INTERNAL_ERROR);
        }

        $rules = $analyticsArray[renderer_plugin_combo_analytics::QUALITY][renderer_plugin_combo_analytics::RULES];

        /**
         * We may got null
         * array_key_exists() expects parameter 2 to be array,
         * null given in /opt/www/datacadamia.com/lib/plugins/combo/action/qualitymessage.php on line 113
         */
        if ($rules == null) {
            return Message::createInfoMessage("No rules found in the analytics document");
        }

        /**
         * If there is no info, nothing to show
         */
        if (!array_key_exists(renderer_plugin_combo_analytics::INFO, $rules)) {
            return Message::createInfoMessage("No quality rules information to show");
        }

        /**
         * The error info
         */
        $qualityInfoRules = $rules[renderer_plugin_combo_analytics::INFO];

        /**
         * Excluding the excluded rules
         */

        $excludedRulesConf =ExecutionContext::getActualOrCreateFromEnv()->getConfig()->getValue(QualityMessageHandler::CONF_EXCLUDED_QUALITY_RULES_FROM_DYNAMIC_MONITORING);
        $excludedRules = preg_split("/,/", $excludedRulesConf);
        foreach ($excludedRules as $excludedRule) {
            if (array_key_exists($excludedRule, $qualityInfoRules)) {
                unset($qualityInfoRules[$excludedRule]);
            }
        }

        if (sizeof($qualityInfoRules) <= 0) {
            return Message::createInfoMessage("No quality rules information to show");
        }

        $qualityScore = $analyticsArray[renderer_plugin_combo_analytics::QUALITY][renderer_plugin_combo_analytics::SCORING][renderer_plugin_combo_analytics::SCORE];
        $message = "<p>The page has a " . PluginUtility::getDocumentationHyperLink("quality:score", "quality score") . " of {$qualityScore}.</p>";

        $lowQuality = $analyticsArray[renderer_plugin_combo_analytics::QUALITY][renderer_plugin_combo_analytics::LOW];
        if ($lowQuality) {

            $mandatoryFailedRules = $analyticsArray[renderer_plugin_combo_analytics::QUALITY][renderer_plugin_combo_analytics::FAILED_MANDATORY_RULES];
            $rulesUrl = PluginUtility::getDocumentationHyperLink("quality:rule", "rules");
            $lqPageUrl = PluginUtility::getDocumentationHyperLink("low_quality_page", "low quality page");
            $message .= "<div class='alert alert-warning'>This is a {$lqPageUrl} because it has failed the following mandatory {$rulesUrl}:";
            $message .= "<ul style='margin-bottom: 0'>";
            /**
             * A low quality page should have
             * failed mandatory rules
             * but due to the asycn nature, sometimes
             * we don't have an array
             */
            if (is_array($mandatoryFailedRules)) {
                foreach ($mandatoryFailedRules as $mandatoryFailedRule) {
                    $message .= "<li>" . PluginUtility::getDocumentationHyperLink("quality:rule#list", $mandatoryFailedRule) . "</li>";
                }
            }
            $message .= "</ul>";
            $message .= "</div>";
        }
        $message .= "<p>You can still win a couple of points.</p>";
        $message .= "<ul>";
        foreach ($qualityInfoRules as $qualityRule => $qualityInfo) {
            $message .= "<li>$qualityInfo</li>";
        }
        $message .= "</ul>";

        $page = MarkupPath::createPageFromPathObject($wikiPath);
        $qualityMonitoringIndicator = QualityDynamicMonitoringOverwrite::createFromPage($page)->getValueOrDefault();
        if (!$qualityMonitoringIndicator) {
            $docLink = PluginUtility::getDocumentationHyperLink(self::CANONICAL, "configuration");
            $message .= "<p>This page is not quality monitored due its $docLink.</p>";
        }
        return Message::createInfoMessage($message);

    }

    public static function renderXhtml(TagAttributes $tagAttributes): string
    {
        $pageId = $tagAttributes->getComponentAttributeValue("page-id");
        if (empty($pageId)) {
            $contextPath = ExecutionContext::getActualOrCreateFromEnv()->getContextPath();
        } else {
            $contextPath = WikiPath::createMarkupPathFromId($pageId);
        }
        $message = QualityTag::createQualityReport($contextPath);
        return $message->getContent();


    }
}
