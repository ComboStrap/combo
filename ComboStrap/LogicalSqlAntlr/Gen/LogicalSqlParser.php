<?php

/*
 * Generated from D:/dokuwiki/lib/plugins/combo/grammar\LogicalSql.g4 by ANTLR 4.9.1
 */

namespace ComboStrap\LogicalSqlAntlr\Gen {
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

	final class LogicalSqlParser extends Parser
	{
		public const SCOL = 1, DOT = 2, LPAREN = 3, RPAREN = 4, LSQUARE = 5, RSQUARE = 6, 
               LCURLY = 7, RCURLY = 8, COMMA = 9, BITWISEXOR = 10, DOLLAR = 11, 
               EQUAL = 12, STAR = 13, PLUS = 14, MINUS = 15, TILDE = 16, 
               PIPE2 = 17, DIV = 18, MOD = 19, LT2 = 20, GT2 = 21, AMP = 22, 
               PIPE = 23, QUESTION = 24, LESS_THAN = 25, LESS_THAN_OR_EQUAL = 26, 
               GREATER_THAN = 27, GREATER_THAN_OR_EQUAL = 28, EQ = 29, NOT_EQUAL = 30, 
               NOT_EQ2 = 31, AND = 32, AS = 33, ASC = 34, BETWEEN = 35, 
               BY = 36, DESC = 37, FALSE = 38, FROM = 39, GLOB = 40, IN = 41, 
               IS = 42, ISNULL = 43, LIKE = 44, LIMIT = 45, NOT = 46, NOTNULL = 47, 
               NOW = 48, NULL = 49, OR = 50, ORDER = 51, SELECT = 52, TRUE = 53, 
               WHERE = 54, RANDOM = 55, StringLiteral = 56, CharSetLiteral = 57, 
               IntegralLiteral = 58, Number = 59, NumberLiteral = 60, ByteLengthLiteral = 61, 
               SqlName = 62, SPACES = 63;

		public const RULE_sqlNames = 0, RULE_column = 1, RULE_expression = 2, 
               RULE_predicate = 3, RULE_columns = 4, RULE_predicates = 5, 
               RULE_tables = 6, RULE_limit = 7, RULE_orderBys = 8, RULE_orderByDef = 9, 
               RULE_logicalSql = 10;

		/**
		 * @var array<string>
		 */
		public const RULE_NAMES = [
			'sqlNames', 'column', 'expression', 'predicate', 'columns', 'predicates', 
			'tables', 'limit', 'orderBys', 'orderByDef', 'logicalSql'
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
		    "DESC", "FALSE", "FROM", "GLOB", "IN", "IS", "ISNULL", "LIKE", "LIMIT", 
		    "NOT", "NOTNULL", "NOW", "NULL", "OR", "ORDER", "SELECT", "TRUE", 
		    "WHERE", "RANDOM", "StringLiteral", "CharSetLiteral", "IntegralLiteral", 
		    "Number", "NumberLiteral", "ByteLengthLiteral", "SqlName", "SPACES"
		];

