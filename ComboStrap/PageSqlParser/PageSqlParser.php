<?php

/*
 * Generated from D:/dokuwiki/lib/plugins/combo/grammar\PageSql.g4 by ANTLR 4.9.3
 */

namespace ComboStrap\PageSqlParser {
	use Antlr\Antlr4\Runtime\Atn\ATN;
	use Antlr\Antlr4\Runtime\Atn\ATNDeserializer;
	use Antlr\Antlr4\Runtime\Atn\ParserATNSimulator;
	use Antlr\Antlr4\Runtime\Dfa\DFA;
	use Antlr\Antlr4\Runtime\Error\Exceptions\FailedPredicateException;
	use Antlr\Antlr4\Runtime\Error\Exceptions\NoViableAltException;
	use Antlr\Antlr4\Runtime\PredictionContexts\PredictionContextCache;
	use Antlr\Antlr4\Runtime\Error\Exceptions\RecognitionException;
	use Antlr\Antlr4\Runtime\RuleContext;
	use Antlr\Antlr4\Runtime\Token;
	use Antlr\Antlr4\Runtime\TokenStream;
	use Antlr\Antlr4\Runtime\Vocabulary;
	use Antlr\Antlr4\Runtime\VocabularyImpl;
	use Antlr\Antlr4\Runtime\RuntimeMetaData;
	use Antlr\Antlr4\Runtime\Parser;

	final class PageSqlParser extends Parser
	{
		public const SCOL = 1, DOT = 2, LPAREN = 3, RPAREN = 4, LSQUARE = 5, RSQUARE = 6, 
               LCURLY = 7, RCURLY = 8, COMMA = 9, BITWISEXOR = 10, DOLLAR = 11, 
               EQUAL = 12, STAR = 13, PLUS = 14, MINUS = 15, TILDE = 16, 
               PIPE2 = 17, DIV = 18, MOD = 19, LT2 = 20, GT2 = 21, AMP = 22, 
               PIPE = 23, QUESTION = 24, LESS_THAN = 25, LESS_THAN_OR_EQUAL = 26, 
               GREATER_THAN = 27, GREATER_THAN_OR_EQUAL = 28, EQ = 29, NOT_EQUAL = 30, 
               NOT_EQ2 = 31, AND = 32, AS = 33, ASC = 34, BETWEEN = 35, 
               BY = 36, DESC = 37, ESCAPE = 38, FALSE = 39, FROM = 40, GLOB = 41, 
               IN = 42, IS = 43, ISNULL = 44, LIKE = 45, LIMIT = 46, NOT = 47, 
               NOTNULL = 48, NOW = 49, NULL = 50, OR = 51, ORDER = 52, SELECT = 53, 
               TRUE = 54, WHERE = 55, RANDOM = 56, DATE = 57, DATETIME = 58, 
               PAGES = 59, BACKLINKS = 60, DESCENDANTS = 61, StringLiteral = 62, 
               CharSetLiteral = 63, IntegralLiteral = 64, Number = 65, NumberLiteral = 66, 
               ByteLengthLiteral = 67, SqlName = 68, SPACES = 69;

		public const RULE_functionNames = 0, RULE_constantNames = 1, RULE_tableNames = 2, 
               RULE_sqlNames = 3, RULE_column = 4, RULE_pattern = 5, RULE_expression = 6, 
               RULE_predicate = 7, RULE_columns = 8, RULE_predicateGroup = 9, 
               RULE_predicates = 10, RULE_tables = 11, RULE_limit = 12, 
               RULE_orderBys = 13, RULE_orderByDef = 14, RULE_pageSql = 15;

		/**
		 * @var array<string>
		 */
		public const RULE_NAMES = [
			'functionNames', 'constantNames', 'tableNames', 'sqlNames', 'column', 
			'pattern', 'expression', 'predicate', 'columns', 'predicateGroup', 'predicates', 
			'tables', 'limit', 'orderBys', 'orderByDef', 'pageSql'
		];

		/**
		 * @var array<string|null>
		 */
		private const LITERAL_NAMES = [
		    null, "';'", "'.'", "'('", "')'", "'['", "']'", "'{'", "'}'", "','", 
		    "'^'", "'\$'", "'='", "'*'", "'+'", "'-'", "'~'", "'||'", "'/'", "'%'", 
		    "'<<'", "'>>'", "'&'", "'|'", "'?'", "'<'", "'<='", "'>'", "'>='", 
		    "'=='", "'!='", "'<>'"
		];

		/**
		 * @var array<string>
		 */
		private const SYMBOLIC_NAMES = [
		    null, "SCOL", "DOT", "LPAREN", "RPAREN", "LSQUARE", "RSQUARE", "LCURLY", 
		    "RCURLY", "COMMA", "BITWISEXOR", "DOLLAR", "EQUAL", "STAR", "PLUS", 
		    "MINUS", "TILDE", "PIPE2", "DIV", "MOD", "LT2", "GT2", "AMP", "PIPE", 
		    "QUESTION", "LESS_THAN", "LESS_THAN_OR_EQUAL", "GREATER_THAN", "GREATER_THAN_OR_EQUAL", 
		    "EQ", "NOT_EQUAL", "NOT_EQ2", "AND", "AS", "ASC", "BETWEEN", "BY", 
		    "DESC", "ESCAPE", "FALSE", "FROM", "GLOB", "IN", "IS", "ISNULL", "LIKE", 
		    "LIMIT", "NOT", "NOTNULL", "NOW", "NULL", "OR", "ORDER", "SELECT", 
		    "TRUE", "WHERE", "RANDOM", "DATE", "DATETIME", "PAGES", "BACKLINKS", 
		    "DESCENDANTS", "StringLiteral", "CharSetLiteral", "IntegralLiteral", 
		    "Number", "NumberLiteral", "ByteLengthLiteral", "SqlName", "SPACES"
		];

