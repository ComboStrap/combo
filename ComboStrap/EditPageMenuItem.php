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

    const CLASS_HTML = "combo-edit-page-item";
    const CANONICAL = "edit-page";

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
        $linkAttributes['href'] = "#";
        $dataAttributeNamespace = Bootstrap::getDataNamespace();
        $linkAttributes["data{$dataAttributeNamespace}-toggle"] = "popover";
        $linkAttributes["data{$dataAttributeNamespace}-html"] = "true";
        $linkAttributes["data{$dataAttributeNamespace}-title"] = "Edit this page";


        $page = Page::createPageFromRequestedPage();
        foreach($page->getSecondarySlots() as $secondarySlot){

        };


        /**
         * All page should be shown,
         * also the actual
         * because when the user is going
         * in admin mode, it's an easy way to get back
         */


        $html .= '<ol>' . PHP_EOL;
        foreach ($pages as $id => $name) {

            $html .= '<li>';
            $html .= $this->createLink($id, $name);
            $html .= '</li>' . PHP_EOL;

        }
        $html .= '</ol>' . PHP_EOL;
        $html .= '</nav>' . PHP_EOL;


        $linkAttributes["data{$dataAttributeNamespace}-content"] = $html;

        // Dismiss on next click
        // To debug, just comment this line
        $linkAttributes["data{$dataAttributeNamespace}-trigger"] = "focus";

        // See for the tabindex
        // https://getbootstrap.com/docs/5.1/components/popovers/#dismiss-on-next-click
        $linkAttributes['tabindex'] = "0";

        $linkAttributes["data{$dataAttributeNamespace}custom-class"] = self::CLASS_HTML;
        return $linkAttributes;


    }

    public function getTitle(): string
    {
        return "Edit the page";
    }

    public function getSvg(): string
    {
        /** @var string icon file */
        return Site::getComboImagesDirectory()->resolve('edit-page.svg')->toString();
    }


}
