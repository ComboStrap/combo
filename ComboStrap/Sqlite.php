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


use dokuwiki\plugin\sqlite\SQLiteDB;
use helper_plugin_sqlite;

class Sqlite
{


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


    private helper_plugin_sqlite $sqlitePlugin;

    /**
     * @var SqliteRequest the actual request. If not closed, it will be close.
     * Otherwise, it's not possible to delete the database file. See {@link self::deleteDatabasesFile()}
     */
    private SqliteRequest $actualRequest;


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
     * @throws ExceptionSqliteNotAvailable
     */
    public static function createOrGetSqlite($databaseName = self::MAIN_DATABASE_NAME): Sqlite
    {

        $sqliteExecutionObjectIdentifier = Sqlite::class . "-$databaseName";
        $executionContext = ExecutionContext::getActualOrCreateFromEnv();

        try {
            /**
             * @var Sqlite $sqlite
             *
             *
             * sqlite is stored globally
             * because when we create a new instance, it will open the
             * sqlite file.
             *
             * In a {@link cli_plugin_combo} run, you will run in the error:
             * ``
             * failed to open stream: Too many open files
             * ``
             * As there is by default a limit of 1024 open files
             * which means that if there is more than 1024 pages
             * that you replicate using a new sqlite instance each time,
             * you fail.
             *
             */
            $sqlite = $executionContext->getRuntimeObject($sqliteExecutionObjectIdentifier);
        } catch (ExceptionNotFound $e) {
            $sqlite = null;
        }

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
            throw new ExceptionSqliteNotAvailable($sqliteMandatoryMessage);
        }

        $adapter = $sqlitePlugin->getAdapter();
        if ($adapter == null) {
            self::sendMessageAsNotAvailable();
        }

        $adapter->setUseNativeAlter(true);

        list($databaseName, $databaseDefinitionDir) = self::getDatabaseNameAndDefinitionDirectory($databaseName);
        $init = $sqlitePlugin->init($databaseName, $databaseDefinitionDir);
        if (!$init) {
            $message = "Unable to initialize Sqlite";
            throw new ExceptionSqliteNotAvailable($message);
        }
        // regexp implementation
        // https://stackoverflow.com/questions/5071601/how-do-i-use-regex-in-a-sqlite-query/18484596#18484596
        $adapter = $sqlitePlugin->getAdapter();
        $regexFunctioName = 'regexp';
        $regexpClosure = function ($pattern, $data, $delimiter = '~', $modifiers = 'isuS') {
            if (isset($pattern, $data) === true) {
                return (preg_match(sprintf('%1$s%2$s%1$s%3$s', $delimiter, $pattern, $modifiers), $data) > 0);
            }
            return null;
        };
        $regexArgCount = 4;
        if (!self::isJuneVersion($adapter)) {
            /** @noinspection PhpUndefinedMethodInspection */
            $adapter->create_function($regexFunctioName, $regexpClosure, $regexArgCount);
        } else {
            $adapter->getPdo()->sqliteCreateFunction($regexFunctioName, $regexpClosure, $regexArgCount);
        }

