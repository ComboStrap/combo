<?php

use ComboStrap\WikiPath;
use ComboStrap\ExceptionCompile;
use ComboStrap\ExceptionNotFound;
use ComboStrap\FileSystems;
use ComboStrap\FetcherPage;
use ComboStrap\PageElement;
use ComboStrap\LogUtility;
use ComboStrap\Markup;
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


        $page = Markup::createFromRequestedPage();

        /**
         * Header
         */
        try {
            $pageHeaderSlotName = Site::getPageHeaderSlotName();
            if ($page->getPathObject()->toPathString() === ":$pageHeaderSlotName") {
                $pageHeaderPath = PageElement::getDefaultElementContentPath(FetcherPage::PAGE_HEADER_ELEMENT);
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
            if ($page->getPathObject()->toPathString() === ":$pageFooterSlotName") {
                $pageFooterPath = PageElement::getDefaultElementContentPath(FetcherPage::PAGE_FOOTER_ELEMENT);
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



