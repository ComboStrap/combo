<?php


namespace ComboStrap;


use PDO;

class SqliteResult
{
    private $res;
    /**
     * @var SqliteRequest
     */
    private $sqlite;
    /**
     * @var \helper_plugin_sqlite
     */
    private $sqlitePlugin;

    /**
     * SqliteResult constructor.
     */
    public function __construct(SqliteRequest $sqlite, $res)
    {
        $this->sqlite = $sqlite;
        $this->res = $res;
        $this->sqlitePlugin = $this->sqlite->getSqliteConnection()->getSqlitePlugin();

    }

    public function getRows(): array
    {

        $adapter = $this->sqlitePlugin->getAdapter();
        if (!Sqlite::isJuneVersion($adapter)) {
            return $this->sqlitePlugin->res2arr($this->res);
        }

        /**
         * note:
         * * fetch mode may be also {@link PDO::FETCH_NUM}
         * * {@link helper_plugin_sqlite::res2arr()} but without the fucking cache !
         */
        return $this->res->fetchAll(PDO::FETCH_ASSOC);


    }

    public function close(): SqliteResult
    {
        /**
         * $this->res may be a boolean {@link }
         * is a number in CI
         *
         * We get:
         * Error: Call to a member function closeCursor() on int
         * /home/runner/work/combo/combo/lib/plugins/sqlite/classes/adapter_pdosqlite.php:125
         */
        if ($this->res instanceof \PDOStatement) {
            $this->sqlitePlugin->res_close($this->res);
        }
        $this->res = null;
        return $this;
    }

    public function getInsertId(): string
    {
        $adapter = $this->sqlitePlugin->getAdapter();
        if (!Sqlite::isJuneVersion($adapter)) {
            /** @noinspection PhpUndefinedMethodInspection */
            return $adapter->getDb()->lastInsertId();
        } else {
            return $adapter->getPdo()->lastInsertId();
        }

    }

    public function getChangeCount()
    {
        return $this->sqlitePlugin->countChanges($this->res);
    }

    public function getFirstCellValue()
    {
        return $this->sqlitePlugin->res2single($this->res);
    }

    public function getFirstCellValueAsInt(): int
    {
        return intval($this->getFirstCellValue());
    }

    public function getFirstRow()
    {
        $rows = $this->getRows();
        if (sizeof($rows) >= 1) {
            return $rows[0];
        }
        return [];
    }


}
