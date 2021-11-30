<?php
/**
 * Copyright (c) 2021. ComboStrap, Inc. and its affiliates. All Rights Reserved.
 *
 * This source code is licensed under the GPL license found in the
 * COPYING  file in the root directory of this source tree.
 *
 * @license  GPL 3 (https://www.gnu.org/licenses/gpl-3.0.en.html)
 * @author   ComboStrap <support@combostrap.com>
 *
 */

namespace ComboStrap;


use dokuwiki\Cache\CacheParser;
use dokuwiki\Cache\CacheRenderer;
use renderer_plugin_combo_analytics;

class Analytics
{


    const DATE_MODIFIED = 'date_modified';
    /**
     * Constant in Key or value
     */
    const HEADER_POSITION = 'header_id';
    const INTERNAL_BACKLINK_COUNT = "internal_backlink_count";
    const WORD_COUNT = 'word_count';
    const INTERNAL_LINK_DISTANCE = 'internal_link_distance';
    const INTERWIKI_LINK_COUNT = "interwiki_link_count";
    const EDITS_COUNT = 'edits_count';
    const INTERNAL_LINK_BROKEN_COUNT = 'internal_broken_link_count';
    const TITLE = 'title';
    const INTERNAL_LINK_COUNT = 'internal_link_count';
    const LOCAL_LINK_COUNT = "local_link_count"; // ie fragment #hallo
    const EXTERNAL_MEDIA_COUNT = 'external_media_count';
    const CHAR_COUNT = 'char_count';
    const MEDIA_COUNT = 'media_count';
    const INTERNAL_MEDIA_COUNT = 'internal_media_count';
    const INTERNAL_BROKEN_MEDIA_COUNT = 'internal_broken_media_count';
    const EXTERNAL_LINK_COUNT = 'external_link_count';
    const TEMPLATE_LINK_COUNT = 'template_link_count';
    const HEADING_COUNT = 'heading_count';
    const SYNTAX_COUNT = "syntax_count";
    const QUALITY = 'quality';
    const STATISTICS = "statistics";
    const EMAIL_COUNT = "email_count";
    const WINDOWS_SHARE_COUNT = "windows_share_count";

    /**
     * An array of info for errors mostly
     */
    const INFO = "info";
    const H1 = "h1";
    const LOW = "low";
    const RULES = "rules";
    const DETAILS = 'details';
    const FAILED_MANDATORY_RULES = 'failed_mandatory_rules';
    const PATH = "path";
    const DESCRIPTION = "description";
    const METADATA = 'metadata';
    const DATE_CREATED = 'date_created';
    const DATE_END = "date_end";
    const DATE_START = "date_start";
    const H1_PARSED = "h1_parsed";
    private $page;
    /**
     * @var CacheRenderer
     */
    private $cacheFile;

    /**
     *
     * Analytics constructor.
     */
    public function __construct(Page $page)
    {
        $this->page = $page;
        $cache = new CacheRenderer($this->page->getDokuwikiId(), $this->page->getAbsoluteFileSystemPath(), renderer_plugin_combo_analytics::RENDERER_NAME_MODE);

        $this->cacheFile = File::createFromPath($cache->cache);

    }


    /**
     * @return bool - if a {@link Analytics::render(false)} for the page should occurs
     */
    public
    function shouldAnalyticsProcessOccurs(): bool
    {
        /**
         * If render cache is on
         */
        if (Site::isRenderCacheOn()) {
            /**
             * If there is no cache
             */
            if (!$this->isCached()) {
                return true;
            }
        }


        return false;
    }



    public function isCached(): bool
    {
        return $this->cacheFile->exists();
    }


    /**
     * Generate the analytics file and return the data
     *
     * @return null|Json
     *
     * The p_render function was seen from the {@link p_cached_output} function
     * used the in the switch of the {@link \dokuwiki\Action\Export::preProcess()} function
     */
    function render(): Json
    {
        if (!$this->page->exists()) {
            return Json::createEmpty();
        }
        global $ID;
        $oldId = $ID;
        $ID = $this->page->getDokuwikiId();

        $result = p_cached_output($this->page->getAbsoluteFileSystemPath(), renderer_plugin_combo_analytics::RENDERER_NAME_MODE, $this->page->getDokuwikiId());

        $ID = $oldId;

        return Json::createFromString($result);

    }

    /**
     * Return the JSON analytics data
     *
     * @return Json
     */
    public function getData(): Json
    {
        /**
         * Don't render if the analytics file exists
         * If the data is stale, the render function may create a cycle
         * (for instance, the {@link Page::getLowQualityIndicatorCalculated()}
         * used this data but the {@link renderer_plugin_combo_analytics}
         * will set it {@link Page::setLowQualityIndicatorCalculation()}
         * creating a loop
         */
        if(!$this->exists()){
            return $this->render();
        } else {
            return Json::createFromString($this->cacheFile->getContent());
        }
    }

    public function delete()
    {
        $this->page->deleteRenderCache(renderer_plugin_combo_analytics::RENDERER_NAME_MODE);
    }

    public function getModifiedTime()
    {
        if($this->cacheFile->exists()){
            return $this->cacheFile->getModifiedTime();
        } else {
            return null;
        }
    }

    public function exists()
    {
        return $this->cacheFile->exists();
    }


}
