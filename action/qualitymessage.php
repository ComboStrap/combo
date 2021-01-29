<?php

use ComboStrap\Analytics;
use ComboStrap\LogUtility;
use ComboStrap\Page;
use ComboStrap\PagesIndex;
use ComboStrap\PluginUtility;

if (!defined('DOKU_INC')) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');


require_once(__DIR__ . '/../class/Page.php');
require_once(__DIR__ . '/../class/message.model.php');

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
            $page = Page::createFromEnvironment();
            $analytics = $page->getAnalyticsFromFs();
            $qualityInfoRules = $analytics[Analytics::QUALITY][Analytics::RULES][Analytics::INFO];

            // Excluded rules
            $excludedRules = array(
                renderer_plugin_combo_analytics::RULE_AVERAGE_WORDS_BY_SECTION_MIN,
                renderer_plugin_combo_analytics::RULE_AVERAGE_WORDS_BY_SECTION_MAX
            );
            foreach ($excludedRules as $filter) {
                if (array_key_exists($filter, $qualityInfoRules)) {
                    unset($qualityInfoRules[$filter]);
                }
            }

            if (sizeof($qualityInfoRules) > 0) {

                $qualityScore = $analytics[Analytics::QUALITY][renderer_plugin_combo_analytics::SCORING][renderer_plugin_combo_analytics::SCORE];
                $message = new Message($this);
                $message->addContent("<p>Well played, you got a quality score of {$qualityScore} !</p>");
                $message->addContent("<p>You can still win a couple of points.</p>");
                $message->addContent("<ul>");
                foreach ($qualityInfoRules as $qualityRule => $qualityInfo) {
                    $message->addContent("<li>");
                    $message->addContent($qualityInfo);
                    $message->addContent("</li>");
                }
                $message->addContent("</ul>");

                $message->setSignatureCanonical("quality");
                $message->setSignatureName("Quality module");
                $message->setType(Message::TYPE_CLASSIC);
                $message->setClass(self::QUALITY_BOX_CLASS);

                $message->printMessage();

            }
        }

    }


}
