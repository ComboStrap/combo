<?php


namespace ComboStrap\LogicalSqlAntlr;


use Antlr\Antlr4\Runtime\ParserRuleContext;
use Antlr\Antlr4\Runtime\Tree\ErrorNode;
use Antlr\Antlr4\Runtime\Tree\ParseTreeListener;
use Antlr\Antlr4\Runtime\Tree\ParseTreeWalker;
use Antlr\Antlr4\Runtime\Tree\TerminalNode;
use ComboStrap\LogicalSqlAntlr\Gen\LogicalSqlLexer;
use ComboStrap\LogicalSqlAntlr\Gen\LogicalSqlParser;
use http\Exception\RuntimeException;

/**
 * Class SqlTreeListener
 * @package ComboStrap\LogicalSqlAntlr
 *
 * The listener that is called by {@link  ParseTreeWalker::walk()}
 * that performs a walk on the given parse tree starting at the root
 * and going down recursively with depth-first search.
 */
final class LogicalSqlTreeListener implements ParseTreeListener
{
    /**
     * @var logicalSqlLexer
     */
    private $lexer;
    /**
     * @var logicalSqlParser
     */
    private $parser;
    /**
     * @var String
     */
    private $physicalSql;
    /**
     * @var int
     */
    private $state;

    private const STATE_VALUES = [
        LogicalSqlParser::RULE_columns,
        LogicalSqlParser::RULE_tables,
        LogicalSqlParser::RULE_predicates,
        LogicalSqlParser::RULE_orderBys,
        LogicalSqlParser::RULE_limit,
    ];
    /**
     * @var string[]
     */
    private $parameters = [];
    /**
     * @var array
     */
    private $columns;

    /**
     * SqlTreeListener constructor.
     *
     * @param logicalSqlLexer $lexer
     * @param logicalSqlParser $parser
     */
    public function __construct(LogicalSqlLexer $lexer, LogicalSqlParser $parser)
    {
        $this->lexer = $lexer;
        $this->parser = $parser;
    }


    /**
     * Leaf node
     * @param TerminalNode $node
     */
    public function visitTerminal(TerminalNode $node): void
    {

        $type = $node->getSymbol()->getType();
        $text = $node->getText();
        switch ($type) {
            case LogicalSqlParser::SELECT:
                $this->physicalSql .= "select\n\t*\n";

                /**
                 * The from select is optional
                 * Check if it's there
                 */
                $parent = $node->getParent();
                for ($i = 0; $i < $parent->getChildCount(); $i++) {
                    $child = $parent->getChild($i);
                    if ($child instanceof ParserRuleContext) {
                        /**
                         * @var ParserRuleContext $child
                         */
                        if ($child->getRuleIndex() === LogicalSqlParser::RULE_tables) {
                            return;
                        }
                    }
                }
                $this->physicalSql .= "from\n\tpages\n";
                break;
            case LogicalSqlParser::SqlName:
                switch ($this->state) {
                    case LogicalSqlParser::RULE_predicates:

                        // variable name
                        $variableName = strtolower($text);
                        $this->physicalSql .= "\t{$variableName} ";

                        break;
                    case
                    LogicalSqlParser::RULE_orderBys:
                        $text = strtolower($text);
                        $this->physicalSql .= "\t{$text} ";
                        break;
                    case LogicalSqlParser::RULE_columns:
                        $this->columns[] = $text;
                        break;
                }
                break;
            case LogicalSqlParser::EQUAL:
            case LogicalSqlParser::LIKE:
            case LogicalSqlParser::LESS_THAN_OR_EQUAL:
            case LogicalSqlParser::LESS_THAN:
            case LogicalSqlParser::GREATER_THAN:
            case LogicalSqlParser::GREATER_THAN_OR_EQUAL:
                switch ($this->state) {
                    case LogicalSqlParser::RULE_predicates:
                        $this->physicalSql .= "{$text} ";
                }
                break;
            case LogicalSqlParser::StringLiteral:
                switch ($this->state) {
                    case LogicalSqlParser::RULE_predicates:
                        $grandParent = $node->getParent()->getParent();
                        if ($grandParent  instanceof ParserRuleContext) {
                            if($grandParent->getRuleIndex()===LogicalSqlParser::RULE_expression) {
                                // Literal Value in Expression of an Expression (ie Function)
                                $this->physicalSql .= $text;
                                return;
                            }
                        }
                        // Literal value alone
                        if (
                            ($text[0] === "'" and $text[strlen($text) - 1] === "'")
                            ||
                            ($text[0] === '"' and $text[strlen($text) - 1] === '"')) {
                            $quote = $text[0];
                            $text = substr($text, 1, strlen($text) - 2);
                            $text = str_replace("$quote$quote", "$quote", $text);
                        }
                        $this->parameters[] = $text;
                        $this->physicalSql .= "?";
                        break;
                }
                break;
            case LogicalSqlParser:: AND:
            case LogicalSqlParser:: OR:
                if ($this->state === LogicalSqlParser::RULE_predicates) {
                    $this->physicalSql .= " {$text}\n";
                }
                return;
            case LogicalSqlParser:: DESC:
            case LogicalSqlParser:: LPAREN:
            case LogicalSqlParser:: RPAREN:
            case LogicalSqlParser:: ASC:
                $this->physicalSql .= "{$text}";
                break;
            case LogicalSqlParser:: COMMA:
                switch ($this->state) {
                    case LogicalSqlParser::RULE_columns:
                        return;
                    case LogicalSqlParser::RULE_orderBys:
                        $this->physicalSql .= "{$text}\n";
                        return;
                    default:
                        $this->physicalSql .= "{$text}";
                        return;
                }
            case LogicalSqlParser::LIMIT:
                $this->physicalSql .= "{$text} ";
                return;
            case LogicalSqlParser::Number:
                switch ($this->state) {
                    case LogicalSqlParser::RULE_limit:
                        $this->physicalSql .= "{$text}";
                        return;
                    case LogicalSqlParser::RULE_predicates:
                        $this->parameters[] = $text;
                        $this->physicalSql .= "?";
                        return;
                    default:
                        $this->physicalSql .= "{$text} ";
                        return;
                }
            default:
                // We do nothing because the token may have been printed at a higher level such as order by
        }
    }


