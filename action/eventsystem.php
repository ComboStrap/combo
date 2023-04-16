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
 *
 */

use ComboStrap\Console;
use ComboStrap\Event;
use ComboStrap\ExceptionCompile;
use ComboStrap\ExceptionNotFound;
use ComboStrap\ExceptionRuntimeInternal;
use ComboStrap\ExecutionContext;
use ComboStrap\FileSystems;
use ComboStrap\LogUtility;
use ComboStrap\MarkupPath;
use ComboStrap\Meta\Store\MetadataDbStore;
use ComboStrap\Meta\Store\MetadataDokuWikiStore;
use ComboStrap\PluginUtility;
use ComboStrap\Reference;
use ComboStrap\References;


/**
 * Just start our own event system
 */
class action_plugin_combo_eventsystem extends DokuWiki_Action_Plugin
{


    const CANONICAL = "event";

    public function register(Doku_Event_Handler $controller)
    {

        /**
         * Process the event table
         *
         * We do it after because if there is an error
         * It will not stop the Dokuwiki Processing
         */
        $controller->register_hook('INDEXER_TASKS_RUN', 'AFTER', $this, 'processEventTable', array());


    }

    /**
     */
    public function processEventTable(Doku_Event $event, $param)
    {


        /**
         * Process the async event
         */
        Event::dispatchEvent();


    }




}



