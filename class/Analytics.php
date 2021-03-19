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


class Analytics
{



    const DATE_MODIFIED = 'date_modified';
    /**
     * Constant in Key or value
     */
    const HEADER_POSITION = 'header_id';
    const INTERNAL_BACKLINKS_COUNT = "internal_backlinks_count";
    const WORDS_COUNT = 'words_count';
    const INTERNAL_LINK_DISTANCE = 'internal_links_distance';
    const INTERWIKI_LINKS_COUNT = "interwiki_links_count";
    const EDITS_COUNT = 'edits_count';
    const INTERNAL_LINKS_BROKEN_COUNT = 'internal_broken_links_count';
    const TITLE = 'title';
    const INTERNAL_LINKS_COUNT = 'internal_links_count';
    const LOCAL_LINKS_COUNT = "local_links_count"; // ie fragment #hallo
    const EXTERNAL_MEDIAS = 'external_medias_count';
    const CHARS_COUNT = 'chars_count';
    const INTERNAL_MEDIAS_COUNT = 'internal_medias_count';
    const EXTERNAL_LINKS_COUNT = 'external_links_count';
    const HEADERS_COUNT = 'headers_count';
    const QUALITY = 'quality';
    const STATISTICS = "statistics";
    const EMAILS_COUNT = "emails_count";
    const WINDOWS_SHARE_COUNT = "windows_share_count";

    /**
     * An array of info for errors mostly
     */
    const INFO = "info";
    /**
     * The format returned by the renderer
     */
    const RENDERER_FORMAT = "analytics";
    const RENDERER_NAME_MODE = "combo_".self::RENDERER_FORMAT;
    const H1 = "h1";
    const LOW = "low";
    const RULES = "rules";
    const DETAILS = 'details';
    const FAILED_MANDATORY_RULES = 'failed_mandatory_rules';


    /**
     * @param $pageId
     * @param bool $cache - if true, the data is returned from the cache
     * @return mixed
     * The p_render function was stolen from the {@link p_cached_output} function
     * used the in the switch of the {@link \dokuwiki\Action\Export::preProcess()} function
     */
    public static function processAndGetDataAsJson($pageId, $cache = false)
    {

        return json_decode(self::processAndGetDataAsString($pageId, $cache));

    }

    /**
     * @param $pageId
     * @param bool $cache - if true, the analytics will be cached or retrieved from cache
     * @return bool|string|null
     */
    static function processAndGetDataAsString($pageId, $cache = false)
    {

        global $ID;
        $oldId = $ID;
        $ID = $pageId;
        if (!$cache) {
            $file = wikiFN($pageId);
            if (file_exists($file)) {
                $content = file_get_contents($file);
                $instructions = RenderUtility::getInstructions($content,false);
                return p_render(self::RENDERER_NAME_MODE, $instructions, $info);
            } else {
                return false;
            }
        } else {
            $result = p_cached_output(wikiFN($pageId, 0), self::RENDERER_NAME_MODE, $pageId);
        }
        $ID = $oldId;
        return $result;

    }

    public static function processAndGetDataAsArray($pageId, $cache = false)
    {

        return json_decode(self::processAndGetDataAsString($pageId, $cache),true);

    }




}
