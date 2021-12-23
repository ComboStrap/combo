<?php

use ComboStrap\Call;
use ComboStrap\CallStack;
use ComboStrap\Event;
use ComboStrap\ExceptionCombo;
use ComboStrap\LinkUtility;
use ComboStrap\LogUtility;
use ComboStrap\MetadataDbStore;
use ComboStrap\MetadataDokuWikiStore;
use ComboStrap\Page;
use ComboStrap\PageId;
use ComboStrap\PagePath;
use ComboStrap\PluginUtility;
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

        /**
         * To delete the analytics
         */
        $controller->register_hook(MetadataDokuWikiStore::PAGE_METADATA_MUTATION_EVENT, 'AFTER', $this, 'backlinksUpdate', array());

    }


    /**
     * https://www.dokuwiki.org/devel:event:parser_handler_done
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

    }

    /**
     * Just delete the Analytics
     * TODO: Put that in a pub/sub model via the PAGES_TO_REPLICATE
     */
    function backlinksUpdate(Doku_Event $event, $params)
    {


        $data = $event->data;

        if ($data["name"] !== References::getPersistentName()) {
            return;
        };

        $newRows = $data["new_value"];
        $oldRows = $data["old_value"];

        $newReferences = [];
        if ($newRows !== null) {
            foreach ($newRows as $rowNewValue) {
                $reference = $rowNewValue[Reference::getPersistentName()];
                $newReferences[$reference] = $reference;
            }
        }

        if ($oldRows !== null) {
            foreach ($oldRows as $oldRow) {
                $oldReference = $oldRow[Reference::getPersistentName()];
                if (isset($newReferences[$oldReference])) {
                    unset($newReferences[$oldReference]);
                } else {
                    Page::createPageFromQualifiedPath($oldReference)
                        ->getAnalyticsDocument()
                        ->deleteIfExists();
                    Event::createEvent('BACKLINK_MUTATION', [PagePath::getPersistentName() => $oldReference]);
                }
            }
        }
        foreach ($newReferences as $newReference) {
            Page::createPageFromQualifiedPath($newReference)
                ->getAnalyticsDocument()
                ->deleteIfExists();
            Event::createEvent('BACKLINK_MUTATION', [PagePath::getPersistentName() => $newReference]);
        }


    }

}
