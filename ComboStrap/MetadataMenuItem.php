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
use renderer_plugin_combo_analytics;

/**
 * Class MenuItem
 * @package ComboStrap
 *
 */
class MetadataMenuItem extends AbstractItem
{

    const ITEM_ID = "combo_metadata_item_id";
    const CANONICAL = "metadata";

    /**
     * MetadataMenuItem constructor.
     */
    public function __construct()
    {
        PluginUtility::getSnippetManager()->attachJavascriptSnippetForRequest(self::CANONICAL);
    }


    /**
     *
     * @return string
     */
    public function getLabel(): string
    {
        return "Metadata";
    }

    public function getLinkAttributes($classprefix = 'menuitem '): array
    {
        $linkAttributes = parent::getLinkAttributes($classprefix);
        $linkAttributes['id'] = self::ITEM_ID;
        return $linkAttributes;
    }

    public function getTitle(): string
    {
        return "Show the Metadata";
    }

    public function getSvg(): string
    {
        /** @var string icon file */
        return Resources::getImagesDirectory() . '/tag-text.svg';
    }


}
