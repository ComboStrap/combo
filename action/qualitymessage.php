<?php


use ComboStrap\ExceptionCompile;
use ComboStrap\ExecutionContext;
use ComboStrap\HttpResponse;
use ComboStrap\HttpResponseStatus;
use ComboStrap\Identity;
use ComboStrap\Message;
use ComboStrap\Mime;
use ComboStrap\MarkupPath;
use ComboStrap\PluginUtility;
use ComboStrap\QualityMenuItem;


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

    public static function createHtmlQualityNote(MarkupPath $page): Message
    {
        if ($page->isSecondarySlot()) {
            return Message::createErrorMessage("A has no quality metrics");

        }


        if (!$page->exists()) {
            return Message::createInfoMessage("The page does not exist");
        }


        try {
            $analyticsArray = \ComboStrap\Json::createFromPath($page->fetchAnalyticsPath())->toArray();
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
        global $conf;
        $excludedRulesConf = $conf['plugin'][PluginUtility::PLUGIN_BASE_NAME][self::CONF_EXCLUDED_QUALITY_RULES_FROM_DYNAMIC_MONITORING];
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

        if (!$page->isDynamicQualityMonitored()) {
            $docLink = PluginUtility::getDocumentationHyperLink(":dynamic-quality-monitoring", "configuration");
            $message .= "<p>This page is not quality monitored due its $docLink.</p>";
        }
        return Message::createInfoMessage($message);

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
        $executingContext = ExecutionContext::getActualOrCreateFromEnv();
        if (empty($id)) {
            $executingContext->response()
                ->setStatus(HttpResponseStatus::BAD_REQUEST)
                ->setEvent($event)
                ->setCanonical(self::CANONICAL)
                ->setBody("The page id should not be empty", Mime::getHtml())
                ->end();
            return;
        }

        /**
         * Quality is just for the writers
         */
        if (!Identity::isWriter($id)) {
            $executingContext->response()
                ->setStatus(HttpResponseStatus::NOT_AUTHORIZED)
                ->setEvent($event)
                ->setBody("Quality is only for writer", Mime::getHtml())
                ->end();
            return;
        }


        $page = MarkupPath::createMarkupFromId($id);

        $message = self::createHtmlQualityNote($page);

        $status = $message->getStatus();
        if ($status === null) {
            $status = HttpResponseStatus::ALL_GOOD;
        }

        $executingContext->response()
            ->setStatus($status)
            ->setEvent($event)
            ->setCanonical(self::CANONICAL)
            ->setBody($message->getContent(), Mime::getHtml())
            ->end();

    }
}
