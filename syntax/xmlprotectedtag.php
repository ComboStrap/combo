<?php


use ComboStrap\CallStack;
use ComboStrap\ConsoleTag;
use ComboStrap\Dimension;
use ComboStrap\Html;
use ComboStrap\PipelineTag;
use ComboStrap\PluginUtility;
use ComboStrap\Prism;
use ComboStrap\TagAttributes;

require_once(__DIR__ . '/../ComboStrap/StringUtility.php');
require_once(__DIR__ . '/../ComboStrap/Prism.php');

if (!defined('DOKU_INC')) die();

/**
 *
 *
 */
class syntax_plugin_combo_xmlprotectedtag extends DokuWiki_Syntax_Plugin
{


    function getType()
    {
        /**
         * You can't write in a code block
         */
        return 'protected';
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
        /**
         * Should be less than the code syntax plugin
         * which is 200
         **/
        return 199;
    }

    const TAGS = [ConsoleTag::TAG, PipelineTag::TAG];

    function connectTo($mode)
    {

        foreach (self::TAGS as $tag) {
            $pattern = PluginUtility::getContainerTagPattern($tag);
            $this->Lexer->addEntryPattern($pattern, $mode, PluginUtility::getModeFromTag($this->getPluginComponent()));
        }


    }


    function postConnect()
    {
        foreach (self::TAGS as $tag) {
            $this->Lexer->addExitPattern('</' . $tag . '>', PluginUtility::getModeFromTag($this->getPluginComponent()));
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

        return syntax_plugin_combo_xmltag::handleStatic($match, $state, $pos, $handler);

    }

    /**
     * Render the output
     * @param string $format
     * @param Doku_Renderer $renderer
     * @param array $data - what the function handle() return'ed
     * @return boolean - rendered correctly? (however, returned value is not used at the moment)
     * @see DokuWiki_Syntax_Plugin::render()
     *
     */
    function render($format, Doku_Renderer $renderer, $data): bool
    {
        return syntax_plugin_combo_xmltag::renderStatic($format, $renderer, $data, $this);
    }


}

