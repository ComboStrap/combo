<?php

use ComboStrap\DatabasePageRow;
use ComboStrap\FileSystems;
use ComboStrap\Page;
use ComboStrap\PageUrlPath;


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
         * they will be overwritten by calling the {@link getID()} function
         *
         */
        $id = getID();
        $page = Page::createPageFromId($id);
        if (!FileSystems::exists($page->getPath())) {
            // Is it a permanent link
            $pageId = PageUrlPath::decodePageId($page->getPath()->getLastName());
            if ($pageId !== null) {
                $page = DatabasePageRow::createFromPageIdAbbr($pageId)->getPage();
                if ($page === null) {
                    return;
                }
                if (!FileSystems::exists($page->getPath())) {
                    return;
                }
                if ($id === $page->getUrlId()){
                    /**
                     * hack as {@link getID()} invoked later reads the id from the input variable
                     */
                    global $INPUT;
                    $INPUT->set("id",$page->getPath()->getDokuwikiId());
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



