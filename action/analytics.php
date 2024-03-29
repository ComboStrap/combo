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


require_once(__DIR__ . '/../vendor/autoload.php');

/**
 * Mandatory, don't known why ? Otherwise it does not work
 * The class is not found
 */
require_once(__DIR__ . '/../ComboStrap/AnalyticsMenuItem.php');

use Combostrap\AnalyticsMenuItem;
use ComboStrap\ExceptionNotFound;
use ComboStrap\ExecutionContext;
use ComboStrap\Identity;

/**
 * Class action_plugin_combo_analytics
 * Update the analytics data
 */
class action_plugin_combo_analytics extends DokuWiki_Action_Plugin
{


    public function register(Doku_Event_Handler $controller)
    {


        /**
         * Add a icon in the page tools menu
         * https://www.dokuwiki.org/devel:event:menu_items_assembly
         */
        $controller->register_hook('MENU_ITEMS_ASSEMBLY', 'AFTER', $this, 'handle_rail_bar');


    }


    public function handle_rail_bar(Doku_Event $event, $param)
    {


        $executionContext = ExecutionContext::getActualOrCreateFromEnv();
        if (!$executionContext->isPublicationAction()) {
            // a search for instance
            return;
        }

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
        $exists = $INFO['exists'] ?? null;
        if (!$exists) {
            return;
        }
        array_splice($event->data['items'], -1, 0, array(new AnalyticsMenuItem()));

    }


}



