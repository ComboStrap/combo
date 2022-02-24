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
 * Inspiration:
 * https://raw.githubusercontent.com/splitbrain/dokuwiki-plugin-dw2pdf/master/MenuItem.php
 */
class QualityMenuItem extends AbstractItem
{


    const CLASS_HTML = "combo-quality-item";
    const CANONICAL = "quality";
    const CLASS_HTML_LOW = "combo-quality-item-low";

    /**
     * @var Page
     */
    private $page;

    /**
     * QualityMenuItem constructor.
     */
    public function __construct()
    {
        $snippetManager = PluginUtility::getSnippetManager();
        $snippetManager->attachJavascriptComboLibrary();
        $snippetManager->attachJavascriptSnippetForRequest(self::CANONICAL);
        $this->page = Page::createPageFromRequestedPage();
        if($this->page->isLowQualityPage()){
            $snippetManager->attachCssSnippetForRequest(self::CANONICAL);
        }
        parent::__construct();

    }


    /**
     *
     * @return string
     */
    public function getLabel(): string
    {
        $suffix = "";
        if ($this->page->isLowQualityPage()) {
            $suffix = "(Low)";
        }
        return "Page Quality $suffix";
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
        if ($this->page->isLowQualityPage()) {
            $linkAttributes['class'] .= " ".self::CLASS_HTML_LOW;
        }

        return $linkAttributes;
    }

    public function getTitle(): string
    {
        $title = "Show the page quality";
        if ($this->page->isLowQualityPage()) {
            $title .= "\n(This page is a low quality page)";
        } else {
            $title .= "\n(This page has a normal quality)";
        }
        return htmlentities($title);
    }

    public function getSvg(): string
    {

        if ($this->page->isLowQualityPage()) {
            /** @var string icon file */
            return Site::getComboImagesDirectory()->resolve( 'quality-alert.svg')->toString();
        } else {
            /**
             * @var string icon file
             * !!! Same icon used in the landing page !!!
             */
            return Site::getComboImagesDirectory()->resolve('quality.svg')->toString();
        }
    }


}
