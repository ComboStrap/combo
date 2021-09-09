<?php


namespace ComboStrap;


/**
 *
 * @package ComboStrap
 */
class SqlLogical
{
    const SQLITE_JSON = "sqliteWithJsonSupport";
    const SQLITE_NO_JSON = "sqliteWithoutJsonSupport";
    private $logicalSql;
    /**
     * @var SqlParser
     */
    private $sqlParser;


    /**
     * SqlLogical constructor.
     */
    public function __construct($logicalSql)
    {
        $this->logicalSql = $logicalSql;
        $this->sqlParser = SqlParser::create($this->logicalSql)
            ->parse();
    }

    public static function create($logicalSql)
    {
        return new SqlLogical($logicalSql);
    }

    public function toPhysical($databaseTarget = self::SQLITE_JSON)
    {


        $physicalSql = "select\n\t";

        if ($databaseTarget === self::SQLITE_JSON) {
            $columnsIdentifier = [];
            foreach ($this->sqlParser->getColumnIdentifiers() as $columnIdentifier) {

                $columnsIdentifier[] = "json_extract(analytics, '$.metadata.$columnIdentifier') as $columnIdentifier";

            }
            $physicalSql .= implode(",\n\t", $columnsIdentifier);
        } else {
            $physicalSql .= "*";
        }

        $physicalSql .= "\nfrom\n\tpages\nwhere\n\tanalytics is not null";

        /**
         * Predicates
         */
        $predicates = $this->sqlParser->getPredicates();
        if (sizeof($predicates) > 0) {
            $physicalSql .= " and";
            foreach ($predicates as $nextLogicalOperator => $predicate) {

                $physicalSql .= "\n\t$physicalSql";
                if (!empty($nextLogicalOperator)) {
                    $physicalSql .= " $nextLogicalOperator";
                }

            }
        }

        /**
         * Order by
         */
        $orderBys = $this->sqlParser->getOrderBys();
        if (sizeof($orderBys) > 0) {
            $physicalSql .= "\norder by\n\t" . implode(",\n\t", $orderBys);
        }

        /**
         * Limit
         */
        $limit = $this->sqlParser->getLimit();
        if (!empty($limit)) {
            $physicalSql .= "\nlimit $limit";
        }
        return $physicalSql;

    }

    public function getColumns()
    {
        return $this->sqlParser->getColumnIdentifiers();
    }
}
