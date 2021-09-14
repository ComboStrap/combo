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
		public const SCOL = 1, DOT = 2, OPEN_PAR = 3, CLOSE_PAR = 4, COMMA = 5, 
               EQUAL = 6, STAR = 7, PLUS = 8, MINUS = 9, TILDE = 10, PIPE2 = 11, 
               DIV = 12, MOD = 13, LT2 = 14, GT2 = 15, AMP = 16, PIPE = 17, 
               LESS_THAN = 18, LESS_THAN_OR_EQUAL = 19, GREATER_THAN = 20, 
               GREATER_THAN_OR_EQUAL = 21, EQ = 22, NOT_EQUAL = 23, NOT_EQ2 = 24, 
               AND = 25, AS = 26, ASC = 27, BETWEEN = 28, BY = 29, DESC = 30, 
               FALSE = 31, FROM = 32, GLOB = 33, IN = 34, IS = 35, ISNULL = 36, 
               LIKE = 37, LIMIT = 38, NOT = 39, NOTNULL = 40, NOW = 41, 
               NULL = 42, OR = 43, ORDER = 44, SELECT = 45, TRUE = 46, WHERE = 47, 
               SPACES = 48, INTEGER_LITERAL = 49, NUMERIC_LITERAL = 50, 
               STRING_LITERAL = 51, SQL_NAME = 52;

		public const RULE_column = 0, RULE_columnAlias = 1, RULE_literalValue = 2, 
               RULE_predicate = 3, RULE_columns = 4, RULE_predicates = 5, 
               RULE_where = 6, RULE_tables = 7, RULE_logicalSql = 8, RULE_tabelName = 9, 
               RULE_columnName = 10, RULE_limit = 11, RULE_orderBy = 12, 
               RULE_orderByDef = 13, RULE_order = 14;

		/**
		 * @var array<string>
		 */
		public const RULE_NAMES = [
			'column', 'columnAlias', 'literalValue', 'predicate', 'columns', 'predicates', 
			'where', 'tables', 'logicalSql', 'tabelName', 'columnName', 'limit', 
			'orderBy', 'orderByDef', 'order'
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
		    "WHERE", "SPACES", "INTEGER_LITERAL", "NUMERIC_LITERAL", "STRING_LITERAL", 
		    "SQL_NAME"
		];

		/**
		 * @var string
		 */
		private const SERIALIZED_ATN =
			"\u{3}\u{608B}\u{A72A}\u{8133}\u{B9ED}\u{417C}\u{3BE7}\u{7786}\u{5964}" .
		    "\u{3}\u{36}\u{8E}\u{4}\u{2}\u{9}\u{2}\u{4}\u{3}\u{9}\u{3}\u{4}\u{4}" .
		    "\u{9}\u{4}\u{4}\u{5}\u{9}\u{5}\u{4}\u{6}\u{9}\u{6}\u{4}\u{7}\u{9}" .
		    "\u{7}\u{4}\u{8}\u{9}\u{8}\u{4}\u{9}\u{9}\u{9}\u{4}\u{A}\u{9}\u{A}" .
		    "\u{4}\u{B}\u{9}\u{B}\u{4}\u{C}\u{9}\u{C}\u{4}\u{D}\u{9}\u{D}\u{4}" .
		    "\u{E}\u{9}\u{E}\u{4}\u{F}\u{9}\u{F}\u{4}\u{10}\u{9}\u{10}\u{3}\u{2}" .
		    "\u{3}\u{2}\u{3}\u{2}\u{5}\u{2}\u{24}\u{A}\u{2}\u{3}\u{2}\u{5}\u{2}" .
		    "\u{27}\u{A}\u{2}\u{3}\u{2}\u{5}\u{2}\u{2A}\u{A}\u{2}\u{3}\u{3}\u{3}" .
		    "\u{3}\u{3}\u{4}\u{3}\u{4}\u{3}\u{5}\u{3}\u{5}\u{3}\u{5}\u{3}\u{5}" .
		    "\u{5}\u{5}\u{34}\u{A}\u{5}\u{3}\u{5}\u{3}\u{5}\u{3}\u{5}\u{5}\u{5}" .
		    "\u{39}\u{A}\u{5}\u{3}\u{5}\u{5}\u{5}\u{3C}\u{A}\u{5}\u{3}\u{5}\u{3}" .
		    "\u{5}\u{3}\u{5}\u{3}\u{5}\u{3}\u{5}\u{3}\u{5}\u{5}\u{5}\u{44}\u{A}" .
		    "\u{5}\u{3}\u{5}\u{3}\u{5}\u{3}\u{5}\u{3}\u{5}\u{3}\u{5}\u{7}\u{5}" .
		    "\u{4B}\u{A}\u{5}\u{C}\u{5}\u{E}\u{5}\u{4E}\u{B}\u{5}\u{5}\u{5}\u{50}" .
		    "\u{A}\u{5}\u{3}\u{5}\u{5}\u{5}\u{53}\u{A}\u{5}\u{3}\u{6}\u{3}\u{6}" .
		    "\u{3}\u{6}\u{7}\u{6}\u{58}\u{A}\u{6}\u{C}\u{6}\u{E}\u{6}\u{5B}\u{B}" .
		    "\u{6}\u{3}\u{7}\u{3}\u{7}\u{3}\u{7}\u{7}\u{7}\u{60}\u{A}\u{7}\u{C}" .
		    "\u{7}\u{E}\u{7}\u{63}\u{B}\u{7}\u{3}\u{8}\u{3}\u{8}\u{5}\u{8}\u{67}" .
		    "\u{A}\u{8}\u{3}\u{9}\u{3}\u{9}\u{5}\u{9}\u{6B}\u{A}\u{9}\u{3}\u{A}" .
		    "\u{3}\u{A}\u{3}\u{A}\u{3}\u{A}\u{3}\u{A}\u{5}\u{A}\u{72}\u{A}\u{A}" .
		    "\u{3}\u{A}\u{5}\u{A}\u{75}\u{A}\u{A}\u{3}\u{B}\u{3}\u{B}\u{3}\u{C}" .
		    "\u{3}\u{C}\u{3}\u{D}\u{3}\u{D}\u{3}\u{D}\u{3}\u{E}\u{3}\u{E}\u{3}" .
		    "\u{E}\u{3}\u{E}\u{3}\u{E}\u{7}\u{E}\u{83}\u{A}\u{E}\u{C}\u{E}\u{E}" .
		    "\u{E}\u{86}\u{B}\u{E}\u{3}\u{F}\u{3}\u{F}\u{5}\u{F}\u{8A}\u{A}\u{F}" .
		    "\u{3}\u{10}\u{3}\u{10}\u{3}\u{10}\u{2}\u{2}\u{11}\u{2}\u{4}\u{6}\u{8}" .
		    "\u{A}\u{C}\u{E}\u{10}\u{12}\u{14}\u{16}\u{18}\u{1A}\u{1C}\u{1E}\u{2}" .
		    "\u{7}\u{3}\u{2}\u{35}\u{36}\u{6}\u{2}\u{21}\u{21}\u{2B}\u{2C}\u{30}" .
		    "\u{30}\u{33}\u{35}\u{5}\u{2}\u{8}\u{8}\u{14}\u{17}\u{19}\u{19}\u{4}" .
		    "\u{2}\u{1B}\u{1B}\u{2D}\u{2D}\u{4}\u{2}\u{1D}\u{1D}\u{20}\u{20}\u{2}" .
		    "\u{93}\u{2}\u{20}\u{3}\u{2}\u{2}\u{2}\u{4}\u{2B}\u{3}\u{2}\u{2}\u{2}" .
		    "\u{6}\u{2D}\u{3}\u{2}\u{2}\u{2}\u{8}\u{2F}\u{3}\u{2}\u{2}\u{2}\u{A}" .
		    "\u{54}\u{3}\u{2}\u{2}\u{2}\u{C}\u{5C}\u{3}\u{2}\u{2}\u{2}\u{E}\u{66}" .
		    "\u{3}\u{2}\u{2}\u{2}\u{10}\u{6A}\u{3}\u{2}\u{2}\u{2}\u{12}\u{6C}\u{3}" .
		    "\u{2}\u{2}\u{2}\u{14}\u{76}\u{3}\u{2}\u{2}\u{2}\u{16}\u{78}\u{3}\u{2}" .
		    "\u{2}\u{2}\u{18}\u{7A}\u{3}\u{2}\u{2}\u{2}\u{1A}\u{7D}\u{3}\u{2}\u{2}" .
		    "\u{2}\u{1C}\u{87}\u{3}\u{2}\u{2}\u{2}\u{1E}\u{8B}\u{3}\u{2}\u{2}\u{2}" .
		    "\u{20}\u{23}\u{7}\u{36}\u{2}\u{2}\u{21}\u{22}\u{7}\u{4}\u{2}\u{2}" .
		    "\u{22}\u{24}\u{7}\u{36}\u{2}\u{2}\u{23}\u{21}\u{3}\u{2}\u{2}\u{2}" .
		    "\u{23}\u{24}\u{3}\u{2}\u{2}\u{2}\u{24}\u{29}\u{3}\u{2}\u{2}\u{2}\u{25}" .
		    "\u{27}\u{7}\u{1C}\u{2}\u{2}\u{26}\u{25}\u{3}\u{2}\u{2}\u{2}\u{26}" .
		    "\u{27}\u{3}\u{2}\u{2}\u{2}\u{27}\u{28}\u{3}\u{2}\u{2}\u{2}\u{28}\u{2A}" .
		    "\u{5}\u{4}\u{3}\u{2}\u{29}\u{26}\u{3}\u{2}\u{2}\u{2}\u{29}\u{2A}\u{3}" .
		    "\u{2}\u{2}\u{2}\u{2A}\u{3}\u{3}\u{2}\u{2}\u{2}\u{2B}\u{2C}\u{9}\u{2}" .
		    "\u{2}\u{2}\u{2C}\u{5}\u{3}\u{2}\u{2}\u{2}\u{2D}\u{2E}\u{9}\u{3}\u{2}" .
		    "\u{2}\u{2E}\u{7}\u{3}\u{2}\u{2}\u{2}\u{2F}\u{52}\u{5}\u{16}\u{C}\u{2}" .
		    "\u{30}\u{31}\u{9}\u{4}\u{2}\u{2}\u{31}\u{53}\u{5}\u{6}\u{4}\u{2}\u{32}" .
		    "\u{34}\u{7}\u{29}\u{2}\u{2}\u{33}\u{32}\u{3}\u{2}\u{2}\u{2}\u{33}" .
		    "\u{34}\u{3}\u{2}\u{2}\u{2}\u{34}\u{38}\u{3}\u{2}\u{2}\u{2}\u{35}\u{39}" .
		    "\u{7}\u{27}\u{2}\u{2}\u{36}\u{39}\u{7}\u{23}\u{2}\u{2}\u{37}\u{39}" .
		    "\u{5}\u{6}\u{4}\u{2}\u{38}\u{35}\u{3}\u{2}\u{2}\u{2}\u{38}\u{36}\u{3}" .
		    "\u{2}\u{2}\u{2}\u{38}\u{37}\u{3}\u{2}\u{2}\u{2}\u{39}\u{53}\u{3}\u{2}" .
		    "\u{2}\u{2}\u{3A}\u{3C}\u{7}\u{29}\u{2}\u{2}\u{3B}\u{3A}\u{3}\u{2}" .
		    "\u{2}\u{2}\u{3B}\u{3C}\u{3}\u{2}\u{2}\u{2}\u{3C}\u{3D}\u{3}\u{2}\u{2}" .
		    "\u{2}\u{3D}\u{3E}\u{7}\u{1E}\u{2}\u{2}\u{3E}\u{3F}\u{5}\u{6}\u{4}" .
		    "\u{2}\u{3F}\u{40}\u{7}\u{1B}\u{2}\u{2}\u{40}\u{41}\u{5}\u{6}\u{4}" .
		    "\u{2}\u{41}\u{53}\u{3}\u{2}\u{2}\u{2}\u{42}\u{44}\u{7}\u{29}\u{2}" .
		    "\u{2}\u{43}\u{42}\u{3}\u{2}\u{2}\u{2}\u{43}\u{44}\u{3}\u{2}\u{2}\u{2}" .
		    "\u{44}\u{45}\u{3}\u{2}\u{2}\u{2}\u{45}\u{46}\u{7}\u{24}\u{2}\u{2}" .
		    "\u{46}\u{4F}\u{7}\u{5}\u{2}\u{2}\u{47}\u{4C}\u{5}\u{6}\u{4}\u{2}\u{48}" .
		    "\u{49}\u{7}\u{7}\u{2}\u{2}\u{49}\u{4B}\u{5}\u{6}\u{4}\u{2}\u{4A}\u{48}" .
		    "\u{3}\u{2}\u{2}\u{2}\u{4B}\u{4E}\u{3}\u{2}\u{2}\u{2}\u{4C}\u{4A}\u{3}" .
		    "\u{2}\u{2}\u{2}\u{4C}\u{4D}\u{3}\u{2}\u{2}\u{2}\u{4D}\u{50}\u{3}\u{2}" .
		    "\u{2}\u{2}\u{4E}\u{4C}\u{3}\u{2}\u{2}\u{2}\u{4F}\u{47}\u{3}\u{2}\u{2}" .
		    "\u{2}\u{4F}\u{50}\u{3}\u{2}\u{2}\u{2}\u{50}\u{51}\u{3}\u{2}\u{2}\u{2}" .
		    "\u{51}\u{53}\u{7}\u{6}\u{2}\u{2}\u{52}\u{30}\u{3}\u{2}\u{2}\u{2}\u{52}" .
		    "\u{33}\u{3}\u{2}\u{2}\u{2}\u{52}\u{3B}\u{3}\u{2}\u{2}\u{2}\u{52}\u{43}" .
		    "\u{3}\u{2}\u{2}\u{2}\u{53}\u{9}\u{3}\u{2}\u{2}\u{2}\u{54}\u{59}\u{5}" .
		    "\u{2}\u{2}\u{2}\u{55}\u{56}\u{7}\u{7}\u{2}\u{2}\u{56}\u{58}\u{5}\u{2}" .
		    "\u{2}\u{2}\u{57}\u{55}\u{3}\u{2}\u{2}\u{2}\u{58}\u{5B}\u{3}\u{2}\u{2}" .
		    "\u{2}\u{59}\u{57}\u{3}\u{2}\u{2}\u{2}\u{59}\u{5A}\u{3}\u{2}\u{2}\u{2}" .
		    "\u{5A}\u{B}\u{3}\u{2}\u{2}\u{2}\u{5B}\u{59}\u{3}\u{2}\u{2}\u{2}\u{5C}" .
		    "\u{61}\u{5}\u{8}\u{5}\u{2}\u{5D}\u{5E}\u{9}\u{5}\u{2}\u{2}\u{5E}\u{60}" .
		    "\u{5}\u{8}\u{5}\u{2}\u{5F}\u{5D}\u{3}\u{2}\u{2}\u{2}\u{60}\u{63}\u{3}" .
		    "\u{2}\u{2}\u{2}\u{61}\u{5F}\u{3}\u{2}\u{2}\u{2}\u{61}\u{62}\u{3}\u{2}" .
		    "\u{2}\u{2}\u{62}\u{D}\u{3}\u{2}\u{2}\u{2}\u{63}\u{61}\u{3}\u{2}\u{2}" .
		    "\u{2}\u{64}\u{65}\u{7}\u{31}\u{2}\u{2}\u{65}\u{67}\u{5}\u{C}\u{7}" .
		    "\u{2}\u{66}\u{64}\u{3}\u{2}\u{2}\u{2}\u{66}\u{67}\u{3}\u{2}\u{2}\u{2}" .
		    "\u{67}\u{F}\u{3}\u{2}\u{2}\u{2}\u{68}\u{69}\u{7}\u{22}\u{2}\u{2}\u{69}" .
		    "\u{6B}\u{5}\u{14}\u{B}\u{2}\u{6A}\u{68}\u{3}\u{2}\u{2}\u{2}\u{6A}" .
		    "\u{6B}\u{3}\u{2}\u{2}\u{2}\u{6B}\u{11}\u{3}\u{2}\u{2}\u{2}\u{6C}\u{6D}" .
		    "\u{7}\u{2F}\u{2}\u{2}\u{6D}\u{6E}\u{5}\u{A}\u{6}\u{2}\u{6E}\u{6F}" .
		    "\u{5}\u{10}\u{9}\u{2}\u{6F}\u{71}\u{5}\u{E}\u{8}\u{2}\u{70}\u{72}" .
		    "\u{5}\u{1A}\u{E}\u{2}\u{71}\u{70}\u{3}\u{2}\u{2}\u{2}\u{71}\u{72}" .
		    "\u{3}\u{2}\u{2}\u{2}\u{72}\u{74}\u{3}\u{2}\u{2}\u{2}\u{73}\u{75}\u{5}" .
		    "\u{18}\u{D}\u{2}\u{74}\u{73}\u{3}\u{2}\u{2}\u{2}\u{74}\u{75}\u{3}" .
		    "\u{2}\u{2}\u{2}\u{75}\u{13}\u{3}\u{2}\u{2}\u{2}\u{76}\u{77}\u{7}\u{36}" .
		    "\u{2}\u{2}\u{77}\u{15}\u{3}\u{2}\u{2}\u{2}\u{78}\u{79}\u{7}\u{36}" .
		    "\u{2}\u{2}\u{79}\u{17}\u{3}\u{2}\u{2}\u{2}\u{7A}\u{7B}\u{7}\u{28}" .
		    "\u{2}\u{2}\u{7B}\u{7C}\u{7}\u{33}\u{2}\u{2}\u{7C}\u{19}\u{3}\u{2}" .
		    "\u{2}\u{2}\u{7D}\u{7E}\u{7}\u{2E}\u{2}\u{2}\u{7E}\u{7F}\u{7}\u{1F}" .
		    "\u{2}\u{2}\u{7F}\u{84}\u{5}\u{1C}\u{F}\u{2}\u{80}\u{81}\u{7}\u{7}" .
		    "\u{2}\u{2}\u{81}\u{83}\u{5}\u{1C}\u{F}\u{2}\u{82}\u{80}\u{3}\u{2}" .
		    "\u{2}\u{2}\u{83}\u{86}\u{3}\u{2}\u{2}\u{2}\u{84}\u{82}\u{3}\u{2}\u{2}" .
		    "\u{2}\u{84}\u{85}\u{3}\u{2}\u{2}\u{2}\u{85}\u{1B}\u{3}\u{2}\u{2}\u{2}" .
		    "\u{86}\u{84}\u{3}\u{2}\u{2}\u{2}\u{87}\u{89}\u{5}\u{16}\u{C}\u{2}" .
		    "\u{88}\u{8A}\u{5}\u{1E}\u{10}\u{2}\u{89}\u{88}\u{3}\u{2}\u{2}\u{2}" .
		    "\u{89}\u{8A}\u{3}\u{2}\u{2}\u{2}\u{8A}\u{1D}\u{3}\u{2}\u{2}\u{2}\u{8B}" .
		    "\u{8C}\u{9}\u{6}\u{2}\u{2}\u{8C}\u{1F}\u{3}\u{2}\u{2}\u{2}\u{14}\u{23}" .
		    "\u{26}\u{29}\u{33}\u{38}\u{3B}\u{43}\u{4C}\u{4F}\u{52}\u{59}\u{61}" .
		    "\u{66}\u{6A}\u{71}\u{74}\u{84}\u{89}";

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
		public function column() : Context\ColumnContext
		{
		    $localContext = new Context\ColumnContext($this->ctx, $this->getState());

		    $this->enterRule($localContext, 0, self::RULE_column);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(30);
		        $this->match(self::SQL_NAME);
		        $this->setState(33);
		        $this->errorHandler->sync($this);
		        $_la = $this->input->LA(1);

		        if ($_la === self::DOT) {
		        	$this->setState(31);
		        	$this->match(self::DOT);
		        	$this->setState(32);
		        	$this->match(self::SQL_NAME);
		        }
		        $this->setState(39);
		        $this->errorHandler->sync($this);
		        $_la = $this->input->LA(1);

		        if (((($_la) & ~0x3f) === 0 && ((1 << $_la) & ((1 << self::AS) | (1 << self::STRING_LITERAL) | (1 << self::SQL_NAME))) !== 0)) {
		        	$this->setState(36);
		        	$this->errorHandler->sync($this);
		        	$_la = $this->input->LA(1);

		        	if ($_la === self::AS) {
		        		$this->setState(35);
		        		$this->match(self::AS);
		        	}
		        	$this->setState(38);
		        	$this->columnAlias();
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
		public function columnAlias() : Context\ColumnAliasContext
		{
		    $localContext = new Context\ColumnAliasContext($this->ctx, $this->getState());

		    $this->enterRule($localContext, 2, self::RULE_columnAlias);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(41);

		        $_la = $this->input->LA(1);

		        if (!($_la === self::STRING_LITERAL || $_la === self::SQL_NAME)) {
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
		public function literalValue() : Context\LiteralValueContext
		{
		    $localContext = new Context\LiteralValueContext($this->ctx, $this->getState());

		    $this->enterRule($localContext, 4, self::RULE_literalValue);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(43);

		        $_la = $this->input->LA(1);

		        if (!(((($_la) & ~0x3f) === 0 && ((1 << $_la) & ((1 << self::FALSE) | (1 << self::NOW) | (1 << self::NULL) | (1 << self::TRUE) | (1 << self::INTEGER_LITERAL) | (1 << self::NUMERIC_LITERAL) | (1 << self::STRING_LITERAL))) !== 0))) {
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
		public function predicate() : Context\PredicateContext
		{
		    $localContext = new Context\PredicateContext($this->ctx, $this->getState());

		    $this->enterRule($localContext, 6, self::RULE_predicate);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(45);
		        $this->columnName();
		        $this->setState(80);
		        $this->errorHandler->sync($this);

		        switch ($this->getInterpreter()->adaptivePredict($this->input, 9, $this->ctx)) {
		        	case 1:
		        	    $this->setState(46);

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
		        	    $this->setState(47);
		        	    $this->literalValue();
		        	break;

		        	case 2:
		        	    $this->setState(49);
		        	    $this->errorHandler->sync($this);
		        	    $_la = $this->input->LA(1);

		        	    if ($_la === self::NOT) {
		        	    	$this->setState(48);
		        	    	$this->match(self::NOT);
		        	    }
		        	    $this->setState(54);
		        	    $this->errorHandler->sync($this);

		        	    switch ($this->input->LA(1)) {
		        	        case self::LIKE:
		        	        	$this->setState(51);
		        	        	$this->match(self::LIKE);
		        	        	break;

		        	        case self::GLOB:
		        	        	$this->setState(52);
		        	        	$this->match(self::GLOB);
		        	        	break;

		        	        case self::FALSE:
		        	        case self::NOW:
		        	        case self::NULL:
		        	        case self::TRUE:
		        	        case self::INTEGER_LITERAL:
		        	        case self::NUMERIC_LITERAL:
		        	        case self::STRING_LITERAL:
		        	        	$this->setState(53);
		        	        	$this->literalValue();
		        	        	break;

		        	    default:
		        	    	throw new NoViableAltException($this);
		        	    }
		        	break;

		        	case 3:
		        	    $this->setState(57);
		        	    $this->errorHandler->sync($this);
		        	    $_la = $this->input->LA(1);

		        	    if ($_la === self::NOT) {
		        	    	$this->setState(56);
		        	    	$this->match(self::NOT);
		        	    }
		        	    $this->setState(59);
		        	    $this->match(self::BETWEEN);
		        	    $this->setState(60);
		        	    $this->literalValue();
		        	    $this->setState(61);
		        	    $this->match(self::AND);
		        	    $this->setState(62);
		        	    $this->literalValue();
		        	break;

		        	case 4:
		        	    $this->setState(65);
		        	    $this->errorHandler->sync($this);
		        	    $_la = $this->input->LA(1);

		        	    if ($_la === self::NOT) {
		        	    	$this->setState(64);
		        	    	$this->match(self::NOT);
		        	    }
		        	    $this->setState(67);
		        	    $this->match(self::IN);
		        	    $this->setState(68);
		        	    $this->match(self::OPEN_PAR);
		        	    $this->setState(77);
		        	    $this->errorHandler->sync($this);
		        	    $_la = $this->input->LA(1);

		        	    if (((($_la) & ~0x3f) === 0 && ((1 << $_la) & ((1 << self::FALSE) | (1 << self::NOW) | (1 << self::NULL) | (1 << self::TRUE) | (1 << self::INTEGER_LITERAL) | (1 << self::NUMERIC_LITERAL) | (1 << self::STRING_LITERAL))) !== 0)) {
		        	    	$this->setState(69);
		        	    	$this->literalValue();
		        	    	$this->setState(74);
		        	    	$this->errorHandler->sync($this);

		        	    	$_la = $this->input->LA(1);
		        	    	while ($_la === self::COMMA) {
		        	    		$this->setState(70);
		        	    		$this->match(self::COMMA);
		        	    		$this->setState(71);
		        	    		$this->literalValue();
		        	    		$this->setState(76);
		        	    		$this->errorHandler->sync($this);
		        	    		$_la = $this->input->LA(1);
		        	    	}
		        	    }
		        	    $this->setState(79);
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
		public function columns() : Context\ColumnsContext
		{
		    $localContext = new Context\ColumnsContext($this->ctx, $this->getState());

		    $this->enterRule($localContext, 8, self::RULE_columns);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(82);
		        $this->column();
		        $this->setState(87);
		        $this->errorHandler->sync($this);

		        $_la = $this->input->LA(1);
		        while ($_la === self::COMMA) {
		        	$this->setState(83);
		        	$this->match(self::COMMA);
		        	$this->setState(84);
		        	$this->column();
		        	$this->setState(89);
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
		        $this->setState(90);
		        $this->predicate();
		        $this->setState(95);
		        $this->errorHandler->sync($this);

		        $_la = $this->input->LA(1);
		        while ($_la === self::AND || $_la === self::OR) {
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
		        	$this->predicate();
		        	$this->setState(97);
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
		public function where() : Context\WhereContext
		{
		    $localContext = new Context\WhereContext($this->ctx, $this->getState());

		    $this->enterRule($localContext, 12, self::RULE_where);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(100);
		        $this->errorHandler->sync($this);
		        $_la = $this->input->LA(1);

		        if ($_la === self::WHERE) {
		        	$this->setState(98);
		        	$this->match(self::WHERE);
		        	$this->setState(99);
		        	$this->predicates();
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

		    $this->enterRule($localContext, 14, self::RULE_tables);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(104);
		        $this->errorHandler->sync($this);
		        $_la = $this->input->LA(1);

		        if ($_la === self::FROM) {
		        	$this->setState(102);
		        	$this->match(self::FROM);
		        	$this->setState(103);
		        	$this->tabelName();
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

		    $this->enterRule($localContext, 16, self::RULE_logicalSql);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(106);
		        $this->match(self::SELECT);
		        $this->setState(107);
		        $this->columns();
		        $this->setState(108);
		        $this->tables();
		        $this->setState(109);
		        $this->where();
		        $this->setState(111);
		        $this->errorHandler->sync($this);
		        $_la = $this->input->LA(1);

		        if ($_la === self::ORDER) {
		        	$this->setState(110);
		        	$this->orderBy();
		        }
		        $this->setState(114);
		        $this->errorHandler->sync($this);
		        $_la = $this->input->LA(1);

		        if ($_la === self::LIMIT) {
		        	$this->setState(113);
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

		/**
		 * @throws RecognitionException
		 */
		public function tabelName() : Context\TabelNameContext
		{
		    $localContext = new Context\TabelNameContext($this->ctx, $this->getState());

		    $this->enterRule($localContext, 18, self::RULE_tabelName);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(116);
		        $this->match(self::SQL_NAME);
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
		public function columnName() : Context\ColumnNameContext
		{
		    $localContext = new Context\ColumnNameContext($this->ctx, $this->getState());

		    $this->enterRule($localContext, 20, self::RULE_columnName);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(118);
		        $this->match(self::SQL_NAME);
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

		    $this->enterRule($localContext, 22, self::RULE_limit);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(120);
		        $this->match(self::LIMIT);
		        $this->setState(121);
		        $this->match(self::INTEGER_LITERAL);
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
		public function orderBy() : Context\OrderByContext
		{
		    $localContext = new Context\OrderByContext($this->ctx, $this->getState());

		    $this->enterRule($localContext, 24, self::RULE_orderBy);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(123);
		        $this->match(self::ORDER);
		        $this->setState(124);
		        $this->match(self::BY);
		        $this->setState(125);
		        $this->orderByDef();
		        $this->setState(130);
		        $this->errorHandler->sync($this);

		        $_la = $this->input->LA(1);
		        while ($_la === self::COMMA) {
		        	$this->setState(126);
		        	$this->match(self::COMMA);
		        	$this->setState(127);
		        	$this->orderByDef();
		        	$this->setState(132);
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

		    $this->enterRule($localContext, 26, self::RULE_orderByDef);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(133);
		        $this->columnName();
		        $this->setState(135);
		        $this->errorHandler->sync($this);
		        $_la = $this->input->LA(1);

		        if ($_la === self::ASC || $_la === self::DESC) {
		        	$this->setState(134);
		        	$this->order();
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
		public function order() : Context\OrderContext
		{
		    $localContext = new Context\OrderContext($this->ctx, $this->getState());

		    $this->enterRule($localContext, 28, self::RULE_order);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(137);

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
	use ComboStrap\LogicalSqlAntlr\Gen\LogicalSqlParser;
	use ComboStrap\LogicalSqlAntlr\Gen\LogicalSqlVisitor;
	use ComboStrap\LogicalSqlAntlr\Gen\LogicalSqlListener;

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
	     * @return array<TerminalNode>|TerminalNode|null
	     */
	    public function SQL_NAME(?int $index = null)
	    {
	    	if ($index === null) {
	    		return $this->getTokens(LogicalSqlParser::SQL_NAME);
	    	}

	        return $this->getToken(LogicalSqlParser::SQL_NAME, $index);
	    }

	    public function DOT() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::DOT, 0);
	    }

	    public function columnAlias() : ?ColumnAliasContext
	    {
	    	return $this->getTypedRuleContext(ColumnAliasContext::class, 0);
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

	class ColumnAliasContext extends ParserRuleContext
	{
		public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
		{
			parent::__construct($parent, $invokingState);
		}

		public function getRuleIndex() : int
		{
		    return LogicalSqlParser::RULE_columnAlias;
	    }

	    public function SQL_NAME() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::SQL_NAME, 0);
	    }

	    public function STRING_LITERAL() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::STRING_LITERAL, 0);
	    }

		public function enterRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof LogicalSqlListener) {
			    $listener->enterColumnAlias($this);
		    }
		}

		public function exitRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof LogicalSqlListener) {
			    $listener->exitColumnAlias($this);
		    }
		}

		public function accept(ParseTreeVisitor $visitor)
		{
			if ($visitor instanceof LogicalSqlVisitor) {
			    return $visitor->visitColumnAlias($this);
		    }

			return $visitor->visitChildren($this);
		}
	} 

	class LiteralValueContext extends ParserRuleContext
	{
		public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
		{
			parent::__construct($parent, $invokingState);
		}

		public function getRuleIndex() : int
		{
		    return LogicalSqlParser::RULE_literalValue;
	    }

	    public function INTEGER_LITERAL() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::INTEGER_LITERAL, 0);
	    }

	    public function NUMERIC_LITERAL() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::NUMERIC_LITERAL, 0);
	    }

	    public function STRING_LITERAL() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::STRING_LITERAL, 0);
	    }

	    public function NULL() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::NULL, 0);
	    }

	    public function TRUE() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::TRUE, 0);
	    }

	    public function FALSE() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::FALSE, 0);
	    }

	    public function NOW() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::NOW, 0);
	    }

		public function enterRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof LogicalSqlListener) {
			    $listener->enterLiteralValue($this);
		    }
		}

		public function exitRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof LogicalSqlListener) {
			    $listener->exitLiteralValue($this);
		    }
		}

		public function accept(ParseTreeVisitor $visitor)
		{
			if ($visitor instanceof LogicalSqlVisitor) {
			    return $visitor->visitLiteralValue($this);
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

	    public function columnName() : ?ColumnNameContext
	    {
	    	return $this->getTypedRuleContext(ColumnNameContext::class, 0);
	    }

	    /**
	     * @return array<LiteralValueContext>|LiteralValueContext|null
	     */
	    public function literalValue(?int $index = null)
	    {
	    	if ($index === null) {
	    		return $this->getTypedRuleContexts(LiteralValueContext::class);
	    	}

	        return $this->getTypedRuleContext(LiteralValueContext::class, $index);
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

	    public function OPEN_PAR() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::OPEN_PAR, 0);
	    }

	    public function CLOSE_PAR() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::CLOSE_PAR, 0);
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

	class WhereContext extends ParserRuleContext
	{
		public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
		{
			parent::__construct($parent, $invokingState);
		}

		public function getRuleIndex() : int
		{
		    return LogicalSqlParser::RULE_where;
	    }

	    public function WHERE() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::WHERE, 0);
	    }

	    public function predicates() : ?PredicatesContext
	    {
	    	return $this->getTypedRuleContext(PredicatesContext::class, 0);
	    }

		public function enterRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof LogicalSqlListener) {
			    $listener->enterWhere($this);
		    }
		}

		public function exitRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof LogicalSqlListener) {
			    $listener->exitWhere($this);
		    }
		}

		public function accept(ParseTreeVisitor $visitor)
		{
			if ($visitor instanceof LogicalSqlVisitor) {
			    return $visitor->visitWhere($this);
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

	    public function tabelName() : ?TabelNameContext
	    {
	    	return $this->getTypedRuleContext(TabelNameContext::class, 0);
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

	    public function tables() : ?TablesContext
	    {
	    	return $this->getTypedRuleContext(TablesContext::class, 0);
	    }

	    public function where() : ?WhereContext
	    {
	    	return $this->getTypedRuleContext(WhereContext::class, 0);
	    }

	    public function orderBy() : ?OrderByContext
	    {
	    	return $this->getTypedRuleContext(OrderByContext::class, 0);
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

	class TabelNameContext extends ParserRuleContext
	{
		public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
		{
			parent::__construct($parent, $invokingState);
		}

		public function getRuleIndex() : int
		{
		    return LogicalSqlParser::RULE_tabelName;
	    }

	    public function SQL_NAME() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::SQL_NAME, 0);
	    }

		public function enterRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof LogicalSqlListener) {
			    $listener->enterTabelName($this);
		    }
		}

		public function exitRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof LogicalSqlListener) {
			    $listener->exitTabelName($this);
		    }
		}

		public function accept(ParseTreeVisitor $visitor)
		{
			if ($visitor instanceof LogicalSqlVisitor) {
			    return $visitor->visitTabelName($this);
		    }

			return $visitor->visitChildren($this);
		}
	} 

	class ColumnNameContext extends ParserRuleContext
	{
		public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
		{
			parent::__construct($parent, $invokingState);
		}

		public function getRuleIndex() : int
		{
		    return LogicalSqlParser::RULE_columnName;
	    }

	    public function SQL_NAME() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::SQL_NAME, 0);
	    }

		public function enterRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof LogicalSqlListener) {
			    $listener->enterColumnName($this);
		    }
		}

		public function exitRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof LogicalSqlListener) {
			    $listener->exitColumnName($this);
		    }
		}

		public function accept(ParseTreeVisitor $visitor)
		{
			if ($visitor instanceof LogicalSqlVisitor) {
			    return $visitor->visitColumnName($this);
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

	    public function INTEGER_LITERAL() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::INTEGER_LITERAL, 0);
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

	class OrderByContext extends ParserRuleContext
	{
		public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
		{
			parent::__construct($parent, $invokingState);
		}

		public function getRuleIndex() : int
		{
		    return LogicalSqlParser::RULE_orderBy;
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
			    $listener->enterOrderBy($this);
		    }
		}

		public function exitRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof LogicalSqlListener) {
			    $listener->exitOrderBy($this);
		    }
		}

		public function accept(ParseTreeVisitor $visitor)
		{
			if ($visitor instanceof LogicalSqlVisitor) {
			    return $visitor->visitOrderBy($this);
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

	    public function columnName() : ?ColumnNameContext
	    {
	    	return $this->getTypedRuleContext(ColumnNameContext::class, 0);
	    }

	    public function order() : ?OrderContext
	    {
	    	return $this->getTypedRuleContext(OrderContext::class, 0);
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

	class OrderContext extends ParserRuleContext
	{
		public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
		{
			parent::__construct($parent, $invokingState);
		}

		public function getRuleIndex() : int
		{
		    return LogicalSqlParser::RULE_order;
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
			    $listener->enterOrder($this);
		    }
		}

		public function exitRule(ParseTreeListener $listener) : void
		{
			if ($listener instanceof LogicalSqlListener) {
			    $listener->exitOrder($this);
		    }
		}

		public function accept(ParseTreeVisitor $visitor)
		{
			if ($visitor instanceof LogicalSqlVisitor) {
			    return $visitor->visitOrder($this);
		    }

			return $visitor->visitChildren($this);
		}
	} 
}