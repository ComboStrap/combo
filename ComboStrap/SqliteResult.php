<?php


namespace ComboStrap;


class SqliteResult
{
    private $res;
    /**
     * @var Sqlite
     */
    private $sqlite;

    /**
     * SqliteResult constructor.
     */
    public function __construct(Sqlite $sqlite, $res)
    {
        $this->sqlite = $sqlite;
        $this->res = $res;
    }

    public function getRows(): array
    {
        return $this->sqlite->getSqlitePlugin()->res2arr($this->res);
    }

    public function close(){
        $this->sqlite->getSqlitePlugin()->res_close($this->res);
    }


}
