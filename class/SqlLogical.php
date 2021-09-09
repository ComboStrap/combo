<?php


namespace ComboStrap;


/**
 *
 * @package ComboStrap
 */
class SqlLogical
{
    const SQLITE_JSON = "sqliteWithJsonSupport";
    private $logicalSql;


    /**
     * SqlLogical constructor.
     */
    public function __construct($logicalSql)
    {
        $this->logicalSql = $logicalSql;
    }

    public static function create($logicalSql)
    {
        return new SqlLogical($logicalSql);
    }

    public function toPhysical($databaseTarget = self::SQLITE_JSON)
    {


        $sql = SqlParser::create($this->logicalSql)
            ->parse();

        $physicalSql = "select\n\t";
        $columnsIdentifier = [];
        foreach ($sql->getColumnIdentifiers() as $columnIdentifier) {

            $columnsIdentifier[] = "json_extract(analytics, '$.metadata.$columnIdentifier') as $columnIdentifier";

        }
        $physicalSql .= implode(",\n\t", $columnsIdentifier);
        $physicalSql .= "\nfrom\n\tpages\nwhere\n\tanalytics is not null";

        /**
         * Predicates
         */
        $predicates = $sql->getPredicates();
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
        $orderBys = $sql->getOrderBys();
        if (sizeof($orderBys) > 0) {
            $physicalSql .= "\norder by\n\t" . implode(",\n\t", $orderBys);
        }

        /**
         * Limit
         */
        $limit = $sql->getLimit();
        if (!empty($limit)) {
            $physicalSql .= "\nlimit $limit";
        }
        return $physicalSql;

    }
}
