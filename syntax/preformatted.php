<?php


use ComboStrap\PluginUtility;


/**
 * Overwrite {@link \dokuwiki\Parsing\ParserMode\Preformatted}
 */
if (!defined('DOKU_INC')) die();


class syntax_plugin_combo_preformatted extends DokuWiki_Syntax_Plugin
{

    const TAG='preformatted';
    /**
     * Enable or disable this component
     */
    const CONF_PREFORMATTED_ENABLE = 'preformattedEnable';

    /**
     * Syntax Type.
     *
     * Needs to return one of the mode types defined in $PARSER_MODES in parser.php
     * @see https://www.dokuwiki.org/devel:syntax_plugins#syntax_types
     * @see DokuWiki_Syntax_Plugin::getType()
     */
    function getType()
    {
        return 'container';
    }

    /**
     * How DokuWiki will add P element
     *
     *  * 'normal' - The plugin can be used inside paragraphs
     *  * 'block'  - Open paragraphs need to be closed before plugin output - block should not be inside paragraphs
     *  * 'stack'  - Special case. Plugin wraps other paragraphs. - Stacks can contain paragraphs
     *
     * @see DokuWiki_Syntax_Plugin::getPType()
     */
    function getPType()
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
    function getAllowedTypes()
    {
        return array('baseonly', 'container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs');
    }

    function getSort()
    {
        /**
         * Should be less than the preformatted mode
         * which is 20
         **/
        return 19;
    }


    function connectTo($mode)
    {

        if (!$this->getConf(self::CONF_PREFORMATTED_ENABLE)) {

            $patterns = array('\n  (?![\*\-])', '\n\t(?![\*\-])');
            foreach ($patterns as $pattern) {
                $this->Lexer->addEntryPattern($pattern, $mode, PluginUtility::getModeForComponent($this->getPluginComponent()));
            }

        }


    }


    function postConnect()
    {
        $patterns = array('\n  ', '\n\t');
        foreach ($patterns as $pattern) {
            $this->Lexer->addExitPattern($pattern, PluginUtility::getModeForComponent($this->getPluginComponent()));
        }
    }

    /**
     *
     * The handle function goal is to parse the matched syntax through the pattern function
     * and to return the result for use in the renderer
     * This result is always cached until the page is modified.
     * @param string $match
     * @param int $state
     * @param int $pos - byte position in the original source file
     * @param Doku_Handler $handler
     * @return array|bool
     * @see DokuWiki_Syntax_Plugin::handle()
     *
     */
    function handle($match, $state, $pos, Doku_Handler $handler)
    {

        return array($match);

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
        $renderer->doc .= trim($data[0]);
        return false;
    }


}

