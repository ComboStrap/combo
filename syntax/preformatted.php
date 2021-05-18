<?php


use ComboStrap\PluginUtility;


/**
 * Overwrite {@link \dokuwiki\Parsing\ParserMode\Preformatted}
 */
if (!defined('DOKU_INC')) die();

/**
 *
 * Preformatted shows a block of text as code via space at the beginning of the line
 *
 * It is the same as <a href="https://github.github.com/gfm/#indented-code-blocks">indented-code-blocks</a>
 * but with 2 characters in place of 4
 *
 * This component is used to:
 *   * showcase preformatted as {@link \ComboStrap\Prism} component
 *   * disable preformatted mode via the function {@link syntax_plugin_combo_preformatted::disablePreformatted()}
 * used in other HTML super set syntax component to disable this behavior
 *
 *
 */
class syntax_plugin_combo_preformatted extends DokuWiki_Syntax_Plugin
{

    const TAG = 'preformatted';


    const CONF_PREFORMATTED_ENABLE = "preformattedEnable";

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
        return array('baseonly', 'container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs');
    }

    function getSort()
    {
        /**
         * Should be less than the preformatted mode
         * which is 20
         * From {@link \dokuwiki\Parsing\ParserMode\Preformatted::getSort()}
         **/
        return 19;
    }


    function connectTo($mode)
    {

        if ($this->getConf(self::CONF_PREFORMATTED_ENABLE, 1)) {

            /**
             * From {@link \dokuwiki\Parsing\ParserMode\Preformatted}
             */
            $patterns = array('\n  (?![\*\-])', '\n\t(?![\*\-])');
            foreach ($patterns as $pattern) {
                $this->Lexer->addEntryPattern($pattern, $mode, PluginUtility::getModeForComponent($this->getPluginComponent()));
            }
            $this->Lexer->addPattern('\n  ', PluginUtility::getModeForComponent($this->getPluginComponent()));
            $this->Lexer->addPattern('\n\t', PluginUtility::getModeForComponent($this->getPluginComponent()));

        }

    }


    function postConnect()
    {
        /**
         * From {@link \dokuwiki\Parsing\ParserMode\Preformatted}
         */
        $this->Lexer->addExitPattern('\n', PluginUtility::getModeForComponent($this->getPluginComponent()));

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

        // return PluginUtility::handleAndReturnUnmatchedData(self::TAG, $match, $handler);
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
        if ($format == "xhtml") {
            $renderer->doc .= PluginUtility::renderUnmatched($data);
        }
        return false;
    }

    /**
     * Utility function to disable preformatted
     * @param $mode
     * @return bool
     */
    public
    static function disablePreformatted($mode)
    {
        /**
         * Disable {@link \dokuwiki\Parsing\ParserMode\Preformatted}
         * and this syntax
         */
        if (
            $mode == 'preformatted'
            ||
            $mode == PluginUtility::getModeForComponent(syntax_plugin_combo_preformatted::TAG)
        ) {
            return false;
        } else {
            return true;
        }
    }

}

