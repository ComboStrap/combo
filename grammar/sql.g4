grammar sql;

/**
 Lexer (ie token)
*/
SCOL:      ';';
DOT:       '.';
OPEN_PAR:  '(';
CLOSE_PAR: ')';
COMMA:     ',';
ASSIGN:    '=';
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
LT:        '<';
LT_EQ:     '<=';
GT:        '>';
GT_EQ:     '>=';
EQ:        '==';
NOT_EQ1:   '!=';
NOT_EQ2:   '<>';

/**
 * Key word
*/
AND_:               A N D;
AS_:                A S;
ASC_:               A S C;
BETWEEN_:           B E T W E E N;
BY_:                B Y;
DESC_:              D E S C;
FALSE_:             F A L S E;
FROM_:              F R O M;
GLOB_:              G L O B;
IN_:                I N;
IS_:                I S;
ISNULL_:            I S N U L L;
LIKE_:              L I K E;
LIMIT_:             L I M I T;
NOT_:               N O T;
NOTNULL_:           N O T N U L L;
NOW_:               N O W;
NULL_:              N U L L;
OR_:                O R;
ORDER_:             O R D E R;
SELECT_:            S E L E C T;
TRUE_:              T R U E;
WHERE_:             W H E R E;

SPACES: [ \u000B\t\r\n] -> channel(HIDDEN);


IDENTIFIER : [a-zA-Z0-9_-]+ ;

STRING_LITERAL: '\'' ( ~'\'' | '\'\'')* '\'';
NUMERIC_LITERAL: ((DIGIT+ ('.' DIGIT*)?) | ('.' DIGIT+)) (E [-+]? DIGIT+)? | '0x' HEX_DIGIT+;

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



result_column: IDENTIFIER ( AS_? column_alias)? ;

column_alias: IDENTIFIER | STRING_LITERAL;

literal_value:
    NUMERIC_LITERAL
    | STRING_LITERAL
    | NULL_
    | TRUE_
    | FALSE_
    | NOW_
;

predicate_expression: column_name
    (
        (( LT | LT_EQ | GT | GT_EQ | NOT_EQ1 | ASSIGN) literal_value)
        |
        (NOT_? (LIKE_|GLOB_| literal_value))
        |
        (NOT_? BETWEEN_ literal_value AND_ literal_value)
        |
        (NOT_? IN_ OPEN_PAR (literal_value ( COMMA literal_value)*)? CLOSE_PAR)
    );


select_stmt:
        SELECT_ result_column (COMMA result_column)*
        (FROM_ table_name )?
        (WHERE_ predicate_expression ((AND_|OR_) predicate_expression)?)?
        order_by_stmt?
        limit_stmt?
;

table_name: any_name ;
column_name: any_name ;

any_name:
    IDENTIFIER
    | STRING_LITERAL
    | OPEN_PAR any_name CLOSE_PAR
;

limit_stmt: LIMIT_ NUMERIC_LITERAL;

order_by_stmt:
    ORDER_ BY_ ordering_term (COMMA ordering_term)*
;

ordering_term:
    column_name asc_desc?
;

asc_desc:
    ASC_
    | DESC_
;
