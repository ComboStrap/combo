<?php

namespace ComboStrap;


use action_plugin_combo_qualitymessage;
use dokuwiki\Cache\CacheInstructions;
use dokuwiki\Cache\CacheRenderer;
use RuntimeException;


/**
 * Page
 */
require_once(__DIR__ . '/PluginUtility.php');

class Page
{
    const CANONICAL_PROPERTY = 'canonical';
    const TITLE_PROPERTY = 'title';

    /**
     * An indicator in the meta
     * that set a boolean to true or false
     * to categorize a page as low quality
     * It can be set manually via the {@link \syntax_plugin_combo_frontmatter front matter}
     * otherwise the {@link \renderer_plugin_combo_analytics}
     * will do it
     */
    const LOW_QUALITY_PAGE_INDICATOR = 'low_quality_page';

    private $id;
    private $canonical;

    /**
     * @var string the absolute or resolved id
     */
    private $absoluteId;

    /**
     * Page constructor.
     * @param $id - the id of the page
     */
    public function __construct($id)
    {

        if (empty($id)){
            LogUtility::msg("A null page id was given");
        }

        $idLowerCase = strtolower($id);
        if ($idLowerCase !== $id) {
            LogUtility::msg("The page id ({$id}) is not equal in lowercase (ie equal to `{$idLowerCase}`)");
        }
        /**
         * characters are not all authorize
         * such as `_` at the end
         */
        $this->id = cleanID($id);
        if ($this->id !== $id) {
            LogUtility::msg("The page id ({$id}) is not conform and should be `{$this->id}`)");
        }
    }


    /**
     *
     * @param string|null $canonical - null or the canonical value
     * @return string - the canonical URL
     */
    public static function getUrl($canonical = null)
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

