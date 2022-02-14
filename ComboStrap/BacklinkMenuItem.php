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

use dokuwiki\Menu\Item\AbstractItem;

/**
 * Class MenuItem
 * @package ComboStrap
 *
 */
class BacklinkMenuItem extends AbstractItem
{

    const CLASS_HTML = "combo-backlink-item";
    const CANONICAL = "backlink";

    /**
     * MetadataMenuItem constructor.
     */
    public function __construct()
    {
        $snippetManager = PluginUtility::getSnippetManager();
        $snippetManager->attachJavascriptComboLibrary();
        $snippetManager->attachJavascriptSnippetForRequest(self::CANONICAL);
        parent::__construct();
    }


    /**
     *
     * @return string
     */
    public function getLabel(): string
    {
        return "Backlinks";
    }

    public function getLinkAttributes($classprefix = 'menuitem '): array
    {
        $linkAttributes = parent::getLinkAttributes($classprefix);
        /**
         * A class and not an id
         * because a menu item can be found twice on
         * a page (For instance if you want to display it in a layout at a
         * breakpoint and at another in another breakpoint
         */
        $linkAttributes['class'] = self::CLASS_HTML;

        return $linkAttributes;
    }

    public function getTitle(): string
    {
        return "Show the backlinks";
    }

    public function getSvg(): string
    {
        /** @var string icon file */
        return Site::getComboImagesDirectory()->resolve('backlink.svg')->toString();
    }


}
