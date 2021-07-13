<?php

namespace ComboStrap;


use action_plugin_combo_qualitymessage;
use dokuwiki\Cache\CacheInstructions;
use dokuwiki\Cache\CacheRenderer;
use renderer_plugin_combo_analytics;
use RuntimeException;


/**
 * Page
 */
require_once(__DIR__ . '/DokuPath.php');

/**
 *
 * Class Page
 * @package ComboStrap
 *
 * This is just a wrapper around a file with the mime Dokuwiki
 * that has a doku path (ie with the `:` separator)
 */
class Page extends DokuPath
{
    const CANONICAL_PROPERTY = 'canonical';
    const TITLE_PROPERTY = 'title';

    const CONF_DISABLE_FIRST_IMAGE_AS_PAGE_IMAGE = "disableFirstImageAsPageImage";

    /**
     * An indicator in the meta
     * that set a boolean to true or false
     * to categorize a page as low quality
     * It can be set manually via the {@link \syntax_plugin_combo_frontmatter front matter}
     * otherwise the {@link \renderer_plugin_combo_analytics}
     * will do it
     */
    const LOW_QUALITY_PAGE_INDICATOR = 'low_quality_page';

    /**
     * The default page type
     */
    const CONF_DEFAULT_PAGE_TYPE = "defaultPageType";
    const WEBSITE_TYPE = "website";
    const ARTICLE_TYPE = "article";
    const ORGANIZATION_TYPE = "organization";
    const NEWS_TYPE = "news";
    const BLOG_TYPE = "blog";
    const DESCRIPTION_PROPERTY = "description";
    const TYPE_PROPERTY = "type";

    /**
     * The scope of a side slot page
     * ie
     *   * a namespace path
     *   * or current, for the namespace of the current requested page
     * If the scope is:
     *   * current, the cache will create a logical page for each namespace
     *   * a namespace path, the cache will be only on this path
     */
    const SCOPE_VALUE_CURRENT = "current";
    const SCOPE_KEY = "scope";


    private $canonical;


    /**
     * @var array|array[]
     */
    private $metadatas;
    /**
     * @var string|null - the description (the origin is in the $descriptionOrigin)
     */
    private $description;
    /**
     * @var string - the dokuwiki
     */
    private $descriptionOrigin;


    /**
     * @var bool Indicator to say if this is a sidebar (or sidekick bar)
     */
    private $isSideSlot = false;

    /**
     * The id requested (ie the main page)
     * The page may be a slot
     * @var string
     */
    private $requestedId;

    /**
     * Page constructor.
     * @param $path - the path id of a page (it may be relative to the requested page)
     *
     */
    public function __construct($path)
    {

        /**
         * Bars have a logical reasoning (ie such as a virtual, alias)
         * They are logically located in the same namespace
         * but the file may be located on the parent
         *
         * This block of code is processing this case
         */
        global $conf;
        $sidebars = array($conf['sidebar']);
        $strapTemplateName = 'strap';
        if ($conf['template'] === $strapTemplateName) {
            $sidebars[] = $conf['tpl'][$strapTemplateName]['sidekickbar'];
        }
        $lastPathPart = DokuPath::getLastPart($path);
        if (in_array($lastPathPart, $sidebars)) {

            $this->isSideSlot = true;

            /**
             * Find the first physical file
             * Don't use ACL otherwise the ACL protection event 'AUTH_ACL_CHECK' will kick in
             * and we got then a recursive problem
             * with the {@link \action_plugin_combo_pageprotection}
             */
            $useAcl = false;
            $id = page_findnearest($path, $useAcl);
            if ($id !== false) {
                $path = DokuPath::SEPARATOR . $id;
            }

        }

        global $ID;
        $this->requestedId = $ID;

        parent::__construct($path, DokuPath::PAGE_TYPE);

    }

    /**
     * @var string the logical id is used with slots.
     *
     * A slot may exist in several node of the file system tree
     * but they can be rendered for a page in a lowest level
     * listing the page of the current namespace
     *
     * The slot is physically stored in one place but is equivalent
     * physically to the same slot in all sub-node.
     *
     * This logical id does take into account this aspect.
     *
     * This is used also to store the HTML output in the cache
     * If this is not a slot the logical id is the {@link DokuPath::getId()}
     */
    public function getLogicalId()
    {
        /**
         * Delete the first separator
         */
        return substr($this->getLogicalPath(), 1);
    }

