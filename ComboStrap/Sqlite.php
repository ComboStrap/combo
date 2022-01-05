<?php /** @noinspection SpellCheckingInspection */

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

    /** @var Sqlite[] $sqlite */
    private static $sqlites;

    /**
     * Principal database
     * (Backup)
     */
    private const  MAIN_DATABASE_NAME = "combo";
    /**
     * Backend Databse
     * (Log, Pub/Sub,...)
     */
    private const  SECONDARY_DB = "combo-secondary";

    private static $sqliteVersion;

    /**
     * @var helper_plugin_sqlite
     */
    private $sqlitePlugin;

    /**
     * Sqlite constructor.
     * @var helper_plugin_sqlite $sqlitePlugin
     */
    public function __construct(helper_plugin_sqlite $sqlitePlugin)
    {
        $this->sqlitePlugin = $sqlitePlugin;
    }


    /**
     *
     * @return Sqlite $sqlite
     */
    public static function createOrGetSqlite($databaseName = self::MAIN_DATABASE_NAME): ?Sqlite
    {

        $sqlite = self::$sqlites[$databaseName];
        if ($sqlite !== null) {
            $res = $sqlite->doWeNeedToCreateNewInstance();
            if ($res === false) {
                return $sqlite;
            }
        }


        /**
         * Init
         * @var helper_plugin_sqlite $sqlitePlugin
         */
        $sqlitePlugin = plugin_load('helper', 'sqlite');
        /**
         * Not enabled / loaded
         */
        if ($sqlitePlugin === null) {

            $sqliteMandatoryMessage = "The Sqlite Plugin is mandatory. Some functionalities of the ComboStrap Plugin may not work.";
            LogUtility::log2FrontEnd($sqliteMandatoryMessage, LogUtility::LVL_MSG_ERROR);
            return null;
        }

        $adapter = $sqlitePlugin->getAdapter();
        if ($adapter == null) {
            self::sendMessageAsNotAvailable();
            return null;
        }

        $adapter->setUseNativeAlter(true);

        global $conf;

        if ($databaseName === self::MAIN_DATABASE_NAME) {
            $oldDbName = '404manager';
            $oldDbFile = $conf['metadir'] . "/{$oldDbName}.sqlite";
            $oldDbFileSqlite3 = $conf['metadir'] . "/{$oldDbName}.sqlite3";
            if (file_exists($oldDbFile) || file_exists($oldDbFileSqlite3)) {
                $databaseName = $oldDbName;
            }
        }

        $updatedir = DOKU_PLUGIN . PluginUtility::PLUGIN_BASE_NAME . "/db/$databaseName";
        $init = $sqlitePlugin->init($databaseName, $updatedir);
        if (!$init) {
            # TODO: Message 'SqliteUnableToInitialize'
            $message = "Unable to initialize Sqlite";
            LogUtility::msg($message, MSG_MANAGERS_ONLY);
            return null;
        }
        // regexp implementation
        // https://stackoverflow.com/questions/5071601/how-do-i-use-regex-in-a-sqlite-query/18484596#18484596
        $adapter = $sqlitePlugin->getAdapter();
        $adapter->create_function('regexp',
            function ($pattern, $data, $delimiter = '~', $modifiers = 'isuS') {
                if (isset($pattern, $data) === true) {
                    return (preg_match(sprintf('%1$s%2$s%1$s%3$s', $delimiter, $pattern, $modifiers), $data) > 0);
                }
                return null;
            },
            4
        );

        $sqlite = new Sqlite($sqlitePlugin);
        self::$sqlites[$databaseName] = $sqlite;
        return $sqlite;

    }

    public static function createOrGetBackendSqlite(): ?Sqlite
    {
        return self::createOrGetSqlite(self::SECONDARY_DB);
    }

    public static function createSelectFromTableAndColumns(string $tableName, array $columns = null): string
    {
        if ($columns === null) {
            $columnStatement = "*";
        } else {
            $columnsStatement = [];
            foreach ($columns as $columnName) {
                $columnsStatement[] = "$columnName as \"$columnName\"";
            }
            $columnStatement = implode(", ", $columnsStatement);
        }
        return "select $columnStatement from $tableName";

    }

    /**
     * Print debug info to the console in order to resolve
     * RuntimeException: HY000 8 attempt to write a readonly database
     * https://phpunit.readthedocs.io/en/latest/writing-tests-for-phpunit.html#error-output
     */
    public function printDbInfoAtConsole()
    {
        $dbFile = $this->sqlitePlugin->getAdapter()->getDbFile();
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
    public function supportJson(): bool
    {


        $res = $this->sqlitePlugin->query("PRAGMA compile_options");
        $isJsonEnabled = false;
        foreach ($this->sqlitePlugin->res2arr($res) as $row) {
            if ($row["compile_option"] === "ENABLE_JSON1") {
                $isJsonEnabled = true;
                break;
            }
        };
        $this->sqlitePlugin->res_close($res);
        return $isJsonEnabled;
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
    private function doWeNeedToCreateNewInstance(): bool
    {

        global $conf;
        $metaDir = $conf['metadir'];


        /**
         * Adapter may be null
         * when the SQLite & PDO SQLite
         * are not installed
         * ie: SQLite & PDO SQLite support missing
         */
        $adapter = $this->sqlitePlugin->getAdapter();
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
            $this->close();
            return true;
        }
        // the file is in the meta directory
        if (strpos($dbFile, $metaDir) === 0) {
            // we are still in a class run
            return false;
        }
        $this->close();
        return true;
    }

    public
    function close()
    {

        $adapter = $this->sqlitePlugin->getAdapter();
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
            if (file_exists($sqliteFile)) {
                $result = unlink($sqliteFile);
                if ($result === false) {
                    throw new RuntimeException("Unable to delete the file ($sqliteFile). Did you close all resources ?");
                }
            }

        }
        /**
         * Forwhatever reason, closing in php
         * is putting the variable to null
         * We do it also in the static variable to be sure
         */
        self::$sqlites[$this->getDbName()] == null;


    }

    public function getDbName(): string
    {
        return $this->sqlitePlugin->getAdapter()->getName();
    }

    public static function closeAll()
    {

        $sqlites = self::$sqlites;
        if ($sqlites !== null) {
            foreach ($sqlites as $sqlite) {
                $sqlite->close();
            }
            /**
             * Set it to null
             */
            Sqlite::$sqlites = null;
        }
    }


    public function getSqlitePlugin(): helper_plugin_sqlite
    {
        return $this->sqlitePlugin;
    }

    public function createRequest(): SqliteRequest
    {
        return new SqliteRequest($this);
    }

    public function getVersion()
    {
        if (self::$sqliteVersion === null) {
            $request = $this->createRequest()
                ->setQuery("select sqlite_version()");
            try {
                self::$sqliteVersion = $request
                    ->execute()
                    ->getFirstCellValue();
            } catch (ExceptionCombo $e) {
                self::$sqliteVersion = "unknown";
            } finally {
                $request->close();
            }
        }
        return self::$sqliteVersion;
    }

    /**
     * @param string $option
     * @return bool - true if the option is available
     */
    public function hasOption(string $option): bool
    {
        try {
            $present = $this->createRequest()
                ->setQueryParametrized("select count(1) from pragma_compile_options() where compile_options = ?", $option)
                ->execute()
                ->getFirstCellValueAsInt();
            return $present === 1;
        } catch (ExceptionCombo $e) {
            LogUtility::msg("Error while trying to see if the sqlite option is available");
            return false;
        }

    }
}
