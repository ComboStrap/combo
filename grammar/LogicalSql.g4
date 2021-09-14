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

SPACES: [ \u000B\t\r\n] -> channel(HIDDEN);

INTEGER_LITERAL: DIGIT+;
NUMERIC_LITERAL: DIGIT+ ('.' DIGIT*)?;

STRING_LITERAL: '\'' ( ~'\'' | '\'\'')* '\'';


/**
 * Sql also does not permit
 * to start with a number
 * (just ot have no conflict with a NUMERIC_LITERAL)
*/
SQL_NAME : [a-zA-Z] [a-zA-Z0-9]*;

/**
 * Fragment rules does not result in tokens visible to the parser.
 * They aid in the recognition of tokens.
*/

fragment HEX_DIGIT: [0-9a-fA-F];
fragment DIGIT:     [0-9];

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


column: SQL_NAME (DOT SQL_NAME)? ( AS? columnAlias)?;

columnAlias: SQL_NAME | STRING_LITERAL;

literalValue:
    INTEGER_LITERAL
    | NUMERIC_LITERAL
    | STRING_LITERAL
    | NULL
    | TRUE
    | FALSE
    | NOW
;

predicate: columnName
    (
        (( LESS_THAN | LESS_THAN_OR_EQUAL | GREATER_THAN | GREATER_THAN_OR_EQUAL | NOT_EQUAL | EQUAL) literalValue)
        |
        (NOT? (LIKE|GLOB| literalValue))
        |
        (NOT? BETWEEN literalValue AND literalValue)
        |
        (NOT? IN OPEN_PAR (literalValue ( COMMA literalValue)*)? CLOSE_PAR)
    );

columns: column (COMMA column)*;

predicates: predicate ((AND|OR) predicate)*;

where: (WHERE predicates)?;
tables: (FROM tabelName)?;

logicalSql:
        SELECT columns
        tables
        where
        orderBy?
        limit?
;

tabelName: SQL_NAME ;

columnName: SQL_NAME ;


limit: LIMIT INTEGER_LITERAL;

orderBy: ORDER BY orderByDef (COMMA orderByDef)* ;

orderByDef: SQL_NAME (ASC | DESC)? ;

