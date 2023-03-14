<?php

namespace ComboStrap;


/**
 * Utility class for breadcrumb
 *
 *
 *
 * https://en.wikipedia.org/wiki/Breadcrumb_navigation#Websites
 */
class BreadcrumbTag
{
    /**
     * The type of breadcrumb
     *
     * Navigation is a markup that should be present
     * only once in a page
     */
    public const NAVIGATION_TYPE = "nav";
    /**
     * Typography is when a breadcrumb is used in a iterator
     * for instance as sub-title
     */
    public const TYPOGRAPHY_TYPE = "typo";
    public const MARKUP_BLOCK = "breadcrumb";
    public const LOGICAL_TAG = "breadcrumb";
    public const CANONICAL_HIERARCHICAL = "breadcrumb-hierarchical";
    public const DEPTH_ATTRIBUTE = "depth";
    const TYPES = [self::TYPOGRAPHY_TYPE, self::NAVIGATION_TYPE];
    const MARKUP_INLINE = "ibreadcrumb";

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
     */
    public static function toBreadCrumbHtml(TagAttributes $tagAttributes = null): string
    {

        if ($tagAttributes === null) {
            $tagAttributes = TagAttributes::createEmpty(self::MARKUP_BLOCK);
        }


        /**
         * Get the page
         */
        $path = ExecutionContext::getActualOrCreateFromEnv()->getContextPath();
        $actualPath = MarkupPath::createPageFromPathObject($path);

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

                $lisHtmlOutput = self::getLiHtmlOutput($actualPath, true);
                while (true) {
                    try {
                        $actualPath = $actualPath->getParent();
                    } catch (ExceptionNotFound $e) {
                        break;
                    }
                    $liHtmlOutput = self::getLiHtmlOutput($actualPath);
                    $lisHtmlOutput = $liHtmlOutput . $lisHtmlOutput;
                }
                $htmlOutput .= $lisHtmlOutput;
                // close the breadcrumb
                $htmlOutput .= '</ol>';
                $htmlOutput .= '</nav>';
                return $htmlOutput;
            case self::TYPOGRAPHY_TYPE:

                try {
                    $requiredDepth = DataType::toInteger($tagAttributes->getValueAndRemoveIfPresent(self::DEPTH_ATTRIBUTE));
                } catch (ExceptionBadArgument $e) {
                    LogUtility::error("We were unable to determine the depth attribute. The depth was set to 1. Error: {$e->getMessage()}");
                    $requiredDepth = 1;
                }
                if ($requiredDepth > 1) {
                    SnippetSystem::getFromContext()->attachCssInternalStyleSheet("breadcrumb-$type");
                }
                $htmlOutput = $tagAttributes->toHtmlEnterTag("span");
                $lisHtmlOutput = "";
                $actualDepth = 0;
                while (true) {
                    try {
                        $actualPath = $actualPath->getParent();
                    } catch (ExceptionNotFound $e) {
                        break;
                    }
                    $actualDepth = $actualDepth + 1;
                    $nameOrDefault = $actualPath->getNameOrDefault();
                    $liHtmlOutput = "<span class=\"breadcrumb-$type-item\">$nameOrDefault</span>";
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
     * @param MarkupPath $page
     * @param bool $current
     * @param bool $link
     * @return string - the list item for the page
     */
    public static function getLiHtmlOutput(MarkupPath $page, bool $current = false, bool $link = true): string
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
                $liHtmlOutput .= $page->getHtmlAnchorLink(self::CANONICAL_HIERARCHICAL);
            } else {
                $liHtmlOutput .= $page->getNameOrDefault();
            }
        } else {
            $liHtmlOutput .= $page->getNameOrDefault();
        }
        $liHtmlOutput .= '</li>';
        return $liHtmlOutput;
    }

    /**
     * Same rendering for typographic or navigational breadcrumb
     * @param TagAttributes $tagAttributes
     * @return string
     */
    public static function render(TagAttributes $tagAttributes): string
    {

        try {
            ExecutionContext::getActualOrCreateFromEnv()
                ->getExecutingMarkupHandler()
                ->getOutputCacheDependencies()
                // the output has the data from the requested page
                ->addDependency(MarkupCacheDependencies::REQUESTED_PAGE_DEPENDENCY)
                // the data from the requested page is dependent on the name, title or description of the page
                ->addDependency(MarkupCacheDependencies::PAGE_PRIMARY_META_DEPENDENCY);
        } catch (ExceptionNotFound $e) {
            // not a fetcher markup run
        }

        return BreadcrumbTag::toBreadCrumbHtml($tagAttributes);

    }

    public static function getDefaultBlockAttributes(): array
    {
        return [TagAttributes::TYPE_KEY => BreadcrumbTag::NAVIGATION_TYPE];
    }

    public static function getDefaultInlineAttributes(): array
    {
        return [TagAttributes::TYPE_KEY => BreadcrumbTag::TYPOGRAPHY_TYPE];
    }
}
