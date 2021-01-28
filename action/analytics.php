<?php

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

/**
 * Class action_plugin_combo_analytics
 * Update the analytics data
 */
class action_plugin_combo_analytics extends DokuWiki_Action_Plugin
{


    public function register(Doku_Event_Handler $controller)
    {
        /**
         * Called on every page view
         * https://www.dokuwiki.org/devel:event:indexer_tasks_run
         * Called on every page write
         * https://www.dokuwiki.org/devel:event:io_wikipage_write
         * On update to an existing page this event is called twice,
         * once for the transfer of the old version to the attic (rev will have a value)
         * and once to write the new version of the page into the wiki (rev is false)
         */
        if (false) {
            $controller->register_hook('INDEXER_TASKS_RUN', 'AFTER', $this, 'handle_update_analytics', array());
        }
    }

    public function handle_new_page(Doku_Event $event, $param){

        global $ID;
        $page = new Page($ID);
        $canonical = $page->getCanonical();
        $event->data["tpl"] = <<<EOF
---json
{
    "canonical":"{$canonical}",
    "title":"A title to show on the Search Engine Result Pages",
    "description":"A description show on the Search Engine Result Pages"
}
---
This content was created by the [[https://combostrap.com/frontmatter|frontmatter component]].
EOF;


    }
}



