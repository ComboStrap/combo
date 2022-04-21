<?php

use ComboStrap\DatabasePageRow;
use ComboStrap\FileSystems;
use ComboStrap\Page;
use ComboStrap\PageUrlPath;
use ComboStrap\PluginUtility;


/**
 *   * Change the lang of the page if present
 *   * Modify some style
 */
class action_plugin_combo_lang extends DokuWiki_Action_Plugin
{

    const CANONICAL = "lang";

    public function register(Doku_Event_Handler $controller)
    {

        /**
         * https://www.dokuwiki.org/devel:event:init_lang_load
         */
        $controller->register_hook('INIT_LANG_LOAD', 'BEFORE', $this, 'load_lang', array());
        $controller->register_hook('INIT_LANG_LOAD', 'AFTER', $this, 'modifyRtlStyling', array());


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
            $encodedPageId = PageUrlPath::getShortEncodedPageIdFromUrlId($page->getPath()->getLastName());
            if ($encodedPageId !== null) {
                $pageId = PageUrlPath::decodePageId($encodedPageId);
                if ($pageId !== null) {
                    $page = DatabasePageRow::createFromPageIdAbbr($pageId)->getPage();
                    if ($page === null) {
                        return;
                    }
                    if (!FileSystems::exists($page->getPath())) {
                        return;
                    }

                    /**
                     * hack as {@link getID()} invoked later reads the id from the input variable
                     */
                    global $INPUT;
                    $INPUT->set("id", $page->getPath()->getDokuwikiId());

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

    /**
     *
     *
     * In case of a RTL lang, we put the secedit button to the left
     *
     * @param Doku_Event $event
     * @param $params
     *
     */
    function modifyRtlStyling(Doku_Event $event, $params)
    {

        /**
         * Lang for a page
         *
         * https://www.w3.org/International/questions/qa-html-language-declarations
         *   * Always use a language attribute on the html element.
         *   * When serving XHTML 1.x (ie. using a MIME type such as application/xhtml+xml),
         * use both the lang attribute and the xml:lang attribute together
         *
         * See also {@link \ComboStrap\Lang::processLangAttribute()} for the localization of an element
         *
         * put the button to the end when the page has a language direction of rtl
         */
        global $lang;
        if ($lang['direction'] === "rtl") {
            PluginUtility::getSnippetManager()->attachCssInternalStylesheetForRequest(self::CANONICAL . "-rtl");
        }


    }


}



