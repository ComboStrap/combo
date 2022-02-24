<?php


namespace ComboStrap;


use Antlr\Antlr4\Runtime\ParserRuleContext;
use Antlr\Antlr4\Runtime\Tree\ErrorNode;
use Antlr\Antlr4\Runtime\Tree\ParseTreeListener;
use Antlr\Antlr4\Runtime\Tree\ParseTreeWalker;
use Antlr\Antlr4\Runtime\Tree\TerminalNode;
use ComboStrap\PageSqlParser\PageSqlLexer;
use ComboStrap\PageSqlParser\PageSqlParser;


/**
 * Class SqlTreeListener
 * @package ComboStrap\LogicalSqlAntlr
 *
 * The listener that is called by {@link  ParseTreeWalker::walk()}
 * that performs a walk on the given parse tree starting at the root
 * and going down recursively with depth-first search.
 *
 * The process is to check all token and to process them
 * with context
 */
final class PageSqlTreeListener implements ParseTreeListener
{
    const BACKLINKS = "backlinks";
    /**
     * @var PageSqlLexer
     */
    private $lexer;
    /**
     * @var PageSqlParser
     */
    private $parser;
    /**
     * @var String
     */
    private $physicalSql;
    /**
     * @var int
     */
    private $ruleState;

    private const STATE_VALUES = [
        PageSqlParser::RULE_columns,
        PageSqlParser::RULE_tables,
        PageSqlParser::RULE_predicates,
        PageSqlParser::RULE_orderBys,
        PageSqlParser::RULE_limit,
    ];
    /**
     * @var string[]
     */
    private $parameters = [];
    /**
     * @var array
     */
    private $columns = [];
    /**
     * @var string
     */
    private $pageSqlString;
    /**
     * backlinks or pages
     * @var string
     */
    private $type;