		/**
		 * @var string
		 */
		private const SERIALIZED_ATN =
			"\u{3}\u{608B}\u{A72A}\u{8133}\u{B9ED}\u{417C}\u{3BE7}\u{7786}\u{5964}" .
		    "\u{3}\u{47}\u{C7}\u{4}\u{2}\u{9}\u{2}\u{4}\u{3}\u{9}\u{3}\u{4}\u{4}" .
		    "\u{9}\u{4}\u{4}\u{5}\u{9}\u{5}\u{4}\u{6}\u{9}\u{6}\u{4}\u{7}\u{9}" .
		    "\u{7}\u{4}\u{8}\u{9}\u{8}\u{4}\u{9}\u{9}\u{9}\u{4}\u{A}\u{9}\u{A}" .
		    "\u{4}\u{B}\u{9}\u{B}\u{4}\u{C}\u{9}\u{C}\u{4}\u{D}\u{9}\u{D}\u{4}" .
		    "\u{E}\u{9}\u{E}\u{4}\u{F}\u{9}\u{F}\u{4}\u{10}\u{9}\u{10}\u{4}\u{11}" .
		    "\u{9}\u{11}\u{3}\u{2}\u{3}\u{2}\u{3}\u{3}\u{3}\u{3}\u{3}\u{4}\u{3}" .
		    "\u{4}\u{3}\u{5}\u{3}\u{5}\u{3}\u{6}\u{3}\u{6}\u{3}\u{6}\u{5}\u{6}" .
		    "\u{2E}\u{A}\u{6}\u{3}\u{6}\u{3}\u{6}\u{3}\u{6}\u{5}\u{6}\u{33}\u{A}" .
		    "\u{6}\u{5}\u{6}\u{35}\u{A}\u{6}\u{3}\u{7}\u{3}\u{7}\u{3}\u{8}\u{3}" .
		    "\u{8}\u{3}\u{8}\u{3}\u{8}\u{3}\u{8}\u{5}\u{8}\u{3E}\u{A}\u{8}\u{3}" .
		    "\u{8}\u{3}\u{8}\u{3}\u{8}\u{5}\u{8}\u{43}\u{A}\u{8}\u{3}\u{8}\u{3}" .
		    "\u{8}\u{7}\u{8}\u{47}\u{A}\u{8}\u{C}\u{8}\u{E}\u{8}\u{4A}\u{B}\u{8}" .
		    "\u{3}\u{8}\u{3}\u{8}\u{5}\u{8}\u{4E}\u{A}\u{8}\u{3}\u{9}\u{3}\u{9}" .
		    "\u{3}\u{9}\u{3}\u{9}\u{5}\u{9}\u{54}\u{A}\u{9}\u{3}\u{9}\u{3}\u{9}" .
		    "\u{3}\u{9}\u{3}\u{9}\u{5}\u{9}\u{5A}\u{A}\u{9}\u{3}\u{9}\u{3}\u{9}" .
		    "\u{5}\u{9}\u{5E}\u{A}\u{9}\u{3}\u{9}\u{5}\u{9}\u{61}\u{A}\u{9}\u{3}" .
		    "\u{9}\u{3}\u{9}\u{3}\u{9}\u{3}\u{9}\u{3}\u{9}\u{3}\u{9}\u{5}\u{9}" .
		    "\u{69}\u{A}\u{9}\u{3}\u{9}\u{3}\u{9}\u{3}\u{9}\u{3}\u{9}\u{3}\u{9}" .
		    "\u{7}\u{9}\u{70}\u{A}\u{9}\u{C}\u{9}\u{E}\u{9}\u{73}\u{B}\u{9}\u{5}" .
		    "\u{9}\u{75}\u{A}\u{9}\u{3}\u{9}\u{5}\u{9}\u{78}\u{A}\u{9}\u{3}\u{A}" .
		    "\u{3}\u{A}\u{3}\u{A}\u{7}\u{A}\u{7D}\u{A}\u{A}\u{C}\u{A}\u{E}\u{A}" .
		    "\u{80}\u{B}\u{A}\u{3}\u{B}\u{3}\u{B}\u{3}\u{B}\u{3}\u{B}\u{7}\u{B}" .
		    "\u{86}\u{A}\u{B}\u{C}\u{B}\u{E}\u{B}\u{89}\u{B}\u{B}\u{3}\u{B}\u{3}" .
		    "\u{B}\u{3}\u{C}\u{3}\u{C}\u{3}\u{C}\u{5}\u{C}\u{90}\u{A}\u{C}\u{3}" .
		    "\u{C}\u{3}\u{C}\u{3}\u{C}\u{5}\u{C}\u{95}\u{A}\u{C}\u{7}\u{C}\u{97}" .
		    "\u{A}\u{C}\u{C}\u{C}\u{E}\u{C}\u{9A}\u{B}\u{C}\u{3}\u{D}\u{3}\u{D}" .
		    "\u{3}\u{D}\u{3}\u{E}\u{3}\u{E}\u{3}\u{E}\u{3}\u{F}\u{3}\u{F}\u{3}" .
		    "\u{F}\u{3}\u{F}\u{3}\u{F}\u{3}\u{F}\u{7}\u{F}\u{A8}\u{A}\u{F}\u{C}" .
		    "\u{F}\u{E}\u{F}\u{AB}\u{B}\u{F}\u{5}\u{F}\u{AD}\u{A}\u{F}\u{3}\u{10}" .
		    "\u{3}\u{10}\u{5}\u{10}\u{B1}\u{A}\u{10}\u{3}\u{11}\u{3}\u{11}\u{5}" .
		    "\u{11}\u{B5}\u{A}\u{11}\u{3}\u{11}\u{3}\u{11}\u{5}\u{11}\u{B9}\u{A}" .
		    "\u{11}\u{3}\u{11}\u{5}\u{11}\u{BC}\u{A}\u{11}\u{3}\u{11}\u{5}\u{11}" .
		    "\u{BF}\u{A}\u{11}\u{3}\u{11}\u{5}\u{11}\u{C2}\u{A}\u{11}\u{3}\u{11}" .
		    "\u{5}\u{11}\u{C5}\u{A}\u{11}\u{3}\u{11}\u{2}\u{2}\u{12}\u{2}\u{4}" .
		    "\u{6}\u{8}\u{A}\u{C}\u{E}\u{10}\u{12}\u{14}\u{16}\u{18}\u{1A}\u{1C}" .
		    "\u{1E}\u{20}\u{2}\u{9}\u{3}\u{2}\u{3B}\u{3C}\u{3}\u{2}\u{3D}\u{3F}" .
		    "\u{4}\u{2}\u{43}\u{43}\u{46}\u{46}\u{4}\u{2}\u{40}\u{40}\u{44}\u{44}" .
		    "\u{5}\u{2}\u{E}\u{E}\u{1B}\u{1E}\u{20}\u{20}\u{4}\u{2}\u{22}\u{22}" .
		    "\u{35}\u{35}\u{4}\u{2}\u{24}\u{24}\u{27}\u{27}\u{2}\u{D9}\u{2}\u{22}" .
		    "\u{3}\u{2}\u{2}\u{2}\u{4}\u{24}\u{3}\u{2}\u{2}\u{2}\u{6}\u{26}\u{3}" .
		    "\u{2}\u{2}\u{2}\u{8}\u{28}\u{3}\u{2}\u{2}\u{2}\u{A}\u{2A}\u{3}\u{2}" .
		    "\u{2}\u{2}\u{C}\u{36}\u{3}\u{2}\u{2}\u{2}\u{E}\u{4D}\u{3}\u{2}\u{2}" .
		    "\u{2}\u{10}\u{4F}\u{3}\u{2}\u{2}\u{2}\u{12}\u{79}\u{3}\u{2}\u{2}\u{2}" .
		    "\u{14}\u{81}\u{3}\u{2}\u{2}\u{2}\u{16}\u{8C}\u{3}\u{2}\u{2}\u{2}\u{18}" .
		    "\u{9B}\u{3}\u{2}\u{2}\u{2}\u{1A}\u{9E}\u{3}\u{2}\u{2}\u{2}\u{1C}\u{A1}" .
		    "\u{3}\u{2}\u{2}\u{2}\u{1E}\u{AE}\u{3}\u{2}\u{2}\u{2}\u{20}\u{B2}\u{3}" .
		    "\u{2}\u{2}\u{2}\u{22}\u{23}\u{9}\u{2}\u{2}\u{2}\u{23}\u{3}\u{3}\u{2}" .
		    "\u{2}\u{2}\u{24}\u{25}\u{7}\u{33}\u{2}\u{2}\u{25}\u{5}\u{3}\u{2}\u{2}" .
		    "\u{2}\u{26}\u{27}\u{9}\u{3}\u{2}\u{2}\u{27}\u{7}\u{3}\u{2}\u{2}\u{2}" .
		    "\u{28}\u{29}\u{9}\u{4}\u{2}\u{2}\u{29}\u{9}\u{3}\u{2}\u{2}\u{2}\u{2A}" .
		    "\u{2D}\u{5}\u{8}\u{5}\u{2}\u{2B}\u{2C}\u{7}\u{4}\u{2}\u{2}\u{2C}\u{2E}" .
		    "\u{5}\u{8}\u{5}\u{2}\u{2D}\u{2B}\u{3}\u{2}\u{2}\u{2}\u{2D}\u{2E}\u{3}" .
		    "\u{2}\u{2}\u{2}\u{2E}\u{34}\u{3}\u{2}\u{2}\u{2}\u{2F}\u{32}\u{7}\u{23}" .
		    "\u{2}\u{2}\u{30}\u{33}\u{5}\u{8}\u{5}\u{2}\u{31}\u{33}\u{7}\u{40}" .
		    "\u{2}\u{2}\u{32}\u{30}\u{3}\u{2}\u{2}\u{2}\u{32}\u{31}\u{3}\u{2}\u{2}" .
		    "\u{2}\u{33}\u{35}\u{3}\u{2}\u{2}\u{2}\u{34}\u{2F}\u{3}\u{2}\u{2}\u{2}" .
		    "\u{34}\u{35}\u{3}\u{2}\u{2}\u{2}\u{35}\u{B}\u{3}\u{2}\u{2}\u{2}\u{36}" .
		    "\u{37}\u{9}\u{5}\u{2}\u{2}\u{37}\u{D}\u{3}\u{2}\u{2}\u{2}\u{38}\u{3E}" .
		    "\u{7}\u{46}\u{2}\u{2}\u{39}\u{3E}\u{7}\u{40}\u{2}\u{2}\u{3A}\u{3E}" .
		    "\u{7}\u{44}\u{2}\u{2}\u{3B}\u{3E}\u{7}\u{43}\u{2}\u{2}\u{3C}\u{3E}" .
		    "\u{5}\u{4}\u{3}\u{2}\u{3D}\u{38}\u{3}\u{2}\u{2}\u{2}\u{3D}\u{39}\u{3}" .
		    "\u{2}\u{2}\u{2}\u{3D}\u{3A}\u{3}\u{2}\u{2}\u{2}\u{3D}\u{3B}\u{3}\u{2}" .
		    "\u{2}\u{2}\u{3D}\u{3C}\u{3}\u{2}\u{2}\u{2}\u{3E}\u{4E}\u{3}\u{2}\u{2}" .
		    "\u{2}\u{3F}\u{40}\u{5}\u{2}\u{2}\u{2}\u{40}\u{42}\u{7}\u{5}\u{2}\u{2}" .
		    "\u{41}\u{43}\u{5}\u{E}\u{8}\u{2}\u{42}\u{41}\u{3}\u{2}\u{2}\u{2}\u{42}" .
		    "\u{43}\u{3}\u{2}\u{2}\u{2}\u{43}\u{48}\u{3}\u{2}\u{2}\u{2}\u{44}\u{45}" .
		    "\u{7}\u{B}\u{2}\u{2}\u{45}\u{47}\u{5}\u{E}\u{8}\u{2}\u{46}\u{44}\u{3}" .
		    "\u{2}\u{2}\u{2}\u{47}\u{4A}\u{3}\u{2}\u{2}\u{2}\u{48}\u{46}\u{3}\u{2}" .
		    "\u{2}\u{2}\u{48}\u{49}\u{3}\u{2}\u{2}\u{2}\u{49}\u{4B}\u{3}\u{2}\u{2}" .
		    "\u{2}\u{4A}\u{48}\u{3}\u{2}\u{2}\u{2}\u{4B}\u{4C}\u{7}\u{6}\u{2}\u{2}" .
		    "\u{4C}\u{4E}\u{3}\u{2}\u{2}\u{2}\u{4D}\u{3D}\u{3}\u{2}\u{2}\u{2}\u{4D}" .
		    "\u{3F}\u{3}\u{2}\u{2}\u{2}\u{4E}\u{F}\u{3}\u{2}\u{2}\u{2}\u{4F}\u{77}" .
		    "\u{5}\u{8}\u{5}\u{2}\u{50}\u{51}\u{9}\u{6}\u{2}\u{2}\u{51}\u{78}\u{5}" .
		    "\u{E}\u{8}\u{2}\u{52}\u{54}\u{7}\u{31}\u{2}\u{2}\u{53}\u{52}\u{3}" .
		    "\u{2}\u{2}\u{2}\u{53}\u{54}\u{3}\u{2}\u{2}\u{2}\u{54}\u{55}\u{3}\u{2}" .
		    "\u{2}\u{2}\u{55}\u{56}\u{7}\u{2F}\u{2}\u{2}\u{56}\u{59}\u{5}\u{C}" .
		    "\u{7}\u{2}\u{57}\u{58}\u{7}\u{28}\u{2}\u{2}\u{58}\u{5A}\u{7}\u{40}" .
		    "\u{2}\u{2}\u{59}\u{57}\u{3}\u{2}\u{2}\u{2}\u{59}\u{5A}\u{3}\u{2}\u{2}" .
		    "\u{2}\u{5A}\u{5E}\u{3}\u{2}\u{2}\u{2}\u{5B}\u{5C}\u{7}\u{2B}\u{2}" .
		    "\u{2}\u{5C}\u{5E}\u{5}\u{C}\u{7}\u{2}\u{5D}\u{53}\u{3}\u{2}\u{2}\u{2}" .
		    "\u{5D}\u{5B}\u{3}\u{2}\u{2}\u{2}\u{5E}\u{78}\u{3}\u{2}\u{2}\u{2}\u{5F}" .
		    "\u{61}\u{7}\u{31}\u{2}\u{2}\u{60}\u{5F}\u{3}\u{2}\u{2}\u{2}\u{60}" .
		    "\u{61}\u{3}\u{2}\u{2}\u{2}\u{61}\u{62}\u{3}\u{2}\u{2}\u{2}\u{62}\u{63}" .
		    "\u{7}\u{25}\u{2}\u{2}\u{63}\u{64}\u{5}\u{E}\u{8}\u{2}\u{64}\u{65}" .
		    "\u{7}\u{22}\u{2}\u{2}\u{65}\u{66}\u{5}\u{E}\u{8}\u{2}\u{66}\u{78}" .
		    "\u{3}\u{2}\u{2}\u{2}\u{67}\u{69}\u{7}\u{31}\u{2}\u{2}\u{68}\u{67}" .
		    "\u{3}\u{2}\u{2}\u{2}\u{68}\u{69}\u{3}\u{2}\u{2}\u{2}\u{69}\u{6A}\u{3}" .
		    "\u{2}\u{2}\u{2}\u{6A}\u{6B}\u{7}\u{2C}\u{2}\u{2}\u{6B}\u{74}\u{7}" .
		    "\u{5}\u{2}\u{2}\u{6C}\u{71}\u{5}\u{E}\u{8}\u{2}\u{6D}\u{6E}\u{7}\u{B}" .
		    "\u{2}\u{2}\u{6E}\u{70}\u{5}\u{E}\u{8}\u{2}\u{6F}\u{6D}\u{3}\u{2}\u{2}" .
		    "\u{2}\u{70}\u{73}\u{3}\u{2}\u{2}\u{2}\u{71}\u{6F}\u{3}\u{2}\u{2}\u{2}" .
		    "\u{71}\u{72}\u{3}\u{2}\u{2}\u{2}\u{72}\u{75}\u{3}\u{2}\u{2}\u{2}\u{73}" .
		    "\u{71}\u{3}\u{2}\u{2}\u{2}\u{74}\u{6C}\u{3}\u{2}\u{2}\u{2}\u{74}\u{75}" .
		    "\u{3}\u{2}\u{2}\u{2}\u{75}\u{76}\u{3}\u{2}\u{2}\u{2}\u{76}\u{78}\u{7}" .
		    "\u{6}\u{2}\u{2}\u{77}\u{50}\u{3}\u{2}\u{2}\u{2}\u{77}\u{5D}\u{3}\u{2}" .
		    "\u{2}\u{2}\u{77}\u{60}\u{3}\u{2}\u{2}\u{2}\u{77}\u{68}\u{3}\u{2}\u{2}" .
		    "\u{2}\u{78}\u{11}\u{3}\u{2}\u{2}\u{2}\u{79}\u{7E}\u{5}\u{A}\u{6}\u{2}" .
		    "\u{7A}\u{7B}\u{7}\u{B}\u{2}\u{2}\u{7B}\u{7D}\u{5}\u{A}\u{6}\u{2}\u{7C}" .
		    "\u{7A}\u{3}\u{2}\u{2}\u{2}\u{7D}\u{80}\u{3}\u{2}\u{2}\u{2}\u{7E}\u{7C}" .
		    "\u{3}\u{2}\u{2}\u{2}\u{7E}\u{7F}\u{3}\u{2}\u{2}\u{2}\u{7F}\u{13}\u{3}" .
		    "\u{2}\u{2}\u{2}\u{80}\u{7E}\u{3}\u{2}\u{2}\u{2}\u{81}\u{82}\u{7}\u{5}" .
		    "\u{2}\u{2}\u{82}\u{87}\u{5}\u{10}\u{9}\u{2}\u{83}\u{84}\u{9}\u{7}" .
		    "\u{2}\u{2}\u{84}\u{86}\u{5}\u{10}\u{9}\u{2}\u{85}\u{83}\u{3}\u{2}" .
		    "\u{2}\u{2}\u{86}\u{89}\u{3}\u{2}\u{2}\u{2}\u{87}\u{85}\u{3}\u{2}\u{2}" .
		    "\u{2}\u{87}\u{88}\u{3}\u{2}\u{2}\u{2}\u{88}\u{8A}\u{3}\u{2}\u{2}\u{2}" .
		    "\u{89}\u{87}\u{3}\u{2}\u{2}\u{2}\u{8A}\u{8B}\u{7}\u{6}\u{2}\u{2}\u{8B}" .
		    "\u{15}\u{3}\u{2}\u{2}\u{2}\u{8C}\u{8F}\u{7}\u{39}\u{2}\u{2}\u{8D}" .
		    "\u{90}\u{5}\u{10}\u{9}\u{2}\u{8E}\u{90}\u{5}\u{14}\u{B}\u{2}\u{8F}" .
		    "\u{8D}\u{3}\u{2}\u{2}\u{2}\u{8F}\u{8E}\u{3}\u{2}\u{2}\u{2}\u{90}\u{98}" .
		    "\u{3}\u{2}\u{2}\u{2}\u{91}\u{94}\u{9}\u{7}\u{2}\u{2}\u{92}\u{95}\u{5}" .
		    "\u{10}\u{9}\u{2}\u{93}\u{95}\u{5}\u{14}\u{B}\u{2}\u{94}\u{92}\u{3}" .
		    "\u{2}\u{2}\u{2}\u{94}\u{93}\u{3}\u{2}\u{2}\u{2}\u{95}\u{97}\u{3}\u{2}" .
		    "\u{2}\u{2}\u{96}\u{91}\u{3}\u{2}\u{2}\u{2}\u{97}\u{9A}\u{3}\u{2}\u{2}" .
		    "\u{2}\u{98}\u{96}\u{3}\u{2}\u{2}\u{2}\u{98}\u{99}\u{3}\u{2}\u{2}\u{2}" .
		    "\u{99}\u{17}\u{3}\u{2}\u{2}\u{2}\u{9A}\u{98}\u{3}\u{2}\u{2}\u{2}\u{9B}" .
		    "\u{9C}\u{7}\u{2A}\u{2}\u{2}\u{9C}\u{9D}\u{5}\u{6}\u{4}\u{2}\u{9D}" .
		    "\u{19}\u{3}\u{2}\u{2}\u{2}\u{9E}\u{9F}\u{7}\u{30}\u{2}\u{2}\u{9F}" .
		    "\u{A0}\u{7}\u{43}\u{2}\u{2}\u{A0}\u{1B}\u{3}\u{2}\u{2}\u{2}\u{A1}" .
		    "\u{AC}\u{7}\u{36}\u{2}\u{2}\u{A2}\u{AD}\u{7}\u{3A}\u{2}\u{2}\u{A3}" .
		    "\u{A4}\u{7}\u{26}\u{2}\u{2}\u{A4}\u{A9}\u{5}\u{1E}\u{10}\u{2}\u{A5}" .
		    "\u{A6}\u{7}\u{B}\u{2}\u{2}\u{A6}\u{A8}\u{5}\u{1E}\u{10}\u{2}\u{A7}" .
		    "\u{A5}\u{3}\u{2}\u{2}\u{2}\u{A8}\u{AB}\u{3}\u{2}\u{2}\u{2}\u{A9}\u{A7}" .
		    "\u{3}\u{2}\u{2}\u{2}\u{A9}\u{AA}\u{3}\u{2}\u{2}\u{2}\u{AA}\u{AD}\u{3}" .
		    "\u{2}\u{2}\u{2}\u{AB}\u{A9}\u{3}\u{2}\u{2}\u{2}\u{AC}\u{A2}\u{3}\u{2}" .
		    "\u{2}\u{2}\u{AC}\u{A3}\u{3}\u{2}\u{2}\u{2}\u{AD}\u{1D}\u{3}\u{2}\u{2}" .
		    "\u{2}\u{AE}\u{B0}\u{7}\u{46}\u{2}\u{2}\u{AF}\u{B1}\u{9}\u{8}\u{2}" .
		    "\u{2}\u{B0}\u{AF}\u{3}\u{2}\u{2}\u{2}\u{B0}\u{B1}\u{3}\u{2}\u{2}\u{2}" .
		    "\u{B1}\u{1F}\u{3}\u{2}\u{2}\u{2}\u{B2}\u{B4}\u{7}\u{37}\u{2}\u{2}" .
		    "\u{B3}\u{B5}\u{7}\u{3A}\u{2}\u{2}\u{B4}\u{B3}\u{3}\u{2}\u{2}\u{2}" .
		    "\u{B4}\u{B5}\u{3}\u{2}\u{2}\u{2}\u{B5}\u{B8}\u{3}\u{2}\u{2}\u{2}\u{B6}" .
		    "\u{B9}\u{7}\u{F}\u{2}\u{2}\u{B7}\u{B9}\u{5}\u{12}\u{A}\u{2}\u{B8}" .
		    "\u{B6}\u{3}\u{2}\u{2}\u{2}\u{B8}\u{B7}\u{3}\u{2}\u{2}\u{2}\u{B8}\u{B9}" .
		    "\u{3}\u{2}\u{2}\u{2}\u{B9}\u{BB}\u{3}\u{2}\u{2}\u{2}\u{BA}\u{BC}\u{5}" .
		    "\u{18}\u{D}\u{2}\u{BB}\u{BA}\u{3}\u{2}\u{2}\u{2}\u{BB}\u{BC}\u{3}" .
		    "\u{2}\u{2}\u{2}\u{BC}\u{BE}\u{3}\u{2}\u{2}\u{2}\u{BD}\u{BF}\u{5}\u{16}" .
		    "\u{C}\u{2}\u{BE}\u{BD}\u{3}\u{2}\u{2}\u{2}\u{BE}\u{BF}\u{3}\u{2}\u{2}" .
		    "\u{2}\u{BF}\u{C1}\u{3}\u{2}\u{2}\u{2}\u{C0}\u{C2}\u{5}\u{1C}\u{F}" .
		    "\u{2}\u{C1}\u{C0}\u{3}\u{2}\u{2}\u{2}\u{C1}\u{C2}\u{3}\u{2}\u{2}\u{2}" .
		    "\u{C2}\u{C4}\u{3}\u{2}\u{2}\u{2}\u{C3}\u{C5}\u{5}\u{1A}\u{E}\u{2}" .
		    "\u{C4}\u{C3}\u{3}\u{2}\u{2}\u{2}\u{C4}\u{C5}\u{3}\u{2}\u{2}\u{2}\u{C5}" .
		    "\u{21}\u{3}\u{2}\u{2}\u{2}\u{1F}\u{2D}\u{32}\u{34}\u{3D}\u{42}\u{48}" .
		    "\u{4D}\u{53}\u{59}\u{5D}\u{60}\u{68}\u{71}\u{74}\u{77}\u{7E}\u{87}" .
		    "\u{8F}\u{94}\u{98}\u{A9}\u{AC}\u{B0}\u{B4}\u{B8}\u{BB}\u{BE}\u{C1}" .
		    "\u{C4}";

