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
class EditPageMenuItem extends AbstractItem
{

    const CLASS_HTML = "combo-edit-page-item";
    const CANONICAL = "edit-page";
    const EDIT_ACTION = "Edit";
    const CREATE_ACTION = "Create";

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
        return "Edit the page";
    }

    public function getSvg(): string
    {
        /** @var string icon file */
        return Site::getComboImagesDirectory()->resolve('edit-page.svg')->toString();
    }

    public function createHtml(): string
    {
        $secondaryPage = Page::createPageFromRequestedPage();
        $allPaths = [];
        foreach (Site::getSecondarySlotNames() as $secondarySlot) {

            $actualPath = $secondaryPage->getPath();
            $label = $secondarySlot;
            try {
                switch ($secondarySlot) {
                    case Site::getSidebarName():
                        $label = "Page Sidebar";
                        break;
                    case Site::getPrimaryHeaderSlotName():
                        $label = "Main Header";
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
            } catch (ExceptionCompile $e) {
                // error if strap was not loaded
                // should have happen before when we get the secondary name (getSecondarySlotNames)
            }
            while (($parentPath = $actualPath->getParent()) != null) {
                $secondaryPath = $parentPath->resolve($secondarySlot);
                try {
                    $secondaryPage = Page::createPageFromQualifiedPath($secondaryPath->toString());
                    $class = "link-combo";
                    if (FileSystems::exists($secondaryPath)) {
                        $action = self::EDIT_ACTION;
                    } else {
                        $action = self::CREATE_ACTION;
                        $class .= " text-alert";
                    }
                    $url = $secondaryPage->getUrl();
                    if (strpos($url, "?") !== false) {
                        // without url rewrite
                        // /./doku.php?id=slot_main_header
                        $url .= DokuwikiUrl::AMPERSAND_URL_ENCODED_FOR_HTML;
                    } else {
                        // with url rewrite, the id parameter is not seen
                        $url .= "?";
                    }
                    $url .= "do=edit";
                    $allPaths[] = "<a href=\"$url\" class=\"$class\">$action $label ({$secondaryPath->toString()})</a>";

                    if ($action === self::EDIT_ACTION) {
                        break;
                    }
                } catch (ExceptionBadSyntax $e) {
                    // should not happen
                }
                // loop
                $actualPath = $parentPath;
            }

        };


        /**
         * All page should be shown,
         * also the actual
         * because when the user is going
         * in admin mode, it's an easy way to get back
         */
        $lis = "";
        foreach ($allPaths as $actualPath) {

            $lis .= "<li>$actualPath</li>" . PHP_EOL;

        }
        return <<<EOF
<nav>
    <ol>
        $lis
    </ol>
</nav>
EOF;
    }


}