    public function getLogicalPath()
    {

        /**
         * Set the logical id
         * When no $ID is set (for instance, test),
         * the logical id is the id
         *
         * The logical id depends on the namespace attribute of the {@link \syntax_plugin_combo_pageexplorer}
         * stored in the `scope` metadata.
         */
        $scopePath = $this->getMetadata(self::SCOPE_KEY);
        if ($scopePath == null) {

            return $this->getPath();

        } else {
            if ($scopePath == self::SCOPE_VALUE_CURRENT) {

                /**
                 * The logical id is the slot name
                 * inside the current (ie actual namespace)
                 */
                $actualNamespace = getNS($this->requestedId);
                $logicalId = $this->getName();
                resolve_pageid($actualNamespace, $logicalId, $exists);
                return DokuPath::SEPARATOR . $logicalId;

            } else {
                /**
                 * The logical id is fixed
                 * Logically, it should be the same than the {@link Page::getId() id}
                 */
                return $scopePath . DokuPath::SEPARATOR . $this->getName();
            }
        }

    }


    /**
     *
     *
     * Dokuwiki Methodology taken from {@link tpl_metaheaders()}
     * @return string - the Dokuwiki URL
     */
    public
    function getUrl()
    {
        if ($this->isHomePage()) {
            $url = DOKU_URL;
        } else {
            $url = wl($this->getId(), '', true, '&');
        }
        return $url;
    }

    public
    static function createPageFromEnvironment()
    {
        $path = PluginUtility::getPageId();
        if ($path != null) {
            return new Page($path);
        } else {
            LogUtility::msg("We were unable to determine the page from the variables environment", LogUtility::LVL_MSG_ERROR);
            return null;
        }
    }


    /**
     * Does the page is known in the pages table
     * @return array
     */
    function getRow()
    {

        $sqlite = Sqlite::getSqlite();
        $res = $sqlite->query("SELECT * FROM pages where id = ?", $this->getId());
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

        $res = Sqlite::getSqlite()->query('delete from pages where id = ?', $this->getId());
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
        $res = $sqlite->query("SELECT count(*) FROM pages where id = ?", $this->getId());
        $count = $sqlite->res2single($res);
        $sqlite->res_close($res);
        return $count;

    }

    /**
     * Exist in FS
     * @return bool
     * @deprecated use {@link DokuPath::exists()} instead
     */
    function existInFs()
    {
        return $this->exists();
    }

    private
    function persistPageAlias($canonical, $alias)
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

    static function createPageFromPath($pathId)
    {
        return new Page($pathId);
    }

    static function createPageFromId($id)
    {
        return new Page(DokuPath::IdToAbsolutePath($id));
    }

    /**
     * @param $canonical
     * @return Page - an id of an existing page
     */
    static function createPageFromCanonical($canonical)
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
            return self::createPageFromPath($id)->setCanonical($canonical);
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

