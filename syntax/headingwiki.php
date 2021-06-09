<?php

use ComboStrap\CallStack;
use ComboStrap\PluginUtility;
use ComboStrap\TagAttributes;


/**
 * Class headingwiki
 * Taking over {@link \dokuwiki\Parsing\ParserMode\Header}
 */
class syntax_plugin_combo_headingwiki extends DokuWiki_Syntax_Plugin
{

    /**
     * Header pattern that we expect ie  ==== Hello =====
     * Found in {@link \dokuwiki\Parsing\ParserMode\Header}
     * One modification is that it permits one `=` to get the h6
     */

    const ENTRY_PATTERN = '^[\s\t]*={1,6}(?=.*={1,6}\s*\r??\n)';
    const EXIT_PATTERN = '={1,6}\s*(?=\r??\n)';
    const TAG = "headingwiki";

    public function getSort()
    {
        /**
         * Less than 50 from
         * {@link \dokuwiki\Parsing\ParserMode\Header::getSort()}
         */
        return 49;
    }

    public function getType()
    {
        return syntax_plugin_combo_heading::SYNTAX_TYPE;
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
    public function getPType()
    {
        return syntax_plugin_combo_heading::SYNTAX_PTYPE;
    }

    public function connectTo($mode)
    {


        $this->Lexer->addEntryPattern(self::ENTRY_PATTERN, $mode, PluginUtility::getModeForComponent($this->getPluginComponent()));
    }

    public function postConnect()
    {
        $this->Lexer->addExitPattern(self::EXIT_PATTERN, PluginUtility::getModeForComponent($this->getPluginComponent()));
    }


    public function handle($match, $state, $pos, Doku_Handler $handler)
    {
        switch ($state) {

            case DOKU_LEXER_ENTER:
                /**
                 * Title regexp
                 */
                $attributes[syntax_plugin_combo_heading::LEVEL] = 7 - strlen(trim($match));
                $callStack = CallStack::createFromHandler($handler);

                $parentTag = $callStack->moveToParent();
                $context = syntax_plugin_combo_heading::getContext($parentTag);

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $attributes,
                    PluginUtility::CONTEXT => $context
                );
            case DOKU_LEXER_UNMATCHED :

                return PluginUtility::handleAndReturnUnmatchedData(self::TAG, trim($match), $handler);

            case DOKU_LEXER_EXIT :
                $callStack = CallStack::createFromHandler($handler);
                $openingTag = $callStack->moveToPreviousCorrespondingOpeningCall();
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $openingTag->getAttributes()
                );

        }
        return array();
    }

    public function render($format, Doku_Renderer $renderer, $data)
    {

        if ($format == "xhtml") {
            $state = $data[PluginUtility::STATE];
            switch ($state) {

                case DOKU_LEXER_ENTER:
                    /**
                     * The short title ie ( === title === )
                     * @var Doku_Renderer_xhtml $renderer
                     */
                    $callStackArray = $data[PluginUtility::ATTRIBUTES];
                    $tagAttributes = TagAttributes::createFromCallStackArray($callStackArray);
                    $context = $data[PluginUtility::CONTEXT];
                    syntax_plugin_combo_heading::renderOpeningTag($context, $tagAttributes, $renderer);
                    return true;
                case DOKU_LEXER_UNMATCHED:
                    $renderer->doc .= PluginUtility::renderUnmatched($data);
                    return true;
                case DOKU_LEXER_EXIT:
                    $callStackArray = $data[PluginUtility::ATTRIBUTES];
                    $tagAttributes = TagAttributes::createFromCallStackArray($callStackArray);
                    $renderer->doc .= syntax_plugin_combo_heading::renderClosingTag($tagAttributes);
                    return true;

            }
        }
        return false;
    }



}
