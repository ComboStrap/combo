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
 * To be able to debug, disable the trigger data attribute
 * The popover will stay on the page
 */
class HistoricalBreadcrumbMenuItem extends AbstractItem
{


    const RECENT_PAGES_VISITED = "Recent Pages Visited";

    /**
     * This unique name should not be in the {@link \action_plugin_combo_historicalbreadcrumb}
     * to avoid circular reference
     */
    const HISTORICAL_BREADCRUMB_NAME = "historical-breadcrumb";
    const CANONICAL = "breadcrumb";

    public function __construct()
    {
        /**
         * Making popover active
         */
        $snippetManager = PluginUtility::getSnippetManager();
        $snippetManager
            ->addPopoverLibrary()
            ->attachJavascriptFromComponentId(HistoricalBreadcrumbMenuItem::HISTORICAL_BREADCRUMB_NAME);

        /**
         * Css
         */
        $snippetManager->attachCssInternalStylesheet(HistoricalBreadcrumbMenuItem::HISTORICAL_BREADCRUMB_NAME);

        parent::__construct();

    }


    /**
     *
     * @return string
     */
    public function getLabel(): string
    {
        return self::RECENT_PAGES_VISITED;
    }

    public function getLinkAttributes($classprefix = 'menuitem '): array
    {

        $linkAttributes = parent::getLinkAttributes($classprefix);
        $linkAttributes['href'] = "#";
        $dataAttributeNamespace = Bootstrap::getDataNamespace();
        $linkAttributes["data{$dataAttributeNamespace}-toggle"] = "popover";
        $linkAttributes["data{$dataAttributeNamespace}-placement"] = "left";
        $linkAttributes["data{$dataAttributeNamespace}-html"] = "true";
        global $lang;
        $linkAttributes["data{$dataAttributeNamespace}-title"] = $lang['breadcrumb'];


        $pages = breadcrumbs();
        if (sizeof($pages) === 0) {
            // happens when there is no history
            return $linkAttributes;
        }
        $pages = array_reverse($pages);

        /**
         * All page should be shown,
         * also the actual
         * because when the user is going
         * in admin mode, it's an easy way to get back
         */
        $actualPageId = array_keys($pages)[0];
        $actualPageName = array_shift($pages);
        $html = $this->createLink($actualPageId, $actualPageName, self::HISTORICAL_BREADCRUMB_NAME . "-home");

        $html .= '<ol>' . PHP_EOL;
        foreach ($pages as $id => $name) {

            $html .= '<li>';
            $html .= $this->createLink($id, $name);
            $html .= '</li>' . PHP_EOL;

        }
        $html .= '</ol>' . PHP_EOL;
        $html .= '</nav>' . PHP_EOL;


        $linkAttributes["data{$dataAttributeNamespace}-content"] = $html;

        // https://github.com/ComboStrap/combo/issues/109
        // Don't use the dismiss, happens before a link navigation
        // preventing links to work
        // $linkAttributes["data{$dataAttributeNamespace}-trigger"] = "focus";

        // See for the tabindex
        // https://getbootstrap.com/docs/5.1/components/popovers/#dismiss-on-next-click
        $linkAttributes['tabindex'] = "0";

        $linkAttributes["data{$dataAttributeNamespace}-custom-class"] = "historical-breadcrumb";
        return $linkAttributes;

    }

    public function getTitle(): string
    {
        /**
         * The title (unfortunately) is deleted from the anchor
         * and is used as header in the popover
         */
        return self::RECENT_PAGES_VISITED;
    }

    public function getSvg(): string
    {
        /** @var string icon file */
        return DirectoryLayout::getComboImagesDirectory()->resolve('history.svg')->toAbsoluteId();
    }

    /**
     * @param $id
     * @param $name
     * @param null $class
     * @return string
     */
    public function createLink($id, $name, $class = null): string
    {
        $page = MarkupPath::createMarkupFromId($id);
        if ($name == "start") {
            $name = "Home Page";
        } else {
            $name = $page->getTitleOrDefault();
        }

        try {
            $attributes = LinkMarkup::createFromPageIdOrPath($id)->toAttributes(self::CANONICAL);
        } catch (ExceptionCompile $e) {
            return LogUtility::wrapInRedForHtml("Error on breadcrumb markup ref. Message: {$e->getMessage()}");
        }
        if ($class !== null) {
            $attributes->addClassName($class);
        }

        return $attributes->toHtmlEnterTag("a") . $name . "</a>";
    }


}
