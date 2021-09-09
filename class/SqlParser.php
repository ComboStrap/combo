<?php


namespace ComboStrap;


class SqlParser
{

    /**
     * We are in the columns definition
     * after the select
     */
    const IDENTIFIER_START_STATE = "identifier_start_state";
    const START_STATE = "start";
    const STATE_IN_QUOTE = "in_quote";
    private $sql;
    private $cols = [];
    /**
     * The state of FSM
     * @var string
     */
    private $state;


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

        /**
         * The word is the where the splitting happen
         * (ie space)
         */
        $word = "";

        /**
         * The token is a serie of word
         * For instance, for a identifier (column)
         * `column as alias`
         */
        $token = "";

        $this->state = self::START_STATE;
        for ($i = 0; $i < mb_strlen($sql); $i++) {
            $char = mb_substr($sql, $i, 1);
            switch ($char) {
                case ',':
                    if ($this->state != self::STATE_IN_QUOTE) {
                        if ($this->state == self::IDENTIFIER_START_STATE) {
                            $this->processColumn($token);
                        } else {
                            $this->triggerBadState();
                        }
                    } else {
                        $token .= $char;
                    }
                    break;
                case ' ':
                    // End word
                    switch ($word) {
                        case "select":
                            $this->state = self::IDENTIFIER_START_STATE;
                            $token = "";
                            if ($this->state != self::START_STATE) {
                                LogUtility::msg("A sql should start with the key word `select`", LogUtility::LVL_MSG_ERROR);
                                return $this;
                            }
                            break;
                        default:
                            $token .= $word . $char;
                    }
                    // init
                    $word = "";
                    break;
                case '\'':
                    break;
                default:
                    $word .= $char;
            }
        }
        if (!empty($word) || !empty($token)) {
            LogUtility::msg("The word ($word) or the token ($token) is not empty", LogUtility::LVL_MSG_ERROR);
        }
        return $this;
    }

    public function getColumns()
    {
        return $this->cols;
    }

    private function processToken($actualToken)
    {
        $actualToken = trim($actualToken);

        $this->cols[$actualToken] = $actualToken;

        return $actualToken;
    }

    private function processColumn($actualToken)
    {

    }

    private function triggerBadState()
    {
        LogUtility::msg("Unknown Bad State: $this->state}", LogUtility::LVL_MSG_ERROR);
    }

}

class SqlColumn
{

}
