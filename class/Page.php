<?php

namespace ComboStrap;


use dokuwiki\Cache\CacheInstructions;
use dokuwiki\Cache\CacheRenderer;
use http\Exception\RuntimeException;

/**
 * Class urlCanonical with all canonical methodology
 */
require_once(__DIR__ . '/PluginUtility.php');

class Page
{
    const CANONICAL_PROPERTY = 'canonical';
    const TITLE_PROPERTY = 'title';
    private $id;
    private $canonical;

    /**
     * Page constructor.
     * @param $id - the id of the page
     */
    public function __construct($id)
    {
        $this->id = $id;
        if (strtolower($id) !== $id) {
            throw new \RuntimeException("The page id ({$id}) is not in lowercase");
        }
    }


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
        return $canonicalUrl;
    }


    /**
     * Does the page is known in the pages table
     * @return array
     */
    function getRow()
    {

        $sqlite = Sqlite::getSqlite();
        $res = $sqlite->query("SELECT * FROM pages where id = ?", $this->id);
        if (!$res) {
            throw new \RuntimeException("An exception has occurred with the select pages query");
        }
        $res2arr = $sqlite->res2row($res);
        $sqlite->res_close($res);
        return $res2arr;


    }

    /**
     * Delete Page
     */
    function deleteInDb()
    {

        $res = Sqlite::getSqlite()->query('delete from pages where id = ?', $this->id);
        if (!$res) {
            LogUtility::msg("Something went wrong when deleting a page");
        }

    }

    /**
     * Does the page is known in the pages table
     * @return int
     */
    function existInDb()
    {
        $sqlite = Sqlite::getSqlite();
        $res = $sqlite->query("SELECT count(*) FROM pages where id = ?", $this->id);
        $count = $sqlite->res2single($res);
        $sqlite->res_close($res);
        return $count;

    }

    /**
     * Exist in FS
     * @return bool
     */
    function existInFs()
    {
        return page_exists($this->id);
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
            throw new \RuntimeException("An exception has occurred with the alia selection query");
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

    static function createFromId($id)
    {
        return new Page($id);
    }

    /**
     * @param $canonical
     * @return Page - an id of an existing page
     */
    static function createFromCanonical($canonical)
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
            return self::createFromId($id)->setCanonical($canonical);
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

            return self::createFromId($id)
                ->setCanonical($canonical);
        }

        return self::createFromId($canonical);

    }

    /**
     * Persist a page in the database
     */
    function processAndPersistInDb()
    {

        $canonical = p_get_metadata($this->id, "canonical");
        if ($canonical != "") {

            // Do we have a page attached to this canonical
            $sqlite = Sqlite::getSqlite();
            $res = $sqlite->query("select ID from pages where CANONICAL = ?", $canonical);
            if (!$res) {
                LogUtility::msg("An exception has occurred with the search id from canonical");
            }
            $idInDb = $sqlite->res2single($res);
            $sqlite->res_close($res);
            if ($idInDb && $idInDb != $this->id) {
                // If the page does not exist anymore we delete it
                if (!page_exists($idInDb)) {
                    $res = $sqlite->query("delete from pages where ID = ?", $idInDb);
                    if (!$res) {
                        LogUtility::msg("An exception has occurred during the deletion of the page");
                    }
                    $sqlite->res_close($res);

                } else {
                    LogUtility::msg("The page ($$this->id) and the page ($idInDb) have the same canonical ($canonical)", LogUtility::LVL_MSG_ERROR, "url:manager");
                }
                $this->persistPageAlias($canonical, $idInDb);
            }

            // Do we have a canonical on this page
            $res = $sqlite->query("select canonical from pages where ID = ?", $this->id);
            if (!$res) {
                LogUtility::msg("An exception has occurred with the query");
            }
            $canonicalInDb = $sqlite->res2single($res);
            $sqlite->res_close($res);

            $row = array(
                "CANONICAL" => $canonical,
                "ID" => $this->id
            );
            if ($canonicalInDb && $canonicalInDb != $canonical) {

                // Persist alias
                $this->persistPageAlias($canonical, $this->id);

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
    static function createFromUrl($url)
    {
        // Replace / by : and suppress the first : because the global $ID does not have it
        $parsedQuery = parse_url($url, PHP_URL_QUERY);
        $parsedQueryArray = [];
        parse_str($parsedQuery, $parsedQueryArray);
        $queryId = 'id';
        if (array_key_exists($queryId, $parsedQueryArray)) {
            // Doku form (ie doku.php?id=)
            $id = $parsedQueryArray[$queryId];
        } else {
            // Slash form ie (/my/id)
            $urlPath = parse_url($url, PHP_URL_PATH);
            $id = substr(str_replace("/", ":", $urlPath), 1);
        }
        return self::createFromId($id);
    }

    private function setCanonical($canonical)
    {
        $this->canonical = $canonical;
        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function isBar()
    {
        global $conf;
        $barsName = array($conf['sidebar']);
        $strapTemplateName = 'strap';
        if ($conf['template'] === $strapTemplateName) {
            $barsName[] = $conf['tpl'][$strapTemplateName]['headerbar'];
            $barsName[] = $conf['tpl'][$strapTemplateName]['footerbar'];
            $barsName[] = $conf['tpl'][$strapTemplateName]['sidekickbar'];
        }
        return in_array($this->getName(), $barsName);
    }

    private function getName()
    {
        $names = $this->getNames();
        return $names[sizeOf($names) - 1];
    }

    public function getNames()
    {
        return preg_split("/:/", $this->id);
    }

    public function isStartPage()
    {
        global $conf;
        return $this->getName() == $conf['start'];
    }

    /**
     * Return a canonical if set
     * otherwise derive it from the id
     * by taking the last two parts
     *
     * @return string
     */
    public function getCanonical()
    {
        if (!empty($this->canonical)) {
            return $this->canonical;
        } else {
            $names = $this->getNames();
            $namesLength = sizeof($names);
            if ($namesLength == 1) {

                return $this->id;

            } else {

                return join(":", array_slice($names, $namesLength - 2));

            }
        }
    }

    /**
     * @return mixed
     */
    public function getAnalyticsFromDb()
    {
        $sqlite = Sqlite::getSqlite();
        if ($sqlite==null){
            return array();
        }
        $res = $sqlite->query("select ANALYTICS from pages where ID = ? ", $this->id);
        if (!$res) {
            LogUtility::msg("An exception has occurred with the pages selection query");
        }
        $jsonString = $sqlite->res2single($res);
        $sqlite->res_close($res);
        if (!empty($jsonString)){
            return json_decode($jsonString,true);
        } else {
            return array();
        }

    }

    /**
     * Return the metadata stored in the file system
     * @return array|array[]
     */
    public function getMetadata()
    {
        /**
         * Read / not get (get can trigger a rendering of the meta again)
         */
        return p_read_metadata($this->id);
    }

    /**
     *
     * @return mixed the internal links or null
     */
    public function getInternalLinksFromMeta()
    {
        $metadata = $this->getMetadata();
        if (key_exists('current',$metadata)){
            $current = $metadata['current'];
            if (key_exists('relation',$current)){
                $relation = $current['relation'];
                if (is_array($relation)) {
                    if (key_exists('references', $relation)) {
                        return $relation['references'];
                    }
                }
            }
        }
        return null;
    }

    public function saveAnalytics(array $analytics)
    {

        $sqlite = Sqlite::getSqlite();
        if ($sqlite != null) {
            /**
             * Sqlite Plugin installed
             */

            $json = json_encode($analytics, JSON_PRETTY_PRINT);
            $entry = array(
                'CANONICAL' => $this->getCanonical(),
                'ANALYTICS' => $json,
                'ID' => $this->getId()
            );
            $res = $sqlite->query("SELECT count(*) FROM PAGES where ID = ?", $this->getId());
            if ($sqlite->res2single($res) == 1) {
                // Upset not supported on all version
                //$upsert = 'insert into PAGES (ID,CANONICAL,ANALYTICS) values (?,?,?) on conflict (ID,CANONICAL) do update set ANALYTICS = EXCLUDED.ANALYTICS';
                $update = 'update PAGES SET CANONICAL = ?, ANALYTICS = ? where ID=?';
                $res = $sqlite->query($update, $entry);
            } else {
                $res = $sqlite->storeEntry('PAGES', $entry);
            }
            if (!$res) {
                LogUtility::msg("There was a problem during the upsert: {$sqlite->getAdapter()->getDb()->errorInfo()}");
            }
            $sqlite->res_close($res);
        }

    }

    public function deleteCache($mode = "xhtml")
    {
        if ($this->existInFs()) {

            $file = wikiFN($this->id);

            /**
             * Output of {@link DokuWiki_Syntax_Plugin::handle}
             */
            $cache = new CacheInstructions($this->id, $file);
            $cache->removeCache();

            /**
             * Output of {@link DokuWiki_Syntax_Plugin::render()}
             */
            $cache = new CacheRenderer($this->id, $file, $mode);
            $cache->removeCache();

        }
    }

    public function deleteAnalyticsCache()
    {
        $this->deleteCache(Analytics::RENDERER_NAME_MODE);

    }

    public function isAnalyticsCached()
    {
        $file = wikiFN($this->id);
        $cache = new CacheRenderer($this->id, $file, Analytics::RENDERER_NAME_MODE);
        return file_exists($cache->cache);
    }

    /**
     *
     * @return string - the full path to the meta file
     */
    public function getMetaFile(){
        return metaFN($this->id, '.meta');
    }

    public function askAnalyticsRefresh()
    {
        $this->deleteAnalyticsCache();
        $sqlite = Sqlite::getSqlite();
        if ($sqlite!=null) {
            $entry = array(
                "ID" => $this->id,
                "TIMESTAMP" => date('Y-m-d H:i:s', time())
            );
            $res = $sqlite->storeEntry('ANALYTICS_TO_REFRESH', $entry);
            if (!$res) {
                LogUtility::msg("There was a problem during the insert: {$sqlite->getAdapter()->getDb()->errorInfo()}");
            }
            $sqlite->res_close($res);
        }

    }

    public function isAnalyticsStale()
    {
        $sqlite = Sqlite::getSqlite();
        $res = $sqlite->query("SELECT count(*) FROM ANALYTICS_TO_REFRESH where ID = ?", $this->getId());
        if (!$res) {
            LogUtility::msg("There was a problem during the select: {$sqlite->getAdapter()->getDb()->errorInfo()}");
        }
        $value = $sqlite->res2single($res);
        $sqlite->res_close($res);
        return $value === "1";

    }

    /**
     * @return mixed analytics as array
     */
    public function refreshAnalytics()
    {

        /**
         * Refresh and cache
         * (The delete is normally not needed, just to be sure)
         */
        $this->deleteAnalyticsCache();
        $analytics = Analytics::processAndGetDataAsArray($this->getId());

        /**
         * Delete from the table
         */
        $sqlite = Sqlite::getSqlite();
        if ($sqlite!=null) {
            $res = $sqlite->query("DELETE FROM ANALYTICS_TO_REFRESH where ID = ?", $this->getId());
            if (!$res) {
                LogUtility::msg("There was a problem during the delete: {$sqlite->getAdapter()->getDb()->errorInfo()}");
            }
            $sqlite->res_close($res);

        }
        return $analytics;

    }


}
