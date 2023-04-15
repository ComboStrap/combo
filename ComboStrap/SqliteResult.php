<?php


namespace ComboStrap;


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
        return $this->sqlitePlugin->res2arr($this->res);
    }

    public function close(): SqliteResult
    {
        /**
         * $this->res is a number in CI
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
        return $this->sqlitePlugin->getAdapter()->getDb()->lastInsertId();
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
