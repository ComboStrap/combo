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
    const NAME = "name";
    const PATH = "path";
    const DESCRIPTION = "description";
    const METADATA = 'metadata';
    const CANONICAL = "canonical";
    const DATE_CREATED = 'date_created';
    const DATE_END = "date_end";
    const DATE_START = "date_start";


    /**
     * @param $pageId
     * @param bool $cache - if true, the data is returned from the cache
     * @return mixed
     * The p_render function was stolen from the {@link p_cached_output} function
     * used the in the switch of the {@link \dokuwiki\Action\Export::preProcess()} function
     */
    public static function getDataAsJsonObject($pageId, $cache = false)
    {

        return json_decode(self::getDataAsJsonString($pageId, $cache));

    }

    /**
     * @param $pageId
     * @param bool $cache - if true, the analytics will be cached or retrieved from cache
     * @return bool|string|null
     */
    static function getDataAsJsonString($pageId, $cache = false)
    {

        global $ID;
        $oldId = $ID;
        $ID = $pageId;
        if (!$cache) {
            $file = wikiFN($pageId);
            if (file_exists($file)) {
                $content = file_get_contents($file);
                $instructions = RenderUtility::getInstructionsAndStripPEventually($content,false);
                return p_render(renderer_plugin_combo_analytics::RENDERER_NAME_MODE, $instructions, $info);
            } else {
                return false;
            }
        } else {
            $result = p_cached_output(wikiFN($pageId, 0), renderer_plugin_combo_analytics::RENDERER_NAME_MODE, $pageId);
        }
        $ID = $oldId;
        return $result;

    }

    public static function getDataAsArray($pageId, $cache = false)
    {

        return json_decode(self::getDataAsJsonString($pageId, $cache),true);

    }




}
