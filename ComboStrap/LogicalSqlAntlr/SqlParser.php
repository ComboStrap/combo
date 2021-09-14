<?php


namespace ComboStrap\LogicalSqlAntlr;


use Antlr\Antlr4\Runtime\CommonTokenStream;
use Antlr\Antlr4\Runtime\Error\Listeners\DiagnosticErrorListener;
use Antlr\Antlr4\Runtime\InputStream;
use Antlr\Antlr4\Runtime\Tree\ParseTreeWalker;
use ComboStrap\LogicalSqlAntlr\Gen\logicalSqlLexer;
use ComboStrap\LogicalSqlAntlr\Gen\logicalSqlParser;


require_once(__DIR__ . '/../PluginUtility.php');

class SqlParser
{
    private $text;


    public function __construct($text)
    {
        $this->text = $text;
    }

    public static function create(string $string): SqlParser
    {
        return new SqlParser($string);
    }

    function parse(){
        $input = InputStream::fromString($this->text);
        $lexer = new logicalSqlLexer($input);
        $tokens = new CommonTokenStream($lexer);
        $parser = new logicalSqlParser($tokens);
        $parser->addErrorListener(new DiagnosticErrorListener());
        $parser->setBuildParseTree(true);
        $tree = $parser->logicalSql();

        /**
         * Performs a walk on the given parse tree starting at the root
         * and going down recursively with depth-first search.
         */
        $listener = new sqlTreeListener($lexer, $parser);
        ParseTreeWalker::default()->walk($listener, $tree);
        return $listener->getPhysicalSql();
    }

}
