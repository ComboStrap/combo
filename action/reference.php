<?php

use ComboStrap\Call;
use ComboStrap\CallStack;
use ComboStrap\ExceptionCombo;
use ComboStrap\LinkUtility;
use ComboStrap\LogUtility;
use ComboStrap\MetadataDbStore;
use ComboStrap\MetadataDokuWikiStore;
use ComboStrap\Page;
use ComboStrap\PageId;
use ComboStrap\Reference;
use ComboStrap\References;

require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');


/**
 * Track Link mutation
 */
class action_plugin_combo_reference extends DokuWiki_Action_Plugin
{


    /**
     * @param Doku_Event_Handler $controller
     */
    function register(Doku_Event_Handler $controller)
    {


        /**
         * https://www.dokuwiki.org/devel:event:parser_handler_done
         */
        $controller->register_hook('PARSER_HANDLER_DONE', 'AFTER', $this, 'storeReference', array());

    }


    /**
     * https://www.dokuwiki.org/devel:event:parser_handler_done
     */
    function storeReference(Doku_Event $event, $params)
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
        $page = Page::createPageFromId($ID);
        $references = References::createFromResource($page)
            ->setReadStore(MetadataDokuWikiStore::class);

        while ($actualCall = $callStack->next()) {
            if (
                $actualCall->getTagName() === syntax_plugin_combo_link::TAG
                && $actualCall->getState() === DOKU_LEXER_ENTER
            ) {
                $ref = $actualCall->getAttribute(Reference::REF_PROPERTY);
                $link = LinkUtility::createFromRef($ref);
                if ($link->getType() === LinkUtility::TYPE_INTERNAL) {
                    $ref = Reference::createFromResource($page)
                        ->buildFromStoreValue($link->getInternalPage()->getPath()->toString());
                    $references->addRow([$ref]);
                }
            }
        }

        try {
            $references
                ->setWriteStore(MetadataDokuWikiStore::class)
                ->persist();
        } catch (ExceptionCombo $e) {
            LogUtility::msg("Reference error when persisting to the file system store: " . $e->getMessage(), LogUtility::LVL_MSG_ERROR);
        }

        try {

            // page id check
            // because we are the end of the parse
            // if there is a frontmatter with a page id. It should have be set
             PageId::createForPage($page)
                 ->getPageIdOrGenerate();

            $references
                ->setWriteStore(MetadataDbStore::class)
                ->persist();
        } catch (ExceptionCombo $e) {
            LogUtility::msg("Reference error when persisting to the database: " . $e->getMessage(), LogUtility::LVL_MSG_ERROR);
        }


    }


}
