# Antlr


With the [version 4.9.3](#version)
```cmd
antlr4 ^
    -o D:\dokuwiki\lib\plugins\combo\ComboStrap\PageSqlParser ^
    -package ComboStrap\PageSqlParser ^
    -Dlanguage=PHP ^
    -lib D:/dokuwiki/lib/plugins/combo/grammar  ^
    D:/dokuwiki/lib/plugins/combo/grammar\PageSql.g4
```


Don't for now as the idea plugin is not compatible. In the generator configuration in Idea:
  * Output directory: `D:\dokuwiki\lib\plugins\combo\`
  * Package: `ComboStrap\PageSqlParser`
  * Language: `PHP`
  * Lib (not yet used): `D:\dokuwiki\lib\plugins\combo\grammar`

See the [documentation](https://datacadamia.com/antlr/idea#test_antlr_rule)


## Version


For php7, you should install:
  * Runtime 0.5.1
    * Runtime 0.6 as a bad ATN
    * Above 0.6 antlr requires php8 - The antlr version can be seen in `Antlr\Antlr4\Runtime\RuntimeMetadata`. It's `4.9.3`
```bash
composer require antlr/antlr4-php-runtime:0.5.1
```
  * We can't manually use the antlr idea plugin (too old: 1.17 (ANTLR 4.9.2) (File > Settings > Install Manually)
https://plugins.jetbrains.com/plugin/7358-antlr-v4/versions
  * [Download the version 4.9.3](https://github.com/antlr/website-antlr4/blob/gh-pages/download/antlr-4.9.3-complete.jar)
  * And run it at the command line

## How to use in IntelliJ

Select the `pageSql` rule, right and select `Test Rule Page Sql`
