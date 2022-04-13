<?php

use ComboStrap\PluginUtility;
use ComboStrap\Site;


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
    function getPType(): string
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
         *
         * The rendering is used only when exporting to another format
         * (XML) to render on another platform such as mobile
         */

        if ($format === "xhtml") {
            /**
             *
             * The actual dokuwiki heading is to
             * add the edit section comment
             * when rendering the next heading
             * See {@link Doku_Renderer_xhtml::header()}
             * that calls {@link Doku_Renderer_xhtml::finishSectionEdit()}
             *
             * It means that the edit button of the previous section would be
             * between the next section and next heading
             */
            $wikiHeadingEnabled = syntax_plugin_combo_headingwiki::isEnabled();
            if (!$wikiHeadingEnabled) {
                return false;
            }
        }

        $state = $data[PluginUtility::STATE];
        switch ($state) {
            case DOKU_LEXER_ENTER :
                $renderer->doc .= '<section>' . DOKU_LF;
                break;
            case DOKU_LEXER_UNMATCHED :
                $renderer->doc .= PluginUtility::renderUnmatched($data);
                break;
            case DOKU_LEXER_EXIT :
                $renderer->doc .= '</section>' . DOKU_LF;
                break;
        }
        return true;

    }


}

