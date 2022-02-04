<?php

use ComboStrap\PluginUtility;


/**
 * Class syntax_plugin_combo_section
 * Implementation of a outline section
 *
 */
class syntax_plugin_combo_section extends DokuWiki_Syntax_Plugin
{

    const TAG = "section";

    /**
     * Syntax Type.
     *
     * Needs to return one of the mode types defined in $PARSER_MODES in parser.php
     * @see DokuWiki_Syntax_Plugin::getType()
     */
    function getType(): string
    {
        return 'container';
    }

    /**
     * How Dokuwiki will add P element
     *
     * * 'normal' - The plugin can be used inside paragraphs
     *  * 'block'  - Open paragraphs need to be closed before plugin output - block should not be inside paragraphs
     *  * 'stack'  - Special case. Plugin wraps other paragraphs. - Stacks can contain paragraphs
     *
     * @see DokuWiki_Syntax_Plugin::getPType()
     */
    function getPType()
    {
        return 'stack';
    }

    /**
     * @return array
     * Allow which kind of plugin inside
     *
     * Array('baseonly','container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs')
     *
     * Return an array of one or more of the mode types {@link $PARSER_MODES} in Parser.php
     */
    function getAllowedTypes(): array
    {
        return array('container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs');
    }


    function getSort(): int
    {
        return 201;
    }


    function connectTo($mode)
    {
        /**
         * Call is generated in {@link action_plugin_combo_headingpostprocessing}
         */
    }


    function postConnect()
    {

        /**
         * Call is generated in {@link action_plugin_combo_headingpostprocessing}
         */

    }

    function handle($match, $state, $pos, Doku_Handler $handler): array
    {

        return array();

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

        /**
         * As of today, we don't render on XHTML
         * because it would go in the way
         * of the {@link html_secedit()} function
         *
         * The rendering is used only when exporting to another format
         * (XML) to render on another platform such as mobile
         */
        if ($format === renderer_plugin_combo_xml::FORMAT) {

            /** @var renderer_plugin_combo_xml $renderer */
            $state = $data[PluginUtility::STATE];
            switch ($state) {
                case DOKU_LEXER_ENTER :
                    $renderer->doc .= '<section>'.DOKU_LF;
                    break;
                case DOKU_LEXER_UNMATCHED :
                    $renderer->doc .= PluginUtility::renderUnmatched($data);
                    break;
                case DOKU_LEXER_EXIT :
                    $renderer->doc .= '</section>'.DOKU_LF;
                    break;
            }
            return true;
        }

        // unsupported $mode
        return false;
    }


}

