<?php

/*
 * Generated from D:/dokuwiki/lib/plugins/combo/grammar\logicalSql.g4 by ANTLR 4.9.1
 */

namespace ComboStrap\LogicalSqlAntlr\Gen;
use Antlr\Antlr4\Runtime\Tree\ParseTreeListener;

/**
 * This interface defines a complete listener for a parse tree produced by
 * {@see logicalSqlParser}.
 */
interface logicalSqlListener extends ParseTreeListener {
	/**
	 * Enter a parse tree produced by {@see logicalSqlParser::result_column()}.
	 * @param $context The parse tree.
	 */
	public function enterResult_column(Context\Result_columnContext $context) : void;
	/**
	 * Exit a parse tree produced by {@see logicalSqlParser::result_column()}.
	 * @param $context The parse tree.
	 */
	public function exitResult_column(Context\Result_columnContext $context) : void;
	/**
	 * Enter a parse tree produced by {@see logicalSqlParser::column_alias()}.
	 * @param $context The parse tree.
	 */
	public function enterColumn_alias(Context\Column_aliasContext $context) : void;
	/**
	 * Exit a parse tree produced by {@see logicalSqlParser::column_alias()}.
	 * @param $context The parse tree.
	 */
	public function exitColumn_alias(Context\Column_aliasContext $context) : void;
	/**
	 * Enter a parse tree produced by {@see logicalSqlParser::literal_value()}.
	 * @param $context The parse tree.
	 */
	public function enterLiteral_value(Context\Literal_valueContext $context) : void;
	/**
	 * Exit a parse tree produced by {@see logicalSqlParser::literal_value()}.
	 * @param $context The parse tree.
	 */
	public function exitLiteral_value(Context\Literal_valueContext $context) : void;
	/**
	 * Enter a parse tree produced by {@see logicalSqlParser::predicate_expression()}.
	 * @param $context The parse tree.
	 */
	public function enterPredicate_expression(Context\Predicate_expressionContext $context) : void;
	/**
	 * Exit a parse tree produced by {@see logicalSqlParser::predicate_expression()}.
	 * @param $context The parse tree.
	 */
	public function exitPredicate_expression(Context\Predicate_expressionContext $context) : void;
	/**
	 * Enter a parse tree produced by {@see logicalSqlParser::logicalSql()}.
	 * @param $context The parse tree.
	 */
	public function enterLogicalSql(Context\LogicalSqlContext $context) : void;
	/**
	 * Exit a parse tree produced by {@see logicalSqlParser::logicalSql()}.
	 * @param $context The parse tree.
	 */
	public function exitLogicalSql(Context\LogicalSqlContext $context) : void;
	/**
	 * Enter a parse tree produced by {@see logicalSqlParser::table_name()}.
	 * @param $context The parse tree.
	 */
	public function enterTable_name(Context\Table_nameContext $context) : void;
	/**
	 * Exit a parse tree produced by {@see logicalSqlParser::table_name()}.
	 * @param $context The parse tree.
	 */
	public function exitTable_name(Context\Table_nameContext $context) : void;
	/**
	 * Enter a parse tree produced by {@see logicalSqlParser::column_name()}.
	 * @param $context The parse tree.
	 */
	public function enterColumn_name(Context\Column_nameContext $context) : void;
	/**
	 * Exit a parse tree produced by {@see logicalSqlParser::column_name()}.
	 * @param $context The parse tree.
	 */
	public function exitColumn_name(Context\Column_nameContext $context) : void;
	/**
	 * Enter a parse tree produced by {@see logicalSqlParser::any_name()}.
	 * @param $context The parse tree.
	 */
	public function enterAny_name(Context\Any_nameContext $context) : void;
	/**
	 * Exit a parse tree produced by {@see logicalSqlParser::any_name()}.
	 * @param $context The parse tree.
	 */
	public function exitAny_name(Context\Any_nameContext $context) : void;
	/**
	 * Enter a parse tree produced by {@see logicalSqlParser::limit_stmt()}.
	 * @param $context The parse tree.
	 */
	public function enterLimit_stmt(Context\Limit_stmtContext $context) : void;
	/**
	 * Exit a parse tree produced by {@see logicalSqlParser::limit_stmt()}.
	 * @param $context The parse tree.
	 */
	public function exitLimit_stmt(Context\Limit_stmtContext $context) : void;
	/**
	 * Enter a parse tree produced by {@see logicalSqlParser::order_by_stmt()}.
	 * @param $context The parse tree.
	 */
	public function enterOrder_by_stmt(Context\Order_by_stmtContext $context) : void;
	/**
	 * Exit a parse tree produced by {@see logicalSqlParser::order_by_stmt()}.
	 * @param $context The parse tree.
	 */
	public function exitOrder_by_stmt(Context\Order_by_stmtContext $context) : void;
	/**
	 * Enter a parse tree produced by {@see logicalSqlParser::ordering_term()}.
	 * @param $context The parse tree.
	 */
	public function enterOrdering_term(Context\Ordering_termContext $context) : void;
	/**
	 * Exit a parse tree produced by {@see logicalSqlParser::ordering_term()}.
	 * @param $context The parse tree.
	 */
	public function exitOrdering_term(Context\Ordering_termContext $context) : void;
	/**
	 * Enter a parse tree produced by {@see logicalSqlParser::asc_desc()}.
	 * @param $context The parse tree.
	 */
	public function enterAsc_desc(Context\Asc_descContext $context) : void;
	/**
	 * Exit a parse tree produced by {@see logicalSqlParser::asc_desc()}.
	 * @param $context The parse tree.
	 */
	public function exitAsc_desc(Context\Asc_descContext $context) : void;
}