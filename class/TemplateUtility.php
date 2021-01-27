<?php
/**
 * Copyright (c) 2020. ComboStrap, Inc. and its affiliates. All Rights Reserved.
 *
 * This source code is licensed under the GPL license found in the
 * COPYING  file in the root directory of this source tree.
 *
 * @license  GPL 3 (https://www.gnu.org/licenses/gpl-3.0.en.html)
 * @author   ComboStrap <support@combostrap.com>
 *
 */

namespace ComboStrap;


class TemplateUtility
{

    static function render($pageTemplate,$pageId, $pageTitle){

        /**
         * Hack: Replace every " by a ' to be able to detect the title on a pipeline
         * @see {@link \syntax_plugin_combo_pipeline}
         */
        $pageTitle = str_replace('"',"'",$pageTitle);
        $tpl = str_replace("\$title", $pageTitle, $pageTemplate);
        return str_replace("\$id", $pageId, $tpl);

    }

}
