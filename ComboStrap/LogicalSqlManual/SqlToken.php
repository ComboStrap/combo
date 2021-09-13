<?php


namespace ComboStrap\LogicalSqlManual;


class SqlToken
{
    const SELECT_TOKEN = "select";
    const TOKEN_TYPE_LOGICAL_OPERATOR = "logicalOperator";
    const TOKEN_TYPE_ORDER_BY = "orderBy";
    const TOKEN_TYPE_IDENTIFIER = "identifier";
    const TOKEN_TYPE_LIMIT = "limit";
    /**
     * Type of token
     */
    const TOKEN_TYPE_PREDICATE = "predicate";

    private $token;
    private $type;


    /**
     * SqlPredicate constructor.
     */
    public function __construct($type, $token)
    {
        $this->token = $token;
        $this->type = $type;
    }

    public static function create($type, $token)
    {
        return new SqlToken($type, $token);
    }

    public function getType()
    {
        return $this->type;
    }

    public function getTokenString()
    {
        return $this->token;
    }

}
