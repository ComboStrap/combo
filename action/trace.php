<?php

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

use ComboStrap\Bootstrap;
use ComboStrap\PluginUtility;
use ComboStrap\SnippetManager;
use ComboStrap\TraceMenuItem;

require_once(__DIR__ . '/../class/' . 'TraceMenuItem.php');

/**
 * Class action_plugin_combo_analytics
 * Update the analytics data
 */
class action_plugin_combo_trace extends DokuWiki_Action_Plugin
{


    public function register(Doku_Event_Handler $controller)
    {

        /**
         * Add a icon in the page tools menu
         * https://www.dokuwiki.org/devel:event:menu_items_assembly
         */
        $controller->register_hook('MENU_ITEMS_ASSEMBLY', 'AFTER', $this, 'handle_trace_item_rail_bar');


    }


    public function handle_trace_item_rail_bar(Doku_Event $event, $param)
    {

        if(Bootstrap::getBootStrapMajorVersion()== Bootstrap::BootStrapFiveMajorVersion) {


            /**
             * The `view` property defines the menu that is currently built
             * https://www.dokuwiki.org/devel:menus
             * If this is not the site menu, return
             */
            if ($event->data['view'] != 'site') return;

            /**
             * Making popover active
             */
            PluginUtility::getSnippetManager()->attachJavascriptSnippetForRequest("popover");

            array_splice($event->data['items'], -1, 0, array(new TraceMenuItem()));

        }

    }




}