		/**
		 * @var string
		 */
		private const SERIALIZED_ATN =
			"\u{3}\u{608B}\u{A72A}\u{8133}\u{B9ED}\u{417C}\u{3BE7}\u{7786}\u{5964}" .
		    "\u{3}\u{41}\u{8F}\u{4}\u{2}\u{9}\u{2}\u{4}\u{3}\u{9}\u{3}\u{4}\u{4}" .
		    "\u{9}\u{4}\u{4}\u{5}\u{9}\u{5}\u{4}\u{6}\u{9}\u{6}\u{4}\u{7}\u{9}" .
		    "\u{7}\u{4}\u{8}\u{9}\u{8}\u{4}\u{9}\u{9}\u{9}\u{4}\u{A}\u{9}\u{A}" .
		    "\u{4}\u{B}\u{9}\u{B}\u{4}\u{C}\u{9}\u{C}\u{3}\u{2}\u{3}\u{2}\u{3}" .
		    "\u{3}\u{3}\u{3}\u{3}\u{3}\u{5}\u{3}\u{1E}\u{A}\u{3}\u{3}\u{3}\u{5}" .
		    "\u{3}\u{21}\u{A}\u{3}\u{3}\u{3}\u{3}\u{3}\u{5}\u{3}\u{25}\u{A}\u{3}" .
		    "\u{5}\u{3}\u{27}\u{A}\u{3}\u{3}\u{4}\u{3}\u{4}\u{3}\u{4}\u{3}\u{4}" .
		    "\u{3}\u{4}\u{7}\u{4}\u{2E}\u{A}\u{4}\u{C}\u{4}\u{E}\u{4}\u{31}\u{B}" .
		    "\u{4}\u{3}\u{4}\u{3}\u{4}\u{5}\u{4}\u{35}\u{A}\u{4}\u{3}\u{5}\u{3}" .
		    "\u{5}\u{3}\u{5}\u{3}\u{5}\u{5}\u{5}\u{3B}\u{A}\u{5}\u{3}\u{5}\u{3}" .
		    "\u{5}\u{3}\u{5}\u{5}\u{5}\u{40}\u{A}\u{5}\u{3}\u{5}\u{3}\u{5}\u{3}" .
		    "\u{5}\u{3}\u{5}\u{3}\u{5}\u{3}\u{5}\u{5}\u{5}\u{48}\u{A}\u{5}\u{3}" .
		    "\u{5}\u{3}\u{5}\u{3}\u{5}\u{3}\u{5}\u{3}\u{5}\u{7}\u{5}\u{4F}\u{A}" .
		    "\u{5}\u{C}\u{5}\u{E}\u{5}\u{52}\u{B}\u{5}\u{5}\u{5}\u{54}\u{A}\u{5}" .
		    "\u{3}\u{5}\u{5}\u{5}\u{57}\u{A}\u{5}\u{3}\u{6}\u{3}\u{6}\u{3}\u{6}" .
		    "\u{7}\u{6}\u{5C}\u{A}\u{6}\u{C}\u{6}\u{E}\u{6}\u{5F}\u{B}\u{6}\u{3}" .
		    "\u{7}\u{3}\u{7}\u{3}\u{7}\u{3}\u{7}\u{7}\u{7}\u{65}\u{A}\u{7}\u{C}" .
		    "\u{7}\u{E}\u{7}\u{68}\u{B}\u{7}\u{3}\u{8}\u{3}\u{8}\u{3}\u{8}\u{3}" .
		    "\u{9}\u{3}\u{9}\u{3}\u{9}\u{3}\u{A}\u{3}\u{A}\u{3}\u{A}\u{3}\u{A}" .
		    "\u{3}\u{A}\u{7}\u{A}\u{75}\u{A}\u{A}\u{C}\u{A}\u{E}\u{A}\u{78}\u{B}" .
		    "\u{A}\u{3}\u{B}\u{3}\u{B}\u{5}\u{B}\u{7C}\u{A}\u{B}\u{3}\u{C}\u{3}" .
		    "\u{C}\u{5}\u{C}\u{80}\u{A}\u{C}\u{3}\u{C}\u{3}\u{C}\u{5}\u{C}\u{84}" .
		    "\u{A}\u{C}\u{3}\u{C}\u{5}\u{C}\u{87}\u{A}\u{C}\u{3}\u{C}\u{5}\u{C}" .
		    "\u{8A}\u{A}\u{C}\u{3}\u{C}\u{5}\u{C}\u{8D}\u{A}\u{C}\u{3}\u{C}\u{2}" .
		    "\u{2}\u{D}\u{2}\u{4}\u{6}\u{8}\u{A}\u{C}\u{E}\u{10}\u{12}\u{14}\u{16}" .
		    "\u{2}\u{8}\u{4}\u{2}\u{3D}\u{3D}\u{40}\u{40}\u{5}\u{2}\u{3A}\u{3A}" .
		    "\u{3D}\u{3E}\u{40}\u{40}\u{5}\u{2}\u{E}\u{E}\u{1B}\u{1E}\u{20}\u{20}" .
		    "\u{4}\u{2}\u{2A}\u{2A}\u{2E}\u{2E}\u{4}\u{2}\u{22}\u{22}\u{34}\u{34}" .
		    "\u{4}\u{2}\u{24}\u{24}\u{27}\u{27}\u{2}\u{9A}\u{2}\u{18}\u{3}\u{2}" .
		    "\u{2}\u{2}\u{4}\u{1A}\u{3}\u{2}\u{2}\u{2}\u{6}\u{34}\u{3}\u{2}\u{2}" .
		    "\u{2}\u{8}\u{36}\u{3}\u{2}\u{2}\u{2}\u{A}\u{58}\u{3}\u{2}\u{2}\u{2}" .
		    "\u{C}\u{60}\u{3}\u{2}\u{2}\u{2}\u{E}\u{69}\u{3}\u{2}\u{2}\u{2}\u{10}" .
		    "\u{6C}\u{3}\u{2}\u{2}\u{2}\u{12}\u{6F}\u{3}\u{2}\u{2}\u{2}\u{14}\u{79}" .
		    "\u{3}\u{2}\u{2}\u{2}\u{16}\u{7D}\u{3}\u{2}\u{2}\u{2}\u{18}\u{19}\u{9}" .
		    "\u{2}\u{2}\u{2}\u{19}\u{3}\u{3}\u{2}\u{2}\u{2}\u{1A}\u{1D}\u{5}\u{2}" .
		    "\u{2}\u{2}\u{1B}\u{1C}\u{7}\u{4}\u{2}\u{2}\u{1C}\u{1E}\u{5}\u{2}\u{2}" .
		    "\u{2}\u{1D}\u{1B}\u{3}\u{2}\u{2}\u{2}\u{1D}\u{1E}\u{3}\u{2}\u{2}\u{2}" .
		    "\u{1E}\u{26}\u{3}\u{2}\u{2}\u{2}\u{1F}\u{21}\u{7}\u{23}\u{2}\u{2}" .
		    "\u{20}\u{1F}\u{3}\u{2}\u{2}\u{2}\u{20}\u{21}\u{3}\u{2}\u{2}\u{2}\u{21}" .
		    "\u{24}\u{3}\u{2}\u{2}\u{2}\u{22}\u{25}\u{5}\u{2}\u{2}\u{2}\u{23}\u{25}" .
		    "\u{7}\u{3A}\u{2}\u{2}\u{24}\u{22}\u{3}\u{2}\u{2}\u{2}\u{24}\u{23}" .
		    "\u{3}\u{2}\u{2}\u{2}\u{25}\u{27}\u{3}\u{2}\u{2}\u{2}\u{26}\u{20}\u{3}" .
		    "\u{2}\u{2}\u{2}\u{26}\u{27}\u{3}\u{2}\u{2}\u{2}\u{27}\u{5}\u{3}\u{2}" .
		    "\u{2}\u{2}\u{28}\u{35}\u{9}\u{3}\u{2}\u{2}\u{29}\u{2A}\u{7}\u{5}\u{2}" .
		    "\u{2}\u{2A}\u{2F}\u{5}\u{6}\u{4}\u{2}\u{2B}\u{2C}\u{7}\u{B}\u{2}\u{2}" .
		    "\u{2C}\u{2E}\u{5}\u{6}\u{4}\u{2}\u{2D}\u{2B}\u{3}\u{2}\u{2}\u{2}\u{2E}" .
		    "\u{31}\u{3}\u{2}\u{2}\u{2}\u{2F}\u{2D}\u{3}\u{2}\u{2}\u{2}\u{2F}\u{30}" .
		    "\u{3}\u{2}\u{2}\u{2}\u{30}\u{32}\u{3}\u{2}\u{2}\u{2}\u{31}\u{2F}\u{3}" .
		    "\u{2}\u{2}\u{2}\u{32}\u{33}\u{7}\u{6}\u{2}\u{2}\u{33}\u{35}\u{3}\u{2}" .
		    "\u{2}\u{2}\u{34}\u{28}\u{3}\u{2}\u{2}\u{2}\u{34}\u{29}\u{3}\u{2}\u{2}" .
		    "\u{2}\u{35}\u{7}\u{3}\u{2}\u{2}\u{2}\u{36}\u{56}\u{5}\u{2}\u{2}\u{2}" .
		    "\u{37}\u{38}\u{9}\u{4}\u{2}\u{2}\u{38}\u{57}\u{5}\u{6}\u{4}\u{2}\u{39}" .
		    "\u{3B}\u{7}\u{30}\u{2}\u{2}\u{3A}\u{39}\u{3}\u{2}\u{2}\u{2}\u{3A}" .
		    "\u{3B}\u{3}\u{2}\u{2}\u{2}\u{3B}\u{3C}\u{3}\u{2}\u{2}\u{2}\u{3C}\u{3D}" .
		    "\u{9}\u{5}\u{2}\u{2}\u{3D}\u{57}\u{5}\u{6}\u{4}\u{2}\u{3E}\u{40}\u{7}" .
		    "\u{30}\u{2}\u{2}\u{3F}\u{3E}\u{3}\u{2}\u{2}\u{2}\u{3F}\u{40}\u{3}" .
		    "\u{2}\u{2}\u{2}\u{40}\u{41}\u{3}\u{2}\u{2}\u{2}\u{41}\u{42}\u{7}\u{25}" .
		    "\u{2}\u{2}\u{42}\u{43}\u{5}\u{6}\u{4}\u{2}\u{43}\u{44}\u{7}\u{22}" .
		    "\u{2}\u{2}\u{44}\u{45}\u{5}\u{6}\u{4}\u{2}\u{45}\u{57}\u{3}\u{2}\u{2}" .
		    "\u{2}\u{46}\u{48}\u{7}\u{30}\u{2}\u{2}\u{47}\u{46}\u{3}\u{2}\u{2}" .
		    "\u{2}\u{47}\u{48}\u{3}\u{2}\u{2}\u{2}\u{48}\u{49}\u{3}\u{2}\u{2}\u{2}" .
		    "\u{49}\u{4A}\u{7}\u{2B}\u{2}\u{2}\u{4A}\u{53}\u{7}\u{5}\u{2}\u{2}" .
		    "\u{4B}\u{50}\u{5}\u{6}\u{4}\u{2}\u{4C}\u{4D}\u{7}\u{B}\u{2}\u{2}\u{4D}" .
		    "\u{4F}\u{5}\u{6}\u{4}\u{2}\u{4E}\u{4C}\u{3}\u{2}\u{2}\u{2}\u{4F}\u{52}" .
		    "\u{3}\u{2}\u{2}\u{2}\u{50}\u{4E}\u{3}\u{2}\u{2}\u{2}\u{50}\u{51}\u{3}" .
		    "\u{2}\u{2}\u{2}\u{51}\u{54}\u{3}\u{2}\u{2}\u{2}\u{52}\u{50}\u{3}\u{2}" .
		    "\u{2}\u{2}\u{53}\u{4B}\u{3}\u{2}\u{2}\u{2}\u{53}\u{54}\u{3}\u{2}\u{2}" .
		    "\u{2}\u{54}\u{55}\u{3}\u{2}\u{2}\u{2}\u{55}\u{57}\u{7}\u{6}\u{2}\u{2}" .
		    "\u{56}\u{37}\u{3}\u{2}\u{2}\u{2}\u{56}\u{3A}\u{3}\u{2}\u{2}\u{2}\u{56}" .
		    "\u{3F}\u{3}\u{2}\u{2}\u{2}\u{56}\u{47}\u{3}\u{2}\u{2}\u{2}\u{57}\u{9}" .
		    "\u{3}\u{2}\u{2}\u{2}\u{58}\u{5D}\u{5}\u{4}\u{3}\u{2}\u{59}\u{5A}\u{7}" .
		    "\u{B}\u{2}\u{2}\u{5A}\u{5C}\u{5}\u{4}\u{3}\u{2}\u{5B}\u{59}\u{3}\u{2}" .
		    "\u{2}\u{2}\u{5C}\u{5F}\u{3}\u{2}\u{2}\u{2}\u{5D}\u{5B}\u{3}\u{2}\u{2}" .
		    "\u{2}\u{5D}\u{5E}\u{3}\u{2}\u{2}\u{2}\u{5E}\u{B}\u{3}\u{2}\u{2}\u{2}" .
		    "\u{5F}\u{5D}\u{3}\u{2}\u{2}\u{2}\u{60}\u{61}\u{7}\u{38}\u{2}\u{2}" .
		    "\u{61}\u{66}\u{5}\u{8}\u{5}\u{2}\u{62}\u{63}\u{9}\u{6}\u{2}\u{2}\u{63}" .
		    "\u{65}\u{5}\u{8}\u{5}\u{2}\u{64}\u{62}\u{3}\u{2}\u{2}\u{2}\u{65}\u{68}" .
		    "\u{3}\u{2}\u{2}\u{2}\u{66}\u{64}\u{3}\u{2}\u{2}\u{2}\u{66}\u{67}\u{3}" .
		    "\u{2}\u{2}\u{2}\u{67}\u{D}\u{3}\u{2}\u{2}\u{2}\u{68}\u{66}\u{3}\u{2}" .
		    "\u{2}\u{2}\u{69}\u{6A}\u{7}\u{29}\u{2}\u{2}\u{6A}\u{6B}\u{7}\u{40}" .
		    "\u{2}\u{2}\u{6B}\u{F}\u{3}\u{2}\u{2}\u{2}\u{6C}\u{6D}\u{7}\u{2F}\u{2}" .
		    "\u{2}\u{6D}\u{6E}\u{7}\u{3D}\u{2}\u{2}\u{6E}\u{11}\u{3}\u{2}\u{2}" .
		    "\u{2}\u{6F}\u{70}\u{7}\u{35}\u{2}\u{2}\u{70}\u{71}\u{7}\u{26}\u{2}" .
		    "\u{2}\u{71}\u{76}\u{5}\u{14}\u{B}\u{2}\u{72}\u{73}\u{7}\u{B}\u{2}" .
		    "\u{2}\u{73}\u{75}\u{5}\u{14}\u{B}\u{2}\u{74}\u{72}\u{3}\u{2}\u{2}" .
		    "\u{2}\u{75}\u{78}\u{3}\u{2}\u{2}\u{2}\u{76}\u{74}\u{3}\u{2}\u{2}\u{2}" .
		    "\u{76}\u{77}\u{3}\u{2}\u{2}\u{2}\u{77}\u{13}\u{3}\u{2}\u{2}\u{2}\u{78}" .
		    "\u{76}\u{3}\u{2}\u{2}\u{2}\u{79}\u{7B}\u{7}\u{40}\u{2}\u{2}\u{7A}" .
		    "\u{7C}\u{9}\u{7}\u{2}\u{2}\u{7B}\u{7A}\u{3}\u{2}\u{2}\u{2}\u{7B}\u{7C}" .
		    "\u{3}\u{2}\u{2}\u{2}\u{7C}\u{15}\u{3}\u{2}\u{2}\u{2}\u{7D}\u{7F}\u{7}" .
		    "\u{36}\u{2}\u{2}\u{7E}\u{80}\u{7}\u{39}\u{2}\u{2}\u{7F}\u{7E}\u{3}" .
		    "\u{2}\u{2}\u{2}\u{7F}\u{80}\u{3}\u{2}\u{2}\u{2}\u{80}\u{81}\u{3}\u{2}" .
		    "\u{2}\u{2}\u{81}\u{83}\u{5}\u{A}\u{6}\u{2}\u{82}\u{84}\u{5}\u{E}\u{8}" .
		    "\u{2}\u{83}\u{82}\u{3}\u{2}\u{2}\u{2}\u{83}\u{84}\u{3}\u{2}\u{2}\u{2}" .
		    "\u{84}\u{86}\u{3}\u{2}\u{2}\u{2}\u{85}\u{87}\u{5}\u{C}\u{7}\u{2}\u{86}" .
		    "\u{85}\u{3}\u{2}\u{2}\u{2}\u{86}\u{87}\u{3}\u{2}\u{2}\u{2}\u{87}\u{89}" .
		    "\u{3}\u{2}\u{2}\u{2}\u{88}\u{8A}\u{5}\u{12}\u{A}\u{2}\u{89}\u{88}" .
		    "\u{3}\u{2}\u{2}\u{2}\u{89}\u{8A}\u{3}\u{2}\u{2}\u{2}\u{8A}\u{8C}\u{3}" .
		    "\u{2}\u{2}\u{2}\u{8B}\u{8D}\u{5}\u{10}\u{9}\u{2}\u{8C}\u{8B}\u{3}" .
		    "\u{2}\u{2}\u{2}\u{8C}\u{8D}\u{3}\u{2}\u{2}\u{2}\u{8D}\u{17}\u{3}\u{2}" .
		    "\u{2}\u{2}\u{17}\u{1D}\u{20}\u{24}\u{26}\u{2F}\u{34}\u{3A}\u{3F}\u{47}" .
		    "\u{50}\u{53}\u{56}\u{5D}\u{66}\u{76}\u{7B}\u{7F}\u{83}\u{86}\u{89}" .
		    "\u{8C}";

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

