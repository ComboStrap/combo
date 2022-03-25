<?php


require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');

use ComboStrap\FileSystems;
use ComboStrap\LogUtility;
use ComboStrap\Page;
use ComboStrap\Site;

if (!defined('DOKU_INC')) exit;
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');

/**
 * Inject the main header / footer / side into the main document
 */
class action_plugin_combo_mainextended extends DokuWiki_Action_Plugin
{


    public function register(Doku_Event_Handler $controller)
    {

        /**
         *
         * https://www.dokuwiki.org/devel:event:io_wikipage_read
         */
        $controller->register_hook('IO_WIKIPAGE_READ', 'AFTER', $this, 'main_add_secondary_slot');

        /**
         * Page expiration feature
         * https://www.dokuwiki.org/devel:event:PARSER_CACHE_USE
         */
        $controller->register_hook('PARSER_CACHE_USE', 'BEFORE', $this, 'main_add_secondary_slot_dependencies', array());

    }


    /**
     * @param Doku_Event $event
     * Add the footer/header/sidebar around the dokuwiki content
     */
    public function main_add_secondary_slot(Doku_Event &$event)
    {

        global $ACT;

        if ($ACT !== 'show') {
            return;
        }

        /**
         * This function is called also when page with {@link saveWikiText}
         * because it reads the data for old content.
         * And we can't know if this is for a rendering or for a save
         */
        $data = $event->data;
        $ns = $data[1];
        $name = $data[2];
        if ($ns === false) {
            $qualifiedPath = ":$name";
        } else {
            $qualifiedPath = "$ns:$name";
        }
        $page = Page::createPageFromQualifiedPath($qualifiedPath);
        if ($page->isRootHomePage()) {
            return;
        }
        if (!FileSystems::exists($page->getPath())) {
            /**
             * Because this function is also called
             * first at the creation of the page via {@link saveWikiText()}
             * the page may not exists
             */
            return;
        }
        if (sizeof($page->getChildren()) > 0) {
            $event->result = $page->getMarkup();
        }

    }

    /**
     * @param Doku_Event $event
     * Add the footer/header/sidebar as dependencies
     */
    public function main_add_secondary_slot_dependencies(Doku_Event &$event)
    {

        $data = &$event->data;

        if (!is_object($data)) {
            // should not happen
            LogUtility::msg("The cache variable is not an object. Value: $data");
            return;
        }
        if (!property_exists($data, 'page')) {
            $class = get_class($data);
            LogUtility::msg("The property `page` does not exists on the object ($class)");
            return;
        }
        $pageId = $data->page;
        if (empty($pageId)) {
            return;
        }
        if (!property_exists($data, 'mode')) {
            $class = get_class($data);
            LogUtility::msg("The property `mode` does not exists on the object ($class)");
            return;
        }
        $mode = $data->mode;
        if ($mode !== "metadata") {
            return;
        }
        $page = Page::createPageFromId($pageId);
        if ($page->isRootHomePage()) {
            return;
        }
        foreach ($page->getChildren() as $child) {
            $event->data->depends['files'][] = $child->getPath()->toLocalPath()->toString();
        }


    }


}
