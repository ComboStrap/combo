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

require_once(__DIR__ . '/../class/'.'Analytics.php');

/**
 * Class action_plugin_combo_analytics
 * Update the analytics data
 */
class action_plugin_combo_analytics extends DokuWiki_Action_Plugin
{


    public function register(Doku_Event_Handler $controller)
    {

        /**
         * Called on every page write
         * https://www.dokuwiki.org/devel:event:io_wikipage_write
         * On update to an existing page this event is called twice,
         * once for the transfer of the old version to the attic (rev will have a value)
         * and once to write the new version of the page into the wiki (rev is false)
         */
        $controller->register_hook('IO_WIKIPAGE_WRITE', 'AFTER', $this, 'handle_update_analytics', array());

    }

    public function handle_update_analytics(Doku_Event $event, $param)
    {

        $rev = $event->data[3];
        if ($rev===false){
            $id = $event->data[2];
            Analytics::process($id);
        }


    }
}



