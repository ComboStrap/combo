<?php

/*
 * Generated from D:/dokuwiki/lib/plugins/combo/grammar\LogicalSql.g4 by ANTLR 4.9.1
 */

namespace ComboStrap\LogicalSqlAntlr\Gen;

use Antlr\Antlr4\Runtime\Tree\ParseTreeVisitor;

/**
 * This interface defines a complete generic visitor for a parse tree produced by {@see LogicalSqlParser}.
 */
interface LogicalSqlVisitor extends ParseTreeVisitor
{
	/**
	 * Visit a parse tree produced by {@see LogicalSqlParser::functionNames()}.
	 *
	 * @param Context\FunctionNamesContext $context The parse tree.
	 *
	 * @return mixed The visitor result.
	 */
	public function visitFunctionNames(Context\FunctionNamesContext $context);

	/**
	 * Visit a parse tree produced by {@see LogicalSqlParser::tableNames()}.
	 *
	 * @param Context\TableNamesContext $context The parse tree.
	 *
	 * @return mixed The visitor result.
	 */
	public function visitTableNames(Context\TableNamesContext $context);

	/**
	 * Visit a parse tree produced by {@see LogicalSqlParser::sqlNames()}.
	 *
	 * @param Context\SqlNamesContext $context The parse tree.
	 *
	 * @return mixed The visitor result.
	 */
	public function visitSqlNames(Context\SqlNamesContext $context);

	/**
	 * Visit a parse tree produced by {@see LogicalSqlParser::column()}.
	 *
	 * @param Context\ColumnContext $context The parse tree.
	 *
	 * @return mixed The visitor result.
	 */
	public function visitColumn(Context\ColumnContext $context);

	/**
	 * Visit a parse tree produced by {@see LogicalSqlParser::pattern()}.
	 *
	 * @param Context\PatternContext $context The parse tree.
	 *
	 * @return mixed The visitor result.
	 */
	public function visitPattern(Context\PatternContext $context);

	/**
	 * Visit a parse tree produced by {@see LogicalSqlParser::expression()}.
	 *
	 * @param Context\ExpressionContext $context The parse tree.
	 *
	 * @return mixed The visitor result.
	 */
	public function visitExpression(Context\ExpressionContext $context);

	/**
	 * Visit a parse tree produced by {@see LogicalSqlParser::predicate()}.
	 *
	 * @param Context\PredicateContext $context The parse tree.
	 *
	 * @return mixed The visitor result.
	 */
	public function visitPredicate(Context\PredicateContext $context);

	/**
	 * Visit a parse tree produced by {@see LogicalSqlParser::columns()}.
	 *
	 * @param Context\ColumnsContext $context The parse tree.
	 *
	 * @return mixed The visitor result.
	 */
	public function visitColumns(Context\ColumnsContext $context);

	/**
	 * Visit a parse tree produced by {@see LogicalSqlParser::predicates()}.
	 *
	 * @param Context\PredicatesContext $context The parse tree.
	 *
	 * @return mixed The visitor result.
	 */
	public function visitPredicates(Context\PredicatesContext $context);

	/**
	 * Visit a parse tree produced by {@see LogicalSqlParser::tables()}.
	 *
	 * @param Context\TablesContext $context The parse tree.
	 *
	 * @return mixed The visitor result.
	 */
	public function visitTables(Context\TablesContext $context);

	/**
	 * Visit a parse tree produced by {@see LogicalSqlParser::limit()}.
	 *
	 * @param Context\LimitContext $context The parse tree.
	 *
	 * @return mixed The visitor result.
	 */
	public function visitLimit(Context\LimitContext $context);

	/**
	 * Visit a parse tree produced by {@see LogicalSqlParser::orderBys()}.
	 *
	 * @param Context\OrderBysContext $context The parse tree.
	 *
	 * @return mixed The visitor result.
	 */
	public function visitOrderBys(Context\OrderBysContext $context);

	/**
	 * Visit a parse tree produced by {@see LogicalSqlParser::orderByDef()}.
	 *
	 * @param Context\OrderByDefContext $context The parse tree.
	 *
	 * @return mixed The visitor result.
	 */
	public function visitOrderByDef(Context\OrderByDefContext $context);

	/**
	 * Visit a parse tree produced by {@see LogicalSqlParser::logicalSql()}.
	 *
	 * @param Context\LogicalSqlContext $context The parse tree.
	 *
	 * @return mixed The visitor result.
	 */
	public function visitLogicalSql(Context\LogicalSqlContext $context);
}