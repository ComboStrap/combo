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

use ComboStrap\Event;
use ComboStrap\ExceptionTimeOut;
use ComboStrap\Lock;


/**
 * Just start our own event system
 */
class action_plugin_combo_eventsystem extends DokuWiki_Action_Plugin
{


    const CANONICAL = "event";
    private static Lock $taskRunnerlock;

    public function register(Doku_Event_Handler $controller)
    {

        /**
         * Process the event table
         *
         * We do it after because if there is an error
         * It will not stop the Dokuwiki Processing
         */
        $controller->register_hook('INDEXER_TASKS_RUN', 'AFTER', $this, 'processEventTable', array());

        /**
         * https://forum.dokuwiki.org/d/21044-taskrunner-running-multiple-times-eating-the-memory-lock/5
         */
        $controller->register_hook('INDEXER_TASKS_RUN', 'BEFORE', $this, 'lockSystemBefore', array());
        $controller->register_hook('INDEXER_TASKS_RUN', 'AFTER', $this, 'lockSystemAfter', array());


    }

    public function lockSystemBefore(Doku_Event $event, $param)
    {
        print 'ComboLockTaskRunner(): Trying to get a lock' . NL;
        self::$taskRunnerlock = Lock::create("combo-taskrunner");
        try {
            self::$taskRunnerlock->acquire();
        } catch (ExceptionTimeOut $e) {
            // process running
            print 'ComboLockTaskRunner(): Already running, not acquired' . NL;
            return;
        }
        print 'ComboLockTaskRunner(): Locked' . NL;
    }

    public function lockSystemAfter(Doku_Event $event, $param)
    {
        self::$taskRunnerlock->release();
        print 'ComboLockTaskRunner(): Lock Released' . NL;
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
