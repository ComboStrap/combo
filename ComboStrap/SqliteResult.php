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

    public function close()
    {
        $this->sqlitePlugin->res_close($this->res);
        $this->res = null;
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


}