    /**
     * SqlTreeListener constructor.
     *
     * @param PageSqlLexer $lexer
     * @param PageSqlParser $parser
     * @param string $sql
     */
    public function __construct(PageSqlLexer $lexer, PageSqlParser $parser, string $sql)
    {
        $this->lexer = $lexer;
        $this->parser = $parser;
        $this->pageSqlString = $sql;
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
            case PageSqlParser::SELECT:
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
                        if ($child->getRuleIndex() === PageSqlParser::RULE_tables) {
                            return;
                        }
                    }
                }
                $this->physicalSql .= "from\n\tpages\n";
                break;
            case PageSqlParser::SqlName:
                switch ($this->ruleState) {
                    case PageSqlParser::RULE_predicates:

                        // variable name
                        $variableName = strtolower($text);
                        if (substr($this->physicalSql, -1) === "\n") {
                            $this->physicalSql .= "\t";
                        }
                        if ($this->type === self::BACKLINKS) {
                            $variableName = "p." . $variableName;
                        }
                        $this->physicalSql .= "{$variableName} ";

                        break;
                    case
                    PageSqlParser::RULE_orderBys:
                        $variableName = strtolower($text);
                        if ($this->type === self::BACKLINKS) {
                            $variableName = "p." . $variableName;
                        }
                        $this->physicalSql .= "\t{$variableName} ";
                        break;
                    case PageSqlParser::RULE_columns:
                        $this->columns[] = $text;
                        break;
                }
                break;
            case PageSqlParser::EQUAL:
            case PageSqlParser::LIKE:
            case PageSqlParser::GLOB:
            case PageSqlParser::LESS_THAN_OR_EQUAL:
            case PageSqlParser::LESS_THAN:
            case PageSqlParser::GREATER_THAN:
            case PageSqlParser::GREATER_THAN_OR_EQUAL:
            case PageSqlParser::NOT_EQUAL:
                switch ($this->ruleState) {
                    case PageSqlParser::RULE_predicates:
                        $this->physicalSql .= "{$text} ";
                }
                break;
            case PageSqlParser::StringLiteral:
                switch ($this->ruleState) {
                    case PageSqlParser::RULE_predicates:
                        // Parameters
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
            case PageSqlParser:: AND:
            case PageSqlParser:: OR:
                if ($this->ruleState === PageSqlParser::RULE_predicates) {
                    $this->physicalSql .= " {$text}\n";
                }
                return;
            case PageSqlParser::LIMIT:
            case PageSqlParser::NOT:
                $this->physicalSql .= "{$text} ";
                return;
            case PageSqlParser::DESC:
            case PageSqlParser::LPAREN:
            case PageSqlParser::RPAREN:
            case PageSqlParser::ASC:
                $this->physicalSql .= "{$text}";
                break;
            case PageSqlParser:: COMMA:
                switch ($this->ruleState) {
                    case PageSqlParser::RULE_columns:
                        return;
                    case PageSqlParser::RULE_orderBys:
                        $this->physicalSql .= "{$text}\n";
                        return;
                    default:
                        $this->physicalSql .= "{$text}";
                        return;
                }
            case PageSqlParser::ESCAPE:
                $this->physicalSql .= " {$text} ";
                return;
            case PageSqlParser::Number:
                switch ($this->ruleState) {
                    case PageSqlParser::RULE_limit:
                        $this->physicalSql .= "{$text}";
                        return;
                    case PageSqlParser::RULE_predicates:
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
        $charPosition = $node->getSymbol()->getStartIndex();
        $textMakingTheError = $node->getText(); // $this->lexer->getText();

        $position = "at position: $charPosition";
        if ($charPosition != 0) {
            $position .= ", in `" . substr($this->pageSqlString, $charPosition, -1) . "`";
        }
        $message = "PageSql Parsing Error: The token `$textMakingTheError` was unexpected ($position).";
        throw new \RuntimeException($message);

    }


    /**
     *
     * Parent Node
     *
     * On each node, enterRule is called before recursively walking down into child nodes,
     * then {@link PageSqlTreeListener::exitEveryRule()} is called after the recursive call to wind up.
     * Parameters:
     * @param ParserRuleContext $ctx
     */
    public
    function enterEveryRule(ParserRuleContext $ctx): void
    {

        $ruleIndex = $ctx->getRuleIndex();
        if (in_array($ruleIndex, self::STATE_VALUES)) {
            $this->ruleState = $ruleIndex;
        }
        switch ($ruleIndex) {
            case PageSqlParser::RULE_orderBys:
                $this->physicalSql .= "order by\n";
                break;
            case PageSqlParser::RULE_tables:
                $this->physicalSql .= "from\n";
                break;
            case PageSqlParser::RULE_predicates:
                if ($this->type === self::BACKLINKS) {
                    /**
                     * Backlinks query adds already a where clause
                     */
                    $this->physicalSql .= "\tand ";
                } else {
                    $this->physicalSql .= "where\n";
                }
                break;
            case PageSqlParser::RULE_functionNames:
                // Print the function name
                $this->physicalSql .= $ctx->getText();
                break;
            case PageSqlParser::RULE_tableNames:
                // Print the table name
                $tableName = strtolower($ctx->getText());
                $this->type = $tableName;
                if ($tableName === self::BACKLINKS) {
                    $tableName = <<<EOF
    pages p
    join page_references pr on pr.page_id = p.page_id
where
    pr.reference = ?

EOF;
                    $id = PluginUtility::getRequestedWikiId();
                    if (empty($id)) {
                        LogUtility::msg("The page id is unknown. A Page SQL with backlinks should be asked within a page request scope.", LogUtility::LVL_MSG_ERROR, PageSql::CANONICAL);
                    }
                    DokuPath::addRootSeparatorIfNotPresent($id);
                    $this->parameters[] = $id;
                } else {
                    $tableName = "\t$tableName\n";
                }
                $this->physicalSql .= $tableName;
                break;
        }


    }

    /**
     *
     * Parent Node
     *
     * On each node, {@link PageSqlTreeListener::enterEveryRule()} is called before recursively walking down into child nodes,
     * then {@link PageSqlTreeListener::exitEveryRule()} is called after the recursive call to wind up.
     * @param ParserRuleContext $ctx
     */
    public
    function exitEveryRule(ParserRuleContext $ctx): void
    {
        $ruleIndex = $ctx->getRuleIndex();
        switch ($ruleIndex) {
            case PageSqlParser::RULE_predicates:
            case PageSqlParser::RULE_orderBys:
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

    public function getTable(): ?string
    {
        return $this->type;
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
