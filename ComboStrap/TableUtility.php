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

/**
 *
 * Class TableUtility
 * @package ComboStrap
 *
 * @see <a href="https://datatables.net/examples/styling/bootstrap4">DataTables (May be)</a>
 *
 */
class TableUtility
{

    const TABLE_SNIPPET_ID = "table";
    const BOOT_TABLE_CLASSES = 'table table-non-fluid table-hover table-striped';

    static function tableOpen($renderer, $class)
    {

        // table-responsive and
        $bootResponsiveClass = 'table-responsive';

        // Add non-fluid to not have a table that takes 100% of the space
        // Otherwise we can't have floating element at the right and the visual space is too big
        PluginUtility::getSnippetManager()->attachCssInternalStyleSheet(self::TABLE_SNIPPET_ID);

        $bootTableClass = self::BOOT_TABLE_CLASSES;

        $renderer->doc .=<<<EOF
<div class="$class $bootResponsiveClass"><table class="inline $bootTableClass">
EOF;

    }

}
