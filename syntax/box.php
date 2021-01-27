<?php


// must be run within Dokuwiki
use ComboStrap\PluginUtility;

if (!defined('DOKU_INC')) die();

/**
 * Class syntax_plugin_combo_box
 * Implementation of a div
 *
 */
class syntax_plugin_combo_box extends DokuWiki_Syntax_Plugin
{

    const TAG = "box";

    /**
     * Syntax Type.
     *
     * Needs to return one of the mode types defined in $PARSER_MODES in parser.php
     * @see DokuWiki_Syntax_Plugin::getType()
     */
    function getType()
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
    function getAllowedTypes()
    {
        return array('container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs');
    }

    function getSort()
    {
        return 201;
    }


    function connectTo($mode)
    {

        $pattern = PluginUtility::getContainerTagPattern(self::TAG);
        $this->Lexer->addEntryPattern($pattern, $mode, PluginUtility::getModeForComponent($this->getPluginComponent()));
    }


    function postConnect()
    {

        $this->Lexer->addExitPattern('</' . self::TAG . '>', PluginUtility::getModeForComponent($this->getPluginComponent()));

    }

    function handle($match, $state, $pos, Doku_Handler $handler)
    {

        switch ($state) {

            case DOKU_LEXER_ENTER :
                $defaultAttributes = array();
                $inlineAttributes = PluginUtility::getTagAttributes($match);
                $attributes = PluginUtility::mergeAttributes($inlineAttributes, $defaultAttributes);
                return array($state, $attributes);

            case DOKU_LEXER_UNMATCHED :
                return array($state, $match);

            case DOKU_LEXER_EXIT :

                // Important otherwise we don't get an exit in the render
                return array($state, '');


        }
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
    function render($format, Doku_Renderer $renderer, $data)
    {
        if ($format == 'xhtml') {

            /** @var Doku_Renderer_xhtml $renderer */
            list($state, $payload) = $data;
            switch ($state) {
                case DOKU_LEXER_ENTER :
                    $attributes = $payload;
                    $renderer->doc .= '<div';
                    if (sizeof($attributes)>0) {
                        $renderer->doc .= ' ' . PluginUtility::array2HTMLAttributes($attributes);
                    }
                    $renderer->doc .= '>';
                    break;

                case DOKU_LEXER_UNMATCHED :
                    $renderer->doc .= $renderer->_xmlEntities($payload);
                    break;

                case DOKU_LEXER_EXIT :
                    $renderer->doc .= '</div>';
                    break;
            }
            return true;
        }

        // unsupported $mode
        return false;
    }


}

