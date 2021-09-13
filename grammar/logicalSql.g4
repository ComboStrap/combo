//https://github.com/antlr/grammars-v4/blob/master/sql/sqlite

grammar logicalSql;


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

NUMERIC_LITERAL: DIGIT+;
STRING_LITERAL: '\'' ( ~'\'' | '\'\'')* '\'';


IDENTIFIER : [a-zA-Z] [a-zA-Z0-9]*;

/**
 * Fragment rules does not result in tokens visible to the parser.
 * They aid in the recognition of tokens.
*/

fragment HEX_DIGIT: [0-9a-fA-F];
fragment DIGIT:     [0-9];

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
 Parser (ie structure)
*/



result_column: IDENTIFIER (DOT IDENTIFIER)? ( AS? column_alias)? ;

column_alias: IDENTIFIER | STRING_LITERAL;

literal_value:
    NUMERIC_LITERAL
    | STRING_LITERAL
    | NULL
    | TRUE
    | FALSE
    | NOW
;

predicate_expression: column_name
    (
        (( LESS_THAN | LESS_THAN_OR_EQUAL | GREATER_THAN | GREATER_THAN_OR_EQUAL | NOT_EQUAL | EQUAL) literal_value)
        |
        (NOT? (LIKE|GLOB| literal_value))
        |
        (NOT? BETWEEN literal_value AND literal_value)
        |
        (NOT? IN OPEN_PAR (literal_value ( COMMA literal_value)*)? CLOSE_PAR)
    );


logicalSql:
        SELECT result_column (COMMA result_column)*
        (FROM table_name )?
        (WHERE predicate_expression ((AND|OR) predicate_expression)?)?
        order_by_stmt?
        limit_stmt?
;

table_name: any_name ;

column_name: any_name ;

any_name: IDENTIFIER | STRING_LITERAL | OPEN_PAR any_name CLOSE_PAR;

limit_stmt: LIMIT NUMERIC_LITERAL;

order_by_stmt: ORDER BY ordering_term (COMMA ordering_term)* ;

ordering_term: column_name asc_desc? ;

asc_desc: ASC | DESC ;
