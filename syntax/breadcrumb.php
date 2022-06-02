<?php


use ComboStrap\Breadcrumb;
use ComboStrap\CacheDependencies;
use ComboStrap\CacheManager;
use ComboStrap\PageSqlTreeListener;
use ComboStrap\PluginUtility;
use ComboStrap\TagAttributes;


/**
 *
 */
class syntax_plugin_combo_breadcrumb extends DokuWiki_Syntax_Plugin
{

    const TAG = "breadcrumb";

    public const CANONICAL_HIERARCHICAL = "breadcrumb-hierarchical";

    const DEPTH_ATTRIBUTE = "depth";


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
        $default = [TagAttributes::TYPE_KEY => Breadcrumb::NAVIGATION_TYPE];
        $tagAttributes = TagAttributes::createFromTagMatch($match, $default);
        return array(
            PluginUtility::STATE => $state,
            PluginUtility::ATTRIBUTES => $tagAttributes->toCallStackArray()
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
        return self::processRendering($format, $data, $renderer);

    }

    /**
     * Same rendering for typographic or navigational breadcrumb
     * @param string $format
     * @param array $data
     * @param Doku_Renderer $renderer
     * @return bool
     */
    public static function processRendering(string $format, array $data, Doku_Renderer $renderer): bool
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
                $renderer->doc .= Breadcrumb::toBreadCrumbHtml($tagAttributes);
            }
            return true;
        }
        return false;
    }


}

