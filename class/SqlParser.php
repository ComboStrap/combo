<?php


namespace ComboStrap;


class SqlParser
{
    const TOKEN = "token";
    const OPERATOR = "operator";

    /**
     * Type of token
     */
    const TOKEN_TYPE_PREDICATE = "predicate";
    const TOKEN_TYPE_LOGICAL_OPERATOR = "logicalOperator";
    const TOKEN_TYPE_IDENTIFIER = "identifier";
    const TOKEN_TYPE_ORDER_BY = "orderBy";
    const TOKEN_TYPE_LIMIT = "limit";


    /**
     * The state of FSM
     * @var string
     */
    private $state;

    /**
     * The FSM state value while we
     * are going through the statement
     */
    const START_STATE = "start_state";
    const STATE_COLUMN_IDENTIFIER = "column_identifier_state";
    const STATE_IN_QUOTE = "in_quote_state";
    const STATE_ORDER_BY = "order_by_state";
    const STATE_PREDICATE = "where_state";
    const STATE_LIMIT = "limit_state";

    /**
     * The SQL word
     */
    const SELECT_WORD = "select";
    const WHERE_WORD = "where";
    const AND_WORD = "and";
    const OR_WORD = "or";
    const ORDER_WORD = "order";
    const LIMIT_WORD = "limit";
    const SQL_WORDS = [
        self::SELECT_WORD,
        self::WHERE_WORD,
        self::AND_WORD,
        self::OR_WORD,
        self::LIMIT_WORD
    ];


    /**
     * @var string the sql given
     */
    private $sql;


