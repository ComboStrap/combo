<?php


namespace ComboStrap\LogicalSqlAntlr;


use Antlr\Antlr4\Runtime\CommonTokenStream;
use Antlr\Antlr4\Runtime\Error\Listeners\DiagnosticErrorListener;
use Antlr\Antlr4\Runtime\InputStream;
use Antlr\Antlr4\Runtime\Tree\ParseTreeWalker;
use ComboStrap\LogicalSqlAntlr\Gen\LogicalSqlLexer;
use ComboStrap\LogicalSqlAntlr\Gen\LogicalSqlParser;


require_once(__DIR__ . '/../PluginUtility.php');

class SqlParser
{
    private $text;
    /**
     * @var SqlTreeListener
     */
    private $listener;


    public function __construct($text)
    {
        $this->text = $text;
    }

    public static function create(string $string): SqlParser
    {
        $parser = new SqlParser($string);
        $parser->parse();
        return $parser;
    }

    function parse(): SqlParser
    {
        $input = InputStream::fromString($this->text);
        $lexer = new LogicalSqlLexer($input);
        $tokens = new CommonTokenStream($lexer);
        $parser = new LogicalSqlParser($tokens);
        $parser->addErrorListener(new DiagnosticErrorListener());
        $parser->setBuildParseTree(true);
        $tree = $parser->logicalSql();

        /**
         * Performs a walk on the given parse tree starting at the root
         * and going down recursively with depth-first search.
         */
        $this->listener = new SqlTreeListener($lexer, $parser);
        ParseTreeWalker::default()->walk($this->listener, $tree);
        return $this;
    }

    public function getPhysicalSql(): string
    {
        return $this->listener->getPhysicalSql();
    }

    public function getParameters(): array
    {
        return $this->listener->getParameters();
    }

}
