<?php


namespace ComboStrap;


/**
 *
 * @package ComboStrap
 */
class SqlLogical
{
    const SQLITE_JSON =  "sqliteWithJsonSupport";
    private $logicalSql;


    /**
     * SqlLogical constructor.
     */
    public function __construct($logicalSql)
    {
        $this->logicalSql = $logicalSql;
    }

    public static function create($logicalSql){
        return new SqlLogical($logicalSql);
    }

    public function toPhysical($databaseTarget=self::SQLITE_JSON){


        $sql = SqlParser::create($this->logicalSql);

    }
}
