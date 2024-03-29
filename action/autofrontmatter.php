<?php

use ComboStrap\ExceptionCompile;
use ComboStrap\LogUtility;
use ComboStrap\MarkupPath;

require_once(__DIR__ . '/../vendor/autoload.php');

/**
 * Copyright (c) 2021. ComboStrap, Inc. and its affiliates. All Rights Reserved.
 *
 * This source code is licensed under the GPL license found in the
 * COPYING  file in the root directory of this source tree.
 *
 * @license  GPL 3 (https://www.gnu.org/licenses/gpl-3.0.en.html)
 * @author   ComboStrap <support@combostrap.com>
 *
 *
 * @deprecated - the frontmatter is no more used to enter metadata.
 */
class action_plugin_combo_autofrontmatter extends DokuWiki_Action_Plugin
{


    public function register(Doku_Event_Handler $controller)
    {
        /**
         * Called when new page is created
         * In order to set its content
         * https://www.dokuwiki.org/devel:event:common_pagetpl_load
         */
        if (false) {
            $controller->register_hook('COMMON_PAGETPL_LOAD', 'BEFORE', $this, 'handle_new_page', array());
        }
    }

    public function handle_new_page(Doku_Event $event, $param){

        try {
            $page = MarkupPath::createPageFromExecutingId();
        } catch (ExceptionCompile $e) {
            LogUtility::msg("Unable to handle a new page because the global id is unknown");
        }
        $canonical = $page->getCanonicalOrDefault();
        $event->data["tpl"] = <<<EOF
---json
{
    "canonical":"{$canonical}",
    "title":"A [[https://combostrap.com/frontmatter|frontmatter]] title shown on the Search Engine Result Pages",
    "description":"A [[https://combostrap.com/frontmatter|frontmatter]] description shown on the Search Engine Result Pages"
}
---
EOF;


    }
}



