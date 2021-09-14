//https://github.com/antlr/grammars-v4/blob/master/sql/sqlite

grammar LogicalSql;


/**
 Lexer (ie token)
 https://github.com/antlr/antlr4/blob/master/doc/lexer-rules.md
*/
SCOL:      ';';
DOT:       '.';
OPEN_PAR:  '(';
CLOSE_PAR: ')';
COMMA:     ',';
EQUAL:    '=';
STAR:      '*';
PLUS:      '+';
MINUS:     '-';
TILDE:     '~';
PIPE2:     '||';
DIV:       '/';
MOD:       '%';
LT2:       '<<';
GT2:       '>>';
AMP:       '&';
PIPE:      '|';
LESS_THAN:        '<';
LESS_THAN_OR_EQUAL:     '<=';
GREATER_THAN:        '>';
GREATER_THAN_OR_EQUAL:     '>=';
EQ:        '==';
NOT_EQUAL:   '!=';
NOT_EQ2:   '<>';

/**
 * Key word
*/
AND:               A N D;
AS:                A S;
ASC:               A S C;
BETWEEN:           B E T W E E N;
BY:                B Y;
DESC:              D E S C;
FALSE:             F A L S E;
FROM:              F R O M;
GLOB:              G L O B;
IN:                I N;
IS:                I S;
ISNULL:            I S N U L L;
LIKE:              L I K E;
LIMIT:             L I M I T;
NOT:               N O T;
NOTNULL:           N O T N U L L;
NOW:               N O W;
NULL:              N U L L;
OR:                O R;
ORDER:             O R D E R;
SELECT:            S E L E C T;
TRUE:              T R U E;
WHERE:             W H E R E;



/**
* Space are for human (discard)
*/
SPACES: [ \u000B\t\r\n] -> channel(HIDDEN);

/**
* Literal Value capture all literal value
* to avoid token conflict detection between (integer, numeric, ...)
* The type is catched at runtime
*/
LITERAL_VALUE: ALL_LITERAL_VALUE;


/**
 * Sql also does not permit
 * to start with a number
 * (just ot have no conflict with a NUMERIC_LITERAL)
*/
SQL_NAME : [a-zA-Z] [a-zA-Z0-9_-]*;


/**
 * Fragment rules does not result in tokens visible to the parser.
 * They aid in the recognition of tokens.
*/

fragment HEX_DIGIT: [0-9a-fA-F];
fragment DIGIT:     [0-9];
fragment INTEGER_LITERAL: DIGIT+;
fragment NUMERIC_LITERAL: DIGIT+ ('.' DIGIT*)?;
fragment STRING_LITERAL: '\'' ( ~'\'' | '\'\'')* '\'';
fragment ALL_LITERAL_VALUE: STRING_LITERAL | INTEGER_LITERAL | NUMERIC_LITERAL | NULL | TRUE
   | FALSE
   | NOW;


fragment ANY_NAME: SQL_NAME | STRING_LITERAL | OPEN_PAR ANY_NAME CLOSE_PAR;
fragment A: [aA];
fragment B: [bB];
fragment C: [cC];
fragment D: [dD];
fragment E: [eE];
fragment F: [fF];
fragment G: [gG];
fragment H: [hH];
fragment I: [iI];
fragment J: [jJ];
fragment K: [kK];
fragment L: [lL];
fragment M: [mM];
fragment N: [nN];
fragment O: [oO];
fragment P: [pP];
fragment Q: [qQ];
fragment R: [rR];
fragment S: [sS];
fragment T: [tT];
fragment U: [uU];
fragment V: [vV];
fragment W: [wW];
fragment X: [xX];
fragment Y: [yY];
fragment Z: [zZ];

/**
 * Parser (ie structure)
 * https://github.com/antlr/antlr4/blob/master/doc/parser-rules.md
*/


column: SQL_NAME (DOT SQL_NAME)? ( AS? STRING_LITERAL)?;



expression: LITERAL_VALUE | SQL_NAME OPEN_PAR expression ( COMMA expression)* CLOSE_PAR;

predicate: SQL_NAME
    (
        (( LESS_THAN | LESS_THAN_OR_EQUAL | GREATER_THAN | GREATER_THAN_OR_EQUAL | NOT_EQUAL | EQUAL) expression)
        |
        (NOT? (LIKE|GLOB) expression)
        |
        (NOT? BETWEEN expression AND expression)
        |
        (NOT? IN OPEN_PAR (expression ( COMMA expression)*)? CLOSE_PAR)
    );

columns: column (COMMA column)*;

predicates: WHERE predicate ((AND|OR) predicate)*;

tables: FROM SQL_NAME;

/**
 * The type of the literal value is
 * checked afterwards on tree traversing
 * otherwise there is conflict between token
*/
limit: LIMIT LITERAL_VALUE;

orderBys: ORDER BY orderByDef (COMMA orderByDef)* ;

orderByDef: SQL_NAME (ASC | DESC)? ;

/**
* The main/root rule
*/
logicalSql:
        SELECT columns
        tables?
        predicates?
        orderBys?
        limit?
;
