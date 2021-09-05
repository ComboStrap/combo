<?php


namespace ComboStrap;


class SqlParser
{

    const IDENTIFIER_STATE = "identifier";
    const START_STATE = "start";
    private $sql;
    private $cols = [];


    /**
     * Sql constructor.
     */
    public function __construct($sql)
    {
        $this->sql = $sql;
    }

    public static function create($sql)
    {
        return new SqlParser($sql);
    }

    public function parse()
    {
        $sql = trim($this->sql);
        $actualToken = "";
        $state = self::START_STATE;
        for ($i = 0; $i < mb_strlen($sql); $i++) {
            $char = mb_substr($sql, $i, 1);
            switch ($char) {
                case ',':
                    // End token
                    $this->addColumn($actualToken);
                    $actualToken = "";
                    break;
                case ' ':
                    // End token
                    switch ($state) {
                        case self::START_STATE:
                            if (strtolower($actualToken) == "select") {
                                $state = self::IDENTIFIER_STATE;
                                $actualToken = "";
                            } else {
                                LogUtility::msg("A sql should start with the key word `select`", LogUtility::LVL_MSG_ERROR);
                                return $this;
                            }
                            break;
                        case self::IDENTIFIER_STATE:
                            $actualToken .= $char;
                            break;
                        default:
                            LogUtility::msg("Unknown SQL parsing state ($state)", LogUtility::LVL_MSG_ERROR);
                    }
                    break;
                case '\'':
                    break;
                default:
                    $actualToken .= $char;
            }
        }
        if (!empty($actualToken)) {
            $this->addColumn($actualToken);
        }
        return $this;
    }

    public function getColumns()
    {
        return $this->cols;
    }

    private function addColumn($actualToken)
    {
        $actualToken = trim($actualToken);
        $this->cols[$actualToken] = $actualToken;
    }

}
