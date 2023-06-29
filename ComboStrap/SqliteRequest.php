<?php


namespace ComboStrap;


class SqliteRequest
{
    const UPSERT_REQUEST = "Upsert";
    const SELECT_REQUEST = "Select";

    /**
     * delete, insert, update, query with parameters
     */
    const PARAMETRIZED_REQUEST = "Parametrized";
    const STATEMENT_REQUEST = "Statement";
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

    private \helper_plugin_sqlite $sqlitePlugin;

    /**
     * A statement that is not a query
     * @var ?string
     */
    private ?string $statement = null;
    /**
     * The SQL parameters
     * @var array |null
     */
    private ?array $parameters = null;

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
        $sqLiteAdapater = $this->sqlitePlugin->getAdapter();
        $requestType = $this->getRequestType();
        switch ($requestType) {
            case self::UPSERT_REQUEST:
                $statement = $this->getParametrizeReplaceQuery($this->tableName, $this->data);
                $values = array_values($this->data);
                $res = $this->executeParametrizedStatement($statement, $values);
                $queryExecuted = "Upsert of table $this->tableName";
                break;
            case self::SELECT_REQUEST:
                $res = $this->sqlitePlugin->query($this->query);
                $queryExecuted = $this->query;
                break;
            case self::PARAMETRIZED_REQUEST:
                $res = $this->executeParametrizedStatement($this->statement, $this->parameters);
                $queryExecuted = array_merge([$this->statement], $this->parameters);
                break;
            case self::STATEMENT_REQUEST:
                if (!Sqlite::isJuneVersion($sqLiteAdapater)) {
                    /** @noinspection PhpUndefinedMethodInspection */
                    $res = $sqLiteAdapater->executeQuery($this->statement);
                } else {
                    $res = $sqLiteAdapater->query($this->statement);
                }
                $queryExecuted = $this->statement;
                break;
            default:
                throw new ExceptionCompile("The request type ($requestType) was not processed");
        }


        if ($res === null) {
            throw new ExceptionCompile("No Sql request was found to be executed");
        }

        if ($res === false) {
            $message = $this->getErrorMessage();
            throw new ExceptionCompile("Error in the $requestType. Message: {$message}");
        }

        if (!$res instanceof \PDOStatement) {
            $message = $this->getErrorMessage();
            throw new ExceptionCompile("Error in the request type `$requestType`. res is not a PDOStatement but as the value ($res). Message: {$message}, Query: {$queryExecuted}");
        }

        $this->result = new SqliteResult($this, $res);
        return $this->result;
    }

    public
    function getErrorMessage(): string
    {
        $adapter = $this->sqlitePlugin->getAdapter();
        if ($adapter === null) {
            throw new ExceptionRuntimeInternal("The database adapter is null, no error info can be retrieved");
        }
        if (!Sqlite::isJuneVersion($adapter)) {
            /** @noinspection PhpUndefinedMethodInspection */
            $do = $adapter->getDb();
        } else {
            $do = $adapter->getPdo();
        }
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

    public
    function getSqliteConnection(): Sqlite
    {
        return $this->sqlite;
    }

    public
    function close()
    {

        if ($this->result !== null) {
            $this->result->close();
            $this->result = null;
        }

    }

    public
    function setQuery(string $string): SqliteRequest
    {
        $this->query = $string;
        return $this;
    }

    /**
     * @param string $executableSql
     * @param array $parameters
     * @return SqliteResult
     */
    public
    function setQueryParametrized(string $executableSql, array $parameters): SqliteRequest
    {
        $this->statement = $executableSql;
        $this->parameters = $parameters;
        return $this;

    }

    /**
     * @param string $statement
     * @return $this - a statement that will execute
     */
    public
    function setStatement(string $statement): SqliteRequest
    {
        $this->statement = $statement;
        return $this;
    }

    private
    function getRequestType(): string
    {
        if ($this->data !== null && $this->tableName !== null) {

            return self::UPSERT_REQUEST;

        }

        if ($this->query !== null) {

            return self::SELECT_REQUEST;

        }

        if ($this->parameters !== null) {

            return self::PARAMETRIZED_REQUEST;

        }

        return self::STATEMENT_REQUEST;

    }

    /**
     * @param string $table
     * @param array $data
     * @return string - a replace parametrized query
     */
    private function getParametrizeReplaceQuery(string $table, array $data): string
    {
        $columns = array_map(function ($column) {
            return '"' . $column . '"';
        }, array_keys($data));
        $placeholders = array_pad([], count($columns), '?');
        /** @noinspection SqlResolve */
        return 'REPLACE INTO "' . $table . '" (' . join(',', $columns) . ') VALUES (' . join(',',
                $placeholders) . ')';
    }

    private function executeParametrizedStatement(string $statement, array $values): \PDOStatement
    {
        $sqLiteAdapater = $this->sqlitePlugin->getAdapter();
        $queryParametrized = array_merge([$statement], $values);
        if (!Sqlite::isJuneVersion($sqLiteAdapater)) {
            /** @noinspection PhpParamsInspection */
            return $sqLiteAdapater->query($queryParametrized);
        }
        $values = array_values($values);
        return $sqLiteAdapater->query($statement, $values);
    }

}
