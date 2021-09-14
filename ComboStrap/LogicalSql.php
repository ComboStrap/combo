<?php


namespace ComboStrap;


use ComboStrap\LogicalSqlAntlr\LogicalSqlAntlr;

class LogicalSql
{
    /**
     * @var LogicalSqlAntlr
     */
    private $logicalSql;


    /**
     * LogicalSql constructor.
     */
    public function __construct($logicalSql)
    {
        $this->logicalSql = LogicalSqlAntlr::create($logicalSql);
    }

    public function toPhysicalSqlWithParameters(): string
    {
        return $this->logicalSql->getPhysicalSql();
    }

    public function getParameters(): array
    {
        return $this->logicalSql->getParameters();
    }
}
