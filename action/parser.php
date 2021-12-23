<?php
/**
 * Action Component
 * Add a button in the edit toolbar
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Nicolas GERARD
 */

use ComboStrap\Resources;

if (!defined('DOKU_INC')) die();
require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');

/**
 * Class action_plugin_combo_parser
 * Capture if the parser is running
 */
class action_plugin_combo_parser extends DokuWiki_Action_Plugin
{


    const  RUNNING = "running";
    const  NOT_RUNNING = "not_running";
    private static $PARSER_STATE = self::NOT_RUNNING;

    /**
     * register the event handlers
     *

     */
    function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('PARSER_WIKITEXT_PREPROCESS', 'BEFORE', $this, 'startParser', array());
        $controller->register_hook('PARSER_HANDLER_DONE', 'AFTER', $this, 'endParser', array());
    }

    function startParser(&$event, $param)
    {

        self::$PARSER_STATE = self::RUNNING;
        return true;

    }

    function endParser(&$event, $param)
    {

        self::$PARSER_STATE = self::NOT_RUNNING;
        return true;

    }

    static function isParserRunning()
    {
        return self::$PARSER_STATE === self::RUNNING;
    }


}