		protected static $atn;
		protected static $decisionToDFA;
		protected static $sharedContextCache;

		public function __construct(TokenStream $input)
		{
			parent::__construct($input);

			self::initialize();

			$this->interp = new ParserATNSimulator($this, self::$atn, self::$decisionToDFA, self::$sharedContextCache);
		}

		private static function initialize() : void
		{
			if (self::$atn !== null) {
				return;
			}

			RuntimeMetaData::checkVersion('4.9.3', RuntimeMetaData::VERSION);

			$atn = (new ATNDeserializer())->deserialize(self::SERIALIZED_ATN);

			$decisionToDFA = [];
			for ($i = 0, $count = $atn->getNumberOfDecisions(); $i < $count; $i++) {
				$decisionToDFA[] = new DFA($atn->getDecisionState($i), $i);
			}

			self::$atn = $atn;
			self::$decisionToDFA = $decisionToDFA;
			self::$sharedContextCache = new PredictionContextCache();
		}

		public function getGrammarFileName() : string
		{
			return "PageSql.g4";
		}

		public function getRuleNames() : array
		{
			return self::RULE_NAMES;
		}

		public function getSerializedATN() : string
		{
			return self::SERIALIZED_ATN;
		}

		public function getATN() : ATN
		{
			return self::$atn;
		}

