<?php


namespace ComboStrap;


class SqliteRequest
{
    /**
     * @var Sqlite
     */
    private $sqlite;
    /**
     * @var string
     */
    private $tableName;
    /**
     * @var array
     */
    private $data;
    /**
     * @var SqliteResult
     */
    private $result;
    /**
     * @var string
     */
    private $query;
    private $sqlitePlugin;

    /**
     * SqliteRequest constructor.
     * @param Sqlite $sqlite
     */
    public function __construct(Sqlite $sqlite)
    {
        $this->sqlite = $sqlite;
        $this->sqlitePlugin = $sqlite->getSqlitePlugin();
    }

    public function storeIntoTable(string $tableName, array $data): SqliteRequest
    {
        $this->tableName = $tableName;
        $this->data = $data;
        return $this;
    }

    /**
     * @throws ExceptionCombo
     */
    public function execute(): SqliteResult
    {
        $res = null;
        $requestType = "";
        if($this->data!==null && $this->tableName!==null) {
            $res = $this->sqlitePlugin->storeEntry($this->tableName, $this->data);
            $requestType = "Upsert";
        }

        if($this->query!==null){
            $res = $this->sqlitePlugin->query($this->query);
            $requestType = "Query";
        }

        if($res===null) {
            throw new ExceptionCombo("The request is not known");
        }

        if($res===false){
            throw new ExceptionCombo("Error in the $requestType: {$this->sqlitePlugin->getAdapter()->getDb()->errorInfo()}");
        }

        $this->result = new SqliteResult($this, $res);
        return $this->result;
    }

    public function getSqliteConnection(): Sqlite
    {
        return $this->sqlite;
    }

    public function close()
    {

        if($this->result!==null){
            $this->result->close();
        }

    }

    public function setQuery(string $string)
    {
        $this->query = $string;
        return $this;
    }

}
