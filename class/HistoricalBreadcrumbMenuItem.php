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
 * *
 * @package ComboStrap
 *
 *
 */
class HistoricalBreadcrumbMenuItem extends AbstractItem
{
    const RECENT_PAGES_VISITED = "Recent Pages Visited";


    /**
     *
     * @return string
     */
    public function getLabel()
    {
        return self::RECENT_PAGES_VISITED;
    }

    public function getLinkAttributes($classprefix = 'menuitem ')
    {

        $linkAttributes = parent::getLinkAttributes($classprefix);
        $linkAttributes['href'] = "#";
        $dataAttributeNamespace = Bootstrap::getDataNamespace();
        $linkAttributes["data{$dataAttributeNamespace}-toggle"] = "popover";
        $linkAttributes["data{$dataAttributeNamespace}-html"] = "true";
        global $lang;
        $linkAttributes["data{$dataAttributeNamespace}-title"] = $lang['breadcrumb'];


        $html = '<ol>' . PHP_EOL;

        $i = 0;
        foreach (array_reverse(breadcrumbs()) as $id => $name) {

            $i++;
            if ($i == 1) {
                continue;
            } else {
                $html .= '<li>';
            }

            $page = Page::createPageFromId($id);
            if ($name == "start") {
                $name = "Home Page";
            } else {
                $name = $page->getTitleNotEmpty();
            }
            $link = LinkUtility::createFromPageId($id);
            $html .= $link->renderOpenTag() . $name . $link->renderClosingTag();
            $html .= '</li>' . PHP_EOL;

        }
        $html .= '</ol>' . PHP_EOL;
        $html .= '</nav>' . PHP_EOL;
        $linkAttributes["data{$dataAttributeNamespace}-content"] = $html;

        // Dismiss on next click
        $linkAttributes["data{$dataAttributeNamespace}-trigger"] = "focus";
        // See for the tabindex
        // https://getbootstrap.com/docs/5.1/components/popovers/#dismiss-on-next-click
        $linkAttributes['tabindex'] = "0";

        $linkAttributes["data{$dataAttributeNamespace}custom-class"] = "historical-breadcrumb";
        return $linkAttributes;
    }

    public function getTitle()
    {
        /**
         * The title (unfortunately) is deleted from the anchor
         * and is used as header in the popover
         */
        return self::RECENT_PAGES_VISITED;
    }

    public function getSvg()
    {
        /** @var string icon file */
        return Resources::getImagesDirectory() . '/history.svg';
    }


}
