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
class SlotManagerMenuItem extends AbstractItem
{

    const CLASS_HTML = "combo-edit-page-item";
    const CANONICAL = "edit-page";
    const EDIT_ACTION = "Edit";
    const CREATE_ACTION = "Create";
    const TAG = "slot";


    /**
     *
     * @return string
     */
    public function getLabel(): string
    {
        return "Manage the slots";
    }

    public function getLinkAttributes($classprefix = 'menuitem '): array
    {

        PluginUtility::getSnippetManager()->attachJavascriptInternalForRequest("popover");
        PluginUtility::getSnippetManager()->attachCssInternalStylesheetForRequest("popover");

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
        $linkAttributes["data{$dataAttributeNamespace}-title"] = "Slot Manager";

        /**
         * TODO: right when rtl language
         */
        $linkAttributes["data{$dataAttributeNamespace}-placement"] = "left";

        // encoding happens
        $linkAttributes["data{$dataAttributeNamespace}-content"] = $this->createHtml();


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
        return "Slot Manager";
    }

    public function getSvg(): string
    {
        /** @var string icon file */
        return DirectoryLayout::getComboImagesDirectory()->resolve('entypo-text-document-inverted.svg')->toPathString();
    }

    public function createHtml(): string
    {
        $requestedPage = PageFragment::createFromRequestedPage();
        $url = UrlEndpoint::createComboStrapUrl()->setPath("/". self::TAG);
        $html = "<p>Edit and/or create the <a href=\"{$url->toHtmlString()}\">slots</a> of the page</p>";
        foreach (Site::getSecondarySlotNames() as $secondarySlot) {

            $actualPath = $requestedPage->getPath();
            $label = $secondarySlot;
            switch ($secondarySlot) {
                case Site::getSidebarName():
                    $label = "Page Sidebar";
                    break;
                case Site::getPrimaryHeaderSlotName():
                    $label = "Main Header";
                    break;
                case Site::getPrimarySideSlotName():
                    $label = "Main Side";
                    break;
                case Site::getPrimaryFooterSlotName():
                    $label = "Main Footer";
                    break;
                case Site::getPageFooterSlotName():
                    $label = "Page Footer";
                    break;
                case Site::getPageHeaderSlotName():
                    $label = "Page Header";
                    break;
            }
            $html .= "<p class='mb-0 mt-1'><strong>$label</strong></p>";
            $html .= "<table>";

            $parentPath = $actualPath;
            while (true) {
                try {
                    $parentPath = $parentPath->getParent();
                } catch (ExceptionNotFound $e) {
                    break;
                }
                $secondaryPath = $parentPath->resolve($secondarySlot);

                $secondaryPage = PageFragment::createPageFromQualifiedPath($secondaryPath->toPathString());
                $class = StyleUtility::addComboStrapSuffix(\syntax_plugin_combo_link::TAG);
                if (FileSystems::exists($secondaryPath)) {
                    $action = self::EDIT_ACTION;
                    $style = '';
                } else {
                    $action = self::CREATE_ACTION;
                    $style = ' style="color:rgba(0,0,0,0.65)"';
                }
                $url = UrlEndpoint::createDokuUrl()
                    ->addQueryParameter(DokuwikiId::DOKUWIKI_ID_ATTRIBUTE, $secondaryPage->getWikiId())
                    ->addQueryParameter("do", "edit");

                $html .= "<tr><td class='pe-2'>$action</td><td><a href=\"{$url->toHtmlString()}\" class=\"$class\"$style>{$secondaryPath->toPathString()}</a></td></tr>";

                if ($action === self::EDIT_ACTION) {
                    break;
                }

            }
            $html .= "</table>";

        };


        return $html;

    }


}
