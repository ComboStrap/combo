<?php


namespace ComboStrap;


use Antlr\Antlr4\Runtime\CommonTokenStream;
use Antlr\Antlr4\Runtime\Error\Listeners\DiagnosticErrorListener;
use Antlr\Antlr4\Runtime\InputStream;
use Antlr\Antlr4\Runtime\Tree\ParseTreeWalker;
use ComboStrap\PageSqlParser\PageSqlLexer;
use ComboStrap\PageSqlParser\PageSqlParser;


require_once(__DIR__ . '/PluginUtility.php');

class PageSql
{
    const CANONICAL = "sql";

    private $sql;
    /**
     * @var PageSqlTreeListener
     */
    private $listener;


    public function __construct($text)
    {
        $this->sql = $text;
    }

    /**
     * @param string $string
     * @param MarkupPath|null $contextualPage - the page where the sql applies to
     * @return PageSql
     */
    public static function create(string $string, MarkupPath $contextualPage = null): PageSql
    {
        $parser = new PageSql($string);
        $parser->parse($contextualPage);
        return $parser;
    }

    /**
     * @param MarkupPath|null $contextualPage - for the page
     * @return $this
     */
    function parse(MarkupPath $contextualPage = null): PageSql
    {
        $input = InputStream::fromString($this->sql);
        $lexer = new PageSqlLexer($input);
        $tokens = new CommonTokenStream($lexer);
        $parser = new PageSqlParser($tokens);
        $parser->addErrorListener(new DiagnosticErrorListener());
        $parser->setBuildParseTree(true);
        $tree = $parser->pageSql();

        /**
         * Performs a walk on the given parse tree starting at the root
         * and going down recursively with depth-first search.
         */
        $this->listener = new PageSqlTreeListener($lexer, $parser, $this->sql, $contextualPage);
        ParseTreeWalker::default()->walk($this->listener, $tree);
        return $this;
    }

    public function getExecutableSql(): string
    {
        return $this->listener->getPhysicalSql();
    }

    public function getParameters(): array
    {
        return $this->listener->getParameters();
    }

    public function getColumns(): array
    {
        return $this->listener->getColumns();
    }

    public function __toString()
    {
        return $this->sql;
    }

    public function getTable(): ?string
    {
        return $this->listener->getTable();
    }

}
