<?php


use ComboStrap\Bootstrap;
use ComboStrap\Call;
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
    const OUTLINE = "outline";


    public static function toc(Doku_Renderer_xhtml $renderer, $text, $level)
    {
        $hid = $renderer->_headerToLink($text, true);

        //only add items within configured levels
        $renderer->toc_additem($hid, $text, $level);
    }


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

                $parent = $callStack->moveToParent();
                if ($parent != false && $parent->getComponentName() != "section_open") {

                    // Context
                    $context = $parent->getTagName();


                } else {

                    // Outline heading
                    $context = self::OUTLINE;
                }

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $attributes,
                    PluginUtility::CONTEXT => $context
                );

            case DOKU_LEXER_UNMATCHED :

                $callStack = CallStack::createFromHandler($handler);
                $parent = $callStack->moveToParent();

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::CONTEXT => $parent->getContext(),
                    PluginUtility::PAYLOAD => $match,
                );

            case DOKU_LEXER_EXIT :

                $callStack = CallStack::createFromHandler($handler);

                /**
                 * Get the level (ie in the attributes)
                 */
                $openingTag = $callStack->moveToPreviousCorrespondingOpeningCall();
                $openingTagAttributes = $openingTag->getAttributes();

                /**
                 * If it's an outline
                 * We render the input
                 * and we create the call
                 * We do this because the outline element
                 * needed for toc building such as the {@link Doku_Renderer_xhtml::node}
                 * is private. We can't therefore plug in in the toc
                 * without writing our own.
                 * One limitation is that the TOC can be only in HTML
                 */
                $context = $openingTag->getContext();
                if ($context == self::OUTLINE) {

                    // Outline heading
                    $context = self::OUTLINE;
                    $callStack->moveToEnd();
                    $callStack->moveToPreviousCorrespondingOpeningCall();

                    /**
                     * Extract the heading content instructions
                     * and print them
                     */
                    $headingContentInstructions = [];
                    $textForId = "";
                    while ($actualCall = $callStack->next()) {
                        $headingContentInstructions[] = $actualCall->getCall();
                        /**
                         * Unmatched content should not be printed twice
                         */
                        if (
                            $actualCall->getTagName() == $this->getPluginComponent()
                            && $actualCall->getState() == DOKU_LEXER_UNMATCHED
                        ) {
                            $textForId .= $actualCall->getMatchedContent();
                        }
                        $callStack->deleteActualCallAndPrevious();
                    }
                    $textForId = trim($textForId);
                    $headingContent = p_render('xhtml', $headingContentInstructions, $info);
                    $level = $openingTagAttributes[syntax_plugin_combo_title::LEVEL];

                    /**
                     * Code extracted from the end of {@link Doku_Handler::header()}
                     */
                    if ($handler->getStatus('section')) {
                        $handler->addCall('section_close', array(), $pos);
                    }
                    $handler->addCall('header', array($textForId, $level, $pos), $pos);
                    $handler->addPluginCall(
                        PluginUtility::getComponentName(self::TAG),
                        [
                            PluginUtility::PAYLOAD => $headingContent,
                            PluginUtility::STATE => DOKU_LEXER_SPECIAL
                        ],
                        DOKU_LEXER_SPECIAL,
                        0,
                        ""
                    );
                    $handler->addCall('section_open', array($level), $pos);
                    $handler->setStatus('section', true);


                }

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::CONTEXT => $context,
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
                    $context = $data[PluginUtility::CONTEXT];
                    if ($context != self::OUTLINE) {

                        $attributes = $data[PluginUtility::ATTRIBUTES];
                        $tagAttributes = TagAttributes::createFromCallStackArray($attributes);
                        syntax_plugin_combo_title::renderOpeningTag($context, $tagAttributes, $renderer);

                    }

                    break;
                case DOKU_LEXER_UNMATCHED:

                    $renderer->doc .= PluginUtility::renderUnmatched($data);

                    break;
                case DOKU_LEXER_SPECIAL:

                    $renderer->doc .= $data[PluginUtility::PAYLOAD] . "</h1>";

                    break;
                case DOKU_LEXER_EXIT:

                    $context = $data[PluginUtility::CONTEXT];
                    if ($context != self::OUTLINE) {
                        $attributes = $data[PluginUtility::ATTRIBUTES];
                        $tagAttributes = TagAttributes::createFromCallStackArray($attributes);
                        $renderer->doc .= syntax_plugin_combo_title::renderClosingTag($context, $tagAttributes, $renderer);
                    }
                    break;

            }
        }
        // unsupported $mode
        return false;
    }


}

