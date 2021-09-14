<?php


namespace ComboStrap\LogicalSqlAntlr;


use Antlr\Antlr4\Runtime\ParserRuleContext;
use Antlr\Antlr4\Runtime\Tree\ErrorNode;
use Antlr\Antlr4\Runtime\Tree\ParseTreeListener;
use Antlr\Antlr4\Runtime\Tree\ParseTreeWalker;
use Antlr\Antlr4\Runtime\Tree\TerminalNode;
use ComboStrap\LogicalSqlAntlr\Gen\LogicalSqlLexer;
use ComboStrap\LogicalSqlAntlr\Gen\LogicalSqlParser;

/**
 * Class SqlTreeListener
 * @package ComboStrap\LogicalSqlAntlr
 *
 * The listener that is called by {@link  ParseTreeWalker::walk()}
 * that performs a walk on the given parse tree starting at the root
 * and going down recursively with depth-first search.
 */
final class SqlTreeListener implements ParseTreeListener
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
    private $parameters;

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
            case LogicalSqlParser::PAGES:
            case LogicalSqlParser::BACKLINKS:
                $text = strtolower($text);
                $this->physicalSql .= "\t{$text}\n";
                break;
            case LogicalSqlParser::SQL_NAME:
                if ($this->state === LogicalSqlParser::RULE_predicates
                    ||
                    $this->state === LogicalSqlParser::RULE_orderBys
                ) {
                    $text = strtolower($text);
                    $this->physicalSql .= "\t{$text} ";
                }
                break;
            case LogicalSqlParser::EQUAL:
                if ($this->state === LogicalSqlParser::RULE_predicates) {
                    $this->physicalSql .= "{$text} ";
                }
                break;
            case LogicalSqlParser::LITERAL_VALUE:
                switch ($this->state) {
                    case LogicalSqlParser::RULE_predicates:
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
                    case LogicalSqlParser::RULE_limit:
                        $this->physicalSql .= "{$text}";
                        break;
                }
                break;
            case LogicalSqlParser:: AND:
            case LogicalSqlParser:: OR:
                if ($this->state === LogicalSqlParser::RULE_predicates) {
                    $this->physicalSql .= " {$text}\n";
                }
                break;
            case LogicalSqlParser:: DESC:
            case LogicalSqlParser:: ASC:
                $this->physicalSql .= "{$text}";
                break;
            case LogicalSqlParser:: COMMA:
                if ($this->state !== LogicalSqlParser::RULE_columns) {
                    $this->physicalSql .= "{$text}\n";
                }
                break;
            case LogicalSqlParser:: LIMIT:
                $this->physicalSql .= "{$text} ";
                break;

        }
    }


    public function visitErrorNode(ErrorNode $node): void
    {
    }


    /**
     *
     * Parent Node
     *
     * On each node, enterRule is called before recursively walking down into child nodes,
     * then {@link SqlTreeListener::exitEveryRule()} is called after the recursive call to wind up.
     * Parameters:
     * @param ParserRuleContext $ctx
     */
    public function enterEveryRule(ParserRuleContext $ctx): void
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
        }


    }

    /**
     *
     * Parent Node
     *
     * On each node, {@link SqlTreeListener::enterEveryRule()} is called before recursively walking down into child nodes,
     * then {@link SqlTreeListener::exitEveryRule()} is called after the recursive call to wind up.
     * @param ParserRuleContext $ctx
     */
    public function exitEveryRule(ParserRuleContext $ctx): void
    {
        $ruleIndex = $ctx->getRuleIndex();
        switch ($ruleIndex) {
            case LogicalSqlParser::RULE_predicates:
            case LogicalSqlParser::RULE_orderBys:
                $this->physicalSql .= "\n";
                break;
        }

    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * For documentation
     * @param ParserRuleContext $ctx
     * @return string
     */
    private function getRuleName(ParserRuleContext $ctx): string
    {
        $ruleNames = $this->parser->getRuleNames();
        return $ruleNames[$ctx->getRuleIndex()];
    }

    /**
     * For documentation
     * @param TerminalNode $node
     * @return string|null
     */
    private function getTokenName(TerminalNode $node)
    {
        $token = $node->getSymbol();
        return $this->lexer->getVocabulary()->getSymbolicName($token->getType());
    }

    public function getPhysicalSql(): string
    {
        return $this->physicalSql;
    }


}