    /**
     * The word (ie separated by space)
     */
    private $word;
    /**
     * The token is a series of word that represents a higher
     * sequence of text than the word
     *
     * For instance:
     *   * a identifier (column) (separated by ,)
     * `column as alias`
     *   * a predicate (separated `by` or and `and`)
     * `column = alias`
     *   * a sort expression (separated by `,` in an `order by` sequence)
     * `column asc`
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
     * All the tokens in the same sequence
     * that found in the sql
     * @var SqlToken[]
     */
    private $tokens;


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
        $i = -1;
        $sqlLength = mb_strlen($sql);
        while ($i < $sqlLength - 1) {
            $i++;
            $char = mb_substr($sql, $i, 1);
            $this->parsedCharacterInfoForLog = "$char - $i";
            switch ($char) {
                case ',':

                    switch ($this->state) {

                        case self::STATE_IN_QUOTE:
                            $this->token .= $char;
                            break;
                        case  self::STATE_COLUMN_IDENTIFIER:
                            $this->processColumnToken();
                            break;
                        case  self::STATE_ORDER_BY:
                            $this->processOrderByToken();
                            break;
                        default:
                            $this->triggerBadState();
                            break;
                    }

                    break;
                case ' ':
                    // End word
                    $normalizedWord = strtolower($this->word);

                    /**
                     * In Quote Sql Operator ?
                     */
                    if (
                        $this->state == self::STATE_IN_QUOTE &&
                        in_array($normalizedWord, self::SQL_WORDS)
                    ) {
                        $this->word .= $char;
                        continue 2;
                    }

                    /**
                     * Sql Operator
                     */
                    switch ($normalizedWord) {
                        case self::SELECT_WORD:
                            if ($this->state !== self::START_STATE) {
                                LogUtility::msg("A sql should start with the key word `select` not with the word ($this->word)", LogUtility::LVL_MSG_ERROR);
                                return $this;
                            }
                            $this->state = self::STATE_COLUMN_IDENTIFIER;
                            $this->getFinalizedToken();
                            break;
                        case self::WHERE_WORD:
                            if ($this->state !== self::STATE_COLUMN_IDENTIFIER) {
                                LogUtility::msg("The where key word should be located after a select", LogUtility::LVL_MSG_ERROR);
                                return $this;
                            }
                            // Delete the where
                            $this->word = "";
                            $this->processColumnToken();
                            $this->state = self::STATE_PREDICATE;
                            break;
                        case self::LIMIT_WORD:

                            // Delete the limit word
                            $this->word = "";
                            switch ($this->state) {
                                case self::STATE_COLUMN_IDENTIFIER:
                                    $this->processColumnToken();
                                    break;
                                case self::STATE_PREDICATE:
                                    $this->processPredicateToken();
                                    break;
                                case self::STATE_ORDER_BY:
                                    $this->processOrderByToken();
                                    break;
                                default:
                                    LogUtility::msg("The limit key word should not be found at this place on this state ($this->state)", LogUtility::LVL_MSG_ERROR);
                                    return $this;
                            }
                            $this->state = self::STATE_LIMIT;
                            break;
                        case self::AND_WORD:
                        case self::OR_WORD:
                            if (
                                $this->state !== self::STATE_PREDICATE
                                && $this->state != self::STATE_IN_QUOTE
                            ) {
                                LogUtility::msg("The logical `or` and `and` operator should be after the where clause", LogUtility::LVL_MSG_ERROR);
                                return $this;
                            }
                            // delete the and/or
                            $logicalOperator = $this->word;
                            $this->word = "";
                            $this->processPredicateToken($logicalOperator);
                            break;
                        case self::ORDER_WORD:
                            // Do we have a `by` ?
                            $j = $i;
                            $nextWord = "";
                            $nonEmptyCharactersFound = false;
                            while ($j < $sqlLength - 1) {
                                $j++;
                                $charAfterOrder = mb_substr($sql, $j, 1);
                                if ($charAfterOrder !== " ") {
                                    $nonEmptyCharactersFound = true;
                                    $nextWord .= $charAfterOrder;
                                } else {
                                    if ($nonEmptyCharactersFound) {
                                        break;
                                    }
                                }
                            }
                            if ($nextWord === "by") {

                                // Process the token
                                $this->word = ""; // delete the order word
                                switch ($this->state) {
                                    case self::STATE_PREDICATE:
                                        $this->processPredicateToken();
                                        break;
                                    case self::STATE_COLUMN_IDENTIFIER:
                                        $this->processColumnToken();
                                        break;
                                    default:
                                        LogUtility::msg("The `order by` seems to be at the wrong place (state ($this->state) unknown)", LogUtility::LVL_MSG_ERROR);
                                        return $this;

                                }

                                $this->state = self::STATE_ORDER_BY;

                                // Advance the pointer
                                $i = $j;

                            } else {
                                $this->word .= $char;
                            }
                            break;
                        default:
                            if ($this->state === self::STATE_IN_QUOTE) {
                                $this->word .= $char;
                            } else {
                                $this->token .= $this->word . $char;
                                $this->word = "";
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
                        if ($this->openingQuoteCharacter === $char) {
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
                case self::STATE_COLUMN_IDENTIFIER:
                    $this->processColumnToken();
                    break;
                case self::STATE_PREDICATE:
                    $this->processPredicateToken();
                    break;
                case self::STATE_ORDER_BY:
                    $this->processOrderByToken();
                    break;
                case self::STATE_LIMIT:
                    $this->processLimitToken();
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
        return $this->getTokensFromType(self::TOKEN_TYPE_IDENTIFIER);
    }


    private function processColumnToken()
    {
        $token = $this->getFinalizedToken();
        $this->tokens[] = SqlToken::create(self::TOKEN_TYPE_IDENTIFIER, $token);
    }

    private function triggerBadState()
    {
        LogUtility::msg("Unknown Bad State: $this->state} (Token: ($this->token), Word: ($this->word), Character: ($this->parsedCharacterInfoForLog))", LogUtility::LVL_MSG_ERROR);
    }

    private function processPredicateToken($logicalOperator = "")
    {
        $token = $this->getFinalizedToken();
        $this->tokens[] = SqlToken::create(self::TOKEN_TYPE_PREDICATE, $token);
        if (!empty($logicalOperator)) {
            $this->tokens[] = SqlToken::create(self::TOKEN_TYPE_LOGICAL_OPERATOR, $token);
        }

    }

    private function processOrderByToken()
    {
        $token = $this->getFinalizedToken();
        $this->tokens[] = SqlToken::create(self::TOKEN_TYPE_ORDER_BY, $token);

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
        return $this->getTokensFromType(self::TOKEN_TYPE_PREDICATE);
    }

    public function getOrderBys()
    {
        return $this->getTokensFromType(self::TOKEN_TYPE_ORDER_BY);

    }

    private function processLimitToken()
    {
        $token = $this->getFinalizedToken();
        $this->tokens[] = SqlToken::create(self::TOKEN_TYPE_LIMIT, $token);
    }

    public function getLimit()
    {
        return $this->getTokensFromType(self::TOKEN_TYPE_LIMIT);
    }

    private function getTokensFromType($type)
    {
        /**
         * Filter and set the key back to 0,1,2,...
         */
        $tokens = array_values(array_filter(
            $this->tokens,
            function ($a) use ($type) {
                return $a->getType() === $type;
            }
        ));

        return array_map(
            function ($a) {
                return $a->getTokenString();
            },
            $tokens
        );
    }

}



