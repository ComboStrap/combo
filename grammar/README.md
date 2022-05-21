# Antlr


```bash
antlr4 \
    -o D:\dokuwiki\lib\plugins\combo\ComboStrap\PageSqlParser \
    -package ComboStrap\PageSqlParser \
    -Dlanguage=PHP \
    -lib D:/dokuwiki/lib/plugins/combo/grammar \
    D:/dokuwiki/lib/plugins/combo/grammar\PageSql.g4
```

In the generator configuration in Idea:
  * Output directory: `D:\dokuwiki\lib\plugins\combo\`
  * Package: `ComboStrap\PageSqlParser`
  * Language: `PHP`
  * Lib (not yet used): `D:\dokuwiki\lib\plugins\combo\grammar`

See the [documentation](https://datacadamia.com/antlr/idea#test_antlr_rule)


# Version

For php7, you should install
  * Runtime 0.5.1
```bash
composer require antlr/antlr4-php-runtime:0.5.1
```
  * Manually the antlr plugin: 1.17 (ANTLR 4.9.2) (File > Settings > Install Manually)
https://plugins.jetbrains.com/plugin/7358-antlr-v4/versions
