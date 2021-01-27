<?php

namespace ComboStrap;

use helper_plugin_sqlite;

/**
 * Class urlCanonical with all canonical methodology
 */
require_once(__DIR__ . '/PluginUtility.php');

class UrlCanonical
{
    const CANONICAL_PROPERTY = 'canonical';




    /**
     *
     * @param $canonical - null or the canonical value
     * @return string - the canonical URL
     */
    public static function getUrl($canonical)
    {
        if ($canonical != null) {
            $canonicalUrl = getBaseURL(true) . strtr($canonical, ':', '/');
        } else {
            /**
             * Dokuwiki Methodology taken from {@link tpl_metaheaders()}
             */
            global $ID;
            global $conf;
            $canonicalUrl = wl($ID, '', true, '&');
            if ($ID == $conf['start']) {
                $canonicalUrl = DOKU_URL;
            }
        }
        return$canonicalUrl;
    }


    /**
     * Does the page is known in the pages table
     * @param string $id
     * @return array
     */
    function getPage($id)
    {
        $id = strtolower($id);


        $sqlite = Sqlite::getSqlite();
        $res = $sqlite->query("SELECT * FROM pages where id = ?", $id);
        if (!$res) {
            throw new RuntimeException("An exception has occurred with the select pages query");
        }
        $res2arr = $sqlite->res2row($res);
        $sqlite->res_close($res);
        return $res2arr;


    }

    /**
     * Delete Page
     * @param string $id
     */
    function deletePage($id)
    {

        $res = Sqlite::getSqlite()->query('delete from pages where id = ?', $id);
        if (!$res) {
            LogUtility::msg("Something went wrong when deleting a page");
        }

    }

    /**
     * Does the page is known in the pages table
     * @param string $id
     * @return int
     */
    function pageExist($id)
    {
        $id = strtolower($id);
        $sqlite = Sqlite::getSqlite();
        $res = $sqlite->query("SELECT count(*) FROM pages where id = ?", $id);
        $count = $sqlite->res2single($res);
        $sqlite->res_close($res);
        return $count;

    }

    private function persistPageAlias($canonical, $alias)
    {

        $row = array(
            "CANONICAL" => $canonical,
            "ALIAS" => $alias
        );

        // Page has change of location
        // Creation of an alias
        $sqlite = Sqlite::getSqlite();
        $res = $sqlite->query("select count(*) from pages_alias where CANONICAL = ? and ALIAS = ?", $row);
        if (!$res) {
            throw new RuntimeException("An exception has occurred with the alia selection query");
        }
        $aliasInDb = $sqlite->res2single($res);
        $sqlite->res_close($res);
        if ($aliasInDb == 0) {

            $res = $sqlite->storeEntry('pages_alias', $row);
            if (!$res) {
                LogUtility::msg("There was a problem during pages_alias insertion");
            }
        }

    }

    /**
     * @param $canonical
     * @return string|bool - an id of an existent page
     */
    function getPageIdFromCanonical($canonical)
    {

        // Canonical
        $sqlite = Sqlite::getSqlite();
        $res = $sqlite->query("select * from pages where CANONICAL = ? ", $canonical);
        if (!$res) {
            LogUtility::msg("An exception has occurred with the pages selection query");
        }
        $res2arr = $sqlite->res2arr($res);
        $sqlite->res_close($res);
        foreach ($res2arr as $row) {
            $id = $row['ID'];
            if (page_exists($id)) {
                return $id;
            }
        }


        // If the function comes here, it means that the page id was not found in the pages table
        // Alias ?
        // Canonical
        $res = $sqlite->query("select p.ID from pages p, PAGES_ALIAS pa where p.CANONICAL = pa.CANONICAL and pa.ALIAS = ? ", $canonical);
        if (!$res) {
            throw new \RuntimeException("An exception has occurred with the alias selection query");
        }
        $res2arr = $sqlite->res2arr($res);
        $sqlite->res_close($res);
        foreach ($res2arr as $row) {
            $id = $row['ID'];
            if (page_exists($id)) {
                return $id;
            }
        }


        return false;

    }

    /**
     * Process metadata
     */
    function processCanonicalMeta()
    {


        global $ID;
        $canonical = p_get_metadata($ID, "canonical");
        if ($canonical != "") {

            // Do we have a page attached to this canonical
            $sqlite = Sqlite::getSqlite();
            $res = $sqlite->query("select ID from pages where CANONICAL = ?", $canonical);
            if (!$res) {
                LogUtility::msg("An exception has occurred with the search id from canonical");
            }
            $idInDb = $sqlite->res2single($res);
            $sqlite->res_close($res);
            if ($idInDb && $idInDb != $ID) {
                // If the page does not exist anymore we delete it
                if (!page_exists($idInDb)) {
                    $res = $sqlite->query("delete from pages where ID = ?", $idInDb);
                    if (!$res) {
                        LogUtility::msg("An exception has occurred during the deletion of the page");
                    }
                    $sqlite->res_close($res);

                } else {
                    LogUtility::msg("The page ($ID) and the page ($idInDb) have the same canonical ($canonical)", LogUtility::LVL_MSG_ERROR, "url:manager");
                }
                $this->persistPageAlias($canonical, $idInDb);
            }

            // Do we have a canonical on this page
            $res = $sqlite->query("select canonical from pages where ID = ?", $ID);
            if (!$res) {
                LogUtility::msg("An exception has occurred with the query");
            }
            $canonicalInDb = $sqlite->res2single($res);
            $sqlite->res_close($res);

            $row = array(
                "CANONICAL" => $canonical,
                "ID" => $ID
            );
            if ($canonicalInDb && $canonicalInDb != $canonical) {

                // Persist alias
                $this->persistPageAlias($canonical, $ID);

                // Update
                $statement = 'update pages set canonical = ? where id = ?';
                $res = $sqlite->query($statement, $row);
                if (!$res) {
                    LogUtility::msg("There was a problem during page update");
                }
                $sqlite->res_close($res);

            } else {

                if ($canonicalInDb == false) {
                    $res = $sqlite->storeEntry('pages', $row);
                    if (!$res) {
                        LogUtility::msg("There was a problem during pages insertion");
                    }
                    $sqlite->res_close($res);
                }

            }


        }

    }

    /**
     * @param $url - a URL path http://whatever/hello/my/lord (The canonical)
     * @return string - a dokuwiki Id hello:my:lord
     */
    static function toDokuWikiId($url)
    {
        // Replace / by : and suppress the first : because the global $ID does not have it
        $parsedQuery = parse_url($url,PHP_URL_QUERY);
        $parsedQueryArray = [];
        parse_str($parsedQuery,$parsedQueryArray);
        $queryId = 'id';
        if (array_key_exists($queryId,$parsedQueryArray)){
            // Doku form (ie doku.php?id=)
            return $parsedQueryArray[$queryId];
        } else {
            // Slash form ie (/my/id)
            $urlPath = parse_url($url, PHP_URL_PATH);
            return substr(str_replace("/", ":", $urlPath), 1);
        }
    }


}
