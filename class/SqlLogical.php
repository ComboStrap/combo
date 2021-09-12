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
            foreach ($this->sqlParser->getStringColumnIdentifiers() as $columnIdentifier) {

                $columnsIdentifier[] = "json_extract(analytics, '$.metadata.$columnIdentifier') as $columnIdentifier";

            }
            $physicalSql .= implode(",\n\t", $columnsIdentifier);
        } else {
            $physicalSql .= "*";
        }

        $physicalSql .= "\nfrom\n\tpages";

        /**
         * Where tokens
         */
        $parsedWhereTokens = $this->sqlParser->getWhereTokens();
        $parsedWhereTokenSize = sizeof($parsedWhereTokens);

        $whereTokens = [];
        // Special predicates if json
        if ($databaseTarget === self::SQLITE_JSON) {
            $whereTokens[] = SqlToken::create(SqlParser::TOKEN_TYPE_PREDICATE, "analytics is not null");
            if ($parsedWhereTokenSize > 0) {
                $whereTokens[] = SqlToken::create(SqlParser::TOKEN_TYPE_LOGICAL_OPERATOR, "and");
            }
        }
        $whereTokens = array_merge($whereTokens, $parsedWhereTokens);


        if (sizeof($whereTokens) > 0) {
            $physicalSql .= "\nwhere";
            foreach ($whereTokens as $whereToken) {
                if ($whereToken->getType() == SqlParser::TOKEN_TYPE_PREDICATE) {
                    $physicalSql .= "\n\t";
                } else {
                    $physicalSql .= " ";
                }
                $physicalSql .= $whereToken->getTokenString();
            }
        }

        /**
         * Order by
         */
        $orderBys = $this->sqlParser->getStringOrderBys();
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
        return $this->sqlParser->getStringColumnIdentifiers();
    }

    public function __toString()
    {
        return $this->logicalSql;
    }


}
