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

require_once(__DIR__ . '/PluginUtility.php');

use helper_plugin_sqlite;
use RuntimeException;

class Sqlite
{

    /** @var helper_plugin_sqlite $sqlite */
    private static $sqlite;

    /**
     *
     * @return helper_plugin_sqlite $sqlite
     */
    public static function getSqlite()
    {
        $init = self::createNewInstance();
        if (!$init) {
            return Sqlite::$sqlite;
        }

        /**
         * Init
         */
        Sqlite::$sqlite = plugin_load('helper', 'sqlite');
        /**
         * Not enabled / loaded
         */
        if (Sqlite::$sqlite === null) {

            $sqliteMandatoryMessage = "The Sqlite Plugin is mandatory. Some functionalities of the ComboStrap Plugin may not work.";
            LogUtility::log2FrontEnd($sqliteMandatoryMessage, LogUtility::LVL_MSG_ERROR);
            return null;
        }

        $adapter = Sqlite::$sqlite->getAdapter();
        if ($adapter == null) {
            self::sendMessageAsNotAvailable();
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

        $init = Sqlite::$sqlite->init($dbname, DOKU_PLUGIN . PluginUtility::PLUGIN_BASE_NAME . '/db/');
        if (!$init) {
            # TODO: Message 'SqliteUnableToInitialize'
            $message = "Unable to initialize Sqlite";
            LogUtility::msg($message, MSG_MANAGERS_ONLY);
            return null;
        }
        // regexp implementation
        // https://stackoverflow.com/questions/5071601/how-do-i-use-regex-in-a-sqlite-query/18484596#18484596
        $adapter = Sqlite::$sqlite->getAdapter();
        $adapter->create_function('regexp',
            function ($pattern, $data, $delimiter = '~', $modifiers = 'isuS') {
                if (isset($pattern, $data) === true) {
                    return (preg_match(sprintf('%1$s%2$s%1$s%3$s', $delimiter, $pattern, $modifiers), $data) > 0);
                }
                return null;
            },
            4
        );

        return Sqlite::$sqlite;

    }

    /**
     * Print debug info to the console in order to resolve
     * RuntimeException: HY000 8 attempt to write a readonly database
     * https://phpunit.readthedocs.io/en/latest/writing-tests-for-phpunit.html#error-output
     * @param helper_plugin_sqlite $sqlite
     */
    public
    static function printDbInfoAtConsole(helper_plugin_sqlite $sqlite)
    {
        $dbFile = $sqlite->getAdapter()->getDbFile();
        fwrite(STDERR, "Stderr DbFile: " . $dbFile . "\n");
        if (file_exists($dbFile)) {
            fwrite(STDERR, "File does exists\n");
            fwrite(STDERR, "Permission " . substr(sprintf('%o', fileperms($dbFile)), -4) . "\n");
        } else {
            fwrite(STDERR, "File does not exist\n");
        }

        global $conf;
        $metadir = $conf['metadir'];
        fwrite(STDERR, "MetaDir: " . $metadir . "\n");
        $subdir = strpos($dbFile, $metadir) === 0;
        if ($subdir) {
            fwrite(STDERR, "Meta is a subdirectory of the db \n");
        } else {
            fwrite(STDERR, "Meta is a not subdirectory of the db \n");
        }

    }

    /**
     * Json support
     */
    public
    static function supportJson(): bool
    {

        $sqlite = self::getSqlite();
        if ($sqlite === null) {
            self::sendMessageAsNotAvailable();
            return false;
        }

        $res = $sqlite->query("PRAGMA compile_options");
        $isJsonEnabled = false;
        foreach ($sqlite->res2arr($res) as $row) {
            if ($row["compile_option"] === "ENABLE_JSON1") {
                $isJsonEnabled = true;
                break;
            }
        };
        $sqlite->res_close($res);
        return $isJsonEnabled;
    }

    /**
     * @param helper_plugin_sqlite $sqlite
     * @param string $executableSql
     * @param array $parameters
     * @return bool|\PDOStatement|\SQLiteResult
     */
    public
    static function queryWithParameters(helper_plugin_sqlite $sqlite, string $executableSql, array $parameters)
    {
        $args = [$executableSql];
        $args = array_merge($args, $parameters);
        return $sqlite->getAdapter()->query($args);
    }

    public
    static function sendMessageAsNotAvailable(): void
    {
        $sqliteMandatoryMessage = "The Sqlite Php Extension is mandatory. It seems that it's not available on this installation.";
        LogUtility::log2FrontEnd($sqliteMandatoryMessage, LogUtility::LVL_MSG_ERROR);
    }

    /**
     * sqlite is stored in a static variable
     * because when we run the {@link cli_plugin_combo},
     * we will run in the error:
     * ``
     * failed to open stream: Too many open files
     * ``
     * There is by default a limit of 1024 open files
     * which means that if there is more than 1024 pages, you fail.
     *
     *
     */
    private
    static function createNewInstance(): bool
    {

        global $conf;
        $metaDir = $conf['metadir'];

        if (self::$sqlite === null) {
            return true;
        }

        /**
         * Adapter may be null
         * when the SQLite & PDO SQLite
         * are not installed
         * ie: SQLite & PDO SQLite support missing
         */
        $adapter = self::$sqlite->getAdapter();
        if ($adapter === null) {
            return true;
        }

        /**
         * When the database is {@link \helper_plugin_sqlite_adapter::closedb()}
         */
        if ($adapter->getDb() === null) {
            /**
             * We may also open it again
             * {@link \helper_plugin_sqlite_adapter::opendb()}
             * for now, reinit
             */
            return true;
        }
        /**
         * In test, we are running in different context (ie different root
         * directory for DokuWiki and therefore different $conf
         * and therefore different metadir where sqlite is stored)
         * Because a sql file may be deleted, we may get:
         * ```
         * RuntimeException: HY000 8 attempt to write a readonly database:
         * ```
         * To avoid this error, we check that we are still in the same metadir
         * where the sqlite database is stored. If not, we create a new instance
         */
        $dbFile = $adapter->getDbFile();
        if (!file_exists($dbFile)) {
            self::close();
            return true;
        }
        // the file is in the meta directory
        if (strpos($dbFile, $metaDir) === 0) {
            // we are still in a class run
            return false;
        }
        self::close();
        return true;
    }

    public
    static function close()
    {
        if (Sqlite::$sqlite !== null) {
            $adapter = Sqlite::$sqlite->getAdapter();
            if ($adapter !== null) {
                /**
                 * https://www.php.net/manual/en/pdo.connections.php#114822
                 * You put the connection on null
                 * CloseDb do that
                 */
                $adapter->closedb();

                /**
                 * Delete the file If we can't delete the file
                 * there is a resource still open
                 */
                $sqliteFile = $adapter->getDbFile();
                $result = unlink($sqliteFile);
                if ($result === false){
                    throw new RuntimeException("Unable to delete the file ($sqliteFile). Did you close all resources ?");
                }

                /**
                 * Set it to null
                 */
                Sqlite::$sqlite = null;
            }
        }
    }

    public static function getErrorMessage(): string
    {
        $adapter = Sqlite::getSqlite()->getAdapter();
        if ($adapter === null) {
            LogUtility::msg("The database adapter is null, no error info can be retrieved");
            return "";
        }
        $do = $adapter->getDb();
        if ($do === null) {
            LogUtility::msg("The database object is null, it seems that the database connection has been closed");
            return "";
        }
        $errorInfo = $do->errorInfo();
        $message = "";
        $errorCode = $errorInfo[0];
        if ($errorCode === '0000') {
            $message = ("No rows were deleted");
        }
        $errorInfoAsString = var_export($errorInfo, true);
        return "$message. : {$errorInfoAsString}";
    }
}
