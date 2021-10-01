<?php

use Combostrap\AnalyticsMenuItem;
use ComboStrap\Identity;
use ComboStrap\Page;

/**
 * Copyright (c) 2021. ComboStrap, Inc. and its affiliates. All Rights Reserved.
 *
 * This source code is licensed under the GPL license found in the
 * COPYING  file in the root directory of this source tree.
 *
 * @license  GPL 3 (https://www.gnu.org/licenses/gpl-3.0.en.html)
 * @author   ComboStrap <support@combostrap.com>
 *
 */


require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');

/**
 * Class action_plugin_combo_analytics
 * Update the analytics data
 */
class action_plugin_combo_analytics extends DokuWiki_Action_Plugin
{

    /**
     * @var array
     */
    protected $linksBeforeByPage = array();

    public function register(Doku_Event_Handler $controller)
    {


        /**
         * Add a icon in the page tools menu
         * https://www.dokuwiki.org/devel:event:menu_items_assembly
         */
        $controller->register_hook('MENU_ITEMS_ASSEMBLY', 'AFTER', $this, 'handle_rail_bar');

        /**
         * Check the internal link that have been
         * added or deleted to update the backlinks statistics
         * if a link has been added or deleted
         */
        $controller->register_hook('PARSER_METADATA_RENDER', 'AFTER', $this, 'handle_meta_renderer_after', array());
        $controller->register_hook('PARSER_METADATA_RENDER', 'BEFORE', $this, 'handle_meta_renderer_before', array());

    }


    public function handle_rail_bar(Doku_Event $event, $param)
    {

        if (!Identity::isWriter()) {
            return;
        }

        /**
         * The `view` property defines the menu that is currently built
         * https://www.dokuwiki.org/devel:menus
         * If this is not the page menu, return
         */
        if ($event->data['view'] != 'page') return;

        global $INFO;
        if (!$INFO['exists']) {
            return;
        }
        array_splice($event->data['items'], -1, 0, array(new AnalyticsMenuItem()));

    }


    /**
     * Generate the statistics for the internal link added or deleted
     *
     * @param Doku_Event $event
     * @param $param
     */
    public function handle_meta_renderer_before(Doku_Event $event, $param)
    {

        $pageId = $event->data['page'];
        $page = Page::createPageFromId($pageId);
        $links = $page->getInternalLinks();
        if ($links !== null) {
            $this->linksBeforeByPage[$pageId] = $links;
        } else {
            $this->linksBeforeByPage[$pageId] = array();
        }


    }

    /**
     * Save the links before metadata render
     * @param Doku_Event $event
     * @param $param
     */
    public function handle_meta_renderer_after(Doku_Event $event, $param)
    {

        $pageId = $event->data['page'];
        $linksAfter = $event->data['current']['relation']['references'];
        if ($linksAfter == null) {
            $linksAfter = array();
        }
        $linksBefore = $this->linksBeforeByPage[$pageId];
        unset($this->linksBeforeByPage[$pageId]);
        $addedLinks = array();
        foreach ($linksAfter as $linkAfter => $exist) {
            if (array_key_exists($linkAfter, $linksBefore)) {
                unset($linksBefore[$linkAfter]);
            } else {
                $addedLinks[] = $linkAfter;
            }
        }

        /**
         * Process to update the backlinks
         */
        $linksChanged = array_fill_keys($addedLinks, "added");
        foreach ($linksBefore as $deletedLink => $deletedLinkPageExists) {
            $linksChanged[$deletedLink] = 'deleted';
        }
        foreach ($linksChanged as $referentPageId => $status) {
            /**
             * We delete the analytics data of the referent page
             */
            $page = Page::createPageFromId($referentPageId);
            $page->getAnalytics()->delete();
            /**
             * Replication
             */
            $message = "The analytics of the page ($referentPageId) was deleted because a backlink from the page {$pageId} was {$status}";
            $page->getReplicator()->createReplicationRequest($message);

        }

    }


}



