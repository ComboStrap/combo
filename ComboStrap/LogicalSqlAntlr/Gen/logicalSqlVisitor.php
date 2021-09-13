<?php

/*
 * Generated from D:/dokuwiki/lib/plugins/combo/grammar\logicalSql.g4 by ANTLR 4.9.1
 */

namespace ComboStrap\LogicalSqlAntlr\Gen;

use Antlr\Antlr4\Runtime\Tree\ParseTreeVisitor;

/**
 * This interface defines a complete generic visitor for a parse tree produced by {@see logicalSqlParser}.
 */
interface logicalSqlVisitor extends ParseTreeVisitor
{
	/**
	 * Visit a parse tree produced by {@see logicalSqlParser::result_column()}.
	 *
	 * @param Context\Result_columnContext $context The parse tree.
	 *
	 * @return mixed The visitor result.
	 */
	public function visitResult_column(Context\Result_columnContext $context);

	/**
	 * Visit a parse tree produced by {@see logicalSqlParser::column_alias()}.
	 *
	 * @param Context\Column_aliasContext $context The parse tree.
	 *
	 * @return mixed The visitor result.
	 */
	public function visitColumn_alias(Context\Column_aliasContext $context);

	/**
	 * Visit a parse tree produced by {@see logicalSqlParser::literal_value()}.
	 *
	 * @param Context\Literal_valueContext $context The parse tree.
	 *
	 * @return mixed The visitor result.
	 */
	public function visitLiteral_value(Context\Literal_valueContext $context);

	/**
	 * Visit a parse tree produced by {@see logicalSqlParser::predicate_expression()}.
	 *
	 * @param Context\Predicate_expressionContext $context The parse tree.
	 *
	 * @return mixed The visitor result.
	 */
	public function visitPredicate_expression(Context\Predicate_expressionContext $context);

	/**
	 * Visit a parse tree produced by {@see logicalSqlParser::logicalSql()}.
	 *
	 * @param Context\LogicalSqlContext $context The parse tree.
	 *
	 * @return mixed The visitor result.
	 */
	public function visitLogicalSql(Context\LogicalSqlContext $context);

	/**
	 * Visit a parse tree produced by {@see logicalSqlParser::table_name()}.
	 *
	 * @param Context\Table_nameContext $context The parse tree.
	 *
	 * @return mixed The visitor result.
	 */
	public function visitTable_name(Context\Table_nameContext $context);

	/**
	 * Visit a parse tree produced by {@see logicalSqlParser::column_name()}.
	 *
	 * @param Context\Column_nameContext $context The parse tree.
	 *
	 * @return mixed The visitor result.
	 */
	public function visitColumn_name(Context\Column_nameContext $context);

	/**
	 * Visit a parse tree produced by {@see logicalSqlParser::any_name()}.
	 *
	 * @param Context\Any_nameContext $context The parse tree.
	 *
	 * @return mixed The visitor result.
	 */
	public function visitAny_name(Context\Any_nameContext $context);

	/**
	 * Visit a parse tree produced by {@see logicalSqlParser::limit_stmt()}.
	 *
	 * @param Context\Limit_stmtContext $context The parse tree.
	 *
	 * @return mixed The visitor result.
	 */
	public function visitLimit_stmt(Context\Limit_stmtContext $context);

	/**
	 * Visit a parse tree produced by {@see logicalSqlParser::order_by_stmt()}.
	 *
	 * @param Context\Order_by_stmtContext $context The parse tree.
	 *
	 * @return mixed The visitor result.
	 */
	public function visitOrder_by_stmt(Context\Order_by_stmtContext $context);

	/**
	 * Visit a parse tree produced by {@see logicalSqlParser::ordering_term()}.
	 *
	 * @param Context\Ordering_termContext $context The parse tree.
	 *
	 * @return mixed The visitor result.
	 */
	public function visitOrdering_term(Context\Ordering_termContext $context);

	/**
	 * Visit a parse tree produced by {@see logicalSqlParser::asc_desc()}.
	 *
	 * @param Context\Asc_descContext $context The parse tree.
	 *
	 * @return mixed The visitor result.
	 */
	public function visitAsc_desc(Context\Asc_descContext $context);
}