<?php

use ComboStrap\DatabasePage;
use ComboStrap\Page;


/**
 * Change the lang of the page if present
 */
class action_plugin_combo_metalang extends DokuWiki_Action_Plugin
{

    public function register(Doku_Event_Handler $controller)
    {

        /**
         * https://www.dokuwiki.org/devel:event:init_lang_load
         */
        $controller->register_hook('INIT_LANG_LOAD', 'BEFORE', $this, 'load_lang', array());


    }

    public function load_lang(Doku_Event $event, $param)
    {
        /**
         * On the test setup of Dokuwiki
         * this event is send without any context
         * data
         *
         * This event is send before DokuWiki environment has initialized
         * unfortunately
         *
         * We don't have any ID and we can't set them because
         * they will be overwritten
         * {@link getID()}
         */
        $id = getID();
        $page = Page::createPageFromId($id);
        if (!$page->exists()) {
            // Is it a permanent link
            $pageId = Page::decodePageId($page->getDokuPathName());
            if ($pageId !== null) {
                $page = DatabasePage::createFromPageIdAbbr($pageId)->getPage();
                if ($page === null) {
                    return;
                }
                if (!$page->exists()) {
                    return;
                }
                if ($id === $page->getUrlId()){
                    /**
                     * hack as {@link getID()} reads the id from the input variable
                     */
                    global $INPUT;
                    $INPUT->set("id",$page->getDokuwikiId());
                }
            }
        }
        $pageLang = $page->getLangOrDefault();
        global $conf;
        $initialLang = $event->data;
        if ($initialLang != $pageLang) {
            $conf['lang'] = $pageLang;
            $event->data = $pageLang;
        }

    }


}



