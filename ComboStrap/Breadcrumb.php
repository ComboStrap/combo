<?php

namespace ComboStrap;

use syntax_plugin_combo_breadcrumb;

/**
 * Utility class for breadcrumb
 */
class Breadcrumb
{
    /**
     * The type of breadcrumb
     *
     * Navigation is a markup that should be present
     * only once in a page
     */
    public const NAVIGATION_TYPE = "navigation";
    /**
     * Typography is when a breadcrumb is used in a iterator
     * for instance as sub-title
     */
    public const TYPOGRAPHY_TYPE = "typography";

    /**
     * Hierarchical breadcrumbs (you are here)
     *
     * This will return the Hierarchical breadcrumbs.
     *
     * Config:
     *    - $conf['youarehere'] must be true
     *    - add $lang['youarehere'] if $printPrefix is true
     *
     * Metadata comes from here
     * https://developers.google.com/search/docs/data-types/breadcrumb
     *
     * @param TagAttributes|null $tagAttributes
     * @return string
     * @throws ExceptionNotFound
     */
    public static function toBreadCrumbHtml(TagAttributes $tagAttributes = null): string
    {

        if ($tagAttributes === null) {
            $tagAttributes = TagAttributes::createEmpty(syntax_plugin_combo_breadcrumb::TAG);
        }


        /**
         * Get the page
         */
        $path = ContextManager::getOrCreate()->getAttribute(PagePath::PROPERTY_NAME);
        if ($path === null) {
            // should never happen but yeah
            LogUtility::error("Internal Error: The page context was not set. Defaulting to the requested page", syntax_plugin_combo_breadcrumb::CANONICAL_HIERARCHICAL);
            $actual = PageFragment::createFromRequestedPage();
        } else {
            $actual = PageFragment::createPageFromQualifiedPath($path);
        }


        /**
         * TODO: How to check that we should be able to use the navigational type only once ?
         */
        $type = $tagAttributes->getType();


        /**
         * Print in function of the depth
         */
        switch ($type) {
            case self::NAVIGATION_TYPE:
                /**
                 * https://www.w3.org/TR/wai-aria-practices/examples/breadcrumb/index.html
                 * Arial-label Provides a label that describes the type of navigation provided in the nav element.
                 */
                $tagAttributes->addOutputAttributeValue("aria-label", "Hierarchical breadcrumb");
                $htmlOutput = $tagAttributes->toHtmlEnterTag("nav");
                $htmlOutput .= '<ol class="breadcrumb">';

                $lisHtmlOutput = self::getLiHtmlOutput($actual, true);
                $parent = $actual;
                while (true) {
                    try {
                        $parent = $parent->getParent();
                    } catch (ExceptionNotFound $e) {
                        break;
                    }
                    $liHtmlOutput = self::getLiHtmlOutput($actual);
                    $lisHtmlOutput = $liHtmlOutput . $lisHtmlOutput;
                }
                $htmlOutput .= $lisHtmlOutput;
                // close the breadcrumb
                $htmlOutput .= '</ol>';
                $htmlOutput .= '</nav>';
                return $htmlOutput;
            case self::TYPOGRAPHY_TYPE:

                try {
                    $requiredDepth = DataType::toInteger($tagAttributes->getValueAndRemoveIfPresent(PageSqlTreeListener::DEPTH));
                } catch (ExceptionBadArgument $e) {
                    LogUtility::error("We were unable to determine the depth attribute. The depth was set to 1. Error: {$e->getMessage()}");
                    $requiredDepth = 1;
                }
                if ($requiredDepth > 1) {
                    SnippetManager::getOrCreate()->attachCssInternalStyleSheetForSlot("breadcrumb-$type");
                }
                $htmlOutput = $tagAttributes->toHtmlEnterTag("span");
                $lisHtmlOutput = "";
                $actualDepth = 0;
                while ($actual = $actual->getParent()) {
                    $actualDepth = $actualDepth + 1;
                    $nameOrDefault = $actual->getNameOrDefault();
                    $liHtmlOutput = "<span class=\"breadcrumb-typography-item\">$nameOrDefault</span>";
                    $lisHtmlOutput = $liHtmlOutput . $lisHtmlOutput;
                    if ($actualDepth >= $requiredDepth) {
                        break;
                    }
                }
                $htmlOutput .= $lisHtmlOutput;
                $htmlOutput .= '</span>';
                return $htmlOutput;
            default:
                // internal error
                LogUtility::error("The breadcrumb type ($type) is unknown");
                return "";

        }


    }

    /**
     * @param PageFragment $page
     * @param bool $current
     * @param bool $link
     * @return string - the list item for the page
     */
    public static function getLiHtmlOutput(PageFragment $page, bool $current = false, bool $link = true): string
    {
        $liClass = "";
        $liArial = "";
        if ($current) {
            $liClass = " active";
            /**
             * https://www.w3.org/WAI/ARIA/apg/patterns/breadcrumb/
             * Applied to a link in the breadcrumb set to indicate that it represents the current page.
             */
            $liArial = " aria-current=\"page\"";
        }
        $liHtmlOutput = "<li class=\"breadcrumb-item$liClass\"$liArial>";

        if (FileSystems::exists($page->getPathObject()) && $current === false) {
            if ($link) {
                $liHtmlOutput .= $page->getHtmlAnchorLink(syntax_plugin_combo_breadcrumb::CANONICAL_HIERARCHICAL);
            } else {
                $liHtmlOutput .= $page->getNameOrDefault();
            }
        } else {
            $liHtmlOutput .= $page->getNameOrDefault();
        }
        $liHtmlOutput .= '</li>' . PHP_EOL;
        return $liHtmlOutput;
    }
}
