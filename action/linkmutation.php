<?php

use ComboStrap\Call;
use ComboStrap\CallStack;
use ComboStrap\Json;
use ComboStrap\LinkUtility;
use ComboStrap\LogUtility;
use ComboStrap\Page;
use ComboStrap\PluginUtility;
use ComboStrap\Sqlite;

require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');


/**
 * Track Link mutation
 */
class action_plugin_combo_linkmutation extends DokuWiki_Action_Plugin
{
    private $beforeLinkIds = [];


    /**
     * @param Doku_Event_Handler $controller
     */
    function register(Doku_Event_Handler $controller)
    {

        /**
         * https://www.dokuwiki.org/devel:event:parser_wikitext_preprocess
         */
        $controller->register_hook('PARSER_WIKITEXT_PREPROCESS', 'AFTER', $this, 'captureActualLinks', array());

        /**
         * https://www.dokuwiki.org/devel:event:parser_handler_done
         */
        $controller->register_hook('PARSER_HANDLER_DONE', 'AFTER', $this, 'processLinkMutation', array());

    }

    /**
     * Capture the actual links
     */
    function captureActualLinks(Doku_Event $event, $params)
    {
        global $ID;
        if ($ID === null) {
            LogUtility::msg("The ID should be present");
            return;
        }
        $page = Page::createPageFromId($ID);
        $forwardLinks = $page->getForwardLinks();
        $this->beforeLinkIds[$ID] = $forwardLinks;

    }

    /**
     * https://www.dokuwiki.org/devel:event:parser_handler_done
     */
    function processLinkMutation(Doku_Event $event, $params)
    {
        global $ID;
        if ($ID === null) {
            LogUtility::msg("The ID should be present");
            return;
        }

        /**
         * @var Doku_Handler $handler
         */
        $handler = $event->data;
        $callStack = CallStack::createFromHandler($handler);
        $callStack->moveToStart();

        /**
         * @var Call[] $links
         */
        $linkIds = [];
        while ($actualCall = $callStack->next()) {
            if ($actualCall->getTagName() === syntax_plugin_combo_link::TAG) {
                $ref = $actualCall->getAttribute("ref");
                $link = LinkUtility::createFromRef($ref);
                if ($link->getType() === LinkUtility::TYPE_INTERNAL) {
                    $linkIds[] = $link->getInternalPage()->getPath()->getDokuwikiId();
                }
            }
        }
        sort($linkIds);


    }


}
