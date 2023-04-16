<?php

/*
 * Generated from D:/dokuwiki/lib/plugins/combo/grammar\PageSql.g4 by ANTLR 4.9.3
 */

namespace ComboStrap\PageSqlParser;
use Antlr\Antlr4\Runtime\Tree\ParseTreeListener;
use ComboStrap\PageSqlParser\Context\ColumnContext;
use ComboStrap\PageSqlParser\Context\ColumnsContext;
use ComboStrap\PageSqlParser\Context\ExpressionContext;
use ComboStrap\PageSqlParser\Context\FunctionNamesContext;
use ComboStrap\PageSqlParser\Context\LimitContext;
use ComboStrap\PageSqlParser\Context\OrderByDefContext;
use ComboStrap\PageSqlParser\Context\OrderBysContext;
use ComboStrap\PageSqlParser\Context\PageSqlContext;
use ComboStrap\PageSqlParser\Context\PatternContext;
use ComboStrap\PageSqlParser\Context\PredicateContext;
use ComboStrap\PageSqlParser\Context\PredicateGroupContext;
use ComboStrap\PageSqlParser\Context\PredicatesContext;
use ComboStrap\PageSqlParser\Context\SqlNamesContext;
use ComboStrap\PageSqlParser\Context\TableNamesContext;
use ComboStrap\PageSqlParser\Context\TablesContext;

/**
 * This interface defines a complete listener for a parse tree produced by
 * {@see PageSqlParser}.
 */
interface PageSqlListener extends ParseTreeListener {
    /**
     * Enter a parse tree produced by {@see PageSqlParser::functionNames()}.
     * @param FunctionNamesContext $context The parse tree.
     */
	public function enterFunctionNames(Context\FunctionNamesContext $context) : void;

    /**
     * Exit a parse tree produced by {@see PageSqlParser::functionNames()}.
     * @param FunctionNamesContext $context The parse tree.
     */
	public function exitFunctionNames(Context\FunctionNamesContext $context) : void;

    /**
     * Enter a parse tree produced by {@see PageSqlParser::tableNames()}.
     * @param TableNamesContext $context The parse tree.
     */
	public function enterTableNames(Context\TableNamesContext $context) : void;

    /**
     * Exit a parse tree produced by {@see PageSqlParser::tableNames()}.
     * @param TableNamesContext $context The parse tree.
     */
	public function exitTableNames(Context\TableNamesContext $context) : void;

    /**
     * Enter a parse tree produced by {@see PageSqlParser::sqlNames()}.
     * @param SqlNamesContext $context The parse tree.
     */
	public function enterSqlNames(Context\SqlNamesContext $context) : void;

    /**
     * Exit a parse tree produced by {@see PageSqlParser::sqlNames()}.
     * @param SqlNamesContext $context The parse tree.
     */
	public function exitSqlNames(Context\SqlNamesContext $context) : void;

    /**
     * Enter a parse tree produced by {@see PageSqlParser::column()}.
     * @param ColumnContext $context The parse tree.
     */
	public function enterColumn(Context\ColumnContext $context) : void;

    /**
     * Exit a parse tree produced by {@see PageSqlParser::column()}.
     * @param ColumnContext $context The parse tree.
     */
	public function exitColumn(Context\ColumnContext $context) : void;

    /**
     * Enter a parse tree produced by {@see PageSqlParser::pattern()}.
     * @param PatternContext $context The parse tree.
     */
	public function enterPattern(Context\PatternContext $context) : void;

    /**
     * Exit a parse tree produced by {@see PageSqlParser::pattern()}.
     * @param PatternContext $context The parse tree.
     */
	public function exitPattern(Context\PatternContext $context) : void;

    /**
     * Enter a parse tree produced by {@see PageSqlParser::expression()}.
     * @param ExpressionContext $context The parse tree.
     */
	public function enterExpression(Context\ExpressionContext $context) : void;

