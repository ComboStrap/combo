<?php

use ComboStrap\Analytics;
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

require_once(__DIR__ . '/../class/' . 'Analytics.php');

/**
 * Class action_plugin_combo_analytics
 *
 * Update the analytics data if a link has been added or deleted
 */
class action_plugin_combo_metadata extends DokuWiki_Action_Plugin
{

    /**
     * @var array
     */
    protected $linksBeforeByPage = array();

    public function register(Doku_Event_Handler $controller)
    {

        /**
         * Check the internal link that have been
         * added or deleted to update the backlinks statistics
         * if a link has been added or deleted
         */
        $controller->register_hook('PARSER_METADATA_RENDER', 'AFTER', $this, 'handle_meta_renderer_after', array());
        $controller->register_hook('PARSER_METADATA_RENDER', 'BEFORE', $this, 'handle_meta_renderer_before', array());

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
        $links = $event->data['current']['relation']['references'];
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
        unset($pageId, $this->linksBeforeByPage);
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
        $linksChanged = $addedLinks;
        foreach ($linksBefore as $deletedLink => $deletedLinkPageExists) {
            $linksChanged[] = $deletedLink;
        }
        foreach ($linksChanged as $changedLink) {
            /**
             * We delete the cache
             * We don't update the analytics
             * because we want that the quality process will be running
             */
            $addedPage = new Page($changedLink);
            $addedPage->deleteAnalyticsCache();
        }

    }
}



