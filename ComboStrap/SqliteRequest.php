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
     * @var array|string[]
     */
    private $queryParametrized;
    /**
     * A statement that is not a query
     * @var string
     */
    private $statement;

    /**
     * SqliteRequest constructor.
     * @param Sqlite $sqlite
     */
    public function __construct(Sqlite $sqlite)
    {
        $this->sqlite = $sqlite;
        $this->sqlitePlugin = $sqlite->getSqlitePlugin();
    }

    public function setTableRow(string $tableName, array $data): SqliteRequest
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
        if ($this->data !== null && $this->tableName !== null) {
            $res = $this->sqlitePlugin->storeEntry($this->tableName, $this->data);
            $requestType = "Upsert";
        }

        if ($this->query !== null) {
            $res = $this->sqlitePlugin->query($this->query);
            $requestType = "Query Simple";
        }

        if ($this->queryParametrized !== null) {
            $res = $this->sqlitePlugin->getAdapter()->query($this->queryParametrized);
            $requestType = "Query Parametrized"; // delete, insert, update, query
        }

        if($this->statement!==null){
            $res = $this->sqlitePlugin->getAdapter()->getDb()->exec($this->statement);
            $requestType = "statement";
        }

        if ($res === null) {
            throw new ExceptionCombo("No Sql request was found to be executed");
        }

        if ($res === false) {
            $message = $this->getErrorMessage();
            throw new ExceptionCombo("Error in the $requestType. Message: {$message}");
        }

        $this->result = new SqliteResult($this, $res);
        return $this->result;
    }

    public function getErrorMessage(): string
    {
        $adapter = $this->sqlitePlugin->getAdapter();
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
            $message = ("No rows were deleted or updated");
        }
        $errorInfoAsString = implode(", ",$errorInfo);
        return "$message. : {$errorInfoAsString}";
    }

    public function getSqliteConnection(): Sqlite
    {
        return $this->sqlite;
    }

    public function close()
    {

        if ($this->result !== null) {
            $this->result->close();
            $this->result = null;
        }

    }

    public function setQuery(string $string): SqliteRequest
    {
        $this->query = $string;
        return $this;
    }

    /**
     * @param string $executableSql
     * @param array $parameters
     * @return SqliteResult
     */
    public function setQueryParametrized(string $executableSql, array $parameters): SqliteRequest
    {

        $args = [$executableSql];
        $this->queryParametrized = array_merge($args, $parameters);
        return $this;

    }

    /**
     * @param string $statement
     * @return $this - a statement that will execute
     */
    public function setStatement(string $statement): SqliteRequest
    {
        $this->statement = $statement;
        return $this;
    }

}