    /**
     * Exit a parse tree produced by {@see PageSqlParser::expression()}.
     * @param ExpressionContext $context The parse tree.
     */
	public function exitExpression(Context\ExpressionContext $context) : void;

    /**
     * Enter a parse tree produced by {@see PageSqlParser::predicate()}.
     * @param PredicateContext $context The parse tree.
     */
	public function enterPredicate(Context\PredicateContext $context) : void;

    /**
     * Exit a parse tree produced by {@see PageSqlParser::predicate()}.
     * @param PredicateContext $context The parse tree.
     */
	public function exitPredicate(Context\PredicateContext $context) : void;

    /**
     * Enter a parse tree produced by {@see PageSqlParser::columns()}.
     * @param ColumnsContext $context The parse tree.
     */
	public function enterColumns(Context\ColumnsContext $context) : void;

    /**
     * Exit a parse tree produced by {@see PageSqlParser::columns()}.
     * @param ColumnsContext $context The parse tree.
     */
	public function exitColumns(Context\ColumnsContext $context) : void;

    /**
     * Enter a parse tree produced by {@see PageSqlParser::predicateGroup()}.
     * @param PredicateGroupContext $context The parse tree.
     */
	public function enterPredicateGroup(Context\PredicateGroupContext $context) : void;

    /**
     * Exit a parse tree produced by {@see PageSqlParser::predicateGroup()}.
     * @param PredicateGroupContext $context The parse tree.
     */
	public function exitPredicateGroup(Context\PredicateGroupContext $context) : void;

    /**
     * Enter a parse tree produced by {@see PageSqlParser::predicates()}.
     * @param PredicatesContext $context The parse tree.
     */
	public function enterPredicates(Context\PredicatesContext $context) : void;

    /**
     * Exit a parse tree produced by {@see PageSqlParser::predicates()}.
     * @param PredicatesContext $context The parse tree.
     */
	public function exitPredicates(Context\PredicatesContext $context) : void;

    /**
     * Enter a parse tree produced by {@see PageSqlParser::tables()}.
     * @param TablesContext $context The parse tree.
     */
	public function enterTables(Context\TablesContext $context) : void;

    /**
     * Exit a parse tree produced by {@see PageSqlParser::tables()}.
     * @param TablesContext $context The parse tree.
     */
	public function exitTables(Context\TablesContext $context) : void;

    /**
     * Enter a parse tree produced by {@see PageSqlParser::limit()}.
     * @param LimitContext $context The parse tree.
     */
	public function enterLimit(Context\LimitContext $context) : void;

    /**
     * Exit a parse tree produced by {@see PageSqlParser::limit()}.
     * @param LimitContext $context The parse tree.
     */
	public function exitLimit(Context\LimitContext $context) : void;

    /**
     * Enter a parse tree produced by {@see PageSqlParser::orderBys()}.
     * @param OrderBysContext $context The parse tree.
     */
	public function enterOrderBys(Context\OrderBysContext $context) : void;

    /**
     * Exit a parse tree produced by {@see PageSqlParser::orderBys()}.
     * @param OrderBysContext $context The parse tree.
     */
	public function exitOrderBys(Context\OrderBysContext $context) : void;

    /**
     * Enter a parse tree produced by {@see PageSqlParser::orderByDef()}.
     * @param OrderByDefContext $context The parse tree.
     */
	public function enterOrderByDef(Context\OrderByDefContext $context) : void;

    /**
     * Exit a parse tree produced by {@see PageSqlParser::orderByDef()}.
     * @param OrderByDefContext $context The parse tree.
     */
	public function exitOrderByDef(Context\OrderByDefContext $context) : void;

    /**
     * Enter a parse tree produced by {@see PageSqlParser::pageSql()}.
     * @param PageSqlContext $context The parse tree.
     */
	public function enterPageSql(Context\PageSqlContext $context) : void;

    /**
     * Exit a parse tree produced by {@see PageSqlParser::pageSql()}.
     * @param PageSqlContext $context The parse tree.
     */
	public function exitPageSql(Context\PageSqlContext $context) : void;
}
