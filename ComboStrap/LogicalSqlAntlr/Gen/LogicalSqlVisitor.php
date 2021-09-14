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
	 * Visit a parse tree produced by {@see LogicalSqlParser::column()}.
	 *
	 * @param Context\ColumnContext $context The parse tree.
	 *
	 * @return mixed The visitor result.
	 */
	public function visitColumn(Context\ColumnContext $context);

	/**
	 * Visit a parse tree produced by {@see LogicalSqlParser::columnAlias()}.
	 *
	 * @param Context\ColumnAliasContext $context The parse tree.
	 *
	 * @return mixed The visitor result.
	 */
	public function visitColumnAlias(Context\ColumnAliasContext $context);

	/**
	 * Visit a parse tree produced by {@see LogicalSqlParser::literalValue()}.
	 *
	 * @param Context\LiteralValueContext $context The parse tree.
	 *
	 * @return mixed The visitor result.
	 */
	public function visitLiteralValue(Context\LiteralValueContext $context);

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
	 * Visit a parse tree produced by {@see LogicalSqlParser::where()}.
	 *
	 * @param Context\WhereContext $context The parse tree.
	 *
	 * @return mixed The visitor result.
	 */
	public function visitWhere(Context\WhereContext $context);

	/**
	 * Visit a parse tree produced by {@see LogicalSqlParser::tables()}.
	 *
	 * @param Context\TablesContext $context The parse tree.
	 *
	 * @return mixed The visitor result.
	 */
	public function visitTables(Context\TablesContext $context);

	/**
	 * Visit a parse tree produced by {@see LogicalSqlParser::logicalSql()}.
	 *
	 * @param Context\LogicalSqlContext $context The parse tree.
	 *
	 * @return mixed The visitor result.
	 */
	public function visitLogicalSql(Context\LogicalSqlContext $context);

	/**
	 * Visit a parse tree produced by {@see LogicalSqlParser::tabelName()}.
	 *
	 * @param Context\TabelNameContext $context The parse tree.
	 *
	 * @return mixed The visitor result.
	 */
	public function visitTabelName(Context\TabelNameContext $context);

	/**
	 * Visit a parse tree produced by {@see LogicalSqlParser::columnName()}.
	 *
	 * @param Context\ColumnNameContext $context The parse tree.
	 *
	 * @return mixed The visitor result.
	 */
	public function visitColumnName(Context\ColumnNameContext $context);

	/**
	 * Visit a parse tree produced by {@see LogicalSqlParser::limit()}.
	 *
	 * @param Context\LimitContext $context The parse tree.
	 *
	 * @return mixed The visitor result.
	 */
	public function visitLimit(Context\LimitContext $context);

	/**
	 * Visit a parse tree produced by {@see LogicalSqlParser::orderBy()}.
	 *
	 * @param Context\OrderByContext $context The parse tree.
	 *
	 * @return mixed The visitor result.
	 */
	public function visitOrderBy(Context\OrderByContext $context);

	/**
	 * Visit a parse tree produced by {@see LogicalSqlParser::orderByDef()}.
	 *
	 * @param Context\OrderByDefContext $context The parse tree.
	 *
	 * @return mixed The visitor result.
	 */
	public function visitOrderByDef(Context\OrderByDefContext $context);

	/**
	 * Visit a parse tree produced by {@see LogicalSqlParser::order()}.
	 *
	 * @param Context\OrderContext $context The parse tree.
	 *
	 * @return mixed The visitor result.
	 */
	public function visitOrder(Context\OrderContext $context);
}