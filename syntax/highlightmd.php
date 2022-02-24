<?php

use ComboStrap\PluginUtility;


/**
 *
 * Known as code
 * https://spec.commonmark.org/0.30/#code-spans
 *
 * note supported but specific highlight is done with two `==`
 * in some processor
 * https://www.markdownguide.org/extended-syntax/#highlight
 *
 */
class syntax_plugin_combo_highlightmd extends DokuWiki_Syntax_Plugin
{


    const TAG = "highlightmd";
    // Only on one line

    /**
     * Only on one line, otherwise
     * if it's not closed, it will eat all other syntaqx
     */
    const ENTRY_PATTERN = "`[^\n]*(?=`)(?!\n)";

    const EXIT_PATTERN = "`";
    const CANONICAL = self::TAG;

    public function getSort(): int
    {

        return 200;
    }

    public function getType(): string
    {
        return 'formatting';
    }


    /**
     *
     * How Dokuwiki will add P element
     *
     *  * 'normal' - The plugin can be used inside paragraphs (inline)
     *  * 'block'  - Open paragraphs need to be closed before plugin output - block should not be inside paragraphs
     *  * 'stack'  - Special case. Plugin wraps other paragraphs. - Stacks can contain paragraphs
     *
     * @see DokuWiki_Syntax_Plugin::getPType()
     *
     * This is the equivalent of inline or block for css
     */
    public function getPType(): string
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
        return array('formatting', 'substition', 'protected', 'disabled');
    }


    public function connectTo($mode)
    {

        $this->Lexer->addEntryPattern(self::ENTRY_PATTERN, $mode, PluginUtility::getModeFromTag($this->getPluginComponent()));


    }

    public function postConnect()
    {

        $this->Lexer->addExitPattern(self::EXIT_PATTERN, PluginUtility::getModeFromTag($this->getPluginComponent()));

    }


    /**
     * Handle the syntax
     *
     * At the end of the parser, the `section_open` and `section_close` calls
     * are created in {@link action_plugin_combo_headingpostprocessing}
     * and the text inside for the toc is captured
     *
     * @param string $match
     * @param int $state
     * @param int $pos
     * @param Doku_Handler $handler
     * @return array
     */
    public function handle($match, $state, $pos, Doku_Handler $handler): array
    {
        switch ($state) {

            case DOKU_LEXER_EXIT:
            case DOKU_LEXER_ENTER:

                $content = substr($match, 1);
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::PAYLOAD => $content,
                );
            case DOKU_LEXER_UNMATCHED :

                return PluginUtility::handleAndReturnUnmatchedData(self::TAG, $match, $handler);

        }
        return array();
    }

    public function render($format, Doku_Renderer $renderer, $data): bool
    {

        switch ($format) {
            case "xhtml":
                /**
                 * @var Doku_Renderer_xhtml $renderer
                 */
                $state = $data[PluginUtility::STATE];
                switch ($state) {

                    case DOKU_LEXER_ENTER:
                        $renderer->doc .= syntax_plugin_combo_highlightwiki::getOpenTagHighlight(self::TAG) . $data[PluginUtility::PAYLOAD];
                        return true;
                    case DOKU_LEXER_UNMATCHED:
                        $renderer->doc .= PluginUtility::renderUnmatched($data);
                        return true;
                    case DOKU_LEXER_EXIT:
                        $renderer->doc .= "</mark>";
                        return true;

                }
                break;
            case "metadata":
                /**
                 * @var Doku_Renderer_metadata $renderer
                 */
                $state = $data[PluginUtility::STATE];
                switch ($state) {
                    case DOKU_LEXER_ENTER:
                        $renderer->doc .= $data[PluginUtility::PAYLOAD];
                        return true;
                    case DOKU_LEXER_UNMATCHED:
                        $renderer->doc .= PluginUtility::renderUnmatched($data);
                }
                break;
        }

        return false;
    }

    /**
     * @param $match
     * @return int
     */
    public
    function getLevelFromMatch($match)
    {
        return 7 - strlen(trim($match));
    }


    private
    function enableWikiHeading($mode)
    {


        /**
         * Basically all mode that are not `base`
         * To not take the dokuwiki heading
         */
        if (!(in_array($mode, ['base', 'header', 'table']))) {
            return true;
        } else {
            return PluginUtility::getConfValue(self::CONF_WIKI_HIGHLIGHT_ENABLE, self::CONF_DEFAULT_WIKI_ENABLE_VALUE);
        }


    }


}
