<?php

use ComboStrap\PluginUtility;
use ComboStrap\TagAttribute\Boldness;


/**
 *
 * Taking over {@link \dokuwiki\Parsing\ParserMode\Formatting strong}
 *
 * * To disallow the preformatted mode
 * * To add the one line constraint
 *
 */
class syntax_plugin_combo_strong extends DokuWiki_Syntax_Plugin
{


    const TAG = "strong";
    /**
     * Explanation
     *   * `\n?` to take over the listblock {@link \dokuwiki\Parsing\ParserMode\Listblock}
     *
     *   * Only on one line
     *
     */
    const ENTRY_PATTERN = "[ \t]*\*\*(?=[^\n]*\*\*)(?!\n)";
    const EXIT_PATTERN = "\*\*";

    const CANONICAL = Boldness::CANONICAL;


    public function getSort(): int
    {
        /**
         * It's 9
         *
         * Before the 10 of {@link \dokuwiki\Parsing\ParserMode\Listblock}
         * Before the original of 70
         * {@link \dokuwiki\Parsing\ParserMode\Formatting}
         */
        return 9;
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
                $beforeSpaces = str_replace("**", "", $match);
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::PAYLOAD => $beforeSpaces
                );
            case DOKU_LEXER_UNMATCHED :

                return PluginUtility::handleAndReturnUnmatchedData(self::TAG, $match, $handler);

        }
        return array();
    }

    public function render($format, $renderer, $data): bool
    {

        switch ($format) {
            case "xhtml":
            {
                /**
                 * @var Doku_Renderer_xhtml $renderer
                 */
                $state = $data[PluginUtility::STATE];
                switch ($state) {

                    case DOKU_LEXER_ENTER:

                        $renderer->doc .= $data[PluginUtility::PAYLOAD] . "<strong>";
                        return true;
                    case DOKU_LEXER_UNMATCHED:
                        $renderer->doc .= PluginUtility::renderUnmatched($data);
                        return true;
                    case DOKU_LEXER_EXIT:
                        $renderer->doc .= "</strong>";
                        return true;

                }
                break;
            }
            case "metadata":
                /**
                 * For the automatic description, we render
                 * only the un-match
                 *
                 * @var Doku_Renderer_metadata $renderer
                 */
                $state = $data[PluginUtility::STATE];
                if ($state == DOKU_LEXER_UNMATCHED) {
                    $renderer->doc .= PluginUtility::renderUnmatched($data);
                }
                break;
        }

        return false;
    }


}