			RuntimeMetaData::checkVersion('4.9.1', RuntimeMetaData::VERSION);

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
			return "LogicalSql.g4";
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
		public function sqlNames() : Context\SqlNamesContext
		{
		    $localContext = new Context\SqlNamesContext($this->ctx, $this->getState());

		    $this->enterRule($localContext, 0, self::RULE_sqlNames);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(22);

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

		    $this->enterRule($localContext, 2, self::RULE_column);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(24);
		        $this->sqlNames();
		        $this->setState(27);
		        $this->errorHandler->sync($this);
		        $_la = $this->input->LA(1);

		        if ($_la === self::DOT) {
		        	$this->setState(25);
		        	$this->match(self::DOT);
		        	$this->setState(26);
		        	$this->sqlNames();
		        }
		        $this->setState(36);
		        $this->errorHandler->sync($this);
		        $_la = $this->input->LA(1);

		        if (((($_la) & ~0x3f) === 0 && ((1 << $_la) & ((1 << self::AS) | (1 << self::StringLiteral) | (1 << self::Number) | (1 << self::SqlName))) !== 0)) {
		        	$this->setState(30);
		        	$this->errorHandler->sync($this);
		        	$_la = $this->input->LA(1);

		        	if ($_la === self::AS) {
		        		$this->setState(29);
		        		$this->match(self::AS);
		        	}
		        	$this->setState(34);
		        	$this->errorHandler->sync($this);

		        	switch ($this->input->LA(1)) {
		        	    case self::Number:
		        	    case self::SqlName:
		        	    	$this->setState(32);
		        	    	$this->sqlNames();
		        	    	break;

		        	    case self::StringLiteral:
		        	    	$this->setState(33);
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
		public function expression() : Context\ExpressionContext
		{
		    $localContext = new Context\ExpressionContext($this->ctx, $this->getState());

		    $this->enterRule($localContext, 4, self::RULE_expression);

		    try {
		        $this->setState(50);
		        $this->errorHandler->sync($this);

		        switch ($this->input->LA(1)) {
		            case self::StringLiteral:
		            case self::Number:
		            case self::NumberLiteral:
		            case self::SqlName:
		            	$this->enterOuterAlt($localContext, 1);
		            	$this->setState(38);

		            	$_la = $this->input->LA(1);

		            	if (!(((($_la) & ~0x3f) === 0 && ((1 << $_la) & ((1 << self::StringLiteral) | (1 << self::Number) | (1 << self::NumberLiteral) | (1 << self::SqlName))) !== 0))) {
		            	$this->errorHandler->recoverInline($this);
		            	} else {
		            		if ($this->input->LA(1) === Token::EOF) {
		            		    $this->matchedEOF = true;
		            	    }

		            		$this->errorHandler->reportMatch($this);
		            		$this->consume();
		            	}
		            	break;

		            case self::LPAREN:
		            	$this->enterOuterAlt($localContext, 2);
		            	$this->setState(39);
		            	$this->match(self::LPAREN);
		            	$this->setState(40);
		            	$this->expression();
		            	$this->setState(45);
		            	$this->errorHandler->sync($this);

		            	$_la = $this->input->LA(1);
		            	while ($_la === self::COMMA) {
		            		$this->setState(41);
		            		$this->match(self::COMMA);
		            		$this->setState(42);
		            		$this->expression();
		            		$this->setState(47);
		            		$this->errorHandler->sync($this);
		            		$_la = $this->input->LA(1);
		            	}
		            	$this->setState(48);
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

		    $this->enterRule($localContext, 6, self::RULE_predicate);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(52);
		        $this->sqlNames();
		        $this->setState(84);
		        $this->errorHandler->sync($this);

		        switch ($this->getInterpreter()->adaptivePredict($this->input, 11, $this->ctx)) {
		        	case 1:
		        	    $this->setState(53);

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
		        	    $this->setState(54);
		        	    $this->expression();
		        	break;

		        	case 2:
		        	    $this->setState(56);
		        	    $this->errorHandler->sync($this);
		        	    $_la = $this->input->LA(1);

		        	    if ($_la === self::NOT) {
		        	    	$this->setState(55);
		        	    	$this->match(self::NOT);
		        	    }
		        	    $this->setState(58);

		        	    $_la = $this->input->LA(1);

		        	    if (!($_la === self::GLOB || $_la === self::LIKE)) {
		        	    $this->errorHandler->recoverInline($this);
		        	    } else {
		        	    	if ($this->input->LA(1) === Token::EOF) {
		        	    	    $this->matchedEOF = true;
		        	        }

		        	    	$this->errorHandler->reportMatch($this);
		        	    	$this->consume();
		        	    }
		        	    $this->setState(59);
		        	    $this->expression();
		        	break;

		        	case 3:
		        	    $this->setState(61);
		        	    $this->errorHandler->sync($this);
		        	    $_la = $this->input->LA(1);

		        	    if ($_la === self::NOT) {
		        	    	$this->setState(60);
		        	    	$this->match(self::NOT);
		        	    }
		        	    $this->setState(63);
		        	    $this->match(self::BETWEEN);
		        	    $this->setState(64);
		        	    $this->expression();
		        	    $this->setState(65);
		        	    $this->match(self::AND);
		        	    $this->setState(66);
		        	    $this->expression();
		        	break;

		        	case 4:
		        	    $this->setState(69);
		        	    $this->errorHandler->sync($this);
		        	    $_la = $this->input->LA(1);

		        	    if ($_la === self::NOT) {
		        	    	$this->setState(68);
		        	    	$this->match(self::NOT);
		        	    }
		        	    $this->setState(71);
		        	    $this->match(self::IN);
		        	    $this->setState(72);
		        	    $this->match(self::LPAREN);
		        	    $this->setState(81);
		        	    $this->errorHandler->sync($this);
		        	    $_la = $this->input->LA(1);

		        	    if (((($_la) & ~0x3f) === 0 && ((1 << $_la) & ((1 << self::LPAREN) | (1 << self::StringLiteral) | (1 << self::Number) | (1 << self::NumberLiteral) | (1 << self::SqlName))) !== 0)) {
		        	    	$this->setState(73);
		        	    	$this->expression();
		        	    	$this->setState(78);
		        	    	$this->errorHandler->sync($this);

		        	    	$_la = $this->input->LA(1);
		        	    	while ($_la === self::COMMA) {
		        	    		$this->setState(74);
		        	    		$this->match(self::COMMA);
		        	    		$this->setState(75);
		        	    		$this->expression();
		        	    		$this->setState(80);
		        	    		$this->errorHandler->sync($this);
		        	    		$_la = $this->input->LA(1);
		        	    	}
		        	    }
		        	    $this->setState(83);
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

		    $this->enterRule($localContext, 8, self::RULE_columns);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(86);
		        $this->column();
		        $this->setState(91);
		        $this->errorHandler->sync($this);

		        $_la = $this->input->LA(1);
		        while ($_la === self::COMMA) {
		        	$this->setState(87);
		        	$this->match(self::COMMA);
		        	$this->setState(88);
		        	$this->column();
		        	$this->setState(93);
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
		public function predicates() : Context\PredicatesContext
		{
		    $localContext = new Context\PredicatesContext($this->ctx, $this->getState());

		    $this->enterRule($localContext, 10, self::RULE_predicates);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(94);
		        $this->match(self::WHERE);
		        $this->setState(95);
		        $this->predicate();
		        $this->setState(100);
		        $this->errorHandler->sync($this);

		        $_la = $this->input->LA(1);
		        while ($_la === self::AND || $_la === self::OR) {
		        	$this->setState(96);

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
		        	$this->setState(97);
		        	$this->predicate();
		        	$this->setState(102);
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

		    $this->enterRule($localContext, 12, self::RULE_tables);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(103);
		        $this->match(self::FROM);
		        $this->setState(104);
		        $this->match(self::SqlName);
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

		    $this->enterRule($localContext, 14, self::RULE_limit);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(106);
		        $this->match(self::LIMIT);
		        $this->setState(107);
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

		    $this->enterRule($localContext, 16, self::RULE_orderBys);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(109);
		        $this->match(self::ORDER);
		        $this->setState(110);
		        $this->match(self::BY);
		        $this->setState(111);
		        $this->orderByDef();
		        $this->setState(116);
		        $this->errorHandler->sync($this);

		        $_la = $this->input->LA(1);
		        while ($_la === self::COMMA) {
		        	$this->setState(112);
		        	$this->match(self::COMMA);
		        	$this->setState(113);
		        	$this->orderByDef();
		        	$this->setState(118);
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
		public function orderByDef() : Context\OrderByDefContext
		{
		    $localContext = new Context\OrderByDefContext($this->ctx, $this->getState());

		    $this->enterRule($localContext, 18, self::RULE_orderByDef);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(119);
		        $this->match(self::SqlName);
		        $this->setState(121);
		        $this->errorHandler->sync($this);
		        $_la = $this->input->LA(1);

		        if ($_la === self::ASC || $_la === self::DESC) {
		        	$this->setState(120);

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
		public function logicalSql() : Context\LogicalSqlContext
		{
		    $localContext = new Context\LogicalSqlContext($this->ctx, $this->getState());

		    $this->enterRule($localContext, 20, self::RULE_logicalSql);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(123);
		        $this->match(self::SELECT);
		        $this->setState(125);
		        $this->errorHandler->sync($this);
		        $_la = $this->input->LA(1);

		        if ($_la === self::RANDOM) {
		        	$this->setState(124);
		        	$this->match(self::RANDOM);
		        }
		        $this->setState(127);
		        $this->columns();
		        $this->setState(129);
		        $this->errorHandler->sync($this);
		        $_la = $this->input->LA(1);

		        if ($_la === self::FROM) {
		        	$this->setState(128);
		        	$this->tables();
		        }
		        $this->setState(132);
		        $this->errorHandler->sync($this);
		        $_la = $this->input->LA(1);

		        if ($_la === self::WHERE) {
		        	$this->setState(131);
		        	$this->predicates();
		        }
		        $this->setState(135);
		        $this->errorHandler->sync($this);
		        $_la = $this->input->LA(1);

		        if ($_la === self::ORDER) {
		        	$this->setState(134);
		        	$this->orderBys();
		        }
		        $this->setState(138);
		        $this->errorHandler->sync($this);
		        $_la = $this->input->LA(1);

		        if ($_la === self::LIMIT) {
		        	$this->setState(137);
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

namespace ComboStrap\LogicalSqlAntlr\Gen\Context {
	use Antlr\Antlr4\Runtime\ParserRuleContext;
	use Antlr\Antlr4\Runtime\Token;
	use Antlr\Antlr4\Runtime\Tree\ParseTreeVisitor;
	use Antlr\Antlr4\Runtime\Tree\TerminalNode;
	use Antlr\Antlr4\Runtime\Tree\ParseTreeListener;
	use ComboStrap\LogicalSqlAntlr\Gen\LogicalSqlParser;
	use ComboStrap\LogicalSqlAntlr\Gen\LogicalSqlVisitor;
	use ComboStrap\LogicalSqlAntlr\Gen\LogicalSqlListener;

	class SqlNamesContext extends ParserRuleContext
	{
		public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
		{
			parent::__construct($parent, $invokingState);
		}

		public function getRuleIndex() : int
		{
		    return LogicalSqlParser::RULE_sqlNames;
	    }

	    public function SqlName() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::SqlName, 0);
	    }

	    public function Number() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::Number, 0);
	    }

		public function enterRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof LogicalSqlListener) {
			    $listener->enterSqlNames($this);
		    }
		}

		public function exitRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof LogicalSqlListener) {
			    $listener->exitSqlNames($this);
		    }
		}

		public function accept(ParseTreeVisitor $visitor)
		{
			if ($visitor instanceof LogicalSqlVisitor) {
			    return $visitor->visitSqlNames($this);
		    }

			return $visitor->visitChildren($this);
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
		    return LogicalSqlParser::RULE_column;
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
	        return $this->getToken(LogicalSqlParser::DOT, 0);
	    }

	    public function StringLiteral() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::StringLiteral, 0);
	    }

	    public function AS() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::AS, 0);
	    }

		public function enterRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof LogicalSqlListener) {
			    $listener->enterColumn($this);
		    }
		}

		public function exitRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof LogicalSqlListener) {
			    $listener->exitColumn($this);
		    }
		}

		public function accept(ParseTreeVisitor $visitor)
		{
			if ($visitor instanceof LogicalSqlVisitor) {
			    return $visitor->visitColumn($this);
		    }

			return $visitor->visitChildren($this);
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
		    return LogicalSqlParser::RULE_expression;
	    }

	    public function SqlName() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::SqlName, 0);
	    }

