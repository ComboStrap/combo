<?php


// must be run within Dokuwiki
use ComboStrap\PluginUtility;

if (!defined('DOKU_INC')) die();

/**
 * Class syntax_plugin_combo_typo
 */
class syntax_plugin_combo_typo extends DokuWiki_Syntax_Plugin
{

    const TAG = "typo";

    /**
     * Syntax Type.
     *
     * Needs to return one of the mode types defined in {@link $PARSER_MODES} in parser.php
     * @see DokuWiki_Syntax_Plugin::getType()
     */
    function getType()
    {
        return 'formatting';
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
                $attributes = PluginUtility::getTagAttributes($match);
                if (isset($attributes["type"])){
                    $type = $attributes["type"];
                    if ($type == "lead"){
                        PluginUtility::addClass2Attributes("lead",$attributes);
                    }
                }
                $html = "<p ".PluginUtility::array2HTMLAttributes($attributes).">";
                return array(
                    PluginUtility::STATE=> $state,
                    PluginUtility::ATTRIBUTES => $attributes,
                    PluginUtility::PAYLOAD => $html
                );

            case DOKU_LEXER_UNMATCHED :
                return array(
                    PluginUtility::STATE=> $state,
                    PluginUtility::PAYLOAD=>PluginUtility::escape($match)
                );

            case DOKU_LEXER_EXIT :

                // Important otherwise we don't get an exit in the render
                return array(
                    PluginUtility::STATE=> $state,
                    PluginUtility::PAYLOAD=>"</p>");


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
            $state = $data[PluginUtility::STATE];
            switch ($state) {
                case DOKU_LEXER_ENTER :
                case DOKU_LEXER_UNMATCHED :
                    $renderer->doc .= $data[PluginUtility::PAYLOAD];
                    break;

                case DOKU_LEXER_EXIT :
                    $renderer->doc .= $data[PluginUtility::PAYLOAD].DOKU_LF;
                    break;
            }
            return true;
        }

        // unsupported $mode
        return false;
    }


}

