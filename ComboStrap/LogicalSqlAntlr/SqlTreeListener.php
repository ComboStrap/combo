<?php


namespace ComboStrap\LogicalSqlAntlr;


use Antlr\Antlr4\Runtime\ParserRuleContext;
use Antlr\Antlr4\Runtime\Tree\ErrorNode;
use Antlr\Antlr4\Runtime\Tree\ParseTreeListener;
use Antlr\Antlr4\Runtime\Tree\ParseTreeWalker;
use Antlr\Antlr4\Runtime\Tree\TerminalNode;
use ComboStrap\LogicalSqlAntlr\Gen\logicalSqlLexer;
use ComboStrap\LogicalSqlAntlr\Gen\logicalSqlParser;

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
     * SqlTreeListener constructor.
     *
     * @param logicalSqlLexer $lexer
     * @param logicalSqlParser $parser
     */
    public function __construct($lexer,$parser)
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
        $tokeName = $this->getTokenName($node);
        $found = "";
        if(logicalSqlParser::SELECT===$node->getSymbol()->getType()){
            $found = "- select found! ";
        }
        echo "terminal: $tokeName $found - {$node->getText()}\n";
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

        echo "enter - rule name: {$this->getRuleName($ctx)}\n";

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
        echo "exit - rule name: {$this->getRuleName($ctx)}\n";
    }

    private function getRuleName(ParserRuleContext $ctx): string
    {
        $ruleNames = $this->parser->getRuleNames();
        return $ruleNames[$ctx->getRuleIndex()];
    }

    private function getTokenName(TerminalNode $node)
    {
        $token = $node->getSymbol();
        return $this->lexer->getVocabulary()->getSymbolicName($token->getType());
    }
}
