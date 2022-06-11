<?php

use ComboStrap\DokuPath;
use ComboStrap\ExceptionCompile;
use ComboStrap\ExceptionNotFound;
use ComboStrap\FileSystems;
use ComboStrap\Layout;
use ComboStrap\LogUtility;
use ComboStrap\Page;
use ComboStrap\Site;

/**
 * Write the default slot
 */
class action_plugin_combo_slottemplate extends DokuWiki_Action_Plugin
{


    public function register(Doku_Event_Handler $controller)
    {

        $controller->register_hook('COMMON_PAGETPL_LOAD', 'BEFORE', $this, 'handle_new_slot', array());

    }

    public function handle_new_slot(Doku_Event $event, $param)
    {


        $page = Page::createPageFromRequestedPage();

        /**
         * Header
         */
        try {
            $pageHeaderSlotName = Site::getPageHeaderSlotName();
            if ($page->getPath()->toPathString() === ":$pageHeaderSlotName") {
                $pageHeaderPath = Layout::getDefaultAreaContentPath(Layout::PAGE_HEADER_AREA);
                try {
                    $event->data["tpl"] = FileSystems::getContent($pageHeaderPath);
                    $event->data["doreplace"] = false;
                } catch (ExceptionNotFound $e) {
                    // Should not happen
                    LogUtility::errorIfDevOrTest("Internal Error: The default page header was not found");
                }
                return;
            }
        } catch (ExceptionCompile $e) {
            LogUtility::error("We were unable to retrieve the page header slot name. Error: {$e->getMessage()}");
        }

        /**
         * Footer
         */
        try {
            $pageFooterSlotName = Site::getPageFooterSlotName();
            if ($page->getPath()->toPathString() === ":$pageFooterSlotName") {
                $pageFooterPath = Layout::getDefaultAreaContentPath(Layout::PAGE_FOOTER_AREA);
                try {
                    $event->data["tpl"] = FileSystems::getContent($pageFooterPath);
                    $event->data["doreplace"] = false;
                } catch (ExceptionNotFound $e) {
                    // Should not happen
                    LogUtility::errorIfDevOrTest("Internal Error: The default page footer was not found");
                }
                return;
            }
        } catch (ExceptionCompile $e) {
            LogUtility::error("We were unable to retrieve the page footer slot name. Error: {$e->getMessage()}");
            return;
        }


    }
}



