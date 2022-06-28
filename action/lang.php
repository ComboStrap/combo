<?php

use ComboStrap\DatabasePageRow;
use ComboStrap\DokuPath;
use ComboStrap\ExceptionNotFound;
use ComboStrap\FileSystems;
use ComboStrap\LogUtility;
use ComboStrap\PageFragment;
use ComboStrap\PageUrlPath;
use ComboStrap\PluginUtility;


/**
 *   * Change the lang of the page if present
 *   * Modify some style
 */
class action_plugin_combo_lang extends DokuWiki_Action_Plugin
{

    const CANONICAL = "lang";

    /**
     *
     * hack as:
     *   * {@link getID()} invoked later reads the id from the input variable
     *   * {@link PluginUtility::getRequestedWikiId()} read it then also
     *
     * @param string $normalizedId
     * @return void
     */
    private static function setNormalizedId(string $normalizedId)
    {
        global $INPUT;
        $INPUT->set("id", $normalizedId);
    }

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
        /**
         * Arabic characters should not be deleted, otherwise the page id abbr becomes the last name
         * when URL encoding is used with arabic language
         * ie:
         * locale:%F8%B5%F9%81%F8%AD%F8%A9-id1tgpx9
         * becomes
         * locale:id1tgpx9
         */
        $clean = false;
        /** @noinspection PhpConditionAlreadyCheckedInspection */
        $id = getID("id", $clean);
        $id = DokuPath::normalizeWikiPath($id);
        self::setNormalizedId($id);
        $page = PageFragment::createPageFromId($id);
        if (!FileSystems::exists($page->getPath())) {
            // Is it a permanent link
            try {
                $lastPartName = $page->getPath()->getLastNameWithoutExtension();
            } catch (ExceptionNotFound $e) {
                // only the root does not have any name, it should therefore never happen
                LogUtility::internalError("No last name, we were unable to set the request id right", self::CANONICAL);
                return;
            }
            $encodedPageId = PageUrlPath::getShortEncodedPageIdFromUrlId($lastPartName);
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

                    self::setNormalizedId($page->getPath()->getWikiId());


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