	    public function StringLiteral() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::StringLiteral, 0);
	    }

	    public function NumberLiteral() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::NumberLiteral, 0);
	    }

	    public function Number() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::Number, 0);
	    }

	    public function LPAREN() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::LPAREN, 0);
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

	    public function RPAREN() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::RPAREN, 0);
	    }

	    /**
	     * @return array<TerminalNode>|TerminalNode|null
	     */
	    public function COMMA(?int $index = null)
	    {
	    	if ($index === null) {
	    		return $this->getTokens(LogicalSqlParser::COMMA);
	    	}

	        return $this->getToken(LogicalSqlParser::COMMA, $index);
	    }

		public function enterRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof LogicalSqlListener) {
			    $listener->enterExpression($this);
		    }
		}

		public function exitRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof LogicalSqlListener) {
			    $listener->exitExpression($this);
		    }
		}

		public function accept(ParseTreeVisitor $visitor)
		{
			if ($visitor instanceof LogicalSqlVisitor) {
			    return $visitor->visitExpression($this);
		    }

			return $visitor->visitChildren($this);
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
		    return LogicalSqlParser::RULE_predicate;
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
	        return $this->getToken(LogicalSqlParser::BETWEEN, 0);
	    }

	    public function AND() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::AND, 0);
	    }

	    public function IN() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::IN, 0);
	    }

	    public function LPAREN() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::LPAREN, 0);
	    }

	    public function RPAREN() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::RPAREN, 0);
	    }

	    public function LESS_THAN() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::LESS_THAN, 0);
	    }

	    public function LESS_THAN_OR_EQUAL() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::LESS_THAN_OR_EQUAL, 0);
	    }

	    public function GREATER_THAN() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::GREATER_THAN, 0);
	    }

	    public function GREATER_THAN_OR_EQUAL() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::GREATER_THAN_OR_EQUAL, 0);
	    }

	    public function NOT_EQUAL() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::NOT_EQUAL, 0);
	    }

	    public function EQUAL() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::EQUAL, 0);
	    }

	    public function LIKE() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::LIKE, 0);
	    }

	    public function GLOB() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::GLOB, 0);
	    }

	    public function NOT() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::NOT, 0);
	    }

	    /**
	     * @return array<TerminalNode>|TerminalNode|null
	     */
	    public function COMMA(?int $index = null)
	    {
	    	if ($index === null) {
	    		return $this->getTokens(LogicalSqlParser::COMMA);
	    	}

	        return $this->getToken(LogicalSqlParser::COMMA, $index);
	    }

		public function enterRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof LogicalSqlListener) {
			    $listener->enterPredicate($this);
		    }
		}

		public function exitRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof LogicalSqlListener) {
			    $listener->exitPredicate($this);
		    }
		}

		public function accept(ParseTreeVisitor $visitor)
		{
			if ($visitor instanceof LogicalSqlVisitor) {
			    return $visitor->visitPredicate($this);
		    }

			return $visitor->visitChildren($this);
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
		    return LogicalSqlParser::RULE_columns;
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
	    		return $this->getTokens(LogicalSqlParser::COMMA);
	    	}

	        return $this->getToken(LogicalSqlParser::COMMA, $index);
	    }

		public function enterRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof LogicalSqlListener) {
			    $listener->enterColumns($this);
		    }
		}

		public function exitRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof LogicalSqlListener) {
			    $listener->exitColumns($this);
		    }
		}

		public function accept(ParseTreeVisitor $visitor)
		{
			if ($visitor instanceof LogicalSqlVisitor) {
			    return $visitor->visitColumns($this);
		    }

			return $visitor->visitChildren($this);
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
		    return LogicalSqlParser::RULE_predicates;
	    }

	    public function WHERE() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::WHERE, 0);
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
	     * @return array<TerminalNode>|TerminalNode|null
	     */
	    public function AND(?int $index = null)
	    {
	    	if ($index === null) {
	    		return $this->getTokens(LogicalSqlParser::AND);
	    	}

	        return $this->getToken(LogicalSqlParser::AND, $index);
	    }

	    /**
	     * @return array<TerminalNode>|TerminalNode|null
	     */
	    public function OR(?int $index = null)
	    {
	    	if ($index === null) {
	    		return $this->getTokens(LogicalSqlParser::OR);
	    	}

	        return $this->getToken(LogicalSqlParser::OR, $index);
	    }

		public function enterRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof LogicalSqlListener) {
			    $listener->enterPredicates($this);
		    }
		}

		public function exitRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof LogicalSqlListener) {
			    $listener->exitPredicates($this);
		    }
		}

		public function accept(ParseTreeVisitor $visitor)
		{
			if ($visitor instanceof LogicalSqlVisitor) {
			    return $visitor->visitPredicates($this);
		    }

			return $visitor->visitChildren($this);
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
		    return LogicalSqlParser::RULE_tables;
	    }

	    public function FROM() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::FROM, 0);
	    }

	    public function SqlName() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::SqlName, 0);
	    }

		public function enterRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof LogicalSqlListener) {
			    $listener->enterTables($this);
		    }
		}

		public function exitRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof LogicalSqlListener) {
			    $listener->exitTables($this);
		    }
		}

		public function accept(ParseTreeVisitor $visitor)
		{
			if ($visitor instanceof LogicalSqlVisitor) {
			    return $visitor->visitTables($this);
		    }

			return $visitor->visitChildren($this);
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
		    return LogicalSqlParser::RULE_limit;
	    }

	    public function LIMIT() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::LIMIT, 0);
	    }

	    public function Number() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::Number, 0);
	    }

		public function enterRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof LogicalSqlListener) {
			    $listener->enterLimit($this);
		    }
		}

		public function exitRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof LogicalSqlListener) {
			    $listener->exitLimit($this);
		    }
		}

		public function accept(ParseTreeVisitor $visitor)
		{
			if ($visitor instanceof LogicalSqlVisitor) {
			    return $visitor->visitLimit($this);
		    }

			return $visitor->visitChildren($this);
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
		    return LogicalSqlParser::RULE_orderBys;
	    }

	    public function ORDER() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::ORDER, 0);
	    }

	    public function BY() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::BY, 0);
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
	    		return $this->getTokens(LogicalSqlParser::COMMA);
	    	}

	        return $this->getToken(LogicalSqlParser::COMMA, $index);
	    }

		public function enterRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof LogicalSqlListener) {
			    $listener->enterOrderBys($this);
		    }
		}

		public function exitRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof LogicalSqlListener) {
			    $listener->exitOrderBys($this);
		    }
		}

		public function accept(ParseTreeVisitor $visitor)
		{
			if ($visitor instanceof LogicalSqlVisitor) {
			    return $visitor->visitOrderBys($this);
		    }

			return $visitor->visitChildren($this);
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
		    return LogicalSqlParser::RULE_orderByDef;
	    }

	    public function SqlName() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::SqlName, 0);
	    }

	    public function ASC() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::ASC, 0);
	    }

	    public function DESC() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::DESC, 0);
	    }

		public function enterRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof LogicalSqlListener) {
			    $listener->enterOrderByDef($this);
		    }
		}

		public function exitRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof LogicalSqlListener) {
			    $listener->exitOrderByDef($this);
		    }
		}

		public function accept(ParseTreeVisitor $visitor)
		{
			if ($visitor instanceof LogicalSqlVisitor) {
			    return $visitor->visitOrderByDef($this);
		    }

			return $visitor->visitChildren($this);
		}
	} 

	class LogicalSqlContext extends ParserRuleContext
	{
		public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
		{
			parent::__construct($parent, $invokingState);
		}

		public function getRuleIndex() : int
		{
		    return LogicalSqlParser::RULE_logicalSql;
	    }

	    public function SELECT() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::SELECT, 0);
	    }

	    public function columns() : ?ColumnsContext
	    {
	    	return $this->getTypedRuleContext(ColumnsContext::class, 0);
	    }

	    public function RANDOM() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::RANDOM, 0);
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
			if ($listener instanceof LogicalSqlListener) {
			    $listener->enterLogicalSql($this);
		    }
		}

		public function exitRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof LogicalSqlListener) {
			    $listener->exitLogicalSql($this);
		    }
		}

		public function accept(ParseTreeVisitor $visitor)
		{
			if ($visitor instanceof LogicalSqlVisitor) {
			    return $visitor->visitLogicalSql($this);
		    }

			return $visitor->visitChildren($this);
		}
	} 
}