        $sqlite = new Sqlite($sqlitePlugin);
        $executionContext->setRuntimeObject($sqliteExecutionObjectIdentifier, $sqlite);
        return $sqlite;

    }

    /**
     * @throws ExceptionSqliteNotAvailable
     */
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
        /**
         * TODO: We had added the `rowid` on all query
         *  but the underlining code was not supporting it,
         *  adding it in the next release to be able to locate the row
         */
        return "select $columnStatement from $tableName";

    }

    /**
     * Used in test to delete the database file
     * @return void
     * @throws ExceptionFileSystem - if we can delete the databases
     */
    public static function deleteDatabasesFile()
    {
        /**
         * The plugin does not give you the option to
         * where to create the database file
         * See {@link \helper_plugin_sqlite_adapter::initdb()}
         * $this->dbfile = $conf['metadir'].'/'.$dbname.$this->fileextension;
         *
         * If error on delete, see {@link self::close()}
         */
        $metadatDirectory = ExecutionContext::getActualOrCreateFromEnv()
            ->getConfig()
            ->getMetaDataDirectory();
        $fileChildren = FileSystems::getChildrenLeaf($metadatDirectory);
        foreach ($fileChildren as $child) {
            try {
                $extension = $child->getExtension();
            } catch (ExceptionNotFound $e) {
                // ok no extension
                continue;
            }
            if (in_array($extension, ["sqlite", "sqlite3"])) {
                FileSystems::delete($child);
            }

        }
    }

    private static function getDatabaseNameAndDefinitionDirectory($databaseName): array
    {
        global $conf;

        if ($databaseName === self::MAIN_DATABASE_NAME) {
            $oldDbName = '404manager';
            $oldDbFile = $conf['metadir'] . "/{$oldDbName}.sqlite";
            $oldDbFileSqlite3 = $conf['metadir'] . "/{$oldDbName}.sqlite3";
            if (file_exists($oldDbFile) || file_exists($oldDbFileSqlite3)) {
                $databaseName = $oldDbName;
            }
        }

        $databaseDir = DOKU_PLUGIN . PluginUtility::PLUGIN_BASE_NAME . "/db/$databaseName";
        return [$databaseName, $databaseDir];

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


    /**
     * @throws ExceptionSqliteNotAvailable
     */
    public
    static function sendMessageAsNotAvailable(): void
    {
        $sqliteMandatoryMessage = "The Sqlite Php Extension is mandatory. It seems that it's not available on this installation.";
        throw new ExceptionSqliteNotAvailable($sqliteMandatoryMessage);
    }

    /**
     *
     * Old check when there was no {@link ExecutionContext}
     * to reset the Sqlite variable
     * TODO: delete ?
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
        if (!self::isJuneVersion($adapter)) {
            /** @noinspection PhpUndefinedMethodInspection */
            $db = $adapter->getDb();
        } else {
            $db = $adapter->getPdo();
        }
        if ($db === null) {
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

    public function close()
    {

        /**
         * https://www.php.net/manual/en/pdo.connections.php#114822
         * You put the variable connection on null
         * the {@link \helper_plugin_sqlite_adapter::closedb() function} do that
         *
         * If we don't do that, the file is still locked
         * by the sqlite process and the clean up process
         * of dokuwiki test cannot delete it
         *
         * ie to avoid
         * RuntimeException: Unable to delete the file
         * (C:/Users/GERARD~1/AppData/Local/Temp/dwtests-1676813655.6773/data/meta/combo-secondary.sqlite3) in D:\dokuwiki\_test\core\TestUtils.php on line 58
         * {@link TestUtils::rdelete}
         *
         * Windows sort of handling/ bug explained here
         * https://bugs.php.net/bug.php?id=78930&edit=3
         *
         * Null to close the db explanation and bug
         * https://bugs.php.net/bug.php?id=62065
         *
         */

        $this->closeActualRequestIfNotClosed();

        $adapter = $this->sqlitePlugin->getAdapter();
        if ($adapter !== null) {

            if (!$this->isJuneVersion($adapter)) {
                /** @noinspection PhpUndefinedMethodInspection */
                $adapter->closedb();
            } else {
                $adapter->__sleep();
            }

            unset($adapter);

            gc_collect_cycles();

        }

    }

    public function getDbName(): string
    {
        return $this->sqlitePlugin->getAdapter()->getName();
    }


    public function getSqlitePlugin(): helper_plugin_sqlite
    {
        return $this->sqlitePlugin;
    }

    public function createRequest(): SqliteRequest
    {
        $this->closeActualRequestIfNotClosed();
        $this->actualRequest = new SqliteRequest($this);
        return $this->actualRequest;
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
            } catch (ExceptionCompile $e) {
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
                ->setQueryParametrized("select count(1) from pragma_compile_options() where compile_options = ?", [$option])
                ->execute()
                ->getFirstCellValueAsInt();
            return $present === 1;
        } catch (ExceptionCompile $e) {
            LogUtility::msg("Error while trying to see if the sqlite option is available");
            return false;
        }

    }

    /**
     * Internal function that closes the actual request
     * This is to be able to close all resources even if the developer
     * forget.
     *
     * This is needed to be able to delete the database file.
     * See {@link self::close()} for more information
     *
     * @return void
     */
    private function closeActualRequestIfNotClosed()
    {
        if (isset($this->actualRequest)) {
            $this->actualRequest->close();
            unset($this->actualRequest);
        }
    }

    /**
     * Version 2023-06-21 brings error
     * https://github.com/cosmocode/sqlite/releases/tag/2023-06-21
     * https://www.dokuwiki.org/plugin:sqlite#changes_from_earlier_releases
     * @param $adapter
     * @return bool
     */
    public static function isJuneVersion($adapter): bool
    {
        return get_class($adapter) === SQLiteDB::class;
    }

}
