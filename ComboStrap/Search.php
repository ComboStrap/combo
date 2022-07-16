<?php

namespace ComboStrap;

use action_plugin_combo_linkwizard;

/**
 * Function that supports the internal search functions
 */
class Search
{

    /**
     * @param $searchTermWords
     * @param array $columns
     * @return array - the parametrized sql and the parameters
     */
    public static function getPageRowsSql($searchTermWords, array $columns = ["h1", "title", "name"]): array
    {
        $sqlParameters = [];
        $sqlPredicates = [];
        foreach ($searchTermWords as $searchTermWord) {
            if (strlen($searchTermWord) < action_plugin_combo_linkwizard::MINIMAL_WORD_LENGTH) {
                continue;
            }
            $pattern = "%$searchTermWord%";
            $sqlPatternPredicates = [];
            foreach ($columns as $column) {
                $sqlParameters[] = $pattern;
                $sqlPatternPredicates[] = "$column like ? COLLATE NOCASE";
            }
            $sqlPredicates[] = "(" . implode(" or ", $sqlPatternPredicates) . ")";
        }
        $sqlPredicate = implode(" and ", $sqlPredicates);
        $searchTermSql = <<<EOF
select id as "id" from pages where $sqlPredicate order by name
EOF;
        return [$searchTermSql, $sqlParameters];
    }

    /**
     * @param $searchTerm
     * @param array $columns
     * @return MarkupPath[]
     */
    public static function getPages($searchTerm, array $columns = ["h1", "title", "name"]): array
    {
        $minimalWordLength = action_plugin_combo_linkwizard::MINIMAL_WORD_LENGTH;
        if (strlen($searchTerm) < $minimalWordLength) {
            return [];
        }
        $searchTermWords = StringUtility::getWords($searchTerm);
        if (sizeOf($searchTermWords) === 0) {
            return [];
        }
        $sqlite = Sqlite::createOrGetSqlite();
        if ($sqlite === null) {
            return [];
        }
        [$searchTermSql, $sqlParameters] = self::getPageRowsSql($searchTermWords, $columns);
        $request = $sqlite
            ->createRequest()
            ->setQueryParametrized($searchTermSql, $sqlParameters);
        $pages = [];
        try {
            $rows = $request
                ->execute()
                ->getRows();
            foreach ($rows as $row) {
                $pages[] = MarkupPath::createPageFromId($row["id"]);
            }
            return $pages;
        } catch (ExceptionCompile $e) {
            LogUtility::msg("Error while trying to retrieve a list of pages", LogUtility::LVL_MSG_ERROR, action_plugin_combo_linkwizard::CANONICAL);
            return $pages;
        } finally {
            $request->close();
        }
    }
}
