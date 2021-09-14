<?php

/*
 * Generated from D:/dokuwiki/lib/plugins/combo/grammar\LogicalSql.g4 by ANTLR 4.9.1
 */

namespace ComboStrap\LogicalSqlAntlr\Gen;
use Antlr\Antlr4\Runtime\Tree\ParseTreeListener;

/**
 * This interface defines a complete listener for a parse tree produced by
 * {@see LogicalSqlParser}.
 */
interface LogicalSqlListener extends ParseTreeListener {
	/**
	 * Enter a parse tree produced by {@see LogicalSqlParser::column()}.
	 * @param $context The parse tree.
	 */
	public function enterColumn(Context\ColumnContext $context) : void;
	/**
	 * Exit a parse tree produced by {@see LogicalSqlParser::column()}.
	 * @param $context The parse tree.
	 */
	public function exitColumn(Context\ColumnContext $context) : void;
	/**
	 * Enter a parse tree produced by {@see LogicalSqlParser::columnAlias()}.
	 * @param $context The parse tree.
	 */
	public function enterColumnAlias(Context\ColumnAliasContext $context) : void;
	/**
	 * Exit a parse tree produced by {@see LogicalSqlParser::columnAlias()}.
	 * @param $context The parse tree.
	 */
	public function exitColumnAlias(Context\ColumnAliasContext $context) : void;
	/**
	 * Enter a parse tree produced by {@see LogicalSqlParser::literalValue()}.
	 * @param $context The parse tree.
	 */
	public function enterLiteralValue(Context\LiteralValueContext $context) : void;
	/**
	 * Exit a parse tree produced by {@see LogicalSqlParser::literalValue()}.
	 * @param $context The parse tree.
	 */
	public function exitLiteralValue(Context\LiteralValueContext $context) : void;
	/**
	 * Enter a parse tree produced by {@see LogicalSqlParser::predicate()}.
	 * @param $context The parse tree.
	 */
	public function enterPredicate(Context\PredicateContext $context) : void;
	/**
	 * Exit a parse tree produced by {@see LogicalSqlParser::predicate()}.
	 * @param $context The parse tree.
	 */
	public function exitPredicate(Context\PredicateContext $context) : void;
	/**
	 * Enter a parse tree produced by {@see LogicalSqlParser::columns()}.
	 * @param $context The parse tree.
	 */
	public function enterColumns(Context\ColumnsContext $context) : void;
	/**
	 * Exit a parse tree produced by {@see LogicalSqlParser::columns()}.
	 * @param $context The parse tree.
	 */
	public function exitColumns(Context\ColumnsContext $context) : void;
	/**
	 * Enter a parse tree produced by {@see LogicalSqlParser::predicates()}.
	 * @param $context The parse tree.
	 */
	public function enterPredicates(Context\PredicatesContext $context) : void;
	/**
	 * Exit a parse tree produced by {@see LogicalSqlParser::predicates()}.
	 * @param $context The parse tree.
	 */
	public function exitPredicates(Context\PredicatesContext $context) : void;
	/**
	 * Enter a parse tree produced by {@see LogicalSqlParser::where()}.
	 * @param $context The parse tree.
	 */
	public function enterWhere(Context\WhereContext $context) : void;
	/**
	 * Exit a parse tree produced by {@see LogicalSqlParser::where()}.
	 * @param $context The parse tree.
	 */
	public function exitWhere(Context\WhereContext $context) : void;
	/**
	 * Enter a parse tree produced by {@see LogicalSqlParser::tables()}.
	 * @param $context The parse tree.
	 */
	public function enterTables(Context\TablesContext $context) : void;
	/**
	 * Exit a parse tree produced by {@see LogicalSqlParser::tables()}.
	 * @param $context The parse tree.
	 */
	public function exitTables(Context\TablesContext $context) : void;
	/**
	 * Enter a parse tree produced by {@see LogicalSqlParser::logicalSql()}.
	 * @param $context The parse tree.
	 */
	public function enterLogicalSql(Context\LogicalSqlContext $context) : void;
	/**
	 * Exit a parse tree produced by {@see LogicalSqlParser::logicalSql()}.
	 * @param $context The parse tree.
	 */
	public function exitLogicalSql(Context\LogicalSqlContext $context) : void;
	/**
	 * Enter a parse tree produced by {@see LogicalSqlParser::tabelName()}.
	 * @param $context The parse tree.
	 */
	public function enterTabelName(Context\TabelNameContext $context) : void;
	/**
	 * Exit a parse tree produced by {@see LogicalSqlParser::tabelName()}.
	 * @param $context The parse tree.
	 */
	public function exitTabelName(Context\TabelNameContext $context) : void;
	/**
	 * Enter a parse tree produced by {@see LogicalSqlParser::columnName()}.
	 * @param $context The parse tree.
	 */
	public function enterColumnName(Context\ColumnNameContext $context) : void;
	/**
	 * Exit a parse tree produced by {@see LogicalSqlParser::columnName()}.
	 * @param $context The parse tree.
	 */
	public function exitColumnName(Context\ColumnNameContext $context) : void;
	/**
	 * Enter a parse tree produced by {@see LogicalSqlParser::limit()}.
	 * @param $context The parse tree.
	 */
	public function enterLimit(Context\LimitContext $context) : void;
	/**
	 * Exit a parse tree produced by {@see LogicalSqlParser::limit()}.
	 * @param $context The parse tree.
	 */
	public function exitLimit(Context\LimitContext $context) : void;
	/**
	 * Enter a parse tree produced by {@see LogicalSqlParser::orderBy()}.
	 * @param $context The parse tree.
	 */
	public function enterOrderBy(Context\OrderByContext $context) : void;
	/**
	 * Exit a parse tree produced by {@see LogicalSqlParser::orderBy()}.
	 * @param $context The parse tree.
	 */
	public function exitOrderBy(Context\OrderByContext $context) : void;
	/**
	 * Enter a parse tree produced by {@see LogicalSqlParser::orderByDef()}.
	 * @param $context The parse tree.
	 */
	public function enterOrderByDef(Context\OrderByDefContext $context) : void;
	/**
	 * Exit a parse tree produced by {@see LogicalSqlParser::orderByDef()}.
	 * @param $context The parse tree.
	 */
	public function exitOrderByDef(Context\OrderByDefContext $context) : void;
	/**
	 * Enter a parse tree produced by {@see LogicalSqlParser::order()}.
	 * @param $context The parse tree.
	 */
	public function enterOrder(Context\OrderContext $context) : void;
	/**
	 * Exit a parse tree produced by {@see LogicalSqlParser::order()}.
	 * @param $context The parse tree.
	 */
	public function exitOrder(Context\OrderContext $context) : void;
}