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
               PAGES = 48, BACKLINKS = 49, SPACES = 50, LITERAL_VALUE = 51, 
               INTEGER_LITERAL = 52, NUMERIC_LITERAL = 53, STRING_LITERAL = 54, 
               SQL_NAME = 55;

		public const RULE_column = 0, RULE_columnAlias = 1, RULE_predicate = 2, 
               RULE_columns = 3, RULE_predicates = 4, RULE_tables = 5, RULE_logicalSql = 6, 
               RULE_limit = 7, RULE_orderBys = 8, RULE_orderByDef = 9;

		/**
		 * @var array<string>
		 */
		public const RULE_NAMES = [
			'column', 'columnAlias', 'predicate', 'columns', 'predicates', 'tables', 
			'logicalSql', 'limit', 'orderBys', 'orderByDef'
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
		    "WHERE", "PAGES", "BACKLINKS", "SPACES", "LITERAL_VALUE", "INTEGER_LITERAL", 
		    "NUMERIC_LITERAL", "STRING_LITERAL", "SQL_NAME"
		];

		/**
		 * @var string
		 */
		private const SERIALIZED_ATN =
			"\u{3}\u{608B}\u{A72A}\u{8133}\u{B9ED}\u{417C}\u{3BE7}\u{7786}\u{5964}" .
		    "\u{3}\u{39}\u{78}\u{4}\u{2}\u{9}\u{2}\u{4}\u{3}\u{9}\u{3}\u{4}\u{4}" .
		    "\u{9}\u{4}\u{4}\u{5}\u{9}\u{5}\u{4}\u{6}\u{9}\u{6}\u{4}\u{7}\u{9}" .
		    "\u{7}\u{4}\u{8}\u{9}\u{8}\u{4}\u{9}\u{9}\u{9}\u{4}\u{A}\u{9}\u{A}" .
		    "\u{4}\u{B}\u{9}\u{B}\u{3}\u{2}\u{3}\u{2}\u{3}\u{2}\u{5}\u{2}\u{1A}" .
		    "\u{A}\u{2}\u{3}\u{2}\u{5}\u{2}\u{1D}\u{A}\u{2}\u{3}\u{2}\u{5}\u{2}" .
		    "\u{20}\u{A}\u{2}\u{3}\u{3}\u{3}\u{3}\u{3}\u{4}\u{3}\u{4}\u{3}\u{4}" .
		    "\u{3}\u{4}\u{5}\u{4}\u{28}\u{A}\u{4}\u{3}\u{4}\u{3}\u{4}\u{3}\u{4}" .
		    "\u{5}\u{4}\u{2D}\u{A}\u{4}\u{3}\u{4}\u{3}\u{4}\u{3}\u{4}\u{3}\u{4}" .
		    "\u{3}\u{4}\u{5}\u{4}\u{34}\u{A}\u{4}\u{3}\u{4}\u{3}\u{4}\u{3}\u{4}" .
		    "\u{3}\u{4}\u{3}\u{4}\u{7}\u{4}\u{3B}\u{A}\u{4}\u{C}\u{4}\u{E}\u{4}" .
		    "\u{3E}\u{B}\u{4}\u{5}\u{4}\u{40}\u{A}\u{4}\u{3}\u{4}\u{5}\u{4}\u{43}" .
		    "\u{A}\u{4}\u{3}\u{5}\u{3}\u{5}\u{3}\u{5}\u{7}\u{5}\u{48}\u{A}\u{5}" .
		    "\u{C}\u{5}\u{E}\u{5}\u{4B}\u{B}\u{5}\u{3}\u{6}\u{3}\u{6}\u{3}\u{6}" .
		    "\u{3}\u{6}\u{7}\u{6}\u{51}\u{A}\u{6}\u{C}\u{6}\u{E}\u{6}\u{54}\u{B}" .
		    "\u{6}\u{3}\u{7}\u{3}\u{7}\u{3}\u{7}\u{3}\u{8}\u{3}\u{8}\u{3}\u{8}" .
		    "\u{5}\u{8}\u{5C}\u{A}\u{8}\u{3}\u{8}\u{5}\u{8}\u{5F}\u{A}\u{8}\u{3}" .
		    "\u{8}\u{5}\u{8}\u{62}\u{A}\u{8}\u{3}\u{8}\u{5}\u{8}\u{65}\u{A}\u{8}" .
		    "\u{3}\u{9}\u{3}\u{9}\u{3}\u{9}\u{3}\u{A}\u{3}\u{A}\u{3}\u{A}\u{3}" .
		    "\u{A}\u{3}\u{A}\u{7}\u{A}\u{6F}\u{A}\u{A}\u{C}\u{A}\u{E}\u{A}\u{72}" .
		    "\u{B}\u{A}\u{3}\u{B}\u{3}\u{B}\u{5}\u{B}\u{76}\u{A}\u{B}\u{3}\u{B}" .
		    "\u{2}\u{2}\u{C}\u{2}\u{4}\u{6}\u{8}\u{A}\u{C}\u{E}\u{10}\u{12}\u{14}" .
		    "\u{2}\u{8}\u{3}\u{2}\u{38}\u{39}\u{5}\u{2}\u{8}\u{8}\u{14}\u{17}\u{19}" .
		    "\u{19}\u{4}\u{2}\u{23}\u{23}\u{27}\u{27}\u{4}\u{2}\u{1B}\u{1B}\u{2D}" .
		    "\u{2D}\u{3}\u{2}\u{32}\u{33}\u{4}\u{2}\u{1D}\u{1D}\u{20}\u{20}\u{2}" .
		    "\u{80}\u{2}\u{16}\u{3}\u{2}\u{2}\u{2}\u{4}\u{21}\u{3}\u{2}\u{2}\u{2}" .
		    "\u{6}\u{23}\u{3}\u{2}\u{2}\u{2}\u{8}\u{44}\u{3}\u{2}\u{2}\u{2}\u{A}" .
		    "\u{4C}\u{3}\u{2}\u{2}\u{2}\u{C}\u{55}\u{3}\u{2}\u{2}\u{2}\u{E}\u{58}" .
		    "\u{3}\u{2}\u{2}\u{2}\u{10}\u{66}\u{3}\u{2}\u{2}\u{2}\u{12}\u{69}\u{3}" .
		    "\u{2}\u{2}\u{2}\u{14}\u{73}\u{3}\u{2}\u{2}\u{2}\u{16}\u{19}\u{7}\u{39}" .
		    "\u{2}\u{2}\u{17}\u{18}\u{7}\u{4}\u{2}\u{2}\u{18}\u{1A}\u{7}\u{39}" .
		    "\u{2}\u{2}\u{19}\u{17}\u{3}\u{2}\u{2}\u{2}\u{19}\u{1A}\u{3}\u{2}\u{2}" .
		    "\u{2}\u{1A}\u{1F}\u{3}\u{2}\u{2}\u{2}\u{1B}\u{1D}\u{7}\u{1C}\u{2}" .
		    "\u{2}\u{1C}\u{1B}\u{3}\u{2}\u{2}\u{2}\u{1C}\u{1D}\u{3}\u{2}\u{2}\u{2}" .
		    "\u{1D}\u{1E}\u{3}\u{2}\u{2}\u{2}\u{1E}\u{20}\u{5}\u{4}\u{3}\u{2}\u{1F}" .
		    "\u{1C}\u{3}\u{2}\u{2}\u{2}\u{1F}\u{20}\u{3}\u{2}\u{2}\u{2}\u{20}\u{3}" .
		    "\u{3}\u{2}\u{2}\u{2}\u{21}\u{22}\u{9}\u{2}\u{2}\u{2}\u{22}\u{5}\u{3}" .
		    "\u{2}\u{2}\u{2}\u{23}\u{42}\u{7}\u{39}\u{2}\u{2}\u{24}\u{25}\u{9}" .
		    "\u{3}\u{2}\u{2}\u{25}\u{43}\u{7}\u{35}\u{2}\u{2}\u{26}\u{28}\u{7}" .
		    "\u{29}\u{2}\u{2}\u{27}\u{26}\u{3}\u{2}\u{2}\u{2}\u{27}\u{28}\u{3}" .
		    "\u{2}\u{2}\u{2}\u{28}\u{29}\u{3}\u{2}\u{2}\u{2}\u{29}\u{2A}\u{9}\u{4}" .
		    "\u{2}\u{2}\u{2A}\u{43}\u{7}\u{35}\u{2}\u{2}\u{2B}\u{2D}\u{7}\u{29}" .
		    "\u{2}\u{2}\u{2C}\u{2B}\u{3}\u{2}\u{2}\u{2}\u{2C}\u{2D}\u{3}\u{2}\u{2}" .
		    "\u{2}\u{2D}\u{2E}\u{3}\u{2}\u{2}\u{2}\u{2E}\u{2F}\u{7}\u{1E}\u{2}" .
		    "\u{2}\u{2F}\u{30}\u{7}\u{35}\u{2}\u{2}\u{30}\u{31}\u{7}\u{1B}\u{2}" .
		    "\u{2}\u{31}\u{43}\u{7}\u{35}\u{2}\u{2}\u{32}\u{34}\u{7}\u{29}\u{2}" .
		    "\u{2}\u{33}\u{32}\u{3}\u{2}\u{2}\u{2}\u{33}\u{34}\u{3}\u{2}\u{2}\u{2}" .
		    "\u{34}\u{35}\u{3}\u{2}\u{2}\u{2}\u{35}\u{36}\u{7}\u{24}\u{2}\u{2}" .
		    "\u{36}\u{3F}\u{7}\u{5}\u{2}\u{2}\u{37}\u{3C}\u{7}\u{35}\u{2}\u{2}" .
		    "\u{38}\u{39}\u{7}\u{7}\u{2}\u{2}\u{39}\u{3B}\u{7}\u{35}\u{2}\u{2}" .
		    "\u{3A}\u{38}\u{3}\u{2}\u{2}\u{2}\u{3B}\u{3E}\u{3}\u{2}\u{2}\u{2}\u{3C}" .
		    "\u{3A}\u{3}\u{2}\u{2}\u{2}\u{3C}\u{3D}\u{3}\u{2}\u{2}\u{2}\u{3D}\u{40}" .
		    "\u{3}\u{2}\u{2}\u{2}\u{3E}\u{3C}\u{3}\u{2}\u{2}\u{2}\u{3F}\u{37}\u{3}" .
		    "\u{2}\u{2}\u{2}\u{3F}\u{40}\u{3}\u{2}\u{2}\u{2}\u{40}\u{41}\u{3}\u{2}" .
		    "\u{2}\u{2}\u{41}\u{43}\u{7}\u{6}\u{2}\u{2}\u{42}\u{24}\u{3}\u{2}\u{2}" .
		    "\u{2}\u{42}\u{27}\u{3}\u{2}\u{2}\u{2}\u{42}\u{2C}\u{3}\u{2}\u{2}\u{2}" .
		    "\u{42}\u{33}\u{3}\u{2}\u{2}\u{2}\u{43}\u{7}\u{3}\u{2}\u{2}\u{2}\u{44}" .
		    "\u{49}\u{5}\u{2}\u{2}\u{2}\u{45}\u{46}\u{7}\u{7}\u{2}\u{2}\u{46}\u{48}" .
		    "\u{5}\u{2}\u{2}\u{2}\u{47}\u{45}\u{3}\u{2}\u{2}\u{2}\u{48}\u{4B}\u{3}" .
		    "\u{2}\u{2}\u{2}\u{49}\u{47}\u{3}\u{2}\u{2}\u{2}\u{49}\u{4A}\u{3}\u{2}" .
		    "\u{2}\u{2}\u{4A}\u{9}\u{3}\u{2}\u{2}\u{2}\u{4B}\u{49}\u{3}\u{2}\u{2}" .
		    "\u{2}\u{4C}\u{4D}\u{7}\u{31}\u{2}\u{2}\u{4D}\u{52}\u{5}\u{6}\u{4}" .
		    "\u{2}\u{4E}\u{4F}\u{9}\u{5}\u{2}\u{2}\u{4F}\u{51}\u{5}\u{6}\u{4}\u{2}" .
		    "\u{50}\u{4E}\u{3}\u{2}\u{2}\u{2}\u{51}\u{54}\u{3}\u{2}\u{2}\u{2}\u{52}" .
		    "\u{50}\u{3}\u{2}\u{2}\u{2}\u{52}\u{53}\u{3}\u{2}\u{2}\u{2}\u{53}\u{B}" .
		    "\u{3}\u{2}\u{2}\u{2}\u{54}\u{52}\u{3}\u{2}\u{2}\u{2}\u{55}\u{56}\u{7}" .
		    "\u{22}\u{2}\u{2}\u{56}\u{57}\u{9}\u{6}\u{2}\u{2}\u{57}\u{D}\u{3}\u{2}" .
		    "\u{2}\u{2}\u{58}\u{59}\u{7}\u{2F}\u{2}\u{2}\u{59}\u{5B}\u{5}\u{8}" .
		    "\u{5}\u{2}\u{5A}\u{5C}\u{5}\u{C}\u{7}\u{2}\u{5B}\u{5A}\u{3}\u{2}\u{2}" .
		    "\u{2}\u{5B}\u{5C}\u{3}\u{2}\u{2}\u{2}\u{5C}\u{5E}\u{3}\u{2}\u{2}\u{2}" .
		    "\u{5D}\u{5F}\u{5}\u{A}\u{6}\u{2}\u{5E}\u{5D}\u{3}\u{2}\u{2}\u{2}\u{5E}" .
		    "\u{5F}\u{3}\u{2}\u{2}\u{2}\u{5F}\u{61}\u{3}\u{2}\u{2}\u{2}\u{60}\u{62}" .
		    "\u{5}\u{12}\u{A}\u{2}\u{61}\u{60}\u{3}\u{2}\u{2}\u{2}\u{61}\u{62}" .
		    "\u{3}\u{2}\u{2}\u{2}\u{62}\u{64}\u{3}\u{2}\u{2}\u{2}\u{63}\u{65}\u{5}" .
		    "\u{10}\u{9}\u{2}\u{64}\u{63}\u{3}\u{2}\u{2}\u{2}\u{64}\u{65}\u{3}" .
		    "\u{2}\u{2}\u{2}\u{65}\u{F}\u{3}\u{2}\u{2}\u{2}\u{66}\u{67}\u{7}\u{28}" .
		    "\u{2}\u{2}\u{67}\u{68}\u{7}\u{35}\u{2}\u{2}\u{68}\u{11}\u{3}\u{2}" .
		    "\u{2}\u{2}\u{69}\u{6A}\u{7}\u{2E}\u{2}\u{2}\u{6A}\u{6B}\u{7}\u{1F}" .
		    "\u{2}\u{2}\u{6B}\u{70}\u{5}\u{14}\u{B}\u{2}\u{6C}\u{6D}\u{7}\u{7}" .
		    "\u{2}\u{2}\u{6D}\u{6F}\u{5}\u{14}\u{B}\u{2}\u{6E}\u{6C}\u{3}\u{2}" .
		    "\u{2}\u{2}\u{6F}\u{72}\u{3}\u{2}\u{2}\u{2}\u{70}\u{6E}\u{3}\u{2}\u{2}" .
		    "\u{2}\u{70}\u{71}\u{3}\u{2}\u{2}\u{2}\u{71}\u{13}\u{3}\u{2}\u{2}\u{2}" .
		    "\u{72}\u{70}\u{3}\u{2}\u{2}\u{2}\u{73}\u{75}\u{7}\u{39}\u{2}\u{2}" .
		    "\u{74}\u{76}\u{9}\u{7}\u{2}\u{2}\u{75}\u{74}\u{3}\u{2}\u{2}\u{2}\u{75}" .
		    "\u{76}\u{3}\u{2}\u{2}\u{2}\u{76}\u{15}\u{3}\u{2}\u{2}\u{2}\u{13}\u{19}" .
		    "\u{1C}\u{1F}\u{27}\u{2C}\u{33}\u{3C}\u{3F}\u{42}\u{49}\u{52}\u{5B}" .
		    "\u{5E}\u{61}\u{64}\u{70}\u{75}";

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
		        $this->setState(20);
		        $this->match(self::SQL_NAME);
		        $this->setState(23);
		        $this->errorHandler->sync($this);
		        $_la = $this->input->LA(1);

		        if ($_la === self::DOT) {
		        	$this->setState(21);
		        	$this->match(self::DOT);
		        	$this->setState(22);
		        	$this->match(self::SQL_NAME);
		        }
		        $this->setState(29);
		        $this->errorHandler->sync($this);
		        $_la = $this->input->LA(1);

		        if (((($_la) & ~0x3f) === 0 && ((1 << $_la) & ((1 << self::AS) | (1 << self::STRING_LITERAL) | (1 << self::SQL_NAME))) !== 0)) {
		        	$this->setState(26);
		        	$this->errorHandler->sync($this);
		        	$_la = $this->input->LA(1);

		        	if ($_la === self::AS) {
		        		$this->setState(25);
		        		$this->match(self::AS);
		        	}
		        	$this->setState(28);
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
		        $this->setState(31);

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
		public function predicate() : Context\PredicateContext
		{
		    $localContext = new Context\PredicateContext($this->ctx, $this->getState());

		    $this->enterRule($localContext, 4, self::RULE_predicate);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(33);
		        $this->match(self::SQL_NAME);
		        $this->setState(64);
		        $this->errorHandler->sync($this);

		        switch ($this->getInterpreter()->adaptivePredict($this->input, 8, $this->ctx)) {
		        	case 1:
		        	    $this->setState(34);

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
		        	    $this->setState(35);
		        	    $this->match(self::LITERAL_VALUE);
		        	break;

		        	case 2:
		        	    $this->setState(37);
		        	    $this->errorHandler->sync($this);
		        	    $_la = $this->input->LA(1);

		        	    if ($_la === self::NOT) {
		        	    	$this->setState(36);
		        	    	$this->match(self::NOT);
		        	    }
		        	    $this->setState(39);

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
		        	    $this->setState(40);
		        	    $this->match(self::LITERAL_VALUE);
		        	break;

		        	case 3:
		        	    $this->setState(42);
		        	    $this->errorHandler->sync($this);
		        	    $_la = $this->input->LA(1);

		        	    if ($_la === self::NOT) {
		        	    	$this->setState(41);
		        	    	$this->match(self::NOT);
		        	    }
		        	    $this->setState(44);
		        	    $this->match(self::BETWEEN);
		        	    $this->setState(45);
		        	    $this->match(self::LITERAL_VALUE);
		        	    $this->setState(46);
		        	    $this->match(self::AND);
		        	    $this->setState(47);
		        	    $this->match(self::LITERAL_VALUE);
		        	break;

		        	case 4:
		        	    $this->setState(49);
		        	    $this->errorHandler->sync($this);
		        	    $_la = $this->input->LA(1);

		        	    if ($_la === self::NOT) {
		        	    	$this->setState(48);
		        	    	$this->match(self::NOT);
		        	    }
		        	    $this->setState(51);
		        	    $this->match(self::IN);
		        	    $this->setState(52);
		        	    $this->match(self::OPEN_PAR);
		        	    $this->setState(61);
		        	    $this->errorHandler->sync($this);
		        	    $_la = $this->input->LA(1);

		        	    if ($_la === self::LITERAL_VALUE) {
		        	    	$this->setState(53);
		        	    	$this->match(self::LITERAL_VALUE);
		        	    	$this->setState(58);
		        	    	$this->errorHandler->sync($this);

		        	    	$_la = $this->input->LA(1);
		        	    	while ($_la === self::COMMA) {
		        	    		$this->setState(54);
		        	    		$this->match(self::COMMA);
		        	    		$this->setState(55);
		        	    		$this->match(self::LITERAL_VALUE);
		        	    		$this->setState(60);
		        	    		$this->errorHandler->sync($this);
		        	    		$_la = $this->input->LA(1);
		        	    	}
		        	    }
		        	    $this->setState(63);
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

		    $this->enterRule($localContext, 6, self::RULE_columns);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(66);
		        $this->column();
		        $this->setState(71);
		        $this->errorHandler->sync($this);

		        $_la = $this->input->LA(1);
		        while ($_la === self::COMMA) {
		        	$this->setState(67);
		        	$this->match(self::COMMA);
		        	$this->setState(68);
		        	$this->column();
		        	$this->setState(73);
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

		    $this->enterRule($localContext, 8, self::RULE_predicates);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(74);
		        $this->match(self::WHERE);
		        $this->setState(75);
		        $this->predicate();
		        $this->setState(80);
		        $this->errorHandler->sync($this);

		        $_la = $this->input->LA(1);
		        while ($_la === self::AND || $_la === self::OR) {
		        	$this->setState(76);

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
		        	$this->setState(77);
		        	$this->predicate();
		        	$this->setState(82);
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

		    $this->enterRule($localContext, 10, self::RULE_tables);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(83);
		        $this->match(self::FROM);
		        $this->setState(84);

		        $_la = $this->input->LA(1);

		        if (!($_la === self::PAGES || $_la === self::BACKLINKS)) {
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
		public function logicalSql() : Context\LogicalSqlContext
		{
		    $localContext = new Context\LogicalSqlContext($this->ctx, $this->getState());

		    $this->enterRule($localContext, 12, self::RULE_logicalSql);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(86);
		        $this->match(self::SELECT);
		        $this->setState(87);
		        $this->columns();
		        $this->setState(89);
		        $this->errorHandler->sync($this);
		        $_la = $this->input->LA(1);

		        if ($_la === self::FROM) {
		        	$this->setState(88);
		        	$this->tables();
		        }
		        $this->setState(92);
		        $this->errorHandler->sync($this);
		        $_la = $this->input->LA(1);

		        if ($_la === self::WHERE) {
		        	$this->setState(91);
		        	$this->predicates();
		        }
		        $this->setState(95);
		        $this->errorHandler->sync($this);
		        $_la = $this->input->LA(1);

		        if ($_la === self::ORDER) {
		        	$this->setState(94);
		        	$this->orderBys();
		        }
		        $this->setState(98);
		        $this->errorHandler->sync($this);
		        $_la = $this->input->LA(1);

		        if ($_la === self::LIMIT) {
		        	$this->setState(97);
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
		public function limit() : Context\LimitContext
		{
		    $localContext = new Context\LimitContext($this->ctx, $this->getState());

		    $this->enterRule($localContext, 14, self::RULE_limit);

		    try {
		        $this->enterOuterAlt($localContext, 1);
		        $this->setState(100);
		        $this->match(self::LIMIT);
		        $this->setState(101);
		        $this->match(self::LITERAL_VALUE);
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
		        $this->setState(103);
		        $this->match(self::ORDER);
		        $this->setState(104);
		        $this->match(self::BY);
		        $this->setState(105);
		        $this->orderByDef();
		        $this->setState(110);
		        $this->errorHandler->sync($this);

		        $_la = $this->input->LA(1);
		        while ($_la === self::COMMA) {
		        	$this->setState(106);
		        	$this->match(self::COMMA);
		        	$this->setState(107);
		        	$this->orderByDef();
		        	$this->setState(112);
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
		        $this->setState(113);
		        $this->match(self::SQL_NAME);
		        $this->setState(115);
		        $this->errorHandler->sync($this);
		        $_la = $this->input->LA(1);

		        if ($_la === self::ASC || $_la === self::DESC) {
		        	$this->setState(114);

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

	    public function SQL_NAME() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::SQL_NAME, 0);
	    }

	    /**
	     * @return array<TerminalNode>|TerminalNode|null
	     */
	    public function LITERAL_VALUE(?int $index = null)
	    {
	    	if ($index === null) {
	    		return $this->getTokens(LogicalSqlParser::LITERAL_VALUE);
	    	}

	        return $this->getToken(LogicalSqlParser::LITERAL_VALUE, $index);
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

	    public function PAGES() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::PAGES, 0);
	    }

	    public function BACKLINKS() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::BACKLINKS, 0);
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

	    public function LITERAL_VALUE() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::LITERAL_VALUE, 0);
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

	    public function SQL_NAME() : ?TerminalNode
	    {
	        return $this->getToken(LogicalSqlParser::SQL_NAME, 0);
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
}