		public function getVocabulary() : Vocabulary
        {
            static $vocabulary;

			return $vocabulary = $vocabulary ?? new VocabularyImpl(self::LITERAL_NAMES, self::SYMBOLIC_NAMES);
        }

		/**
		 * @throws RecognitionException
		 */
		public function functionNames() : Context\FunctionNamesContext
		{
		    $localContext = new Context\FunctionNamesContext($this->ctx, $this->getState());

		    $this->enterRule($localContext, 0, self::RULE_functionNames);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(32);

		        $_la = $this->input->LA(1);

		        if (!($_la === self::DATE || $_la === self::DATETIME)) {
		        $this->errorHandler->recoverInline($this);
		        } else {
		        	if ($this->input->LA(1) === Token::EOF) {
		        	    $this->matchedEOF = true;
		            }

		        	$this->errorHandler->reportMatch($this);
		        	$this->consume();
		        }
		    } catch (RecognitionException $exception) {
		        $localContext->exception = $exception;
		        $this->errorHandler->reportError($this, $exception);
		        $this->errorHandler->recover($this, $exception);
		    } finally {
		        $this->exitRule();
		    }

		    return $localContext;
		}

		/**
		 * @throws RecognitionException
		 */
		public function constantNames() : Context\ConstantNamesContext
		{
		    $localContext = new Context\ConstantNamesContext($this->ctx, $this->getState());

		    $this->enterRule($localContext, 2, self::RULE_constantNames);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(34);
		        $this->match(self::NOW);
		    } catch (RecognitionException $exception) {
		        $localContext->exception = $exception;
		        $this->errorHandler->reportError($this, $exception);
		        $this->errorHandler->recover($this, $exception);
		    } finally {
		        $this->exitRule();
		    }

