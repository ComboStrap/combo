<?php

/*
 * Generated from D:/dokuwiki/lib/plugins/combo/grammar\logicalSql.g4 by ANTLR 4.9.1
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

	final class logicalSqlParser extends Parser
	{
		public const SCOL = 1, DOT = 2, OPEN_PAR = 3, CLOSE_PAR = 4, COMMA = 5, 
               EQUAL = 6, STAR = 7, PLUS = 8, MINUS = 9, TILDE = 10, PIPE2 = 11, 
               DIV = 12, MOD = 13, LT2 = 14, GT2 = 15, AMP = 16, PIPE = 17, 
               LESS_THAN = 18, LESS_THAN_OR_EQUAL = 19, GREATER_THAN = 20, 
               GREATER_THAN_OR_EQUAL = 21, EQ = 22, NOT_EQUAL = 23, NOT_EQ2 = 24, 
               AND = 25, AS = 26, ASC = 27, BETWEEN = 28, BY = 29, DESC = 30, 
               FALSE = 31, FROM = 32, GLOB = 33, IN = 34, IS = 35, ISNULL = 36, 
               LIKE = 37, LIMIT = 38, NOT = 39, NOTNULL = 40, NOW = 41, 
               NULL = 42, OR = 43, ORDER = 44, SELECT = 45, TRUE = 46, WHERE = 47, 
               SPACES = 48, NUMERIC_LITERAL = 49, STRING_LITERAL = 50, IDENTIFIER = 51;

		public const RULE_result_column = 0, RULE_column_alias = 1, RULE_literal_value = 2, 
               RULE_predicate_expression = 3, RULE_logicalSql = 4, RULE_table_name = 5, 
               RULE_column_name = 6, RULE_any_name = 7, RULE_limit_stmt = 8, 
               RULE_order_by_stmt = 9, RULE_ordering_term = 10, RULE_asc_desc = 11;

		/**
		 * @var array<string>
		 */
		public const RULE_NAMES = [
			'result_column', 'column_alias', 'literal_value', 'predicate_expression', 
			'logicalSql', 'table_name', 'column_name', 'any_name', 'limit_stmt', 
			'order_by_stmt', 'ordering_term', 'asc_desc'
		];

		/**
		 * @var array<string|null>
		 */
		private const LITERAL_NAMES = [
		    null, "';'", "'.'", "'('", "')'", "','", "'='", "'*'", "'+'", "'-'", 
		    "'~'", "'||'", "'/'", "'%'", "'<<'", "'>>'", "'&'", "'|'", "'<'", 
		    "'<='", "'>'", "'>='", "'=='", "'!='", "'<>'"
		];

		/**
		 * @var array<string>
		 */
		private const SYMBOLIC_NAMES = [
		    null, "SCOL", "DOT", "OPEN_PAR", "CLOSE_PAR", "COMMA", "EQUAL", "STAR", 
		    "PLUS", "MINUS", "TILDE", "PIPE2", "DIV", "MOD", "LT2", "GT2", "AMP", 
		    "PIPE", "LESS_THAN", "LESS_THAN_OR_EQUAL", "GREATER_THAN", "GREATER_THAN_OR_EQUAL", 
		    "EQ", "NOT_EQUAL", "NOT_EQ2", "AND", "AS", "ASC", "BETWEEN", "BY", 
		    "DESC", "FALSE", "FROM", "GLOB", "IN", "IS", "ISNULL", "LIKE", "LIMIT", 
		    "NOT", "NOTNULL", "NOW", "NULL", "OR", "ORDER", "SELECT", "TRUE", 
		    "WHERE", "SPACES", "NUMERIC_LITERAL", "STRING_LITERAL", "IDENTIFIER"
		];

		/**
		 * @var string
		 */
		private const SERIALIZED_ATN =
			"\u{3}\u{608B}\u{A72A}\u{8133}\u{B9ED}\u{417C}\u{3BE7}\u{7786}\u{5964}" .
		    "\u{3}\u{35}\u{89}\u{4}\u{2}\u{9}\u{2}\u{4}\u{3}\u{9}\u{3}\u{4}\u{4}" .
		    "\u{9}\u{4}\u{4}\u{5}\u{9}\u{5}\u{4}\u{6}\u{9}\u{6}\u{4}\u{7}\u{9}" .
		    "\u{7}\u{4}\u{8}\u{9}\u{8}\u{4}\u{9}\u{9}\u{9}\u{4}\u{A}\u{9}\u{A}" .
		    "\u{4}\u{B}\u{9}\u{B}\u{4}\u{C}\u{9}\u{C}\u{4}\u{D}\u{9}\u{D}\u{3}" .
		    "\u{2}\u{3}\u{2}\u{3}\u{2}\u{5}\u{2}\u{1E}\u{A}\u{2}\u{3}\u{2}\u{5}" .
		    "\u{2}\u{21}\u{A}\u{2}\u{3}\u{2}\u{5}\u{2}\u{24}\u{A}\u{2}\u{3}\u{3}" .
		    "\u{3}\u{3}\u{3}\u{4}\u{3}\u{4}\u{3}\u{5}\u{3}\u{5}\u{3}\u{5}\u{3}" .
		    "\u{5}\u{5}\u{5}\u{2E}\u{A}\u{5}\u{3}\u{5}\u{3}\u{5}\u{3}\u{5}\u{5}" .
		    "\u{5}\u{33}\u{A}\u{5}\u{3}\u{5}\u{5}\u{5}\u{36}\u{A}\u{5}\u{3}\u{5}" .
		    "\u{3}\u{5}\u{3}\u{5}\u{3}\u{5}\u{3}\u{5}\u{3}\u{5}\u{5}\u{5}\u{3E}" .
		    "\u{A}\u{5}\u{3}\u{5}\u{3}\u{5}\u{3}\u{5}\u{3}\u{5}\u{3}\u{5}\u{7}" .
		    "\u{5}\u{45}\u{A}\u{5}\u{C}\u{5}\u{E}\u{5}\u{48}\u{B}\u{5}\u{5}\u{5}" .
		    "\u{4A}\u{A}\u{5}\u{3}\u{5}\u{5}\u{5}\u{4D}\u{A}\u{5}\u{3}\u{6}\u{3}" .
		    "\u{6}\u{3}\u{6}\u{3}\u{6}\u{7}\u{6}\u{53}\u{A}\u{6}\u{C}\u{6}\u{E}" .
		    "\u{6}\u{56}\u{B}\u{6}\u{3}\u{6}\u{3}\u{6}\u{5}\u{6}\u{5A}\u{A}\u{6}" .
		    "\u{3}\u{6}\u{3}\u{6}\u{3}\u{6}\u{3}\u{6}\u{5}\u{6}\u{60}\u{A}\u{6}" .
		    "\u{5}\u{6}\u{62}\u{A}\u{6}\u{3}\u{6}\u{5}\u{6}\u{65}\u{A}\u{6}\u{3}" .
		    "\u{6}\u{5}\u{6}\u{68}\u{A}\u{6}\u{3}\u{7}\u{3}\u{7}\u{3}\u{8}\u{3}" .
		    "\u{8}\u{3}\u{9}\u{3}\u{9}\u{3}\u{9}\u{3}\u{9}\u{3}\u{9}\u{3}\u{9}" .
		    "\u{5}\u{9}\u{74}\u{A}\u{9}\u{3}\u{A}\u{3}\u{A}\u{3}\u{A}\u{3}\u{B}" .
		    "\u{3}\u{B}\u{3}\u{B}\u{3}\u{B}\u{3}\u{B}\u{7}\u{B}\u{7E}\u{A}\u{B}" .
		    "\u{C}\u{B}\u{E}\u{B}\u{81}\u{B}\u{B}\u{3}\u{C}\u{3}\u{C}\u{5}\u{C}" .
		    "\u{85}\u{A}\u{C}\u{3}\u{D}\u{3}\u{D}\u{3}\u{D}\u{2}\u{2}\u{E}\u{2}" .
		    "\u{4}\u{6}\u{8}\u{A}\u{C}\u{E}\u{10}\u{12}\u{14}\u{16}\u{18}\u{2}" .
		    "\u{7}\u{3}\u{2}\u{34}\u{35}\u{6}\u{2}\u{21}\u{21}\u{2B}\u{2C}\u{30}" .
		    "\u{30}\u{33}\u{34}\u{5}\u{2}\u{8}\u{8}\u{14}\u{17}\u{19}\u{19}\u{4}" .
		    "\u{2}\u{1B}\u{1B}\u{2D}\u{2D}\u{4}\u{2}\u{1D}\u{1D}\u{20}\u{20}\u{2}" .
		    "\u{93}\u{2}\u{1A}\u{3}\u{2}\u{2}\u{2}\u{4}\u{25}\u{3}\u{2}\u{2}\u{2}" .
		    "\u{6}\u{27}\u{3}\u{2}\u{2}\u{2}\u{8}\u{29}\u{3}\u{2}\u{2}\u{2}\u{A}" .
		    "\u{4E}\u{3}\u{2}\u{2}\u{2}\u{C}\u{69}\u{3}\u{2}\u{2}\u{2}\u{E}\u{6B}" .
		    "\u{3}\u{2}\u{2}\u{2}\u{10}\u{73}\u{3}\u{2}\u{2}\u{2}\u{12}\u{75}\u{3}" .
		    "\u{2}\u{2}\u{2}\u{14}\u{78}\u{3}\u{2}\u{2}\u{2}\u{16}\u{82}\u{3}\u{2}" .
		    "\u{2}\u{2}\u{18}\u{86}\u{3}\u{2}\u{2}\u{2}\u{1A}\u{1D}\u{7}\u{35}" .
		    "\u{2}\u{2}\u{1B}\u{1C}\u{7}\u{4}\u{2}\u{2}\u{1C}\u{1E}\u{7}\u{35}" .
		    "\u{2}\u{2}\u{1D}\u{1B}\u{3}\u{2}\u{2}\u{2}\u{1D}\u{1E}\u{3}\u{2}\u{2}" .
		    "\u{2}\u{1E}\u{23}\u{3}\u{2}\u{2}\u{2}\u{1F}\u{21}\u{7}\u{1C}\u{2}" .
		    "\u{2}\u{20}\u{1F}\u{3}\u{2}\u{2}\u{2}\u{20}\u{21}\u{3}\u{2}\u{2}\u{2}" .
		    "\u{21}\u{22}\u{3}\u{2}\u{2}\u{2}\u{22}\u{24}\u{5}\u{4}\u{3}\u{2}\u{23}" .
		    "\u{20}\u{3}\u{2}\u{2}\u{2}\u{23}\u{24}\u{3}\u{2}\u{2}\u{2}\u{24}\u{3}" .
		    "\u{3}\u{2}\u{2}\u{2}\u{25}\u{26}\u{9}\u{2}\u{2}\u{2}\u{26}\u{5}\u{3}" .
		    "\u{2}\u{2}\u{2}\u{27}\u{28}\u{9}\u{3}\u{2}\u{2}\u{28}\u{7}\u{3}\u{2}" .
		    "\u{2}\u{2}\u{29}\u{4C}\u{5}\u{E}\u{8}\u{2}\u{2A}\u{2B}\u{9}\u{4}\u{2}" .
		    "\u{2}\u{2B}\u{4D}\u{5}\u{6}\u{4}\u{2}\u{2C}\u{2E}\u{7}\u{29}\u{2}" .
		    "\u{2}\u{2D}\u{2C}\u{3}\u{2}\u{2}\u{2}\u{2D}\u{2E}\u{3}\u{2}\u{2}\u{2}" .
		    "\u{2E}\u{32}\u{3}\u{2}\u{2}\u{2}\u{2F}\u{33}\u{7}\u{27}\u{2}\u{2}" .
		    "\u{30}\u{33}\u{7}\u{23}\u{2}\u{2}\u{31}\u{33}\u{5}\u{6}\u{4}\u{2}" .
		    "\u{32}\u{2F}\u{3}\u{2}\u{2}\u{2}\u{32}\u{30}\u{3}\u{2}\u{2}\u{2}\u{32}" .
		    "\u{31}\u{3}\u{2}\u{2}\u{2}\u{33}\u{4D}\u{3}\u{2}\u{2}\u{2}\u{34}\u{36}" .
		    "\u{7}\u{29}\u{2}\u{2}\u{35}\u{34}\u{3}\u{2}\u{2}\u{2}\u{35}\u{36}" .
		    "\u{3}\u{2}\u{2}\u{2}\u{36}\u{37}\u{3}\u{2}\u{2}\u{2}\u{37}\u{38}\u{7}" .
		    "\u{1E}\u{2}\u{2}\u{38}\u{39}\u{5}\u{6}\u{4}\u{2}\u{39}\u{3A}\u{7}" .
		    "\u{1B}\u{2}\u{2}\u{3A}\u{3B}\u{5}\u{6}\u{4}\u{2}\u{3B}\u{4D}\u{3}" .
		    "\u{2}\u{2}\u{2}\u{3C}\u{3E}\u{7}\u{29}\u{2}\u{2}\u{3D}\u{3C}\u{3}" .
		    "\u{2}\u{2}\u{2}\u{3D}\u{3E}\u{3}\u{2}\u{2}\u{2}\u{3E}\u{3F}\u{3}\u{2}" .
		    "\u{2}\u{2}\u{3F}\u{40}\u{7}\u{24}\u{2}\u{2}\u{40}\u{49}\u{7}\u{5}" .
		    "\u{2}\u{2}\u{41}\u{46}\u{5}\u{6}\u{4}\u{2}\u{42}\u{43}\u{7}\u{7}\u{2}" .
		    "\u{2}\u{43}\u{45}\u{5}\u{6}\u{4}\u{2}\u{44}\u{42}\u{3}\u{2}\u{2}\u{2}" .
		    "\u{45}\u{48}\u{3}\u{2}\u{2}\u{2}\u{46}\u{44}\u{3}\u{2}\u{2}\u{2}\u{46}" .
		    "\u{47}\u{3}\u{2}\u{2}\u{2}\u{47}\u{4A}\u{3}\u{2}\u{2}\u{2}\u{48}\u{46}" .
		    "\u{3}\u{2}\u{2}\u{2}\u{49}\u{41}\u{3}\u{2}\u{2}\u{2}\u{49}\u{4A}\u{3}" .
		    "\u{2}\u{2}\u{2}\u{4A}\u{4B}\u{3}\u{2}\u{2}\u{2}\u{4B}\u{4D}\u{7}\u{6}" .
		    "\u{2}\u{2}\u{4C}\u{2A}\u{3}\u{2}\u{2}\u{2}\u{4C}\u{2D}\u{3}\u{2}\u{2}" .
		    "\u{2}\u{4C}\u{35}\u{3}\u{2}\u{2}\u{2}\u{4C}\u{3D}\u{3}\u{2}\u{2}\u{2}" .
		    "\u{4D}\u{9}\u{3}\u{2}\u{2}\u{2}\u{4E}\u{4F}\u{7}\u{2F}\u{2}\u{2}\u{4F}" .
		    "\u{54}\u{5}\u{2}\u{2}\u{2}\u{50}\u{51}\u{7}\u{7}\u{2}\u{2}\u{51}\u{53}" .
		    "\u{5}\u{2}\u{2}\u{2}\u{52}\u{50}\u{3}\u{2}\u{2}\u{2}\u{53}\u{56}\u{3}" .
		    "\u{2}\u{2}\u{2}\u{54}\u{52}\u{3}\u{2}\u{2}\u{2}\u{54}\u{55}\u{3}\u{2}" .
		    "\u{2}\u{2}\u{55}\u{59}\u{3}\u{2}\u{2}\u{2}\u{56}\u{54}\u{3}\u{2}\u{2}" .
		    "\u{2}\u{57}\u{58}\u{7}\u{22}\u{2}\u{2}\u{58}\u{5A}\u{5}\u{C}\u{7}" .
		    "\u{2}\u{59}\u{57}\u{3}\u{2}\u{2}\u{2}\u{59}\u{5A}\u{3}\u{2}\u{2}\u{2}" .
		    "\u{5A}\u{61}\u{3}\u{2}\u{2}\u{2}\u{5B}\u{5C}\u{7}\u{31}\u{2}\u{2}" .
		    "\u{5C}\u{5F}\u{5}\u{8}\u{5}\u{2}\u{5D}\u{5E}\u{9}\u{5}\u{2}\u{2}\u{5E}" .
		    "\u{60}\u{5}\u{8}\u{5}\u{2}\u{5F}\u{5D}\u{3}\u{2}\u{2}\u{2}\u{5F}\u{60}" .
		    "\u{3}\u{2}\u{2}\u{2}\u{60}\u{62}\u{3}\u{2}\u{2}\u{2}\u{61}\u{5B}\u{3}" .
		    "\u{2}\u{2}\u{2}\u{61}\u{62}\u{3}\u{2}\u{2}\u{2}\u{62}\u{64}\u{3}\u{2}" .
		    "\u{2}\u{2}\u{63}\u{65}\u{5}\u{14}\u{B}\u{2}\u{64}\u{63}\u{3}\u{2}" .
		    "\u{2}\u{2}\u{64}\u{65}\u{3}\u{2}\u{2}\u{2}\u{65}\u{67}\u{3}\u{2}\u{2}" .
		    "\u{2}\u{66}\u{68}\u{5}\u{12}\u{A}\u{2}\u{67}\u{66}\u{3}\u{2}\u{2}" .
		    "\u{2}\u{67}\u{68}\u{3}\u{2}\u{2}\u{2}\u{68}\u{B}\u{3}\u{2}\u{2}\u{2}" .
		    "\u{69}\u{6A}\u{5}\u{10}\u{9}\u{2}\u{6A}\u{D}\u{3}\u{2}\u{2}\u{2}\u{6B}" .
		    "\u{6C}\u{5}\u{10}\u{9}\u{2}\u{6C}\u{F}\u{3}\u{2}\u{2}\u{2}\u{6D}\u{74}" .
		    "\u{7}\u{35}\u{2}\u{2}\u{6E}\u{74}\u{7}\u{34}\u{2}\u{2}\u{6F}\u{70}" .
		    "\u{7}\u{5}\u{2}\u{2}\u{70}\u{71}\u{5}\u{10}\u{9}\u{2}\u{71}\u{72}" .
		    "\u{7}\u{6}\u{2}\u{2}\u{72}\u{74}\u{3}\u{2}\u{2}\u{2}\u{73}\u{6D}\u{3}" .
		    "\u{2}\u{2}\u{2}\u{73}\u{6E}\u{3}\u{2}\u{2}\u{2}\u{73}\u{6F}\u{3}\u{2}" .
		    "\u{2}\u{2}\u{74}\u{11}\u{3}\u{2}\u{2}\u{2}\u{75}\u{76}\u{7}\u{28}" .
		    "\u{2}\u{2}\u{76}\u{77}\u{7}\u{33}\u{2}\u{2}\u{77}\u{13}\u{3}\u{2}" .
		    "\u{2}\u{2}\u{78}\u{79}\u{7}\u{2E}\u{2}\u{2}\u{79}\u{7A}\u{7}\u{1F}" .
		    "\u{2}\u{2}\u{7A}\u{7F}\u{5}\u{16}\u{C}\u{2}\u{7B}\u{7C}\u{7}\u{7}" .
		    "\u{2}\u{2}\u{7C}\u{7E}\u{5}\u{16}\u{C}\u{2}\u{7D}\u{7B}\u{3}\u{2}" .
		    "\u{2}\u{2}\u{7E}\u{81}\u{3}\u{2}\u{2}\u{2}\u{7F}\u{7D}\u{3}\u{2}\u{2}" .
		    "\u{2}\u{7F}\u{80}\u{3}\u{2}\u{2}\u{2}\u{80}\u{15}\u{3}\u{2}\u{2}\u{2}" .
		    "\u{81}\u{7F}\u{3}\u{2}\u{2}\u{2}\u{82}\u{84}\u{5}\u{E}\u{8}\u{2}\u{83}" .
		    "\u{85}\u{5}\u{18}\u{D}\u{2}\u{84}\u{83}\u{3}\u{2}\u{2}\u{2}\u{84}" .
		    "\u{85}\u{3}\u{2}\u{2}\u{2}\u{85}\u{17}\u{3}\u{2}\u{2}\u{2}\u{86}\u{87}" .
		    "\u{9}\u{6}\u{2}\u{2}\u{87}\u{19}\u{3}\u{2}\u{2}\u{2}\u{15}\u{1D}\u{20}" .
		    "\u{23}\u{2D}\u{32}\u{35}\u{3D}\u{46}\u{49}\u{4C}\u{54}\u{59}\u{5F}" .
		    "\u{61}\u{64}\u{67}\u{73}\u{7F}\u{84}";

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
			return "logicalSql.g4";
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
		public function result_column() : Context\Result_columnContext
		{
		    $localContext = new Context\Result_columnContext($this->ctx, $this->getState());

		    $this->enterRule($localContext, 0, self::RULE_result_column);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(24);
		        $this->match(self::IDENTIFIER);
		        $this->setState(27);
		        $this->errorHandler->sync($this);
		        $_la = $this->input->LA(1);

		        if ($_la === self::DOT) {
		        	$this->setState(25);
		        	$this->match(self::DOT);
		        	$this->setState(26);
		        	$this->match(self::IDENTIFIER);
		        }
		        $this->setState(33);
		        $this->errorHandler->sync($this);
		        $_la = $this->input->LA(1);

		        if (((($_la) & ~0x3f) === 0 && ((1 << $_la) & ((1 << self::AS) | (1 << self::STRING_LITERAL) | (1 << self::IDENTIFIER))) !== 0)) {
		        	$this->setState(30);
		        	$this->errorHandler->sync($this);
		        	$_la = $this->input->LA(1);

		        	if ($_la === self::AS) {
		        		$this->setState(29);
		        		$this->match(self::AS);
		        	}
		        	$this->setState(32);
		        	$this->column_alias();
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
		public function column_alias() : Context\Column_aliasContext
		{
		    $localContext = new Context\Column_aliasContext($this->ctx, $this->getState());

		    $this->enterRule($localContext, 2, self::RULE_column_alias);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(35);

		        $_la = $this->input->LA(1);

		        if (!($_la === self::STRING_LITERAL || $_la === self::IDENTIFIER)) {
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
		public function literal_value() : Context\Literal_valueContext
		{
		    $localContext = new Context\Literal_valueContext($this->ctx, $this->getState());

		    $this->enterRule($localContext, 4, self::RULE_literal_value);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(37);

		        $_la = $this->input->LA(1);

		        if (!(((($_la) & ~0x3f) === 0 && ((1 << $_la) & ((1 << self::FALSE) | (1 << self::NOW) | (1 << self::NULL) | (1 << self::TRUE) | (1 << self::NUMERIC_LITERAL) | (1 << self::STRING_LITERAL))) !== 0))) {
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
		public function predicate_expression() : Context\Predicate_expressionContext
		{
		    $localContext = new Context\Predicate_expressionContext($this->ctx, $this->getState());

		    $this->enterRule($localContext, 6, self::RULE_predicate_expression);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(39);
		        $this->column_name();
		        $this->setState(74);
		        $this->errorHandler->sync($this);

		        switch ($this->getInterpreter()->adaptivePredict($this->input, 9, $this->ctx)) {
		        	case 1:
		        	    $this->setState(40);

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
		        	    $this->setState(41);
		        	    $this->literal_value();
		        	break;

		        	case 2:
		        	    $this->setState(43);
		        	    $this->errorHandler->sync($this);
		        	    $_la = $this->input->LA(1);

		        	    if ($_la === self::NOT) {
		        	    	$this->setState(42);
		        	    	$this->match(self::NOT);
		        	    }
		        	    $this->setState(48);
		        	    $this->errorHandler->sync($this);

		        	    switch ($this->input->LA(1)) {
		        	        case self::LIKE:
		        	        	$this->setState(45);
		        	        	$this->match(self::LIKE);
		        	        	break;

		        	        case self::GLOB:
		        	        	$this->setState(46);
		        	        	$this->match(self::GLOB);
		        	        	break;

		        	        case self::FALSE:
		        	        case self::NOW:
		        	        case self::NULL:
		        	        case self::TRUE:
		        	        case self::NUMERIC_LITERAL:
		        	        case self::STRING_LITERAL:
		        	        	$this->setState(47);
		        	        	$this->literal_value();
		        	        	break;

		        	    default:
		        	    	throw new NoViableAltException($this);
		        	    }
		        	break;

		        	case 3:
		        	    $this->setState(51);
		        	    $this->errorHandler->sync($this);
		        	    $_la = $this->input->LA(1);

		        	    if ($_la === self::NOT) {
		        	    	$this->setState(50);
		        	    	$this->match(self::NOT);
		        	    }
		        	    $this->setState(53);
		        	    $this->match(self::BETWEEN);
		        	    $this->setState(54);
		        	    $this->literal_value();
		        	    $this->setState(55);
		        	    $this->match(self::AND);
		        	    $this->setState(56);
		        	    $this->literal_value();
		        	break;

		        	case 4:
		        	    $this->setState(59);
		        	    $this->errorHandler->sync($this);
		        	    $_la = $this->input->LA(1);

		        	    if ($_la === self::NOT) {
		        	    	$this->setState(58);
		        	    	$this->match(self::NOT);
		        	    }
		        	    $this->setState(61);
		        	    $this->match(self::IN);
		        	    $this->setState(62);
		        	    $this->match(self::OPEN_PAR);
		        	    $this->setState(71);
		        	    $this->errorHandler->sync($this);
		        	    $_la = $this->input->LA(1);

		        	    if (((($_la) & ~0x3f) === 0 && ((1 << $_la) & ((1 << self::FALSE) | (1 << self::NOW) | (1 << self::NULL) | (1 << self::TRUE) | (1 << self::NUMERIC_LITERAL) | (1 << self::STRING_LITERAL))) !== 0)) {
		        	    	$this->setState(63);
		        	    	$this->literal_value();
		        	    	$this->setState(68);
		        	    	$this->errorHandler->sync($this);

		        	    	$_la = $this->input->LA(1);
		        	    	while ($_la === self::COMMA) {
		        	    		$this->setState(64);
		        	    		$this->match(self::COMMA);
		        	    		$this->setState(65);
		        	    		$this->literal_value();
		        	    		$this->setState(70);
		        	    		$this->errorHandler->sync($this);
		        	    		$_la = $this->input->LA(1);
		        	    	}
		        	    }
		        	    $this->setState(73);
		        	    $this->match(self::CLOSE_PAR);
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
		public function logicalSql() : Context\LogicalSqlContext
		{
		    $localContext = new Context\LogicalSqlContext($this->ctx, $this->getState());

		    $this->enterRule($localContext, 8, self::RULE_logicalSql);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(76);
		        $this->match(self::SELECT);
		        $this->setState(77);
		        $this->result_column();
		        $this->setState(82);
		        $this->errorHandler->sync($this);

		        $_la = $this->input->LA(1);
		        while ($_la === self::COMMA) {
		        	$this->setState(78);
		        	$this->match(self::COMMA);
		        	$this->setState(79);
		        	$this->result_column();
		        	$this->setState(84);
		        	$this->errorHandler->sync($this);
		        	$_la = $this->input->LA(1);
		        }
		        $this->setState(87);
		        $this->errorHandler->sync($this);
		        $_la = $this->input->LA(1);

		        if ($_la === self::FROM) {
		        	$this->setState(85);
		        	$this->match(self::FROM);
		        	$this->setState(86);
		        	$this->table_name();
		        }
		        $this->setState(95);
		        $this->errorHandler->sync($this);
		        $_la = $this->input->LA(1);

		        if ($_la === self::WHERE) {
		        	$this->setState(89);
		        	$this->match(self::WHERE);
		        	$this->setState(90);
		        	$this->predicate_expression();
		        	$this->setState(93);
		        	$this->errorHandler->sync($this);
		        	$_la = $this->input->LA(1);

		        	if ($_la === self::AND || $_la === self::OR) {
		        		$this->setState(91);

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
		        		$this->setState(92);
		        		$this->predicate_expression();
		        	}
		        }
		        $this->setState(98);
		        $this->errorHandler->sync($this);
		        $_la = $this->input->LA(1);

		        if ($_la === self::ORDER) {
		        	$this->setState(97);
		        	$this->order_by_stmt();
		        }
		        $this->setState(101);
		        $this->errorHandler->sync($this);
		        $_la = $this->input->LA(1);

		        if ($_la === self::LIMIT) {
		        	$this->setState(100);
		        	$this->limit_stmt();
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
		public function table_name() : Context\Table_nameContext
		{
		    $localContext = new Context\Table_nameContext($this->ctx, $this->getState());

		    $this->enterRule($localContext, 10, self::RULE_table_name);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(103);
		        $this->any_name();
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
		public function column_name() : Context\Column_nameContext
		{
		    $localContext = new Context\Column_nameContext($this->ctx, $this->getState());

		    $this->enterRule($localContext, 12, self::RULE_column_name);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(105);
		        $this->any_name();
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
		public function any_name() : Context\Any_nameContext
		{
		    $localContext = new Context\Any_nameContext($this->ctx, $this->getState());

		    $this->enterRule($localContext, 14, self::RULE_any_name);

		    try {
		        $this->setState(113);
		        $this->errorHandler->sync($this);

		        switch ($this->input->LA(1)) {
		            case self::IDENTIFIER:
		            	$this->enterOuterAlt($localContext, 1);
		            	$this->setState(107);
		            	$this->match(self::IDENTIFIER);
		            	break;

		            case self::STRING_LITERAL:
		            	$this->enterOuterAlt($localContext, 2);
		            	$this->setState(108);
		            	$this->match(self::STRING_LITERAL);
		            	break;

		            case self::OPEN_PAR:
		            	$this->enterOuterAlt($localContext, 3);
		            	$this->setState(109);
		            	$this->match(self::OPEN_PAR);
		            	$this->setState(110);
		            	$this->any_name();
		            	$this->setState(111);
		            	$this->match(self::CLOSE_PAR);
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
		public function limit_stmt() : Context\Limit_stmtContext
		{
		    $localContext = new Context\Limit_stmtContext($this->ctx, $this->getState());

		    $this->enterRule($localContext, 16, self::RULE_limit_stmt);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(115);
		        $this->match(self::LIMIT);
		        $this->setState(116);
		        $this->match(self::NUMERIC_LITERAL);
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
		public function order_by_stmt() : Context\Order_by_stmtContext
		{
		    $localContext = new Context\Order_by_stmtContext($this->ctx, $this->getState());

		    $this->enterRule($localContext, 18, self::RULE_order_by_stmt);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(118);
		        $this->match(self::ORDER);
		        $this->setState(119);
		        $this->match(self::BY);
		        $this->setState(120);
		        $this->ordering_term();
		        $this->setState(125);
		        $this->errorHandler->sync($this);

		        $_la = $this->input->LA(1);
		        while ($_la === self::COMMA) {
		        	$this->setState(121);
		        	$this->match(self::COMMA);
		        	$this->setState(122);
		        	$this->ordering_term();
		        	$this->setState(127);
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
		public function ordering_term() : Context\Ordering_termContext
		{
		    $localContext = new Context\Ordering_termContext($this->ctx, $this->getState());

		    $this->enterRule($localContext, 20, self::RULE_ordering_term);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(128);
		        $this->column_name();
		        $this->setState(130);
		        $this->errorHandler->sync($this);
		        $_la = $this->input->LA(1);

		        if ($_la === self::ASC || $_la === self::DESC) {
		        	$this->setState(129);
		        	$this->asc_desc();
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
		public function asc_desc() : Context\Asc_descContext
		{
		    $localContext = new Context\Asc_descContext($this->ctx, $this->getState());

		    $this->enterRule($localContext, 22, self::RULE_asc_desc);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(132);

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
	use ComboStrap\LogicalSqlAntlr\Gen\logicalSqlParser;
	use ComboStrap\LogicalSqlAntlr\Gen\logicalSqlVisitor;
	use ComboStrap\LogicalSqlAntlr\Gen\logicalSqlListener;

	class Result_columnContext extends ParserRuleContext
	{
		public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
		{
			parent::__construct($parent, $invokingState);
		}

		public function getRuleIndex() : int
		{
		    return logicalSqlParser::RULE_result_column;
	    }

	    /**
	     * @return array<TerminalNode>|TerminalNode|null
	     */
	    public function IDENTIFIER(?int $index = null)
	    {
	    	if ($index === null) {
	    		return $this->getTokens(logicalSqlParser::IDENTIFIER);
	    	}

	        return $this->getToken(logicalSqlParser::IDENTIFIER, $index);
	    }

	    public function DOT() : ?TerminalNode
	    {
	        return $this->getToken(logicalSqlParser::DOT, 0);
	    }

	    public function column_alias() : ?Column_aliasContext
	    {
	    	return $this->getTypedRuleContext(Column_aliasContext::class, 0);
	    }

	    public function AS() : ?TerminalNode
	    {
	        return $this->getToken(logicalSqlParser::AS, 0);
	    }

		public function enterRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof logicalSqlListener) {
			    $listener->enterResult_column($this);
		    }
		}

		public function exitRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof logicalSqlListener) {
			    $listener->exitResult_column($this);
		    }
		}

		public function accept(ParseTreeVisitor $visitor)
		{
			if ($visitor instanceof logicalSqlVisitor) {
			    return $visitor->visitResult_column($this);
		    }

			return $visitor->visitChildren($this);
		}
	} 

	class Column_aliasContext extends ParserRuleContext
	{
		public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
		{
			parent::__construct($parent, $invokingState);
		}

		public function getRuleIndex() : int
		{
		    return logicalSqlParser::RULE_column_alias;
	    }

	    public function IDENTIFIER() : ?TerminalNode
	    {
	        return $this->getToken(logicalSqlParser::IDENTIFIER, 0);
	    }

	    public function STRING_LITERAL() : ?TerminalNode
	    {
	        return $this->getToken(logicalSqlParser::STRING_LITERAL, 0);
	    }

		public function enterRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof logicalSqlListener) {
			    $listener->enterColumn_alias($this);
		    }
		}

		public function exitRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof logicalSqlListener) {
			    $listener->exitColumn_alias($this);
		    }
		}

		public function accept(ParseTreeVisitor $visitor)
		{
			if ($visitor instanceof logicalSqlVisitor) {
			    return $visitor->visitColumn_alias($this);
		    }

			return $visitor->visitChildren($this);
		}
	} 

	class Literal_valueContext extends ParserRuleContext
	{
		public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
		{
			parent::__construct($parent, $invokingState);
		}

		public function getRuleIndex() : int
		{
		    return logicalSqlParser::RULE_literal_value;
	    }

	    public function NUMERIC_LITERAL() : ?TerminalNode
	    {
	        return $this->getToken(logicalSqlParser::NUMERIC_LITERAL, 0);
	    }

	    public function STRING_LITERAL() : ?TerminalNode
	    {
	        return $this->getToken(logicalSqlParser::STRING_LITERAL, 0);
	    }

	    public function NULL() : ?TerminalNode
	    {
	        return $this->getToken(logicalSqlParser::NULL, 0);
	    }

	    public function TRUE() : ?TerminalNode
	    {
	        return $this->getToken(logicalSqlParser::TRUE, 0);
	    }

	    public function FALSE() : ?TerminalNode
	    {
	        return $this->getToken(logicalSqlParser::FALSE, 0);
	    }

	    public function NOW() : ?TerminalNode
	    {
	        return $this->getToken(logicalSqlParser::NOW, 0);
	    }

		public function enterRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof logicalSqlListener) {
			    $listener->enterLiteral_value($this);
		    }
		}

		public function exitRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof logicalSqlListener) {
			    $listener->exitLiteral_value($this);
		    }
		}

		public function accept(ParseTreeVisitor $visitor)
		{
			if ($visitor instanceof logicalSqlVisitor) {
			    return $visitor->visitLiteral_value($this);
		    }

			return $visitor->visitChildren($this);
		}
	} 

	class Predicate_expressionContext extends ParserRuleContext
	{
		public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
		{
			parent::__construct($parent, $invokingState);
		}

		public function getRuleIndex() : int
		{
		    return logicalSqlParser::RULE_predicate_expression;
	    }

	    public function column_name() : ?Column_nameContext
	    {
	    	return $this->getTypedRuleContext(Column_nameContext::class, 0);
	    }

	    /**
	     * @return array<Literal_valueContext>|Literal_valueContext|null
	     */
	    public function literal_value(?int $index = null)
	    {
	    	if ($index === null) {
	    		return $this->getTypedRuleContexts(Literal_valueContext::class);
	    	}

	        return $this->getTypedRuleContext(Literal_valueContext::class, $index);
	    }

	    public function BETWEEN() : ?TerminalNode
	    {
	        return $this->getToken(logicalSqlParser::BETWEEN, 0);
	    }

	    public function AND() : ?TerminalNode
	    {
	        return $this->getToken(logicalSqlParser::AND, 0);
	    }

	    public function IN() : ?TerminalNode
	    {
	        return $this->getToken(logicalSqlParser::IN, 0);
	    }

	    public function OPEN_PAR() : ?TerminalNode
	    {
	        return $this->getToken(logicalSqlParser::OPEN_PAR, 0);
	    }

	    public function CLOSE_PAR() : ?TerminalNode
	    {
	        return $this->getToken(logicalSqlParser::CLOSE_PAR, 0);
	    }

	    public function LESS_THAN() : ?TerminalNode
	    {
	        return $this->getToken(logicalSqlParser::LESS_THAN, 0);
	    }

	    public function LESS_THAN_OR_EQUAL() : ?TerminalNode
	    {
	        return $this->getToken(logicalSqlParser::LESS_THAN_OR_EQUAL, 0);
	    }

	    public function GREATER_THAN() : ?TerminalNode
	    {
	        return $this->getToken(logicalSqlParser::GREATER_THAN, 0);
	    }

	    public function GREATER_THAN_OR_EQUAL() : ?TerminalNode
	    {
	        return $this->getToken(logicalSqlParser::GREATER_THAN_OR_EQUAL, 0);
	    }

	    public function NOT_EQUAL() : ?TerminalNode
	    {
	        return $this->getToken(logicalSqlParser::NOT_EQUAL, 0);
	    }

	    public function EQUAL() : ?TerminalNode
	    {
	        return $this->getToken(logicalSqlParser::EQUAL, 0);
	    }

	    public function LIKE() : ?TerminalNode
	    {
	        return $this->getToken(logicalSqlParser::LIKE, 0);
	    }

	    public function GLOB() : ?TerminalNode
	    {
	        return $this->getToken(logicalSqlParser::GLOB, 0);
	    }

	    public function NOT() : ?TerminalNode
	    {
	        return $this->getToken(logicalSqlParser::NOT, 0);
	    }

	    /**
	     * @return array<TerminalNode>|TerminalNode|null
	     */
	    public function COMMA(?int $index = null)
	    {
	    	if ($index === null) {
	    		return $this->getTokens(logicalSqlParser::COMMA);
	    	}

	        return $this->getToken(logicalSqlParser::COMMA, $index);
	    }

		public function enterRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof logicalSqlListener) {
			    $listener->enterPredicate_expression($this);
		    }
		}

		public function exitRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof logicalSqlListener) {
			    $listener->exitPredicate_expression($this);
		    }
		}

		public function accept(ParseTreeVisitor $visitor)
		{
			if ($visitor instanceof logicalSqlVisitor) {
			    return $visitor->visitPredicate_expression($this);
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
		    return logicalSqlParser::RULE_logicalSql;
	    }

	    public function SELECT() : ?TerminalNode
	    {
	        return $this->getToken(logicalSqlParser::SELECT, 0);
	    }

	    /**
	     * @return array<Result_columnContext>|Result_columnContext|null
	     */
	    public function result_column(?int $index = null)
	    {
	    	if ($index === null) {
	    		return $this->getTypedRuleContexts(Result_columnContext::class);
	    	}

	        return $this->getTypedRuleContext(Result_columnContext::class, $index);
	    }

	    /**
	     * @return array<TerminalNode>|TerminalNode|null
	     */
	    public function COMMA(?int $index = null)
	    {
	    	if ($index === null) {
	    		return $this->getTokens(logicalSqlParser::COMMA);
	    	}

	        return $this->getToken(logicalSqlParser::COMMA, $index);
	    }

	    public function FROM() : ?TerminalNode
	    {
	        return $this->getToken(logicalSqlParser::FROM, 0);
	    }

	    public function table_name() : ?Table_nameContext
	    {
	    	return $this->getTypedRuleContext(Table_nameContext::class, 0);
	    }

	    public function WHERE() : ?TerminalNode
	    {
	        return $this->getToken(logicalSqlParser::WHERE, 0);
	    }

	    /**
	     * @return array<Predicate_expressionContext>|Predicate_expressionContext|null
	     */
	    public function predicate_expression(?int $index = null)
	    {
	    	if ($index === null) {
	    		return $this->getTypedRuleContexts(Predicate_expressionContext::class);
	    	}

	        return $this->getTypedRuleContext(Predicate_expressionContext::class, $index);
	    }

	    public function order_by_stmt() : ?Order_by_stmtContext
	    {
	    	return $this->getTypedRuleContext(Order_by_stmtContext::class, 0);
	    }

	    public function limit_stmt() : ?Limit_stmtContext
	    {
	    	return $this->getTypedRuleContext(Limit_stmtContext::class, 0);
	    }

	    public function AND() : ?TerminalNode
	    {
	        return $this->getToken(logicalSqlParser::AND, 0);
	    }

	    public function OR() : ?TerminalNode
	    {
	        return $this->getToken(logicalSqlParser::OR, 0);
	    }

		public function enterRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof logicalSqlListener) {
			    $listener->enterLogicalSql($this);
		    }
		}

		public function exitRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof logicalSqlListener) {
			    $listener->exitLogicalSql($this);
		    }
		}

		public function accept(ParseTreeVisitor $visitor)
		{
			if ($visitor instanceof logicalSqlVisitor) {
			    return $visitor->visitLogicalSql($this);
		    }

			return $visitor->visitChildren($this);
		}
	} 

	class Table_nameContext extends ParserRuleContext
	{
		public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
		{
			parent::__construct($parent, $invokingState);
		}

		public function getRuleIndex() : int
		{
		    return logicalSqlParser::RULE_table_name;
	    }

	    public function any_name() : ?Any_nameContext
	    {
	    	return $this->getTypedRuleContext(Any_nameContext::class, 0);
	    }

		public function enterRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof logicalSqlListener) {
			    $listener->enterTable_name($this);
		    }
		}

		public function exitRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof logicalSqlListener) {
			    $listener->exitTable_name($this);
		    }
		}

		public function accept(ParseTreeVisitor $visitor)
		{
			if ($visitor instanceof logicalSqlVisitor) {
			    return $visitor->visitTable_name($this);
		    }

			return $visitor->visitChildren($this);
		}
	} 

	class Column_nameContext extends ParserRuleContext
	{
		public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
		{
			parent::__construct($parent, $invokingState);
		}

		public function getRuleIndex() : int
		{
		    return logicalSqlParser::RULE_column_name;
	    }

	    public function any_name() : ?Any_nameContext
	    {
	    	return $this->getTypedRuleContext(Any_nameContext::class, 0);
	    }

		public function enterRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof logicalSqlListener) {
			    $listener->enterColumn_name($this);
		    }
		}

		public function exitRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof logicalSqlListener) {
			    $listener->exitColumn_name($this);
		    }
		}

		public function accept(ParseTreeVisitor $visitor)
		{
			if ($visitor instanceof logicalSqlVisitor) {
			    return $visitor->visitColumn_name($this);
		    }

			return $visitor->visitChildren($this);
		}
	} 

	class Any_nameContext extends ParserRuleContext
	{
		public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
		{
			parent::__construct($parent, $invokingState);
		}

		public function getRuleIndex() : int
		{
		    return logicalSqlParser::RULE_any_name;
	    }

	    public function IDENTIFIER() : ?TerminalNode
	    {
	        return $this->getToken(logicalSqlParser::IDENTIFIER, 0);
	    }

	    public function STRING_LITERAL() : ?TerminalNode
	    {
	        return $this->getToken(logicalSqlParser::STRING_LITERAL, 0);
	    }

	    public function OPEN_PAR() : ?TerminalNode
	    {
	        return $this->getToken(logicalSqlParser::OPEN_PAR, 0);
	    }

	    public function any_name() : ?Any_nameContext
	    {
	    	return $this->getTypedRuleContext(Any_nameContext::class, 0);
	    }

	    public function CLOSE_PAR() : ?TerminalNode
	    {
	        return $this->getToken(logicalSqlParser::CLOSE_PAR, 0);
	    }

		public function enterRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof logicalSqlListener) {
			    $listener->enterAny_name($this);
		    }
		}

		public function exitRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof logicalSqlListener) {
			    $listener->exitAny_name($this);
		    }
		}

		public function accept(ParseTreeVisitor $visitor)
		{
			if ($visitor instanceof logicalSqlVisitor) {
			    return $visitor->visitAny_name($this);
		    }

			return $visitor->visitChildren($this);
		}
	} 

	class Limit_stmtContext extends ParserRuleContext
	{
		public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
		{
			parent::__construct($parent, $invokingState);
		}

		public function getRuleIndex() : int
		{
		    return logicalSqlParser::RULE_limit_stmt;
	    }

	    public function LIMIT() : ?TerminalNode
	    {
	        return $this->getToken(logicalSqlParser::LIMIT, 0);
	    }

	    public function NUMERIC_LITERAL() : ?TerminalNode
	    {
	        return $this->getToken(logicalSqlParser::NUMERIC_LITERAL, 0);
	    }

		public function enterRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof logicalSqlListener) {
			    $listener->enterLimit_stmt($this);
		    }
		}

		public function exitRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof logicalSqlListener) {
			    $listener->exitLimit_stmt($this);
		    }
		}

		public function accept(ParseTreeVisitor $visitor)
		{
			if ($visitor instanceof logicalSqlVisitor) {
			    return $visitor->visitLimit_stmt($this);
		    }

			return $visitor->visitChildren($this);
		}
	} 

	class Order_by_stmtContext extends ParserRuleContext
	{
		public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
		{
			parent::__construct($parent, $invokingState);
		}

		public function getRuleIndex() : int
		{
		    return logicalSqlParser::RULE_order_by_stmt;
	    }

	    public function ORDER() : ?TerminalNode
	    {
	        return $this->getToken(logicalSqlParser::ORDER, 0);
	    }

	    public function BY() : ?TerminalNode
	    {
	        return $this->getToken(logicalSqlParser::BY, 0);
	    }

	    /**
	     * @return array<Ordering_termContext>|Ordering_termContext|null
	     */
	    public function ordering_term(?int $index = null)
	    {
	    	if ($index === null) {
	    		return $this->getTypedRuleContexts(Ordering_termContext::class);
	    	}

	        return $this->getTypedRuleContext(Ordering_termContext::class, $index);
	    }

	    /**
	     * @return array<TerminalNode>|TerminalNode|null
	     */
	    public function COMMA(?int $index = null)
	    {
	    	if ($index === null) {
	    		return $this->getTokens(logicalSqlParser::COMMA);
	    	}

	        return $this->getToken(logicalSqlParser::COMMA, $index);
	    }

		public function enterRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof logicalSqlListener) {
			    $listener->enterOrder_by_stmt($this);
		    }
		}

		public function exitRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof logicalSqlListener) {
			    $listener->exitOrder_by_stmt($this);
		    }
		}

		public function accept(ParseTreeVisitor $visitor)
		{
			if ($visitor instanceof logicalSqlVisitor) {
			    return $visitor->visitOrder_by_stmt($this);
		    }

			return $visitor->visitChildren($this);
		}
	} 

	class Ordering_termContext extends ParserRuleContext
	{
		public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
		{
			parent::__construct($parent, $invokingState);
		}

		public function getRuleIndex() : int
		{
		    return logicalSqlParser::RULE_ordering_term;
	    }

	    public function column_name() : ?Column_nameContext
	    {
	    	return $this->getTypedRuleContext(Column_nameContext::class, 0);
	    }

	    public function asc_desc() : ?Asc_descContext
	    {
	    	return $this->getTypedRuleContext(Asc_descContext::class, 0);
	    }

		public function enterRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof logicalSqlListener) {
			    $listener->enterOrdering_term($this);
		    }
		}

		public function exitRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof logicalSqlListener) {
			    $listener->exitOrdering_term($this);
		    }
		}

		public function accept(ParseTreeVisitor $visitor)
		{
			if ($visitor instanceof logicalSqlVisitor) {
			    return $visitor->visitOrdering_term($this);
		    }

			return $visitor->visitChildren($this);
		}
	} 

	class Asc_descContext extends ParserRuleContext
	{
		public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
		{
			parent::__construct($parent, $invokingState);
		}

		public function getRuleIndex() : int
		{
		    return logicalSqlParser::RULE_asc_desc;
	    }

	    public function ASC() : ?TerminalNode
	    {
	        return $this->getToken(logicalSqlParser::ASC, 0);
	    }

	    public function DESC() : ?TerminalNode
	    {
	        return $this->getToken(logicalSqlParser::DESC, 0);
	    }

		public function enterRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof logicalSqlListener) {
			    $listener->enterAsc_desc($this);
		    }
		}

		public function exitRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof logicalSqlListener) {
			    $listener->exitAsc_desc($this);
		    }
		}

		public function accept(ParseTreeVisitor $visitor)
		{
			if ($visitor instanceof logicalSqlVisitor) {
			    return $visitor->visitAsc_desc($this);
		    }

			return $visitor->visitChildren($this);
		}
	} 
}