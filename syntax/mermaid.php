<?php

require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');

use ComboStrap\CallStack;
use ComboStrap\LogUtility;
use ComboStrap\Mermaid;
use ComboStrap\PluginUtility;
use ComboStrap\TagAttributes;



/**
 * Mermaid
 * https://mermaid-js.github.io/mermaid/
 */
class syntax_plugin_combo_mermaid extends DokuWiki_Syntax_Plugin
{

    const TAG = 'mermaid';

    const CANONICAL = Mermaid::CANONICAL;


    function getType(): string
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
        return array('baseonly', 'container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs');
    }

    function getSort(): int
    {
        return 199;
    }

    public function accepts($mode): bool
    {
        return syntax_plugin_combo_preformatted::disablePreformatted($mode);
    }


    function connectTo($mode)
    {


        $pattern = PluginUtility::getContainerTagPattern(self::TAG);
        $this->Lexer->addEntryPattern($pattern, $mode, PluginUtility::getModeFromTag($this->getPluginComponent()));


    }


    function postConnect()
    {

        $this->Lexer->addExitPattern('</' . self::TAG . '>', PluginUtility::getModeFromTag($this->getPluginComponent()));


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

        switch ($state) {

            case DOKU_LEXER_ENTER :
                $tagAttributes = TagAttributes::createFromTagMatch($match);
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $tagAttributes->toCallStackArray()
                );

            case DOKU_LEXER_UNMATCHED :

                return PluginUtility::handleAndReturnUnmatchedData(self::TAG, $match, $handler);


            case DOKU_LEXER_EXIT :
                $callStack = CallStack::createFromHandler($handler);
                $openingCall = $callStack->moveToPreviousCorrespondingOpeningCall();
                $attributes = $openingCall->getAttributes();
                $mermaidCode = "";
                $mermaidCodeFound = false;
                while ($actual = $callStack->next()) {
                    if (in_array($actual->getTagName(), syntax_plugin_combo_webcode::CODE_TAGS)) {
                        switch ($actual->getState()) {
                            case DOKU_LEXER_ENTER:
                                $actualCodeType = strtolower($actual->getType());
                                if ($actualCodeType === 'mermaid') {
                                    $mermaidCodeFound = true;
                                };
                                break;
                            case DOKU_LEXER_UNMATCHED:
                                if ($mermaidCodeFound) {
                                    $mermaidCode = $actual->getCapturedContent();
                                    break 2;
                                }
                                break;
                        }
                    }
                }
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::PAYLOAD => $mermaidCode,
                    PluginUtility::ATTRIBUTES => $attributes
                );


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
            $state = $data [PluginUtility::STATE];
            switch ($state) {
                case DOKU_LEXER_ENTER :
                    break;

                case DOKU_LEXER_UNMATCHED :

                    $renderer->doc .= PluginUtility::renderUnmatched($data);
                    break;

                case DOKU_LEXER_EXIT :
                    $mermaidCode = $data[PluginUtility::PAYLOAD];
                    if (!empty($mermaidCode)) {
                        Mermaid::addSnippet();
                        $renderer->doc .= Mermaid::enter($data[PluginUtility::ATTRIBUTES]);
                        $renderer->doc .= Mermaid::sanitize($mermaidCode);
                        $renderer->doc .= Mermaid::close();
                    } else {
                        LogUtility::msg("No code component with bnf grammar was found", LogUtility::LVL_MSG_WARNING, self::CANONICAL);
                    }
                    break;

            }
            return true;
        }


        // unsupported $mode
        return false;

    }


}

