<?php

use ComboStrap\Call;
use ComboStrap\CallStack;
use ComboStrap\ExceptionCombo;
use ComboStrap\FileSystems;
use ComboStrap\MarkupRef;
use ComboStrap\LogUtility;
use ComboStrap\MetadataDbStore;
use ComboStrap\MetadataDokuWikiStore;
use ComboStrap\Page;
use ComboStrap\PluginUtility;
use ComboStrap\Reference;
use ComboStrap\References;

require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');


/**
 *
 * This code is part of the backlink mutation features.
 *
 * It stores the references for a pages at once
 * (and not one by one as Dokuwiki does)
 *
 * If the data mutate, the {@link action_plugin_combo_backlinkmutation}
 * will trigger, to recalculate the backlink analytics
 *
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
     * Store the references set for the whole page
     * The datastore will then see a mutation
     * processed by {@link action_plugin_combo_backlinkmutation}
     */
    function storeReference(Doku_Event $event, $params)
    {
        global $ID;
        if ($ID === null) {
            if (!PluginUtility::isDevOrTest()) {
                LogUtility::msg("The request ID was not set and should be present to store references.");
            }
            return;
        }

        /**
         * @var Call[] $links
         */
        $page = Page::createPageFromId($ID);

        /**
         * {@link \ComboStrap\PageId} is given only when the page exists
         * This event can be called even if the page does not exist
         */
        if (!FileSystems::exists($page->getPath())) {
            return;
        }

        /**
         * @var Doku_Handler $handler
         */
        $handler = $event->data;
        $callStack = CallStack::createFromHandler($handler);
        $callStack->moveToStart();

        $references = References::createFromResource($page)
            ->setReadStore(MetadataDokuWikiStore::class);

        while ($actualCall = $callStack->next()) {
            if (
                $actualCall->getTagName() === syntax_plugin_combo_link::TAG
                && $actualCall->getState() === DOKU_LEXER_ENTER
            ) {
                $ref = $actualCall->getAttribute(syntax_plugin_combo_link::ATTRIBUTE_HREF);
                if ($ref === null) {
                    /**
                     * The reference data is null for this link, it may be an external
                     * link created by a component such as {@link syntax_plugin_combo_share}
                     */
                    continue;
                }
                $link = MarkupRef::createFromRef($ref);
                if ($link->getUriType() === MarkupRef::WIKI_URI) {
                    $ref = Reference::createFromResource($page)
                        ->buildFromStoreValue($link->getInternalPage()->getPath()->toString());
                    $references->addRow([$ref]);
                }
            }
        }

        try {
            $references
                ->setWriteStore(MetadataDokuWikiStore::class)
                ->persist()
                ->setWriteStore(MetadataDbStore::class)
                ->persist();
        } catch (ExceptionCombo $e) {
            LogUtility::msg("Reference error when persisting to the file system store: " . $e->getMessage(), LogUtility::LVL_MSG_ERROR);
        }

    }


}
