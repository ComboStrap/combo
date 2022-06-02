<?php


use ComboStrap\Breadcrumb;
use ComboStrap\PluginUtility;
use ComboStrap\TagAttributes;


/**
 * The difference with {@link syntax_plugin_combo_breadcrumb}
 * is the {@link syntax_plugin_combo_ibreadcrumb::getPType()}
 */
class syntax_plugin_combo_ibreadcrumb extends DokuWiki_Syntax_Plugin
{

    const TAG = "ibreadcrumb";


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
        return 'normal';
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

        $default = [
            TagAttributes::TYPE_KEY => Breadcrumb::TYPOGRAPHY_TYPE,
            syntax_plugin_combo_breadcrumb::DEPTH_ATTRIBUTE => 1
        ];
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
        return syntax_plugin_combo_breadcrumb::processRendering($format, $data, $renderer);
    }


}

