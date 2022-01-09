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

class AnalyticsDocument extends OutputDocument
{


    /**
     * Constant in Key or value
     */
    const HEADER_POSITION = 'header_id';
    const WORD_COUNT = 'word_count';
    const INTERNAL_LINK_DISTANCE = 'internal_link_distance';
    const INTERWIKI_LINK_COUNT = "interwiki_link_count";
    const EDITS_COUNT = 'edits_count';
    const INTERNAL_LINK_BROKEN_COUNT = 'internal_broken_link_count';
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
    const LOW = "low";
    const RULES = "rules";
    const DETAILS = 'details';
    const FAILED_MANDATORY_RULES = 'failed_mandatory_rules';
    const METADATA = 'metadata';

    /**
     * @throws ExceptionCombo
     */
    public function getOrProcessJson(): Json
    {
        $content = parent::getOrProcessContent();
        return Json::createFromString($content);
    }


    /**
     * Return the JSON analytics data without any processing
     *
     * Data Type Wrapper around {@link AnalyticsDocument::getContent()}
     * to return a json object
     *
     * @return Json
     * @throws ExceptionCombo
     */
    public function getJson(): Json
    {
        /**
         * Don't render if the analytics file exists
         * If the data is stale, the render function may create a cycle
         * (for instance, the {@link Page::getLowQualityIndicatorCalculated()}
         * used this data but the {@link renderer_plugin_combo_analytics}
         * will set it {@link Page::setLowQualityIndicatorCalculation()}
         * creating a loop
         */
        if (!FileSystems::exists($this->getCachePath())) {
            return Json::createEmpty();
        }
        return Json::createFromString(parent::getContent());


    }


    function getExtension(): string
    {
        /**
         * Unfortunately, you can add a json extension
         * because the format is used to locate the
         * class {@link renderer_plugin_combo_analytics}
         */
        return  renderer_plugin_combo_analytics::RENDERER_NAME_MODE;
    }

    function getRendererName(): string
    {
        return renderer_plugin_combo_analytics::RENDERER_NAME_MODE;
    }


}
