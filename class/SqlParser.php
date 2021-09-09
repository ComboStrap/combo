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

    const WHERE_START_STATE = "where_start_state";
    private $sql;
    private $cols = [];
    /**
     * The state of FSM
     * @var string
     */
    private $state;
    private $predicates = [];

    /**
     * The word is the where the splitting happen
     * (ie by space)
     */
    private $word;

    /**
     * The token is a serie of word
     * For instance, for a identifier (column)
     * `column as alias`
     */
    private $token;
    /**
     * Character index
     * @var int
     */
    private $parsedCharacterInfoForLog;

    /**
     * A variable to remember the previsous state
     * to support quotation
     * @var string
     */
    private $previousState;
    /**
     * The opening quote character to make the difference
     * between a " and a '
     * @var false|string
     */
    private $openingQuoteCharacter;


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


        $this->state = self::START_STATE;
        for ($i = 0; $i < mb_strlen($sql); $i++) {
            $char = mb_substr($sql, $i, 1);
            $this->parsedCharacterInfoForLog = "$char - $i";
            switch ($char) {
                case ',':
                    if ($this->state != self::STATE_IN_QUOTE) {
                        if ($this->state == self::IDENTIFIER_START_STATE) {
                            $this->processColumnToken();
                        } else {
                            $this->triggerBadState();
                        }
                    } else {
                        $this->token .= $char;
                    }
                    break;
                case ' ':
                    // End word
                    switch (strtolower($this->word)) {
                        case "select":
                            if ($this->state !== self::START_STATE) {
                                LogUtility::msg("A sql should start with the key word `select` not with the word ($this->word)", LogUtility::LVL_MSG_ERROR);
                                return $this;
                            }
                            $this->state = self::IDENTIFIER_START_STATE;
                            $this->getFinalizedToken();
                            break;
                        case "where":
                            if ($this->state !== self::IDENTIFIER_START_STATE) {
                                LogUtility::msg("The where key word should be located after a select", LogUtility::LVL_MSG_ERROR);
                                return $this;
                            }
                            // Delete the where
                            $this->word = "";
                            $this->processColumnToken();
                            $this->state = self::WHERE_START_STATE;
                            break;
                        case "and":
                        case "or":
                            if (
                                $this->state !== self::WHERE_START_STATE
                                && $this->state != self::STATE_IN_QUOTE
                            ) {
                                LogUtility::msg("The logical `or` and `and` operator should be after the where clause", LogUtility::LVL_MSG_ERROR);
                                return $this;
                            }
                            // delete the and/or
                            $this->word = "";
                            $this->processPredicateToken();
                            break;
                        default:
                            if ($this->state !== self::STATE_IN_QUOTE) {
                                $this->token .= $this->word . $char;
                                $this->word = "";
                            } else {
                                $this->word .= $char;
                            }
                    }
                    break;
                case '\'':
                case '"':
                    // Quotation management
                    // Character such as , and or will not be marked as a token separator
                    if (
                        $this->state === self::STATE_IN_QUOTE
                    ) {
                        /**
                         * We close the quote only
                         * if this is the previous character
                         *
                         * Don't group the state and the character
                         * in a predicate
                         * otherwise an expression such as
                         * '"hallo  where foo = 'bar '
                         * will pass because of the `'` quotation
                         */
                        if($this->openingQuoteCharacter === $char) {
                            $this->openingQuoteCharacter = "";
                            $this->state = $this->previousState;
                        }
                    } else {
                        $this->previousState = $this->state;
                        $this->openingQuoteCharacter = $char;
                        $this->state = self::STATE_IN_QUOTE;
                    }
                    $this->word .= $char;
                    break;
                default:
                    $this->word .= $char;
            }
        }
        if (!empty($this->word) || !empty($this->token)) {
            switch ($this->state) {
                case self::IDENTIFIER_START_STATE:
                    $this->processColumnToken();
                    break;
                case self::WHERE_START_STATE:
                    $this->processPredicateToken();
                    break;
                case self::STATE_IN_QUOTE:
                    LogUtility::msg("A quote is missing. Unable to find a closing quote ($this->openingQuoteCharacter) in ($this->word)", LogUtility::LVL_MSG_ERROR);
                    break;
                default:
                    LogUtility::msg("At the end of the SQL Parsing, the word ($this->word) and the token ($this->token) should be empty for the state ($this->state)", LogUtility::LVL_MSG_ERROR);
            }
        }
        return $this;
    }

    public function getColumnIdentifiers()
    {
        return $this->cols;
    }


    private function processColumnToken()
    {
        $token = $this->getFinalizedToken();
        $this->cols[] = $token;
    }

    private function triggerBadState()
    {
        LogUtility::msg("Unknown Bad State: $this->state} (Token: ($this->token), Word: ($this->word), Character: ($this->parsedCharacterInfoForLog))", LogUtility::LVL_MSG_ERROR);
    }

    private function processPredicateToken()
    {
        $token = $this->getFinalizedToken();
        $this->predicates[] = $token;

    }

    private function getFinalizedToken()
    {
        $token = $this->token . $this->word;
        $this->token = "";
        $this->word = "";
        return trim($token);
    }

    public function getPredicates()
    {
        return $this->predicates;
    }

}

class SqlColumn
{

}