		    return $localContext;
		}

		/**
		 * @throws RecognitionException
		 */
		public function tableNames() : Context\TableNamesContext
		{
		    $localContext = new Context\TableNamesContext($this->ctx, $this->getState());

		    $this->enterRule($localContext, 4, self::RULE_tableNames);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(36);

		        $_la = $this->input->LA(1);

		        if (!(((($_la) & ~0x3f) === 0 && ((1 << $_la) & ((1 << self::PAGES) | (1 << self::BACKLINKS) | (1 << self::DESCENDANTS))) !== 0))) {
		        $this->errorHandler->recoverInline($this);
		        } else {
		        	if ($this->input->LA(1) === Token::EOF) {
		        	    $this->matchedEOF = true;
		            }

		        	$this->errorHandler->reportMatch($this);
		        	$this->consume();
		        }
		    } catch (RecognitionException $exception) {
		        $localContext->exception = $exception;
		        $this->errorHandler->reportError($this, $exception);
		        $this->errorHandler->recover($this, $exception);
		    } finally {
		        $this->exitRule();
		    }

		    return $localContext;
		}

		/**
		 * @throws RecognitionException
		 */
		public function sqlNames() : Context\SqlNamesContext
		{
		    $localContext = new Context\SqlNamesContext($this->ctx, $this->getState());

		    $this->enterRule($localContext, 6, self::RULE_sqlNames);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(38);

		        $_la = $this->input->LA(1);

		        if (!($_la === self::Number || $_la === self::SqlName)) {
		        $this->errorHandler->recoverInline($this);
		        } else {
		        	if ($this->input->LA(1) === Token::EOF) {
		        	    $this->matchedEOF = true;
		            }

		        	$this->errorHandler->reportMatch($this);
		        	$this->consume();
		        }
		    } catch (RecognitionException $exception) {
		        $localContext->exception = $exception;
		        $this->errorHandler->reportError($this, $exception);
		        $this->errorHandler->recover($this, $exception);
		    } finally {
		        $this->exitRule();
		    }

		    return $localContext;
		}

		/**
		 * @throws RecognitionException
		 */
		public function column() : Context\ColumnContext
		{
		    $localContext = new Context\ColumnContext($this->ctx, $this->getState());

		    $this->enterRule($localContext, 8, self::RULE_column);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(40);
		        $this->sqlNames();
		        $this->setState(43);
		        $this->errorHandler->sync($this);
		        $_la = $this->input->LA(1);

		        if ($_la === self::DOT) {
		        	$this->setState(41);
		        	$this->match(self::DOT);
		        	$this->setState(42);
		        	$this->sqlNames();
		        }
		        $this->setState(50);
		        $this->errorHandler->sync($this);
		        $_la = $this->input->LA(1);

		        if ($_la === self::AS) {
		        	$this->setState(45);
		        	$this->match(self::AS);
		        	$this->setState(48);
		        	$this->errorHandler->sync($this);

		        	switch ($this->input->LA(1)) {
		        	    case self::Number:
		        	    case self::SqlName:
		        	    	$this->setState(46);
		        	    	$this->sqlNames();
		        	    	break;

		        	    case self::StringLiteral:
		        	    	$this->setState(47);
		        	    	$this->match(self::StringLiteral);
		        	    	break;

		        	default:
		        		throw new NoViableAltException($this);
		        	}
		        }
		    } catch (RecognitionException $exception) {
		        $localContext->exception = $exception;
		        $this->errorHandler->reportError($this, $exception);
		        $this->errorHandler->recover($this, $exception);
		    } finally {
		        $this->exitRule();
		    }

		    return $localContext;
		}

		/**
		 * @throws RecognitionException
		 */
		public function pattern() : Context\PatternContext
		{
		    $localContext = new Context\PatternContext($this->ctx, $this->getState());

		    $this->enterRule($localContext, 10, self::RULE_pattern);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(52);

		        $_la = $this->input->LA(1);

		        if (!($_la === self::StringLiteral || $_la === self::NumberLiteral)) {
		        $this->errorHandler->recoverInline($this);
		        } else {
		        	if ($this->input->LA(1) === Token::EOF) {
		        	    $this->matchedEOF = true;
		            }

		        	$this->errorHandler->reportMatch($this);
		        	$this->consume();
		        }
		    } catch (RecognitionException $exception) {
		        $localContext->exception = $exception;
		        $this->errorHandler->reportError($this, $exception);
		        $this->errorHandler->recover($this, $exception);
		    } finally {
		        $this->exitRule();
		    }

		    return $localContext;
		}

		/**
		 * @throws RecognitionException
		 */
		public function expression() : Context\ExpressionContext
		{
		    $localContext = new Context\ExpressionContext($this->ctx, $this->getState());

		    $this->enterRule($localContext, 12, self::RULE_expression);

		    try {
		        $this->setState(75);
		        $this->errorHandler->sync($this);

		        switch ($this->input->LA(1)) {
		            case self::NOW:
		            case self::StringLiteral:
		            case self::Number:
		            case self::NumberLiteral:
		            case self::SqlName:
		            	$this->enterOuterAlt($localContext, 1);
		            	$this->setState(59);
		            	$this->errorHandler->sync($this);

		            	switch ($this->input->LA(1)) {
		            	    case self::SqlName:
		            	    	$this->setState(54);
		            	    	$this->match(self::SqlName);
		            	    	break;

		            	    case self::StringLiteral:
		            	    	$this->setState(55);
		            	    	$this->match(self::StringLiteral);
		            	    	break;

		            	    case self::NumberLiteral:
		            	    	$this->setState(56);
		            	    	$this->match(self::NumberLiteral);
		            	    	break;

		            	    case self::Number:
		            	    	$this->setState(57);
		            	    	$this->match(self::Number);
		            	    	break;

		            	    case self::NOW:
		            	    	$this->setState(58);
		            	    	$this->constantNames();
		            	    	break;

		            	default:
		            		throw new NoViableAltException($this);
		            	}
		            	break;

		            case self::DATE:
		            case self::DATETIME:
		            	$this->enterOuterAlt($localContext, 2);
		            	$this->setState(61);
		            	$this->functionNames();
		            	$this->setState(62);
		            	$this->match(self::LPAREN);
		            	$this->setState(64);
		            	$this->errorHandler->sync($this);
		            	$_la = $this->input->LA(1);

		            	if ((((($_la - 49)) & ~0x3f) === 0 && ((1 << ($_la - 49)) & ((1 << (self::NOW - 49)) | (1 << (self::DATE - 49)) | (1 << (self::DATETIME - 49)) | (1 << (self::StringLiteral - 49)) | (1 << (self::Number - 49)) | (1 << (self::NumberLiteral - 49)) | (1 << (self::SqlName - 49)))) !== 0)) {
		            		$this->setState(63);
		            		$this->expression();
		            	}
		            	$this->setState(70);
		            	$this->errorHandler->sync($this);

		            	$_la = $this->input->LA(1);
		            	while ($_la === self::COMMA) {
		            		$this->setState(66);
		            		$this->match(self::COMMA);
		            		$this->setState(67);
		            		$this->expression();
		            		$this->setState(72);
		            		$this->errorHandler->sync($this);
		            		$_la = $this->input->LA(1);
		            	}
		            	$this->setState(73);
		            	$this->match(self::RPAREN);
		            	break;

		        default:
		        	throw new NoViableAltException($this);
		        }
		    } catch (RecognitionException $exception) {
		        $localContext->exception = $exception;
		        $this->errorHandler->reportError($this, $exception);
		        $this->errorHandler->recover($this, $exception);
		    } finally {
		        $this->exitRule();
		    }

		    return $localContext;
		}

		/**
		 * @throws RecognitionException
		 */
		public function predicate() : Context\PredicateContext
		{
		    $localContext = new Context\PredicateContext($this->ctx, $this->getState());

		    $this->enterRule($localContext, 14, self::RULE_predicate);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(77);
		        $this->sqlNames();
		        $this->setState(117);
		        $this->errorHandler->sync($this);

		        switch ($this->getInterpreter()->adaptivePredict($this->input, 14, $this->ctx)) {
		        	case 1:
		        	    $this->setState(78);

		        	    $_la = $this->input->LA(1);

		        	    if (!(((($_la) & ~0x3f) === 0 && ((1 << $_la) & ((1 << self::EQUAL) | (1 << self::LESS_THAN) | (1 << self::LESS_THAN_OR_EQUAL) | (1 << self::GREATER_THAN) | (1 << self::GREATER_THAN_OR_EQUAL) | (1 << self::NOT_EQUAL))) !== 0))) {
		        	    $this->errorHandler->recoverInline($this);
		        	    } else {
		        	    	if ($this->input->LA(1) === Token::EOF) {
		        	    	    $this->matchedEOF = true;
		        	        }

		        	    	$this->errorHandler->reportMatch($this);
		        	    	$this->consume();
		        	    }
		        	    $this->setState(79);
		        	    $this->expression();
		        	break;

		        	case 2:
		        	    $this->setState(91);
		        	    $this->errorHandler->sync($this);

		        	    switch ($this->input->LA(1)) {
		        	        case self::LIKE:
		        	        case self::NOT:
		        	        	$this->setState(81);
		        	        	$this->errorHandler->sync($this);
		        	        	$_la = $this->input->LA(1);

		        	        	if ($_la === self::NOT) {
		        	        		$this->setState(80);
		        	        		$this->match(self::NOT);
		        	        	}

		        	        	$this->setState(83);
		        	        	$this->match(self::LIKE);
		        	        	$this->setState(84);
		        	        	$this->pattern();
		        	        	$this->setState(87);
		        	        	$this->errorHandler->sync($this);
		        	        	$_la = $this->input->LA(1);

		        	        	if ($_la === self::ESCAPE) {
		        	        		$this->setState(85);
		        	        		$this->match(self::ESCAPE);
		        	        		$this->setState(86);
		        	        		$this->match(self::StringLiteral);
		        	        	}
		        	        	break;

		        	        case self::GLOB:
		        	        	$this->setState(89);
		        	        	$this->match(self::GLOB);
		        	        	$this->setState(90);
		        	        	$this->pattern();
		        	        	break;

		        	    default:
		        	    	throw new NoViableAltException($this);
		        	    }
		        	break;

		        	case 3:
		        	    $this->setState(94);
		        	    $this->errorHandler->sync($this);
		        	    $_la = $this->input->LA(1);

		        	    if ($_la === self::NOT) {
		        	    	$this->setState(93);
		        	    	$this->match(self::NOT);
		        	    }
		        	    $this->setState(96);
		        	    $this->match(self::BETWEEN);
		        	    $this->setState(97);
		        	    $this->expression();
		        	    $this->setState(98);
		        	    $this->match(self::AND);
		        	    $this->setState(99);
		        	    $this->expression();
		        	break;

		        	case 4:
		        	    $this->setState(102);
		        	    $this->errorHandler->sync($this);
		        	    $_la = $this->input->LA(1);

		        	    if ($_la === self::NOT) {
		        	    	$this->setState(101);
		        	    	$this->match(self::NOT);
		        	    }
		        	    $this->setState(104);
		        	    $this->match(self::IN);
		        	    $this->setState(105);
		        	    $this->match(self::LPAREN);
		        	    $this->setState(114);
		        	    $this->errorHandler->sync($this);
		        	    $_la = $this->input->LA(1);

		        	    if ((((($_la - 49)) & ~0x3f) === 0 && ((1 << ($_la - 49)) & ((1 << (self::NOW - 49)) | (1 << (self::DATE - 49)) | (1 << (self::DATETIME - 49)) | (1 << (self::StringLiteral - 49)) | (1 << (self::Number - 49)) | (1 << (self::NumberLiteral - 49)) | (1 << (self::SqlName - 49)))) !== 0)) {
		        	    	$this->setState(106);
		        	    	$this->expression();
		        	    	$this->setState(111);
		        	    	$this->errorHandler->sync($this);

		        	    	$_la = $this->input->LA(1);
		        	    	while ($_la === self::COMMA) {
		        	    		$this->setState(107);
		        	    		$this->match(self::COMMA);
		        	    		$this->setState(108);
		        	    		$this->expression();
		        	    		$this->setState(113);
		        	    		$this->errorHandler->sync($this);
		        	    		$_la = $this->input->LA(1);
		        	    	}
		        	    }
		        	    $this->setState(116);
		        	    $this->match(self::RPAREN);
		        	break;
		        }
		    } catch (RecognitionException $exception) {
		        $localContext->exception = $exception;
		        $this->errorHandler->reportError($this, $exception);
		        $this->errorHandler->recover($this, $exception);
		    } finally {
		        $this->exitRule();
		    }

		    return $localContext;
		}

		/**
		 * @throws RecognitionException
		 */
		public function columns() : Context\ColumnsContext
		{
		    $localContext = new Context\ColumnsContext($this->ctx, $this->getState());

		    $this->enterRule($localContext, 16, self::RULE_columns);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(119);
		        $this->column();
		        $this->setState(124);
		        $this->errorHandler->sync($this);

		        $_la = $this->input->LA(1);
		        while ($_la === self::COMMA) {
		        	$this->setState(120);
		        	$this->match(self::COMMA);
		        	$this->setState(121);
		        	$this->column();
		        	$this->setState(126);
		        	$this->errorHandler->sync($this);
		        	$_la = $this->input->LA(1);
		        }
		    } catch (RecognitionException $exception) {
		        $localContext->exception = $exception;
		        $this->errorHandler->reportError($this, $exception);
		        $this->errorHandler->recover($this, $exception);
		    } finally {
		        $this->exitRule();
		    }

		    return $localContext;
		}

		/**
		 * @throws RecognitionException
		 */
		public function predicateGroup() : Context\PredicateGroupContext
		{
		    $localContext = new Context\PredicateGroupContext($this->ctx, $this->getState());

		    $this->enterRule($localContext, 18, self::RULE_predicateGroup);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(127);
		        $this->match(self::LPAREN);
		        $this->setState(128);
		        $this->predicate();
		        $this->setState(133);
		        $this->errorHandler->sync($this);

		        $_la = $this->input->LA(1);
		        while ($_la === self::AND || $_la === self::OR) {
		        	$this->setState(129);

		        	$_la = $this->input->LA(1);

		        	if (!($_la === self::AND || $_la === self::OR)) {
		        	$this->errorHandler->recoverInline($this);
		        	} else {
		        		if ($this->input->LA(1) === Token::EOF) {
		        		    $this->matchedEOF = true;
		        	    }

		        		$this->errorHandler->reportMatch($this);
		        		$this->consume();
		        	}
		        	$this->setState(130);
		        	$this->predicate();
		        	$this->setState(135);
		        	$this->errorHandler->sync($this);
		        	$_la = $this->input->LA(1);
		        }
		        $this->setState(136);
		        $this->match(self::RPAREN);
		    } catch (RecognitionException $exception) {
		        $localContext->exception = $exception;
		        $this->errorHandler->reportError($this, $exception);
		        $this->errorHandler->recover($this, $exception);
		    } finally {
		        $this->exitRule();
		    }

		    return $localContext;
		}

		/**
		 * @throws RecognitionException
		 */
		public function predicates() : Context\PredicatesContext
		{
		    $localContext = new Context\PredicatesContext($this->ctx, $this->getState());

		    $this->enterRule($localContext, 20, self::RULE_predicates);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(138);
		        $this->match(self::WHERE);
		        $this->setState(141);
		        $this->errorHandler->sync($this);

		        switch ($this->input->LA(1)) {
		            case self::Number:
		            case self::SqlName:
		            	$this->setState(139);
		            	$this->predicate();
		            	break;

		            case self::LPAREN:
		            	$this->setState(140);
		            	$this->predicateGroup();
		            	break;

		        default:
		        	throw new NoViableAltException($this);
		        }
		        $this->setState(150);
		        $this->errorHandler->sync($this);

		        $_la = $this->input->LA(1);
		        while ($_la === self::AND || $_la === self::OR) {
		        	$this->setState(143);

		        	$_la = $this->input->LA(1);

		        	if (!($_la === self::AND || $_la === self::OR)) {
		        	$this->errorHandler->recoverInline($this);
		        	} else {
		        		if ($this->input->LA(1) === Token::EOF) {
		        		    $this->matchedEOF = true;
		        	    }

		        		$this->errorHandler->reportMatch($this);
		        		$this->consume();
		        	}
		        	$this->setState(146);
		        	$this->errorHandler->sync($this);

		        	switch ($this->input->LA(1)) {
		        	    case self::Number:
		        	    case self::SqlName:
		        	    	$this->setState(144);
		        	    	$this->predicate();
		        	    	break;

		        	    case self::LPAREN:
		        	    	$this->setState(145);
		        	    	$this->predicateGroup();
		        	    	break;

		        	default:
		        		throw new NoViableAltException($this);
		        	}
		        	$this->setState(152);
		        	$this->errorHandler->sync($this);
		        	$_la = $this->input->LA(1);
		        }
		    } catch (RecognitionException $exception) {
		        $localContext->exception = $exception;
		        $this->errorHandler->reportError($this, $exception);
		        $this->errorHandler->recover($this, $exception);
		    } finally {
		        $this->exitRule();
		    }

		    return $localContext;
		}

		/**
		 * @throws RecognitionException
		 */
		public function tables() : Context\TablesContext
		{
		    $localContext = new Context\TablesContext($this->ctx, $this->getState());

		    $this->enterRule($localContext, 22, self::RULE_tables);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(153);
		        $this->match(self::FROM);
		        $this->setState(154);
		        $this->tableNames();
		    } catch (RecognitionException $exception) {
		        $localContext->exception = $exception;
		        $this->errorHandler->reportError($this, $exception);
		        $this->errorHandler->recover($this, $exception);
		    } finally {
		        $this->exitRule();
		    }

		    return $localContext;
		}

		/**
		 * @throws RecognitionException
		 */
		public function limit() : Context\LimitContext
		{
		    $localContext = new Context\LimitContext($this->ctx, $this->getState());

		    $this->enterRule($localContext, 24, self::RULE_limit);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(156);
		        $this->match(self::LIMIT);
		        $this->setState(157);
		        $this->match(self::Number);
		    } catch (RecognitionException $exception) {
		        $localContext->exception = $exception;
		        $this->errorHandler->reportError($this, $exception);
		        $this->errorHandler->recover($this, $exception);
		    } finally {
		        $this->exitRule();
		    }

		    return $localContext;
		}

		/**
		 * @throws RecognitionException
		 */
		public function orderBys() : Context\OrderBysContext
		{
		    $localContext = new Context\OrderBysContext($this->ctx, $this->getState());

		    $this->enterRule($localContext, 26, self::RULE_orderBys);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(159);
		        $this->match(self::ORDER);
		        $this->setState(170);
		        $this->errorHandler->sync($this);

		        switch ($this->input->LA(1)) {
		            case self::RANDOM:
		            	$this->setState(160);
		            	$this->match(self::RANDOM);
		            	break;

		            case self::BY:
		            	$this->setState(161);
		            	$this->match(self::BY);
		            	$this->setState(162);
		            	$this->orderByDef();
		            	$this->setState(167);
		            	$this->errorHandler->sync($this);

		            	$_la = $this->input->LA(1);
		            	while ($_la === self::COMMA) {
		            		$this->setState(163);
		            		$this->match(self::COMMA);
		            		$this->setState(164);
		            		$this->orderByDef();
		            		$this->setState(169);
		            		$this->errorHandler->sync($this);
		            		$_la = $this->input->LA(1);
		            	}
		            	break;

		        default:
		        	throw new NoViableAltException($this);
		        }
		    } catch (RecognitionException $exception) {
		        $localContext->exception = $exception;
		        $this->errorHandler->reportError($this, $exception);
		        $this->errorHandler->recover($this, $exception);
		    } finally {
		        $this->exitRule();
		    }

		    return $localContext;
		}

		/**
		 * @throws RecognitionException
		 */
		public function orderByDef() : Context\OrderByDefContext
		{
		    $localContext = new Context\OrderByDefContext($this->ctx, $this->getState());

		    $this->enterRule($localContext, 28, self::RULE_orderByDef);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(172);
		        $this->match(self::SqlName);
		        $this->setState(174);
		        $this->errorHandler->sync($this);
		        $_la = $this->input->LA(1);

		        if ($_la === self::ASC || $_la === self::DESC) {
		        	$this->setState(173);

		        	$_la = $this->input->LA(1);

		        	if (!($_la === self::ASC || $_la === self::DESC)) {
		        	$this->errorHandler->recoverInline($this);
		        	} else {
		        		if ($this->input->LA(1) === Token::EOF) {
		        		    $this->matchedEOF = true;
		        	    }

		        		$this->errorHandler->reportMatch($this);
		        		$this->consume();
		        	}
		        }
		    } catch (RecognitionException $exception) {
		        $localContext->exception = $exception;
		        $this->errorHandler->reportError($this, $exception);
		        $this->errorHandler->recover($this, $exception);
		    } finally {
		        $this->exitRule();
		    }

		    return $localContext;
		}

		/**
		 * @throws RecognitionException
		 */
		public function pageSql() : Context\PageSqlContext
		{
		    $localContext = new Context\PageSqlContext($this->ctx, $this->getState());

		    $this->enterRule($localContext, 30, self::RULE_pageSql);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(176);
		        $this->match(self::SELECT);
		        $this->setState(178);
		        $this->errorHandler->sync($this);
		        $_la = $this->input->LA(1);

		        if ($_la === self::RANDOM) {
		        	$this->setState(177);
		        	$this->match(self::RANDOM);
		        }
		        $this->setState(182);
		        $this->errorHandler->sync($this);

		        switch ($this->input->LA(1)) {
		            case self::STAR:
		            	$this->setState(180);
		            	$this->match(self::STAR);
		            	break;

		            case self::Number:
		            case self::SqlName:
		            	$this->setState(181);
		            	$this->columns();
		            	break;

		            case self::EOF:
		            case self::FROM:
		            case self::LIMIT:
		            case self::ORDER:
		            case self::WHERE:
		            	break;

		        default:
		        	break;
		        }
		        $this->setState(185);
		        $this->errorHandler->sync($this);
		        $_la = $this->input->LA(1);

		        if ($_la === self::FROM) {
		        	$this->setState(184);
		        	$this->tables();
		        }
		        $this->setState(188);
		        $this->errorHandler->sync($this);
		        $_la = $this->input->LA(1);

		        if ($_la === self::WHERE) {
		        	$this->setState(187);
		        	$this->predicates();
		        }
		        $this->setState(191);
		        $this->errorHandler->sync($this);
		        $_la = $this->input->LA(1);

		        if ($_la === self::ORDER) {
		        	$this->setState(190);
		        	$this->orderBys();
		        }
		        $this->setState(194);
		        $this->errorHandler->sync($this);
		        $_la = $this->input->LA(1);

		        if ($_la === self::LIMIT) {
		        	$this->setState(193);
		        	$this->limit();
		        }
		    } catch (RecognitionException $exception) {
		        $localContext->exception = $exception;
		        $this->errorHandler->reportError($this, $exception);
		        $this->errorHandler->recover($this, $exception);
		    } finally {
		        $this->exitRule();
		    }

		    return $localContext;
		}
	}
}