    public
    function visitErrorNode(ErrorNode $node): void
    {
    }


    /**
     *
     * Parent Node
     *
     * On each node, enterRule is called before recursively walking down into child nodes,
     * then {@link LogicalSqlTreeListener::exitEveryRule()} is called after the recursive call to wind up.
     * Parameters:
     * @param ParserRuleContext $ctx
     */
    public
    function enterEveryRule(ParserRuleContext $ctx): void
    {

        $ruleIndex = $ctx->getRuleIndex();
        if (in_array($ruleIndex, self::STATE_VALUES)) {
            $this->state = $ruleIndex;
        }
        switch ($ruleIndex) {
            case LogicalSqlParser::RULE_orderBys:
                $this->physicalSql .= "order by\n";
                break;
            case LogicalSqlParser::RULE_tables:
                $this->physicalSql .= "from\n";
                break;
            case LogicalSqlParser::RULE_predicates:
                $this->physicalSql .= "where\n";
                break;
            case LogicalSqlParser::RULE_functionNames:
                // Print the function name
                $this->physicalSql .= $ctx->getText();
                break;
            case LogicalSqlParser::RULE_tableNames:
                // Print the table name
                $this->physicalSql .= "\t{$ctx->getText()}\n";
                break;
        }


    }

    /**
     *
     * Parent Node
     *
     * On each node, {@link LogicalSqlTreeListener::enterEveryRule()} is called before recursively walking down into child nodes,
     * then {@link LogicalSqlTreeListener::exitEveryRule()} is called after the recursive call to wind up.
     * @param ParserRuleContext $ctx
     */
    public
    function exitEveryRule(ParserRuleContext $ctx): void
    {
        $ruleIndex = $ctx->getRuleIndex();
        switch ($ruleIndex) {
            case LogicalSqlParser::RULE_predicates:
            case LogicalSqlParser::RULE_orderBys:
                $this->physicalSql .= "\n";
                break;
        }

    }

    public
    function getParameters(): array
    {
        return $this->parameters;
    }

    public
    function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * For documentation
     * @param ParserRuleContext $ctx
     * @return string
     */
    private
    function getRuleName(ParserRuleContext $ctx): string
    {
        $ruleNames = $this->parser->getRuleNames();
        return $ruleNames[$ctx->getRuleIndex()];
    }

    /**
     * For documentation
     * @param TerminalNode $node
     * @return string|null
     */
    private
    function getTokenName(TerminalNode $node)
    {
        $token = $node->getSymbol();
        return $this->lexer->getVocabulary()->getSymbolicName($token->getType());
    }

    public
    function getPhysicalSql(): string
    {
        return $this->physicalSql;
    }


}
