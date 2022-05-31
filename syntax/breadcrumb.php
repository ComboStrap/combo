<?php


use ComboStrap\CacheDependencies;
use ComboStrap\CacheManager;
use ComboStrap\ContextManager;
use ComboStrap\DataType;
use ComboStrap\DokuPath;
use ComboStrap\ExceptionBadArgument;
use ComboStrap\ExceptionCompile;
use ComboStrap\FileSystems;
use ComboStrap\LogUtility;
use ComboStrap\MarkupRef;
use ComboStrap\Page;
use ComboStrap\PagePath;
use ComboStrap\PageSqlTreeListener;
use ComboStrap\PluginUtility;
use ComboStrap\Site;
use ComboStrap\TagAttributes;


/**
 *
 */
class syntax_plugin_combo_breadcrumb extends DokuWiki_Syntax_Plugin
{

    const TAG = "breadcrumb";

    public const CANONICAL_HIERARCHICAL = "breadcrumb-hierarchical";

    /**
     * The type of breadcrumb
     *
     * Navigation is a markup that should be present
     * only once in a page
     */
    const NAVIGATION_TYPE = "navigation";
    /**
     * Typography is when a breadcrumb is used in a iterator
     * for instance as sub-title
     */
    const TYPOGRAPHY_TYPE = "typography";


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
            $tagAttributes = TagAttributes::createEmpty(self::TAG);
        }


        try {
            $requiredDepth = DataType::toInteger($tagAttributes->getValueAndRemoveIfPresent(PageSqlTreeListener::DEPTH));
        } catch (ExceptionBadArgument $e) {
            LogUtility::error("We were unable to get the depth attribute. Error: {$e->getMessage()}");
            $requiredDepth = null;
        }

        /**
         * Get the page
         */
        $path = ContextManager::getOrCreate()->getAttribute(PagePath::PROPERTY_NAME);
        if ($path === null) {
            // should never happen but yeah
            LogUtility::error("Internal Error: The page context was not set. Defaulting to the requested page", self::CANONICAL_HIERARCHICAL);
            $actual = Page::createPageFromRequestedPage();
        } else {
            $actual = Page::createPageFromQualifiedPath($path);
        }

        /**
         * Type
         */
        if ($requiredDepth === null) {
            /**
             * TODO: we should not be able to use it in second time
             */
            $type = self::NAVIGATION_TYPE;
        } else {
            $type = self::TYPOGRAPHY_TYPE;
        }

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
                while ($actual = $actual->getParentPage()) {
                    $liHtmlOutput = self::getLiHtmlOutput($actual);
                    $lisHtmlOutput = $liHtmlOutput . $lisHtmlOutput;
                }
                $htmlOutput .= $lisHtmlOutput;
                // close the breadcrumb
                $htmlOutput .= '</ol>';
                $htmlOutput .= '</nav>';
                break;
            default:
                $tagAttributes->addClassName("breadcrumb");
                $htmlOutput = $tagAttributes->toHtmlEnterTag("ol");
                $lisHtmlOutput = "";
                $actualDepth = 0;
                while ($actual = $actual->getParentPage()) {
                    $actualDepth = $actualDepth + 1;
                    $liHtmlOutput = self::getLiHtmlOutput($actual, false, false);
                    $lisHtmlOutput = $liHtmlOutput . $lisHtmlOutput;
                    if ($actualDepth >= $requiredDepth) {
                        break;
                    }
                }
                $htmlOutput .= $lisHtmlOutput;
                $htmlOutput .= '</ol>' . PHP_EOL;

        }


        return $htmlOutput;

    }

    /**
     * @param Page $page
     * @param bool $current
     * @return string - the list item for the page
     */
    private static function getLiHtmlOutput(Page $page, bool $current = false, bool $link = true): string
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

        if (FileSystems::exists($page->getPath()) && $current === false) {
            if ($link) {
                $liHtmlOutput .= $page->getHtmlAnchorLink(self::CANONICAL_HIERARCHICAL);
            } else {
                $liHtmlOutput .= $page->getNameOrDefault();
            }
        } else {
            $liHtmlOutput .= $page->getNameOrDefault();
        }
        $liHtmlOutput .= '</li>' . PHP_EOL;
        return $liHtmlOutput;
    }

    /**
     * Syntax Type.
     *
     * Needs to return one of the mode types defined in {@link $PARSER_MODES} in parser.php
     * @see DokuWiki_Syntax_Plugin::getType()
     */
    function getType(): string
    {
        return 'substition';
    }

    /**
     * How Dokuwiki will add P element
     *
     *  * 'normal' - The plugin can be used inside paragraphs
     *  * 'block'  - Open paragraphs need to be closed before plugin output - block should not be inside paragraphs
     *  * 'stack'  - Special case. Plugin wraps other paragraphs. - Stacks can contain paragraphs
     *
     * @see DokuWiki_Syntax_Plugin::getPType()
     */
    function getPType(): string
    {
        return 'block';
    }

    /**
     * @return array
     * Allow which kind of plugin inside
     *
     * No one of array('baseonly','container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs')
     * because we manage self the content and we call self the parser
     *
     * Return an array of one or more of the mode types {@link $PARSER_MODES} in Parser.php
     */
    function getAllowedTypes(): array
    {
        return array();
    }


    function getSort(): int
    {
        return 201;
    }


    function connectTo($mode)
    {

        $pattern = PluginUtility::getEmptyTagPattern(self::TAG);
        $this->Lexer->addSpecialPattern($pattern, $mode, PluginUtility::getModeFromTag($this->getPluginComponent()));

    }


    function handle($match, $state, $pos, Doku_Handler $handler): array
    {

        $attributes = PluginUtility::getTagAttributes($match);
        return array(
            PluginUtility::STATE => $state,
            PluginUtility::ATTRIBUTES => $attributes
        );

    }

    /**
     * Render the output
     * @param string $format
     * @param Doku_Renderer $renderer
     * @param array $data - what the function handle() return'ed
     * @return boolean - rendered correctly? (however, returned value is not used at the moment)
     * @see DokuWiki_Syntax_Plugin::render()
     *
     *
     */
    function render($format, Doku_Renderer $renderer, $data): bool
    {
        if ($format === 'xhtml') {
            $state = $data[PluginUtility::STATE];
            if ($state === DOKU_LEXER_SPECIAL) {
                $cacheManager = CacheManager::getOrCreate();
                // the output has the data from the requested page
                $cacheManager->addDependencyForCurrentSlot(CacheDependencies::REQUESTED_PAGE_DEPENDENCY);
                // the data from the requested page is dependent on the name, title or description of the page
                $cacheManager->addDependencyForCurrentSlot(CacheDependencies::PAGE_PRIMARY_META_DEPENDENCY);

                $tagAttributes = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES], self::TAG);
                $renderer->doc .= self::toBreadCrumbHtml($tagAttributes);
            }
            return true;
        }
        return false;

    }


}