namespace ComboStrap\PageSqlParser\Context {
	use Antlr\Antlr4\Runtime\ParserRuleContext;
	use Antlr\Antlr4\Runtime\Token;
	use Antlr\Antlr4\Runtime\Tree\ParseTreeVisitor;
	use Antlr\Antlr4\Runtime\Tree\TerminalNode;
	use Antlr\Antlr4\Runtime\Tree\ParseTreeListener;
	use ComboStrap\PageSqlParser\PageSqlParser;
	use ComboStrap\PageSqlParser\PageSqlListener;

	class FunctionNamesContext extends ParserRuleContext
	{
		public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
		{
			parent::__construct($parent, $invokingState);
		}

		public function getRuleIndex() : int
		{
		    return PageSqlParser::RULE_functionNames;
	    }

	    public function DATE() : ?TerminalNode
	    {
	        return $this->getToken(PageSqlParser::DATE, 0);
	    }

	    public function DATETIME() : ?TerminalNode
	    {
	        return $this->getToken(PageSqlParser::DATETIME, 0);
	    }

		public function enterRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof PageSqlListener) {
			    $listener->enterFunctionNames($this);
		    }
		}

		public function exitRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof PageSqlListener) {
			    $listener->exitFunctionNames($this);
		    }
		}
	} 

	class ConstantNamesContext extends ParserRuleContext
	{
		public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
		{
			parent::__construct($parent, $invokingState);
		}

		public function getRuleIndex() : int
		{
		    return PageSqlParser::RULE_constantNames;
	    }

	    public function NOW() : ?TerminalNode
	    {
	        return $this->getToken(PageSqlParser::NOW, 0);
	    }

		public function enterRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof PageSqlListener) {
			    $listener->enterConstantNames($this);
		    }
		}

		public function exitRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof PageSqlListener) {
			    $listener->exitConstantNames($this);
		    }
		}
	} 

	class TableNamesContext extends ParserRuleContext
	{
		public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
		{
			parent::__construct($parent, $invokingState);
		}

		public function getRuleIndex() : int
		{
		    return PageSqlParser::RULE_tableNames;
	    }

	    public function PAGES() : ?TerminalNode
	    {
	        return $this->getToken(PageSqlParser::PAGES, 0);
	    }

	    public function BACKLINKS() : ?TerminalNode
	    {
	        return $this->getToken(PageSqlParser::BACKLINKS, 0);
	    }

	    public function DESCENDANTS() : ?TerminalNode
	    {
	        return $this->getToken(PageSqlParser::DESCENDANTS, 0);
	    }

		public function enterRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof PageSqlListener) {
			    $listener->enterTableNames($this);
		    }
		}

		public function exitRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof PageSqlListener) {
			    $listener->exitTableNames($this);
		    }
		}
	} 

	class SqlNamesContext extends ParserRuleContext
	{
		public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
		{
			parent::__construct($parent, $invokingState);
		}

		public function getRuleIndex() : int
		{
		    return PageSqlParser::RULE_sqlNames;
	    }

	    public function SqlName() : ?TerminalNode
	    {
	        return $this->getToken(PageSqlParser::SqlName, 0);
	    }

	    public function Number() : ?TerminalNode
	    {
	        return $this->getToken(PageSqlParser::Number, 0);
	    }

		public function enterRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof PageSqlListener) {
			    $listener->enterSqlNames($this);
		    }
		}

		public function exitRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof PageSqlListener) {
			    $listener->exitSqlNames($this);
		    }
		}
	} 

	class ColumnContext extends ParserRuleContext
	{
		public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
		{
			parent::__construct($parent, $invokingState);
		}

		public function getRuleIndex() : int
		{
		    return PageSqlParser::RULE_column;
	    }

	    /**
	     * @return array<SqlNamesContext>|SqlNamesContext|null
	     */
	    public function sqlNames(?int $index = null)
	    {
	    	if ($index === null) {
	    		return $this->getTypedRuleContexts(SqlNamesContext::class);
	    	}

	        return $this->getTypedRuleContext(SqlNamesContext::class, $index);
	    }

	    public function DOT() : ?TerminalNode
	    {
	        return $this->getToken(PageSqlParser::DOT, 0);
	    }

	    public function AS() : ?TerminalNode
	    {
	        return $this->getToken(PageSqlParser::AS, 0);
	    }

	    public function StringLiteral() : ?TerminalNode
	    {
	        return $this->getToken(PageSqlParser::StringLiteral, 0);
	    }

		public function enterRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof PageSqlListener) {
			    $listener->enterColumn($this);
		    }
		}

		public function exitRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof PageSqlListener) {
			    $listener->exitColumn($this);
		    }
		}
	} 

	class PatternContext extends ParserRuleContext
	{
		public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
		{
			parent::__construct($parent, $invokingState);
		}

		public function getRuleIndex() : int
		{
		    return PageSqlParser::RULE_pattern;
	    }

	    public function StringLiteral() : ?TerminalNode
	    {
	        return $this->getToken(PageSqlParser::StringLiteral, 0);
	    }

	    public function NumberLiteral() : ?TerminalNode
	    {
	        return $this->getToken(PageSqlParser::NumberLiteral, 0);
	    }

		public function enterRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof PageSqlListener) {
			    $listener->enterPattern($this);
		    }
		}

		public function exitRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof PageSqlListener) {
			    $listener->exitPattern($this);
		    }
		}
	} 

	class ExpressionContext extends ParserRuleContext
	{
		public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
		{
			parent::__construct($parent, $invokingState);
		}

		public function getRuleIndex() : int
		{
		    return PageSqlParser::RULE_expression;
	    }

	    public function SqlName() : ?TerminalNode
	    {
	        return $this->getToken(PageSqlParser::SqlName, 0);
	    }

	    public function StringLiteral() : ?TerminalNode
	    {
	        return $this->getToken(PageSqlParser::StringLiteral, 0);
	    }

	    public function NumberLiteral() : ?TerminalNode
	    {
	        return $this->getToken(PageSqlParser::NumberLiteral, 0);
	    }

	    public function Number() : ?TerminalNode
	    {
	        return $this->getToken(PageSqlParser::Number, 0);
	    }

	    public function constantNames() : ?ConstantNamesContext
	    {
	    	return $this->getTypedRuleContext(ConstantNamesContext::class, 0);
	    }

	    public function functionNames() : ?FunctionNamesContext
	    {
	    	return $this->getTypedRuleContext(FunctionNamesContext::class, 0);
	    }

	    public function LPAREN() : ?TerminalNode
	    {
	        return $this->getToken(PageSqlParser::LPAREN, 0);
	    }

	    public function RPAREN() : ?TerminalNode
	    {
	        return $this->getToken(PageSqlParser::RPAREN, 0);
	    }

	    /**
	     * @return array<ExpressionContext>|ExpressionContext|null
	     */
	    public function expression(?int $index = null)
	    {
	    	if ($index === null) {
	    		return $this->getTypedRuleContexts(ExpressionContext::class);
	    	}

	        return $this->getTypedRuleContext(ExpressionContext::class, $index);
	    }

	    /**
	     * @return array<TerminalNode>|TerminalNode|null
	     */
	    public function COMMA(?int $index = null)
	    {
	    	if ($index === null) {
	    		return $this->getTokens(PageSqlParser::COMMA);
	    	}

	        return $this->getToken(PageSqlParser::COMMA, $index);
	    }

		public function enterRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof PageSqlListener) {
			    $listener->enterExpression($this);
		    }
		}

		public function exitRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof PageSqlListener) {
			    $listener->exitExpression($this);
		    }
		}
	} 

	class PredicateContext extends ParserRuleContext
	{
		public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
		{
			parent::__construct($parent, $invokingState);
		}

		public function getRuleIndex() : int
		{
		    return PageSqlParser::RULE_predicate;
	    }

	    public function sqlNames() : ?SqlNamesContext
	    {
	    	return $this->getTypedRuleContext(SqlNamesContext::class, 0);
	    }

	    /**
	     * @return array<ExpressionContext>|ExpressionContext|null
	     */
	    public function expression(?int $index = null)
	    {
	    	if ($index === null) {
	    		return $this->getTypedRuleContexts(ExpressionContext::class);
	    	}

	        return $this->getTypedRuleContext(ExpressionContext::class, $index);
	    }

	    public function BETWEEN() : ?TerminalNode
	    {
	        return $this->getToken(PageSqlParser::BETWEEN, 0);
	    }

	    public function AND() : ?TerminalNode
	    {
	        return $this->getToken(PageSqlParser::AND, 0);
	    }

	    public function IN() : ?TerminalNode
	    {
	        return $this->getToken(PageSqlParser::IN, 0);
	    }

	    public function LPAREN() : ?TerminalNode
	    {
	        return $this->getToken(PageSqlParser::LPAREN, 0);
	    }

	    public function RPAREN() : ?TerminalNode
	    {
	        return $this->getToken(PageSqlParser::RPAREN, 0);
	    }

	    public function LESS_THAN() : ?TerminalNode
	    {
	        return $this->getToken(PageSqlParser::LESS_THAN, 0);
	    }

	    public function LESS_THAN_OR_EQUAL() : ?TerminalNode
	    {
	        return $this->getToken(PageSqlParser::LESS_THAN_OR_EQUAL, 0);
	    }

	    public function GREATER_THAN() : ?TerminalNode
	    {
	        return $this->getToken(PageSqlParser::GREATER_THAN, 0);
	    }

	    public function GREATER_THAN_OR_EQUAL() : ?TerminalNode
	    {
	        return $this->getToken(PageSqlParser::GREATER_THAN_OR_EQUAL, 0);
	    }

	    public function NOT_EQUAL() : ?TerminalNode
	    {
	        return $this->getToken(PageSqlParser::NOT_EQUAL, 0);
	    }

	    public function EQUAL() : ?TerminalNode
	    {
	        return $this->getToken(PageSqlParser::EQUAL, 0);
	    }

	    public function LIKE() : ?TerminalNode
	    {
	        return $this->getToken(PageSqlParser::LIKE, 0);
	    }

	    public function pattern() : ?PatternContext
	    {
	    	return $this->getTypedRuleContext(PatternContext::class, 0);
	    }

	    public function GLOB() : ?TerminalNode
	    {
	        return $this->getToken(PageSqlParser::GLOB, 0);
	    }

	    public function NOT() : ?TerminalNode
	    {
	        return $this->getToken(PageSqlParser::NOT, 0);
	    }

	    public function ESCAPE() : ?TerminalNode
	    {
	        return $this->getToken(PageSqlParser::ESCAPE, 0);
	    }

	    public function StringLiteral() : ?TerminalNode
	    {
	        return $this->getToken(PageSqlParser::StringLiteral, 0);
	    }

	    /**
	     * @return array<TerminalNode>|TerminalNode|null
	     */
	    public function COMMA(?int $index = null)
	    {
	    	if ($index === null) {
	    		return $this->getTokens(PageSqlParser::COMMA);
	    	}

	        return $this->getToken(PageSqlParser::COMMA, $index);
	    }

		public function enterRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof PageSqlListener) {
			    $listener->enterPredicate($this);
		    }
		}

		public function exitRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof PageSqlListener) {
			    $listener->exitPredicate($this);
		    }
		}
	} 

	class ColumnsContext extends ParserRuleContext
	{
		public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
		{
			parent::__construct($parent, $invokingState);
		}

		public function getRuleIndex() : int
		{
		    return PageSqlParser::RULE_columns;
	    }

	    /**
	     * @return array<ColumnContext>|ColumnContext|null
	     */
	    public function column(?int $index = null)
	    {
	    	if ($index === null) {
	    		return $this->getTypedRuleContexts(ColumnContext::class);
	    	}

	        return $this->getTypedRuleContext(ColumnContext::class, $index);
	    }

	    /**
	     * @return array<TerminalNode>|TerminalNode|null
	     */
	    public function COMMA(?int $index = null)
	    {
	    	if ($index === null) {
	    		return $this->getTokens(PageSqlParser::COMMA);
	    	}

	        return $this->getToken(PageSqlParser::COMMA, $index);
	    }

		public function enterRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof PageSqlListener) {
			    $listener->enterColumns($this);
		    }
		}

		public function exitRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof PageSqlListener) {
			    $listener->exitColumns($this);
		    }
		}
	} 

	class PredicateGroupContext extends ParserRuleContext
	{
		public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
		{
			parent::__construct($parent, $invokingState);
		}

		public function getRuleIndex() : int
		{
		    return PageSqlParser::RULE_predicateGroup;
	    }

	    public function LPAREN() : ?TerminalNode
	    {
	        return $this->getToken(PageSqlParser::LPAREN, 0);
	    }

	    /**
	     * @return array<PredicateContext>|PredicateContext|null
	     */
	    public function predicate(?int $index = null)
	    {
	    	if ($index === null) {
	    		return $this->getTypedRuleContexts(PredicateContext::class);
	    	}

	        return $this->getTypedRuleContext(PredicateContext::class, $index);
	    }

	    public function RPAREN() : ?TerminalNode
	    {
	        return $this->getToken(PageSqlParser::RPAREN, 0);
	    }

	    /**
	     * @return array<TerminalNode>|TerminalNode|null
	     */
	    public function AND(?int $index = null)
	    {
	    	if ($index === null) {
	    		return $this->getTokens(PageSqlParser::AND);
	    	}

	        return $this->getToken(PageSqlParser::AND, $index);
	    }

	    /**
	     * @return array<TerminalNode>|TerminalNode|null
	     */
	    public function OR(?int $index = null)
	    {
	    	if ($index === null) {
	    		return $this->getTokens(PageSqlParser::OR);
	    	}

	        return $this->getToken(PageSqlParser::OR, $index);
	    }

		public function enterRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof PageSqlListener) {
			    $listener->enterPredicateGroup($this);
		    }
		}

		public function exitRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof PageSqlListener) {
			    $listener->exitPredicateGroup($this);
		    }
		}
	} 

	class PredicatesContext extends ParserRuleContext
	{
		public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
		{
			parent::__construct($parent, $invokingState);
		}

		public function getRuleIndex() : int
		{
		    return PageSqlParser::RULE_predicates;
	    }

	    public function WHERE() : ?TerminalNode
	    {
	        return $this->getToken(PageSqlParser::WHERE, 0);
	    }

	    /**
	     * @return array<PredicateContext>|PredicateContext|null
	     */
	    public function predicate(?int $index = null)
	    {
	    	if ($index === null) {
	    		return $this->getTypedRuleContexts(PredicateContext::class);
	    	}

	        return $this->getTypedRuleContext(PredicateContext::class, $index);
	    }

	    /**
	     * @return array<PredicateGroupContext>|PredicateGroupContext|null
	     */
	    public function predicateGroup(?int $index = null)
	    {
	    	if ($index === null) {
	    		return $this->getTypedRuleContexts(PredicateGroupContext::class);
	    	}

	        return $this->getTypedRuleContext(PredicateGroupContext::class, $index);
	    }

	    /**
	     * @return array<TerminalNode>|TerminalNode|null
	     */
	    public function AND(?int $index = null)
	    {
	    	if ($index === null) {
	    		return $this->getTokens(PageSqlParser::AND);
	    	}

	        return $this->getToken(PageSqlParser::AND, $index);
	    }

	    /**
	     * @return array<TerminalNode>|TerminalNode|null
	     */
	    public function OR(?int $index = null)
	    {
	    	if ($index === null) {
	    		return $this->getTokens(PageSqlParser::OR);
	    	}

	        return $this->getToken(PageSqlParser::OR, $index);
	    }

		public function enterRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof PageSqlListener) {
			    $listener->enterPredicates($this);
		    }
		}

		public function exitRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof PageSqlListener) {
			    $listener->exitPredicates($this);
		    }
		}
	} 

	class TablesContext extends ParserRuleContext
	{
		public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
		{
			parent::__construct($parent, $invokingState);
		}

		public function getRuleIndex() : int
		{
		    return PageSqlParser::RULE_tables;
	    }

	    public function FROM() : ?TerminalNode
	    {
	        return $this->getToken(PageSqlParser::FROM, 0);
	    }

	    public function tableNames() : ?TableNamesContext
	    {
	    	return $this->getTypedRuleContext(TableNamesContext::class, 0);
	    }

		public function enterRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof PageSqlListener) {
			    $listener->enterTables($this);
		    }
		}

		public function exitRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof PageSqlListener) {
			    $listener->exitTables($this);
		    }
		}
	} 

	class LimitContext extends ParserRuleContext
	{
		public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
		{
			parent::__construct($parent, $invokingState);
		}

		public function getRuleIndex() : int
		{
		    return PageSqlParser::RULE_limit;
	    }

	    public function LIMIT() : ?TerminalNode
	    {
	        return $this->getToken(PageSqlParser::LIMIT, 0);
	    }

	    public function Number() : ?TerminalNode
	    {
	        return $this->getToken(PageSqlParser::Number, 0);
	    }

		public function enterRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof PageSqlListener) {
			    $listener->enterLimit($this);
		    }
		}

		public function exitRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof PageSqlListener) {
			    $listener->exitLimit($this);
		    }
		}
	} 

	class OrderBysContext extends ParserRuleContext
	{
		public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
		{
			parent::__construct($parent, $invokingState);
		}

		public function getRuleIndex() : int
		{
		    return PageSqlParser::RULE_orderBys;
	    }

	    public function ORDER() : ?TerminalNode
	    {
	        return $this->getToken(PageSqlParser::ORDER, 0);
	    }

	    public function RANDOM() : ?TerminalNode
	    {
	        return $this->getToken(PageSqlParser::RANDOM, 0);
	    }

	    public function BY() : ?TerminalNode
	    {
	        return $this->getToken(PageSqlParser::BY, 0);
	    }

	    /**
	     * @return array<OrderByDefContext>|OrderByDefContext|null
	     */
	    public function orderByDef(?int $index = null)
	    {
	    	if ($index === null) {
	    		return $this->getTypedRuleContexts(OrderByDefContext::class);
	    	}

	        return $this->getTypedRuleContext(OrderByDefContext::class, $index);
	    }

	    /**
	     * @return array<TerminalNode>|TerminalNode|null
	     */
	    public function COMMA(?int $index = null)
	    {
	    	if ($index === null) {
	    		return $this->getTokens(PageSqlParser::COMMA);
	    	}

	        return $this->getToken(PageSqlParser::COMMA, $index);
	    }

		public function enterRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof PageSqlListener) {
			    $listener->enterOrderBys($this);
		    }
		}

		public function exitRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof PageSqlListener) {
			    $listener->exitOrderBys($this);
		    }
		}
	} 

	class OrderByDefContext extends ParserRuleContext
	{
		public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
		{
			parent::__construct($parent, $invokingState);
		}

		public function getRuleIndex() : int
		{
		    return PageSqlParser::RULE_orderByDef;
	    }

	    public function SqlName() : ?TerminalNode
	    {
	        return $this->getToken(PageSqlParser::SqlName, 0);
	    }

	    public function ASC() : ?TerminalNode
	    {
	        return $this->getToken(PageSqlParser::ASC, 0);
	    }

	    public function DESC() : ?TerminalNode
	    {
	        return $this->getToken(PageSqlParser::DESC, 0);
	    }

		public function enterRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof PageSqlListener) {
			    $listener->enterOrderByDef($this);
		    }
		}

		public function exitRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof PageSqlListener) {
			    $listener->exitOrderByDef($this);
		    }
		}
	} 

	class PageSqlContext extends ParserRuleContext
	{
		public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
		{
			parent::__construct($parent, $invokingState);
		}

		public function getRuleIndex() : int
		{
		    return PageSqlParser::RULE_pageSql;
	    }

	    public function SELECT() : ?TerminalNode
	    {
	        return $this->getToken(PageSqlParser::SELECT, 0);
	    }

	    public function RANDOM() : ?TerminalNode
	    {
	        return $this->getToken(PageSqlParser::RANDOM, 0);
	    }

	    public function STAR() : ?TerminalNode
	    {
	        return $this->getToken(PageSqlParser::STAR, 0);
	    }

	    public function columns() : ?ColumnsContext
	    {
	    	return $this->getTypedRuleContext(ColumnsContext::class, 0);
	    }

	    public function tables() : ?TablesContext
	    {
	    	return $this->getTypedRuleContext(TablesContext::class, 0);
	    }

	    public function predicates() : ?PredicatesContext
	    {
	    	return $this->getTypedRuleContext(PredicatesContext::class, 0);
	    }

	    public function orderBys() : ?OrderBysContext
	    {
	    	return $this->getTypedRuleContext(OrderBysContext::class, 0);
	    }

	    public function limit() : ?LimitContext
	    {
	    	return $this->getTypedRuleContext(LimitContext::class, 0);
	    }

		public function enterRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof PageSqlListener) {
			    $listener->enterPageSql($this);
		    }
		}

		public function exitRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof PageSqlListener) {
			    $listener->exitPageSql($this);
		    }
		}
	} 
}