            return self::createPageFromPath($id)
                ->setCanonical($canonical);
        }

        return self::createPageFromPath($canonical);

    }

    /**
     * Persist a page in the database
     */
    function processAndPersistInDb()
    {

        $canonical = p_get_metadata($this->getId(), "canonical");
        if ($canonical != "") {

            // Do we have a page attached to this canonical
            $sqlite = Sqlite::getSqlite();
            $res = $sqlite->query("select ID from pages where CANONICAL = ?", $canonical);
            if (!$res) {
                LogUtility::msg("An exception has occurred with the search id from canonical");
            }
            $idInDb = $sqlite->res2single($res);
            $sqlite->res_close($res);
            if ($idInDb && $idInDb != $this->getId()) {
                // If the page does not exist anymore we delete it
                if (!page_exists($idInDb)) {
                    $res = $sqlite->query("delete from pages where ID = ?", $idInDb);
                    if (!$res) {
                        LogUtility::msg("An exception has occurred during the deletion of the page");
                    }
                    $sqlite->res_close($res);

                } else {
                    LogUtility::msg("The page ($this) and the page ($idInDb) have the same canonical ($canonical)", LogUtility::LVL_MSG_ERROR, "url:manager");
                }
                $this->persistPageAlias($canonical, $idInDb);
            }

            // Do we have a canonical on this page
            $res = $sqlite->query("select canonical from pages where ID = ?", $this->getId());
            if (!$res) {
                LogUtility::msg("An exception has occurred with the query");
            }
            $canonicalInDb = $sqlite->res2single($res);
            $sqlite->res_close($res);

            $row = array(
                "CANONICAL" => $canonical,
                "ID" => $this->getId()
            );
            if ($canonicalInDb && $canonicalInDb != $canonical) {

                // Persist alias
                $this->persistPageAlias($canonical, $this->getId());

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
        return $this;
    }

    private
    function setCanonical($canonical)
    {
        $this->canonical = $canonical;
        return $this;
    }


    public
    function isSlot()
    {
        global $conf;
        $barsName = array($conf['sidebar']);
        $strapTemplateName = 'strap';
        if ($conf['template'] === $strapTemplateName) {
            $loaded = PluginUtility::loadStrapUtilityTemplateIfPresentAndSameVersion();
            if ($loaded) {
                $barsName[] = TplUtility::getHeaderSlotPageName();
                $barsName[] = TplUtility::getFooterSlotPageName();
                $barsName[] = TplUtility::getSideKickSlotPageName();
            }
        }
        return in_array($this->getName(), $barsName);
    }

    public
    function isStrapSideSlot()
    {

        return $this->isSideSlot && Site::isStrapTemplate();

    }


    public
    function isStartPage()
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
    public
    function getCanonical()
    {
        if (empty($this->canonical)) {

            $this->canonical = $this->getPersistentMetadata(Page::CANONICAL_PROPERTY);

            /**
             * The last part of the id as canonical
             */
            // How many last parts are taken into account in the canonical processing (2 by default)
            $canonicalLastNamesCount = PluginUtility::getConfValue(\action_plugin_combo_metacanonical::CANONICAL_LAST_NAMES_COUNT_CONF);
            if (empty($this->canonical) && $canonicalLastNamesCount > 0) {
                /**
                 * Takes the last names part
                 */
                $names = $this->getNames();
                $namesLength = sizeof($names);
                if ($namesLength > $canonicalLastNamesCount) {
                    $names = array_slice($names, $namesLength - $canonicalLastNamesCount);
                }
                /**
                 * If this is a start page, delete the name
                 * ie javascript:start will become javascript
                 */
                if ($this->isStartPage()) {
                    $names = array_slice($names, 0, $namesLength - 1);
                }
                $this->canonical = implode(":", $names);
                p_set_metadata($this->getId(), array(Page::CANONICAL_PROPERTY => $this->canonical));
            }

        }
        return $this->canonical;
    }

    /**
     * @return array|null the analytics array or null if not in db
     */
    public
    function getAnalyticsFromDb()
    {
        $sqlite = Sqlite::getSqlite();
        if ($sqlite == null) {
            return array();
        }
        $res = $sqlite->query("select ANALYTICS from pages where ID = ? ", $this->getId());
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
    public
    function getMetadatas()
    {

        /**
         * Read / not {@link p_get_metadata()}
         * because it can trigger a rendering of the meta again)
         *
         * This is not a {@link Page::renderMetadata()}
         */
        if ($this->metadatas == null) {
            $this->metadatas = p_read_metadata($this->getId());
        }
        return $this->metadatas;

    }

    /**
     *
     * @return mixed the internal links or null
     */
    public
    function getInternalLinksFromMeta()
    {
        $metadata = $this->getMetadatas();
        if (key_exists(self::SCOPE_VALUE_CURRENT, $metadata)) {
            $current = $metadata[self::SCOPE_VALUE_CURRENT];
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

    public
    function saveAnalytics(array $analytics)
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

    /**
     * @param string $mode delete the cache for the format XHTML and {@link renderer_plugin_combo_analytics::RENDERER_NAME_MODE}
     */
    public
    function deleteCache($mode = "xhtml")
    {

        if ($this->exists()) {


            $cache = $this->getInstructionsCache();
            $cache->removeCache();

            $cache = $this->getRenderCache($mode);
            $cache->removeCache();

        }
    }


    public
    function isAnalyticsCached()
    {

        $cache = new CacheRenderer($this->getId(), $this->getFileSystemPath(), renderer_plugin_combo_analytics::RENDERER_NAME_MODE);
        $cacheFile = $cache->cache;
        return file_exists($cacheFile);
    }

    /**
     *
     * @return string - the full path to the meta file
     */
    public
    function getMetaFile()
    {
        return metaFN($this->getId(), '.meta');
    }

    /**
     * @param $reason - a string with the reason
     */
    public
    function deleteCacheAndAskAnalyticsRefresh($reason)
    {
        $this->deleteCache(renderer_plugin_combo_analytics::RENDERER_NAME_MODE);
        $sqlite = Sqlite::getSqlite();
        if ($sqlite != null) {

            /**
             * Check if exists
             */
            $res = $sqlite->query("select count(1) from ANALYTICS_TO_REFRESH where ID = ?", array('ID' => $this->getId()));
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
                    "ID" => $this->getId(),
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

    public
    function isAnalyticsStale()
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
    public
    function processAnalytics()
    {

        /**
         * Refresh and cache
         * (The delete is normally not needed, just to be sure)
         */
        $this->deleteCache(renderer_plugin_combo_analytics::RENDERER_NAME_MODE);
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
    public
    function getAnalyticsFromFs($cache = true)
    {
        if ($cache) {
            /**
             * Note for dev: because cache is off in dev environment,
             * you will get it always processed
             */
            return Analytics::processAndGetDataAsArray($this->getId(), $cache);
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

    public
    function setLowQualityIndicator($newIndicator)
    {
        $actualIndicator = $this->getLowQualityIndicator();
        if ($actualIndicator === null || $actualIndicator !== $newIndicator) {

            /**
             * Don't change the type of the value to a string
             * otherwise dokuwiki will not see a change
             * between true and a string and will not persist the value
             */
            p_set_metadata($this->getId(), array(self::LOW_QUALITY_PAGE_INDICATOR => $newIndicator));

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
    public
    function getBacklinks()
    {
        $backlinks = array();
        foreach (ft_backlinks($this->getId()) as $backlinkId) {
            $backlinks[] = new Page($backlinkId);
        }
        return $backlinks;
    }

    /**
     * @return int - An AUTH_ value for this page for the current logged user
     *
     */
    public
    function getAuthAclValue()
    {
        return auth_quickaclcheck($this->getId());
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


    public
    function getLowQualityIndicator()
    {

        $low = p_get_metadata($this->getId(), self::LOW_QUALITY_PAGE_INDICATOR);
        if ($low === null) {
            return null;
        } else {
            return filter_var($low, FILTER_VALIDATE_BOOLEAN);
        }

    }

    /**
     * @return bool - if a {@link Page::processAnalytics()} for the page should occurs
     */
    public
    function shouldAnalyticsProcessOccurs()
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

            $res = $sqlite->query("select count(1) from pages where ID = ? and ANALYTICS is null", $this->getId());
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


    public
    function getH1()
    {

        $heading = p_get_metadata($this->getId(), Analytics::H1, METADATA_RENDER_USING_SIMPLE_CACHE);
        if (!blank($heading)) {
            return PluginUtility::htmlEncode($heading);
        } else {
            return null;
        }

    }

    /**
     * Return the Title
     */
    public
    function getTitle()
    {

        $id = $this->getId();
        $title = p_get_metadata($id, Analytics::TITLE, METADATA_RENDER_USING_SIMPLE_CACHE);
        if (!blank($title)) {
            return PluginUtility::htmlEncode($title);
        } else {
            return $id;
        }

    }

    /**
     * If true, the page is quality monitored (a note is shown to the writer)
     * @return bool|mixed
     */
    public
    function isQualityMonitored()
    {
        $dynamicQualityIndicator = p_get_metadata($this->getId(), action_plugin_combo_qualitymessage::DISABLE_INDICATOR, METADATA_RENDER_USING_SIMPLE_CACHE);
        if ($dynamicQualityIndicator === null) {
            return true;
        } else {
            return filter_var($dynamicQualityIndicator, FILTER_VALIDATE_BOOLEAN);
        }
    }

    /**
     * @return string|null the title, or h1 if empty or the id if empty
     */
    public
    function getTitleNotEmpty()
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

    public
    function getH1NotEmpty()
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

    public
    function getDescription()
    {

        $this->processDescriptionIfNeeded();
        if ($this->descriptionOrigin == \syntax_plugin_combo_frontmatter::CANONICAL) {
            return $this->description;
        } else {
            return null;
        }


    }


    /**
     * @return string - the description or the dokuwiki generated description
     */
    public
    function getDescriptionOrElseDokuWiki()
    {
        $this->processDescriptionIfNeeded();
        return $this->description;
    }


    public
    function getContent()
    {
        /**
         * use {@link io_readWikiPage(wikiFN($id, $rev), $id, $rev)};
         */
        return rawWiki($this->getId());
    }


    public
    function isInIndex()
    {
        $Indexer = idx_get_indexer();
        $pages = $Indexer->getPages();
        $return = array_search($this->getId(), $pages, true);
        return $return !== false;
    }


    public
    function upsertContent($content, $summary = "Default")
    {
        saveWikiText($this->getId(), $content, $summary);
        return $this;
    }

    public
    function addToIndex()
    {
        idx_addPage($this->getId());
    }

    public
    function getType()
    {
        $type = $this->getPersistentMetadata("type");
        if (isset($type)) {
            return $type;
        } else {
            if ($this->isHomePage()) {
                return self::WEBSITE_TYPE;
            } else {
                $defaultPageTypeConf = PluginUtility::getConfValue(self::CONF_DEFAULT_PAGE_TYPE);
                if (!empty($defaultPageTypeConf)) {
                    return $defaultPageTypeConf;
                } else {
                    return null;
                }
            }
        }
    }


    public
    function getFirstImage()
    {

        $relation = $this->getCurrentMetadata('relation');
        if (isset($relation['firstimage'])) {
            $firstImageId = $relation['firstimage'];
            if (empty($firstImageId)) {
                return null;
            } else {
                // The  metadata store the Id or the url
                // We transform them to a path id
                $pathId = $firstImageId;
                if (!media_isexternal($firstImageId)) {
                    $pathId = DokuPath::SEPARATOR . $firstImageId;
                }
                return MediaLink::createMediaLinkFromPathId($pathId);
            }
        }
        return null;

    }

    /**
     * An array of local images that represents the same image
     * but in different dimension and ratio
     * (may be empty)
     * @return MediaLink[]
     */
    public
    function getLocalImageSet()
    {

        /**
         * Google accepts several images dimension and ratios
         * for the same image
         * We may get an array then
         */
        $imageMeta = $this->getMetadata('image');
        $images = array();
        if (!empty($imageMeta)) {
            if (is_array($imageMeta)) {
                foreach ($imageMeta as $imageIdFromMeta) {
                    $images[] = MediaLink::createMediaLinkFromPathId($imageIdFromMeta);
                }
            } else {
                $images = array(MediaLink::createMediaLinkFromPathId($imageMeta));
            }
        } else {
            if (!PluginUtility::getConfValue(self::CONF_DISABLE_FIRST_IMAGE_AS_PAGE_IMAGE)) {
                $firstImage = $this->getFirstImage();
                if ($firstImage != null) {
                    if ($firstImage->getScheme() == DokuPath::LOCAL_SCHEME) {
                        $images = array($firstImage);
                    }
                }
            }
        }
        return $images;

    }


    /**
     * @return MediaLink
     */
    public
    function getImage()
    {

        $images = $this->getLocalImageSet();
        if (sizeof($images) >= 1) {
            return $images[0];
        } else {
            return null;
        }

    }

    /**
     * Get author name
     *
     * @return string
     */
    public
    function getAuthor()
    {
        $author = $this->getPersistentMetadata('creator');
        return ($author ? $author : null);
    }

    /**
     * Get author ID
     *
     * @return string
     */
    public
    function getAuthorID()
    {
        $user = $this->getPersistentMetadata('user');
        return ($user ? $user : null);
    }


    private
    function getPersistentMetadata($key)
    {
        if (isset($this->getMetadatas()['persistent'][$key])) {
            return $this->getMetadatas()['persistent'][$key];
        } else {
            return null;
        }
    }

    public
    function getPersistentMetadatas()
    {
        return $this->getMetadatas()['persistent'];
    }

    /**
     * The modified date is the last modficaction date
     * the first time, this is the creation date
     * @return false|string|null
     */
    public
    function getModifiedDateString()
    {
        $modified = $this->getModifiedTimestamp();
        if (!empty($modified)) {
            return date(DATE_W3C, $modified);
        } else {
            return null;
        }
    }

    private
    function getCurrentMetadata($key)
    {
        $key = $this->getMetadatas()[self::SCOPE_VALUE_CURRENT][$key];
        return ($key ? $key : null);
    }

    /**
     * Get the create date of page
     *
     * @return int
     */
    public
    function getCreatedTimestamp()
    {
        $created = $this->getPersistentMetadata('date')['created'];
        return ($created ? $created : null);;
    }

    /**
     * Get the modified date of page
     *
     * The modified date is the last modification date
     * the first time, this is the creation date
     *
     * @return int
     */
    public
    function getModifiedTimestamp()
    {
        $modified = $this->getCurrentMetadata('date')['modified'];
        return ($modified ? $modified : null);
    }

    /**
     * Creation date can not be null
     * @return false|string
     */
    public
    function getCreatedDateString()
    {

        $created = $this->getCreatedTimestamp();
        if (!empty($created)) {
            return date(DATE_W3C, $created);
        } else {
            // Not created
            return null;
        }

    }

    /**
     * Refresh the metadata (used only in test)
     */
    public
    function renderMetadata()
    {

        if ($this->metadatas == null) {
            /**
             * Read the metadata from the file
             */
            $this->metadatas = $this->getMetadatas();
        }

        /**
         * Read/render the metadata from the file
         * with parsing
         */
        $this->metadatas = p_render_metadata($this->getId(), $this->metadatas);

        /**
         * ReInitialize
         */
        $this->descriptionOrigin = null;
        $this->description = null;

        /**
         * Return
         */
        return $this;

    }

    public
    function getCountry()
    {

        $country = $this->getPersistentMetadata("country");
        if (!empty($country)) {
            if (!StringUtility::match($country, "[a-zA-Z]{2}")) {
                LogUtility::msg("The country value ($country) for the page (" . $this->getId() . ") does not have two letters (ISO 3166 alpha-2 country code)", LogUtility::LVL_MSG_ERROR, "country");
            }
            return $country;
        } else {

            return Site::getCountry();

        }

    }

    public
    function getLang()
    {
        $lang = $this->getPersistentMetadata("lang");
        if (empty($lang)) {
            global $conf;
            if (isset($conf["lang"])) {
                $lang = $conf["lang"];
            }
        }
        return $lang;
    }

    /**
     * Adapted from {@link FsWikiUtility::getHomePagePath()}
     * @return bool
     */
    public
    function isHomePage()
    {
        global $conf;
        $startPageName = $conf['start'];
        if ($this->getName() == $startPageName) {
            return true;
        } else {
            $namespaceName = noNS(cleanID($this->getNamespacePath()));
            if ($namespaceName == $this->getName()) {
                /**
                 * page named like the NS inside the NS
                 * ie ns:ns
                 */
                $startPage = Page::createPageFromPath($this->getNamespacePath() . ":" . $startPageName);
                if (!$startPage->exists()) {
                    return true;
                }
            }
        }
        return false;
    }


    public
    function getMetadata($key)
    {
        $persistentMetadata = $this->getPersistentMetadata($key);
        if (empty($persistentMetadata)) {
            $persistentMetadata = $this->getCurrentMetadata($key);
        }
        return $persistentMetadata;
    }

    public
    function getPublishedTimestamp()
    {
        $persistentMetadata = $this->getPersistentMetadata(Publication::META_KEY_PUBLISHED);
        if (!empty($persistentMetadata)) {
            $timestamp = strtotime($persistentMetadata);
            if ($timestamp === false) {
                LogUtility::msg("The published date ($persistentMetadata) of the page ($this) is not a valid ISO date.", LogUtility::LVL_MSG_ERROR, "published");
            } else {
                return date("U", $timestamp);
            }
        } else {
            return null;
        }

    }

    /**
     * @return false|int|string|null
     */
    public
    function getPublishedElseCreationTimeStamp()
    {
        $publishedDate = $this->getPublishedTimestamp();
        if (empty($publishedDate)) {
            $publishedDate = $this->getCreatedTimestamp();
        }
        return $publishedDate;
    }

    /**
     * If low page rank or late publication and not logged in,
     * no authorization
     * @param $user
     * @return bool if the page should be protected
     */
    public
    function isProtected($user = '')
    {
        $protected = false;
        if (!Identity::isLoggedIn()) {

            /**
             * Low quality page and late publication should not
             * be public and readable for the search engine
             */

            if ($this->isLowQualityPage()) {
                $lowQualityPageEnabled = PluginUtility::getConfValue(LowQualityPage::CONF_LOW_QUALITY_PAGE_PROTECTION_ENABLE);
                if ($lowQualityPageEnabled == 1) {
                    $protected = true;
                }
            }

            if ($this->isLatePublication()) {

                $latePublicationEnabled = PluginUtility::getConfValue(Publication::CONF_LATE_PUBLICATION_PROTECTION_ENABLE);
                if ($latePublicationEnabled == 1) {
                    $protected = true;
                }

            }
        }
        return $protected;

    }

    public
    function isLatePublication()
    {
        return $this->getPublishedElseCreationTimeStamp() > time();
    }

    public
    function getCanonicalUrl()
    {
        if (!empty($this->getCanonical())) {
            return getBaseURL(true) . strtr($this->getCanonical(), ':', '/');
        }
        return null;
    }

    public
    function getCanonicalUrlOrDefault()
    {
        $url = $this->getCanonicalUrl();
        if (empty($url)) {
            $url = $this->getUrl();
        }
        return $url;
    }

    /**
     *
     * @return string|null - the locale facebook way
     */
    public
    function getLocale()
    {
        $lang = $this->getLang();
        if (!empty($lang)) {

            $country = $this->getCountry();
            if (empty($country)) {
                $country = $lang;
            }
            return $lang . "_" . strtoupper($country);
        }
        return null;
    }

    private
    function processDescriptionIfNeeded()
    {

        if ($this->descriptionOrigin == null) {
            $descriptionArray = $this->getMetadata(Page::DESCRIPTION_PROPERTY);
            if (!empty($descriptionArray)) {
                if (array_key_exists('abstract', $descriptionArray)) {

                    $temporaryDescription = $descriptionArray['abstract'];

                    $this->descriptionOrigin = "dokuwiki";
                    if (array_key_exists('origin', $descriptionArray)) {
                        $this->descriptionOrigin = $descriptionArray['origin'];
                    }

                    if ($this->descriptionOrigin == "dokuwiki") {

                        // suppress the carriage return
                        $temporaryDescription = str_replace("\n", " ", $descriptionArray['abstract']);
                        // suppress the h1
                        $temporaryDescription = str_replace($this->getH1(), "", $temporaryDescription);
                        // Suppress the star, the tab, About
                        $temporaryDescription = preg_replace('/(\*|\t|About)/im', "", $temporaryDescription);
                        // Suppress all double space and trim
                        $temporaryDescription = trim(preg_replace('/  /m', " ", $temporaryDescription));
                        $this->description = $temporaryDescription;

                    } else {

                        $this->description = $temporaryDescription;

                    }
                }

            }
        }

    }

    public
    function hasXhtmlCache()
    {

        $renderCache = $this->getRenderCache("xhtml");
        /**
         * $cache->cache is the file
         */
        return file_exists($renderCache->cache);
    }

    public
    function hasInstructionCache()
    {

        $instructionCache = $this->getInstructionsCache();
        /**
         * $cache->cache is the file
         */
        return file_exists($instructionCache->cache);

    }

    public
    function render()
    {

        if (!$this->isStrapSideSlot()) {
            $template = Site::getTemplate();
            LogUtility::msg("This function renders only sidebar for the " . PluginUtility::getUrl("strap", "strap template") . ". (Actual page: $this, actual template: $template)", LogUtility::LVL_MSG_ERROR);
            return "";
        }


        /**
         * When running a bar rendering
         * The global ID should become the id of the slot
         * (needed for parsing)
         * The $ID is restored at the end of the function
         */
        $logicalId = $this->getLogicalId();
        $scope = $this->getScope();
        if ($scope == null) {
            $scope = "undefined";
        }
        $debugInfo = "Logical Id ($logicalId) - Scope ($scope)";
        global $ID;
        $keep = $ID;
        $ID = $logicalId;


        /**
         * The code below is adapted from {@link p_cached_output()}
         * $ret = p_cached_output($file, 'xhtml', $pageid);
         *
         * We don't use {@link CacheRenderer}
         * because the cache key is the physical file
         */
        global $conf;
        $format = 'xhtml';

        $renderCache = $this->getRenderCache($format);
        if ($renderCache->useCache()) {
            $xhtml = $renderCache->retrieveCache(false);
            if (
                ($conf['allowdebug'] || PluginUtility::isDevOrTest()) && $format == 'xhtml') {
                $xhtml = "\n<!-- $debugInfo - bar cachefile {$renderCache->cache} used -->\n" . $xhtml;
            }
        } else {

            /**
             * Get the instructions
             * Adapted from {@link p_cached_instructions()}
             */
            $instructionsCache = $this->getInstructionsCache();
            if ($instructionsCache->useCache()) {
                $instructions = $instructionsCache->retrieveCache();
            } else {
                // no cache - do some work
                $instructions = p_get_instructions($this->getContent());
                if (!$instructionsCache->storeCache($instructions)) {
                    msg('Unable to save cache file. Hint: disk full; file permissions; safe_mode setting.', -1);
                }
            }

            /**
             * Render
             */
            $xhtml = p_render($format, $instructions, $info);
            if ($info['cache'] && $renderCache->storeCache($xhtml)) {
                if (($conf['allowdebug'] || PluginUtility::isDevOrTest()) && $format == 'xhtml') {
                    $xhtml = "\n<!-- $debugInfo - no bar cachefile used, but created {$renderCache->cache} -->\n" . $xhtml;
                }
            } else {
                $renderCache->removeCache();   //   try to delete cachefile
                if (($conf['allowdebug'] || PluginUtility::isDevOrTest()) && $format == 'xhtml') {
                    $xhtml = "\n<!-- $debugInfo - no bar cachefile used, caching forbidden -->\n" . $xhtml;
                }
            }
        }

        // restore ID
        $ID = $keep;
        return $xhtml;

    }

    /**
     * @param string $outputFormat For instance, "xhtml" or {@links Analytics::RENDERER_NAME_MODE}
     * @return \dokuwiki\Cache\Cache the cache of the page
     *
     * Output of {@link DokuWiki_Syntax_Plugin::render()}
     *
     */
    private
    function getRenderCache($outputFormat)
    {

        if ($this->isStrapSideSlot()) {

            /**
             * Logical cache based on scope (ie logical id) is the scope and part of the key
             */
            return new CacheByLogicalKey($this, $outputFormat);

        } else {

            return new CacheRenderer($this->getId(), $this->getFileSystemPath(), $outputFormat);

        }
    }

    /**
     * @return CacheInstructions
     * The cache of the {@link CallStack call stack} (ie list of output of {@link DokuWiki_Syntax_Plugin::handle})
     */
    private
    function getInstructionsCache()
    {

        if ($this->isStrapSideSlot()) {

            /**
             * @noinspection PhpIncompatibleReturnTypeInspection
             * No inspection because this is not the same object interface
             * because we can't overide the constructor of {@link CacheInstructions}
             * but they should used the same interface (ie manipulate array data)
             */
            return new CacheInstructionsByLogicalKey($this);

        } else {

            return new CacheInstructions($this->getId(), $this->getFileSystemPath());

        }

    }

    public
    function deleteXhtmlCache()
    {
        $this->deleteCache("xhtml");
    }

    public function getAnchorLink()
    {
        $url = $this->getCanonicalUrlOrDefault();
        $title = $this->getTitle();
        return "<a href=\"$url\">$title</a>";
    }


    /**
     * Without the `:` at the end
     * @return string
     */
    public function getNamespacePath()
    {
        $ns = getNS($this->getId());
        /**
         * False means root namespace
         */
        if ($ns == false) {
            return ":";
        } else {
            return ":$ns";
        }
    }

    private function getScope()
    {
        return $this->getMetadata(Page::SCOPE_KEY);
    }


}
