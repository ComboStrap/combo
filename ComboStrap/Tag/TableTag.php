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

namespace ComboStrap\Tag;

use ComboStrap\ExceptionCompile;
use ComboStrap\Outline;
use ComboStrap\PluginUtility;
use ComboStrap\TagAttributes;

/**
 *
 * Class TableUtility
 * @package ComboStrap
 *
 * @see <a href="https://datatables.net/examples/styling/bootstrap4">DataTables (May be)</a>
 *
 * See also: https://www.dokuwiki.org/tips:table_editing
 */
class TableTag
{

    const TABLE_SNIPPET_ID = "table";
    const BOOT_TABLE_CLASSES = 'table table-non-fluid table-hover table-striped';
    const TAG = "table";
    const TABLE_RESPONSIVE_CLASS = "table-responsive";


    /**
     * @param \Doku_Renderer_xhtml $renderer
     * @param $class
     * @return void
     * The call is created during an outline scan at {@link Outline::buildOutline()}
     */
    static function renderEnterXhtml(TagAttributes $tagAttributes, \Doku_Renderer_xhtml $renderer)
    {


        try {
            $position = $tagAttributes->getValueAsInteger(PluginUtility::POSITION);
        } catch (ExceptionCompile $e) {
            $position = null;
        }

        /**
         * We call table_open, to init the {@link \Doku_Renderer_xhtml::$_counter} number that is private otherwise, there
         * is a class name created with the name `row` that is a known bootstrap class
         */
        $doc = $renderer->doc;
        $renderer->table_open(null, null, $position);
        $renderer->doc = $doc;


        // Add non-fluid to not have a table that takes 100% of the space
        // Otherwise we can't have floating element at the right and the visual space is too big
        PluginUtility::getSnippetManager()->attachCssInternalStyleSheet(self::TABLE_SNIPPET_ID);

        $renderer->doc .= TagAttributes::createEmpty(self::TAG)
            ->addClassName(self::TABLE_RESPONSIVE_CLASS)
            ->toHtmlEnterTag("div");
        $renderer->doc .= TagAttributes::createEmpty(self::TAG)
            ->addClassName("inline") // dokuwiki
            ->addClassName(self::BOOT_TABLE_CLASSES)
            ->toHtmlEnterTag("table");

    }

}
