# Antlr


```bash
antlr4 \
    -o D:\dokuwiki\lib\plugins\combo\ComboStrap\LogicalSqlAntlr\Gen \
    -package ComboStrap\LogicalSqlAntlr\Gen \
    -listener \
    -visitor \
    -Dlanguage=PHP \
    -lib D:/dokuwiki/lib/plugins/combo/grammar \
    D:/dokuwiki/lib/plugins/combo/grammar\LogicalSql.g4
```
