<?php


namespace ComboStrap;


class SqlToken
{
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