    public static function createFromEnvironment()
    {
        return new Page(PluginUtility::getPageId());
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
            throw new RuntimeException("An exception has occurred with the select pages query");
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
            throw new RuntimeException("An exception has occurred with the alias selection query");
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
        /**
         * See also {@link noNSorNS}
         */
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
     * @return array|null the analytics array or null if not in db
     */
    public function getAnalyticsFromDb()
    {
        $sqlite = Sqlite::getSqlite();
        if ($sqlite == null) {
            return array();
        }
        $res = $sqlite->query("select ANALYTICS from pages where ID = ? ", $this->id);
        if (!$res) {
            LogUtility::msg("An exception has occurred with the pages selection query");
        }
        $jsonString = trim($sqlite->res2single($res));
        $sqlite->res_close($res);
        if (!empty($jsonString)) {
            return json_decode($jsonString, true);
        } else {
            return null;
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
        if (key_exists('current', $metadata)) {
            $current = $metadata['current'];
            if (key_exists('relation', $current)) {
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


    public function isAnalyticsCached()
    {
        $file = wikiFN($this->id);
        $cache = new CacheRenderer($this->id, $file, Analytics::RENDERER_NAME_MODE);
        $cacheFile = $cache->cache;
        return file_exists($cacheFile);
    }

    /**
     *
     * @return string - the full path to the meta file
     */
    public function getMetaFile()
    {
        return metaFN($this->id, '.meta');
    }

    /**
     * @param $reason - a string with the reason
     */
    public function deleteCacheAndAskAnalyticsRefresh($reason)
    {
        $this->deleteCache(Analytics::RENDERER_NAME_MODE);
        $sqlite = Sqlite::getSqlite();
        if ($sqlite != null) {

            /**
             * Check if exists
             */
            $res = $sqlite->query("select count(1) from ANALYTICS_TO_REFRESH where ID = ?", array('ID' => $this->id));
            if (!$res) {
                LogUtility::msg("There was a problem during the insert: {$sqlite->getAdapter()->getDb()->errorInfo()}");
            }
            $result = $sqlite->res2single($res);
            $sqlite->res_close($res);

            /**
             * If not insert
             */
            if ($result != 1) {
                $entry = array(
                    "ID" => $this->id,
                    "TIMESTAMP" => date('Y-m-d H:i:s', time()),
                    "REASON" => $reason
                );
                $res = $sqlite->storeEntry('ANALYTICS_TO_REFRESH', $entry);
                if (!$res) {
                    LogUtility::msg("There was a problem during the insert: {$sqlite->getAdapter()->getDb()->errorInfo()}");
                }
                $sqlite->res_close($res);
            }

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
     * Delete the cache, process the analytics
     * and return it
     * If you want the analytics from the cache use {@link Page::getAnalyticsFromFs()}
     * instead
     * @return mixed analytics as array
     */
    public function processAnalytics()
    {

        /**
         * Refresh and cache
         * (The delete is normally not needed, just to be sure)
         */
        $this->deleteCache(Analytics::RENDERER_NAME_MODE);
        $analytics = Analytics::processAndGetDataAsArray($this->getId(), true);

        /**
         * Delete from the table
         */
        $sqlite = Sqlite::getSqlite();
        if ($sqlite != null) {
            $res = $sqlite->query("DELETE FROM ANALYTICS_TO_REFRESH where ID = ?", $this->getId());
            if (!$res) {
                LogUtility::msg("There was a problem during the delete: {$sqlite->getAdapter()->getDb()->errorInfo()}");
            }
            $sqlite->res_close($res);

        }
        return $analytics;

    }

    /**
     * @param bool $cache
     * @return mixed
     *
     */
    public function getAnalyticsFromFs($cache = true)
    {
        if ($cache) {
            /**
             * Note for dev: because cache is off in dev environment,
             * you will get it always processed
             */
            return Analytics::processAndGetDataAsArray($this->id, $cache);
        } else {
            /**
             * Process analytics delete at the same a asked refresh
             */
            return $this->processAnalytics();
        }
    }

    /**
     * Set the page quality
     * @param boolean $newIndicator true if this is a low quality page rank false otherwise
     */

    public function setLowQualityIndicator($newIndicator)
    {
        $actualIndicator = $this->getLowQualityIndicator();
        if ($actualIndicator === null || $actualIndicator !== $newIndicator) {

            /**
             * Don't change the type of the value to a string
             * otherwise dokuwiki will not see a change
             * between true and a string and will not persist the value
             */
            p_set_metadata($this->id, array(self::LOW_QUALITY_PAGE_INDICATOR => $newIndicator));

            /**
             * Delete the cache to rewrite the links
             * if the protection is on
             */
            if (PluginUtility::getConfValue(LowQualityPage::CONF_LOW_QUALITY_PAGE_PROTECTION_ENABLE) === 1) {
                foreach ($this->getBacklinks() as $backlink) {
                    $backlink->deleteCache("xhtml");
                }
            }

        }


    }

    /**
     * @return Page[] the backlinks
     */
    public function getBacklinks()
    {
        $backlinks = array();
        foreach (ft_backlinks($this->getAbsoluteId()) as $backlinkId) {
            $backlinks[] = new Page($backlinkId);
        }
        return $backlinks;
    }

    /**
     * @return int - An AUTH_ value for this page for the current logged user
     *
     */
    public function getAuthAclValue()
    {
        return auth_quickaclcheck($this->id);
    }

    /**
     * Low page quality
     * @return bool true if this is a low internal page rank
     */
    function isLowQualityPage()
    {

        $lowQualityIndicator = $this->getLowQualityIndicator();
        if ($lowQualityIndicator == null) {
            /**
             * By default, if a file has not been through
             * a {@link \renderer_plugin_combo_analytics}
             * analysis, this is not a low page
             */
            return false;
        } else {
            return $lowQualityIndicator === true;
        }

    }


    public function getLowQualityIndicator()
    {

        $low = p_get_metadata($this->id, self::LOW_QUALITY_PAGE_INDICATOR);
        if ($low === null) {
            return null;
        } else {
            return filter_var($low, FILTER_VALIDATE_BOOLEAN);
        }

    }

    /**
     * @return bool - if a {@link Page::processAnalytics()} for the page should occurs
     */
    public function shouldAnalyticsProcessOccurs()
    {
        /**
         * If cache is on
         */
        global $conf;
        if ($conf['cachetime'] !== -1) {
            /**
             * If there is no cache
             */
            if (!$this->isAnalyticsCached()) {
                return true;
            }
        }

        /**
         * Check Db
         */
        $sqlite = Sqlite::getSqlite();
        if ($sqlite != null) {

            $res = $sqlite->query("select count(1) from pages where ID = ? and ANALYTICS is null", $this->id);
            if (!$res) {
                LogUtility::msg("An exception has occurred with the analytics detection");
            }
            $count = intval($sqlite->res2single($res));
            $sqlite->res_close($res);
            if ($count >= 1) {
                return true;
            }
        }

        /**
         * Check the refresh table
         */
        if ($sqlite != null) {
            $res = $sqlite->query("SELECT count(*) FROM ANALYTICS_TO_REFRESH where ID = ?", $this->getId());
            if (!$res) {
                LogUtility::msg("There was a problem during the delete: {$sqlite->getAdapter()->getDb()->errorInfo()}");
            }
            $count = $sqlite->res2single($res);
            $sqlite->res_close($res);
            return $count >= 1;
        }

        return false;
    }

    public function __toString()
    {
        return $this->id; //. " ({$this->getH1()})";
    }

    public function getH1()
    {

        $heading = p_get_metadata(cleanID($this->id), Analytics::H1, METADATA_RENDER_USING_SIMPLE_CACHE);
        if (!blank($heading)) {
            return PluginUtility::escape($heading);
        } else {
            return null;
        }

    }

    /**
     * Return the Title
     */
    public function getTitle()
    {

        $title = p_get_metadata(cleanID($this->id), Analytics::TITLE, METADATA_RENDER_USING_SIMPLE_CACHE);
        if (!blank($title)) {
            return PluginUtility::escape($title);
        } else {
            return $this->id;
        }

    }

    /**
     * If true, the page is quality monitored (a note is shown to the writer)
     * @return bool|mixed
     */
    public function isQualityMonitored()
    {
        $dynamicQualityIndicator = p_get_metadata(cleanID($this->id), action_plugin_combo_qualitymessage::DISABLE_INDICATOR, METADATA_RENDER_USING_SIMPLE_CACHE);
        if ($dynamicQualityIndicator === null) {
            return true;
        } else {
            return filter_var($dynamicQualityIndicator, FILTER_VALIDATE_BOOLEAN);
        }
    }

    /**
     * @return string|null the title, or h1 if empty or the id if empty
     */
    public function getTitleNotEmpty()
    {
        $pageTitle = $this->getTitle();
        if ($pageTitle == null) {
            if (!empty($this->getH1())) {
                $pageTitle = $this->getH1();
            } else {
                $pageTitle = $this->getId();
            }
        }
        return $pageTitle;

    }

    public function getH1NotEmpty()
    {

        $h1Title = $this->getH1();
        if ($h1Title == null) {
            if (!empty($this->getTitle())) {
                $h1Title = $this->getTitle();
            } else {
                $h1Title = $this->getId();
            }
        }
        return $h1Title;

    }

    public function getDescription()
    {
        $descriptionMeta = p_get_metadata($this->getId(), "description");
        return $descriptionMeta['abstract'];
    }

    public function getFilePath()
    {
        return wikiFN($this->getId());
    }

    public function getContent()
    {
        return rawWiki($this->id);
    }

    /**
     * The index and  most of the function that are not links related
     * does not have the path to a page with the root element ie ':'
     *
     * This function makes sure that the id does not have ':' as root character
     *
     * See the $page argument of {@link resolve_pageid}
     *
     */
    public function getAbsoluteId()
    {
        if ($this->absoluteId ==null){
            $this->absoluteId = $this->id;
            resolve_pageid("", $this->absoluteId, $exists);
        }
        return $this->absoluteId;

    }

    public function isInIndex()
    {
        $Indexer = idx_get_indexer();
        $pages = $Indexer->getPages();
        $return = array_search($this->getAbsoluteId(), $pages, true);
        return $return !== false;
    }

    /**
     * @return string the id with `:`
     */
    public function getLinkId()
    {
        return ":".$this->id;
    }


}
