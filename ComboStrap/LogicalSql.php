<?php


namespace ComboStrap;


use ComboStrap\LogicalSqlAntlr\LogicalSqlAntlr;

/**
 * Class LogicalSql
 * @package ComboStrap
 *
 * Note that the regexp function is implemented in the {@link Sqlite::getSqlite() Sqlite initialization}
 */
class LogicalSql
{
    /**
     * @var LogicalSqlAntlr
     */
    private $pageSql;


    /**
     * LogicalSql constructor.
     */
    public function __construct($pageSql)
    {
        $this->pageSqlString = $pageSql;
        $this->pageSql = LogicalSqlAntlr::create($pageSql);
    }

    public static function create($logicalSql): LogicalSql
    {
        return new LogicalSql($logicalSql);
    }

    public function toPhysicalSqlWithParameters(): string
    {
        return $this->pageSql->getPhysicalSql();
    }

    public function getParameters(): array
    {
        return $this->pageSql->getParameters();
    }

    public function getColumns()
    {
        return $this->pageSql->getColumns();
    }

    public function __toString()
    {
        return $this->pageSqlString;
    }


}
