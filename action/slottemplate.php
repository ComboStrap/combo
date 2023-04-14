<?php

use ComboStrap\TemplateForWebPage;
use ComboStrap\PluginUtility;
use ComboStrap\SlotSystem;
use ComboStrap\WikiPath;
use ComboStrap\ExceptionCompile;
use ComboStrap\ExceptionNotFound;
use ComboStrap\FileSystems;
use ComboStrap\FetcherPage;
use ComboStrap\TemplateSlot;
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


    const CANONICAL = "slot-template";

    public function register(Doku_Event_Handler $controller)
    {

        $controller->register_hook('COMMON_PAGETPL_LOAD', 'BEFORE', $this, 'handle_new_slot', array());

    }

    public function handle_new_slot(Doku_Event $event, $param)
    {


        $id = $event->data['id'];
        $page = MarkupPath::createMarkupFromId($id);
        if (!$page->isSlot()) {
            return;
        }


        try {
            $pathName = $page->getLastNameWithoutExtension();
        } catch (ExceptionNotFound $e) {
            LogUtility::internalError("Should not happen as it's not the root", self::CANONICAL);
            return;
        }

        $pageHeaderPath = TemplateSlot::createFromPathName($pathName)->getDefaultSlotContentPath();
        if (!FileSystems::exists($pageHeaderPath)) {
            return;
        }
        try {
            $event->data["tpl"] = FileSystems::getContent($pageHeaderPath);
            $event->data["doreplace"] = false;
        } catch (ExceptionNotFound $e) {
            // Should not happen
            LogUtility::error("Internal Error", self::CANONICAL, $e);
        }


    }
}



