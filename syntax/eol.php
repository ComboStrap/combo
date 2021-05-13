<?php


require_once(__DIR__ . "/../class/Analytics.php");
require_once(__DIR__ . "/../class/PluginUtility.php");
require_once(__DIR__ . "/../class/LinkUtility.php");
require_once(__DIR__ . "/../class/HtmlUtility.php");

use ComboStrap\Analytics;
use ComboStrap\PluginUtility;
use ComboStrap\Tag;
use ComboStrap\TagAttributes;

if (!defined('DOKU_INC')) die();

/**
 *
 * A EOL syntax to take over the {@link \dokuwiki\Parsing\ParserMode\Eol}
 *
 * The process is `eol` call are created in the instruction stack
 * and they are at the end transformed as paragraph via {@link \dokuwiki\Parsing\Handler\Block::process()}
 *
 *
 *
 * Basically, you get an new paragraph with a blank line or \\ : https://www.dokuwiki.org/faq:newlines
 *
 * !!!!!
 * Note: p_open call may appears when the {@link \ComboStrap\Syntax::getPType()} is set to `block` or `stack`
 * and the next call is not a block or a stack
 *
 * !!!!!
 */
class syntax_plugin_combo_eol extends DokuWiki_Syntax_Plugin
{

    const TAG = 'eol';



    /**
     * Syntax Type.
     *
     * Needs to return one of the mode types defined in $PARSER_MODES in parser.php
     * @see https://www.dokuwiki.org/devel:syntax_plugins#syntax_types
     */
    function getType()
    {
        return 'paragraphs';
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
        /**
         * !important!
         * The {@link \dokuwiki\Parsing\Handler\Block::process()}
         * will then not create an extra paragraph after it encounters a block
         */
        return 'block';
    }

    /**
     * @return array
     * Allow which kind of plugin inside
     *
     * No one of array('container', 'baseonly', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs')
     * because we manage self the content and we call self the parser
     */
    function getAllowedTypes()
    {
        /**
         * Not needed as we don't have any {@link syntax_plugin_combo_eol::connectTo()}
         */
        return array();
    }


    /**
     * @see Doku_Parser_Mode::getSort()
     * The mode with the lowest sort number will win out
     *
     */
    function getSort()
    {
        /**
         * Not really needed as we don't have any {@link syntax_plugin_combo_eol::connectTo()}
         *
         * Note: if we start to use it should be less than 370
         * Ie Less than {@link \dokuwiki\Parsing\ParserMode\Eol::getSort()}
         */
        return 369;
    }


    function connectTo($mode)
    {

        /**
         * No need to connect
         * This syntax plugin is added dynamically with the {@link Tag::processEolToEndStack()}
         * function
         */

    }


    /**
     * The handler for an internal link
     * based on `internallink` in {@link Doku_Handler}
     * The handler call the good renderer in {@link Doku_Renderer_xhtml} with
     * the parameters (ie for instance internallink)
     * @param string $match
     * @param int $state
     * @param int $pos
     * @param Doku_Handler $handler
     * @return array|bool
     */
    function handle($match, $state, $pos, Doku_Handler $handler)
    {

        /**
         * No need to handle,
         * there is no {@link syntax_plugin_combo_eol::connectTo() connection}
         */
        return true;


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
        // The data
        switch ($format) {
            case 'xhtml':

                /** @var Doku_Renderer_xhtml $renderer */
                $state = $data[PluginUtility::STATE];
                switch ($state) {
                    case DOKU_LEXER_ENTER:
                        $tagAttributes = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES]);
                        $renderer->doc .= $tagAttributes->toHtmlEnterTag("p");
                        break;
                    case DOKU_LEXER_SPECIAL:
                        $tagAttributes = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES]);
                        $renderer->doc .= $tagAttributes->toHtmlEnterTag("p");
                        $renderer->doc .= "</p>";
                        break;
                    case DOKU_LEXER_EXIT:
                        $renderer->doc .= "</p>";
                        break;
                }
                return true;

            case 'metadata':

                /** @var Doku_Renderer_metadata $renderer */


                return true;


            case Analytics::RENDERER_FORMAT:

                /**
                 * @var renderer_plugin_combo_analytics $renderer
                 */
                return true;

        }
        // unsupported $mode
        return false;
    }


}

