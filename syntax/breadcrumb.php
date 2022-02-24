<?php


use ComboStrap\CacheDependencies;
use ComboStrap\CacheManager;
use ComboStrap\DokuPath;
use ComboStrap\ExceptionCombo;
use ComboStrap\FileSystems;
use ComboStrap\MarkupRef;
use ComboStrap\Page;
use ComboStrap\PluginUtility;
use ComboStrap\Site;


/**
 *
 */
class syntax_plugin_combo_breadcrumb extends DokuWiki_Syntax_Plugin
{

    const TAG = "breadcrumb";

    public const CANONICAL_HIERARCHICAL = "breadcrumb-hierarchical";
    const HTML_CLASS = self::CANONICAL_HIERARCHICAL . "-combo";


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
     * @return string
     */
    public static function toBreadCrumbHtml(): string
    {


        // print intermediate namespace links


        /**
         * https://www.w3.org/TR/wai-aria-practices/examples/breadcrumb/index.html
         * Arial-label Provides a label that describes the type of navigation provided in the nav element.
         */
        $class = self::HTML_CLASS;
        $htmlOutput = "<nav aria-label=\"Hierarchical breadcrumb\" class=\"$class\">" . PHP_EOL;
        $htmlOutput .= '<ol class="breadcrumb">' . PHP_EOL;


        $actual = Page::createPageFromRequestedPage();
        $lisHtmlOutput = self::getLiHtmlOutput($actual, true);
        while ($actual = $actual->getParentPage()) {
            $liHtmlOutput = self::getLiHtmlOutput($actual);
            $lisHtmlOutput = $liHtmlOutput . $lisHtmlOutput;
        }
        $htmlOutput .= $lisHtmlOutput;
        // close the breadcrumb
        $htmlOutput .= '</ol>' . PHP_EOL;
        $htmlOutput .= '</nav>' . PHP_EOL;

        return $htmlOutput;

    }

    /**
     * @param Page $page
     * @param bool $current
     * @return string - the list item for the page
     */
    private static function getLiHtmlOutput(Page $page, bool $current = false): string
    {
        $liClass = "";
        $liArial = "";
        if ($current) {
            $liClass = " active";
            /**
             * https://www.w3.org/TR/wai-aria-practices/examples/breadcrumb/index.html
             * Applied to a link in the breadcrumd set to indicate that it represents the current page.
             */
            $liArial = " aria-current=\"page\"";
        }
        $liHtmlOutput = "<li class=\"breadcrumb-item$liClass\"$liArial>";

        if (FileSystems::exists($page->getPath()) && $current === false) {
            $liHtmlOutput .= $page->getHtmlAnchorLink(self::CANONICAL_HIERARCHICAL);
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
        switch ($format) {

            case 'xhtml':
                $state = $data[PluginUtility::STATE];
                if ($state === DOKU_LEXER_SPECIAL) {
                    $cacheManager = CacheManager::getOrCreate();
                    // the output has the data from the requested page
                    $cacheManager->addDependencyForCurrentSlot(CacheDependencies::REQUESTED_PAGE_DEPENDENCY);
                    // the data from the requested page is dependent on the name, title or description of the page
                    $cacheManager->addDependencyForCurrentSlot(CacheDependencies::PAGE_PRIMARY_META_DEPENDENCY);
                    $renderer->doc .= self::toBreadCrumbHtml();
                }
                return true;
        }
        return false;

    }


}

