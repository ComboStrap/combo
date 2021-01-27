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

namespace ComboStrap;


use helper_plugin_sqlite;

class Sqlite
{

    /** @var helper_plugin_sqlite $sqlite */
    protected static $sqlite;

    /**
     * Init the data store
     * Sqlite cannot be static because
     * between two test classes
     * the data dir where the database is saved is deleted.
     *
     * You need to store the variable in your plugin
     *
     * @return helper_plugin_sqlite $sqlite
     */
    public static function getSqlite()
    {
        /**
         * sqlite is stored in a static variable
         * because when we run the {@link cli_plugin_combo},
         * we will run in
         * ``
         * failed to open stream: Too many open files
         * ``
         * There is by default a limit of 1024 open files
         * which means that if there is more than 1024 pages, you fail.
         *
         * In test, we are running in different context (ie different root
         * directory for Dokuwiki and therefore different $conf
         * and therefore different metadir where sqlite is stored)
         * Because a sql file may be deleted, we may get:
         * ```
         * RuntimeException: HY000 8 attempt to write a readonly database:
         * ```
         * To avoid this error, we check that we are still in the same metadir
         * where the sqlite database is stored. If not, we create a new instance
         *
         */
        global $conf;
        $metaDir = $conf['metadir'];
        $init = true;
        if (self::$sqlite != null) {
            $dbFile = self::$sqlite->getAdapter()->getDbFile();
            if (strpos($dbFile, $metaDir) === 0) {
                $init = false;
            } else {
                self::$sqlite->getAdapter()->closedb();
                self::$sqlite = null;
            }
        }
        if ($init) {

            /**
             * Init
             */
            self::$sqlite = plugin_load('helper', 'sqlite');
            if (self::$sqlite == null) {
                # TODO: Man we cannot get the message anymore ['SqliteMandatory'];
                $sqliteMandatoryMessage = "The Sqlite Plugin is mandatory. Some functionalities of the Combostraps Plugin may not work.";
                msg($sqliteMandatoryMessage, LogUtility::LVL_MSG_ERROR);
                return null;
            }
            $adapter = self::$sqlite->getAdapter();
            if ($adapter == null) {
                $sqliteMandatoryMessage = "The Sqlite Php Extension is mandatory. It seems that it's not available on this installation.";
                msg($sqliteMandatoryMessage, LogUtility::LVL_MSG_ERROR);
                return null;
            }

            $adapter->setUseNativeAlter(true);

            // The name of the database (on windows, it should be
            $dbname = strtolower(PluginUtility::PLUGIN_BASE_NAME);
            global $conf;

            $oldDbName = '404manager';
            $oldDbFile = $conf['metadir'] . "/{$oldDbName}.sqlite";
            $oldDbFileSqlite3 = $conf['metadir'] . "/{$oldDbName}.sqlite3";
            if (file_exists($oldDbFile) || file_exists($oldDbFileSqlite3)) {
                $dbname = $oldDbName;
            }

            $init = self::$sqlite->init($dbname, DOKU_PLUGIN . PluginUtility::PLUGIN_BASE_NAME . '/db/');
            if (!$init) {
                # TODO: Message 'SqliteUnableToInitialize'
                $message = "Unable to initialize Sqlite";
                LogUtility::msg($message, MSG_MANAGERS_ONLY);
            }
        }
        return self::$sqlite;

    }
}
