<?php


use ComboStrap\Bootstrap;
use ComboStrap\CallStack;
use ComboStrap\PluginUtility;
use ComboStrap\Tag;
use ComboStrap\TagAttributes;


if (!defined('DOKU_INC')) die();

/**
 * Atx headings
 * https://github.github.com/gfm/#atx-headings
 * https://spec.commonmark.org/0.29/#atx-heading
 * http://www.aaronsw.com/2002/atx/intro
 */
class syntax_plugin_combo_headingatx extends DokuWiki_Syntax_Plugin
{


    const TAG = "headingatx";
    const LEVEL = 'level';
    const EXIT_PATTERN = "\r??\n";


    function getType()
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
        return array('formatting', 'substition', 'protected', 'disabled');
    }

    /**
     *
     * @return int
     */
    function getSort()
    {
        return 49;
    }


    function connectTo($mode)
    {

        $pattern = "^#{1,6}(?=.*" . self::EXIT_PATTERN . ")";
        $this->Lexer->addEntryPattern($pattern, $mode, PluginUtility::getModeForComponent($this->getPluginComponent()));
    }

    public function postConnect()
    {
        $this->Lexer->addExitPattern(self::EXIT_PATTERN, PluginUtility::getModeForComponent($this->getPluginComponent()));
    }


    function handle($match, $state, $pos, Doku_Handler $handler)
    {

        switch ($state) {


            case DOKU_LEXER_ENTER :

                $attributes = [syntax_plugin_combo_title::LEVEL => strlen(trim($match))];
                $callStack = CallStack::createFromHandler($handler);

                $parentTagName = "";
                $parent = $callStack->moveToParent();
                if ($parent != false) {
                    $parentTagName = $parent->getTagName();
                }

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $attributes,
                    PluginUtility::CONTEXT => $parentTagName
                );

            case DOKU_LEXER_UNMATCHED :

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::PAYLOAD => PluginUtility::htmlEncode($match),
                );

            case DOKU_LEXER_EXIT :

                $callStack = CallStack::createFromHandler($handler);
                $openingTag = $callStack->moveToPreviousCorrespondingOpeningCall();
                $openingTagAttributes = $openingTag->getAttributes();

                /**
                 * No link on image
                 */
                $callStack->processNoLinkOnImageToEndStack();

                /**
                 * Go to the parent
                 */
                $parent = $callStack->moveToParent();
                $parentTagName = "";
                // Heading may lived outside a component
                if ($parent != null) {
                    $parentTagName = $parent->getTagName();
                }

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::CONTEXT => $parentTagName,
                    PluginUtility::ATTRIBUTES => $openingTagAttributes

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
            $state = $data[PluginUtility::STATE];
            switch ($state) {

                case DOKU_LEXER_ENTER:
                    $parentTag = $data[PluginUtility::CONTEXT];
                    $attributes = $data[PluginUtility::ATTRIBUTES];
                    $tagAttributes = TagAttributes::createFromCallStackArray($attributes);
                    syntax_plugin_combo_title::renderOpeningTag($parentTag, $tagAttributes, $renderer);
                    break;
                case DOKU_LEXER_UNMATCHED:
                    $renderer->doc .= PluginUtility::renderUnmatched($data);
                    break;
                case DOKU_LEXER_EXIT:
                    $attributes = $data[PluginUtility::ATTRIBUTES];
                    $tagAttributes = TagAttributes::createFromCallStackArray($attributes);
                    $context = $data[PluginUtility::CONTEXT];
                    $renderer->doc .= syntax_plugin_combo_title::renderClosingTag($context, $tagAttributes);
                    break;

            }
        }
        // unsupported $mode
        return false;
    }


}

