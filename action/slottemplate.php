<?php

use ComboStrap\PageTemplate;
use ComboStrap\PluginUtility;
use ComboStrap\SlotSystem;
use ComboStrap\WikiPath;
use ComboStrap\ExceptionCompile;
use ComboStrap\ExceptionNotFound;
use ComboStrap\FileSystems;
use ComboStrap\FetcherPage;
use ComboStrap\PageTemplateSlot;
use ComboStrap\LogUtility;
use ComboStrap\MarkupPath;
use ComboStrap\Site;

/**
 * When a slot/fragment
 * is created,
 * this action will write the default slot
 * into the edit html section
 */
class action_plugin_combo_slottemplate extends DokuWiki_Action_Plugin
{


    public function register(Doku_Event_Handler $controller)
    {

        $controller->register_hook('COMMON_PAGETPL_LOAD', 'BEFORE', $this, 'handle_new_slot', array());

    }

    public function handle_new_slot(Doku_Event $event, $param)
    {


        try {
            $page = MarkupPath::createFromRequestedPage();
        } catch (ExceptionNotFound $e) {
            LogUtility::warning("The requested page was not found");
            return;
        }

        /**
         * Header
         */
        $pageHeaderSlotName = SlotSystem::getPageHeaderSlotName();
        $toQualifiedId = $page->getPathObject()->toAbsoluteString();
        if ($toQualifiedId === ":$pageHeaderSlotName") {
            $pageHeaderPath = PageTemplateSlot::getDefaultElementContentPath(PageTemplateSlot::PAGE_HEADER_ID);
            try {
                $event->data["tpl"] = FileSystems::getContent($pageHeaderPath);
                $event->data["doreplace"] = false;
            } catch (ExceptionNotFound $e) {
                // Should not happen
                LogUtility::errorIfDevOrTest("Internal Error: The default page header was not found");
            }
            return;
        }


        /**
         * Footer
         */

        $pageFooterSlotName = SlotSystem::getPageFooterSlotName();
        if ($toQualifiedId === ":$pageFooterSlotName") {
            $pageFooterPath = PageTemplateSlot::getDefaultElementContentPath(PageTemplateSlot::PAGE_FOOTER_ID);
            try {
                $event->data["tpl"] = FileSystems::getContent($pageFooterPath);
                $event->data["doreplace"] = false;
            } catch (ExceptionNotFound $e) {
                // Should not happen
                LogUtility::errorIfDevOrTest("Internal Error: The default page footer was not found");
            }
        }


    }
}



