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
     * @throws ExceptionCompile
     */
    public function execute(): SqliteResult
    {
        $res = null;
        $requestType = "";
        $queryExecuted="";
        if ($this->data !== null && $this->tableName !== null) {
            $res = $this->sqlitePlugin->storeEntry($this->tableName, $this->data);
            $requestType = "Upsert";
            $queryExecuted = "upsert of table $this->tableName";
        }

        if ($this->query !== null) {
            $res = $this->sqlitePlugin->query($this->query);
            $requestType = "Query Simple";
            $queryExecuted = $this->query;
        }

        if ($this->queryParametrized !== null) {
            $res = $this->sqlitePlugin->getAdapter()->query($this->queryParametrized);
            $requestType = "Query Parametrized"; // delete, insert, update, query
            $queryExecuted = $this->queryParametrized;
        }

        if ($this->statement !== null) {
            $res = $this->sqlitePlugin->getAdapter()->executeQuery($this->statement);
            $requestType = "statement";
            $queryExecuted = $this->statement;
        }

        if ($res === null) {
            throw new ExceptionCompile("No Sql request was found to be executed");
        }

        if ($res === false) {
            $message = $this->getErrorMessage();
            throw new ExceptionCompile("Error in the $requestType. Message: {$message}");
        }

        if((!$res instanceof \PDOStatement)){
            $message = $this->getErrorMessage();
            throw new ExceptionCompile("Error in the request type `$requestType`. res is not a PDOStatement but as the value ($res). Message: {$message}, Query: {$queryExecuted}");
        }

        $this->result = new SqliteResult($this, $res);
        return $this->result;
    }

    public function getErrorMessage(): string
    {
        $adapter = $this->sqlitePlugin->getAdapter();
        if ($adapter === null) {
            throw new ExceptionRuntimeInternal("The database adapter is null, no error info can be retrieved");
        }
        $do = $adapter->getDb();
        if ($do === null) {
            throw new ExceptionRuntimeInternal("The database object is null, it seems that the database connection has been closed. Are you in two differents execution context ?");
        }
        $errorInfo = $do->errorInfo();
        $message = "";
        $errorCode = $errorInfo[0];
        if ($errorCode === '0000') {
            $message = ("No rows were deleted or updated");
        }
        $errorInfoAsString = implode(", ", $errorInfo);
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
