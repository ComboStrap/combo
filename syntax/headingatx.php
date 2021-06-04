<?php


use ComboStrap\Bootstrap;
use ComboStrap\Call;
use ComboStrap\CallStack;
use ComboStrap\LogUtility;
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

                // Determine the type
                $parent = $callStack->moveToParent();
                $headingType = syntax_plugin_combo_headingutil::getHeadingType($parent);
                switch ($headingType) {
                    case syntax_plugin_combo_headingutil::TYPE_TITLE:

                        $context = $parent->getTagName();
                        break;

                    case syntax_plugin_combo_headingutil::TYPE_OUTLINE:

                        $context = syntax_plugin_combo_headingutil::TYPE_OUTLINE;
                        break;

                    default:
                        LogUtility::msg("The heading type ($headingType) is unknown");
                        $context = "";
                        break;
                }

                /**
                 * The context is needed:
                 *   * to add the bootstrap class if it's a card title for instance
                 *   * and to delete {@link syntax_plugin_combo_headingutil::TYPE_OUTLINE} call
                 * in the {@link action_plugin_combo_headingpostprocess} (The rendering is done via Dokuwiki,
                 * see the exit processing for more info on the handling of outline headings)
                 *
                 */
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $attributes,
                    PluginUtility::CONTEXT => $context,
                    PluginUtility::POSITION => $pos
                );

            case DOKU_LEXER_UNMATCHED :

                return array(
                    PluginUtility::STATE => $state,
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
                 * needed for toc building in {@link Doku_Renderer_xhtml::header()}
                 * such as the {@link Doku_Renderer_xhtml::node}
                 * is private. We can't therefore plug in the toc
                 * without writing our own and we can't use two different headings
                 * in the same page
                 */
                $context = $openingTag->getContext();
                if ($context == syntax_plugin_combo_headingutil::TYPE_OUTLINE) {

                    // Outline heading
                    $callStack->moveToEnd();
                    $callStack->moveToPreviousCorrespondingOpeningCall();

                    /**
                     * Extract the heading content instructions
                     * Delete them and print them
                     */
                    $headingContentInstructions = [];
                    $textForId = "";
                    while ($actualCall = $callStack->next()) {
                        $headingContentInstructions[] = $actualCall->getCall();
                        if (
                            $actualCall->getTagName() == $this->getPluginComponent()
                            && $actualCall->getState() == DOKU_LEXER_UNMATCHED
                        ) {
                            $textForId .= $actualCall->getMatchedContent();
                        }
                        // Delete to not render it twice
                        $callStack->deleteActualCallAndPrevious();
                    }
                    $textForId = trim($textForId);
                    $headingContent = p_render('xhtml', $headingContentInstructions, $info);
                    $level = $openingTagAttributes[syntax_plugin_combo_title::LEVEL];

                    /**
                     * Code extracted and adapted from the end of {@link Doku_Handler::header()}
                     */
                    $headingPosition = $openingTag->getPosition();
                    if ($handler->getStatus('section')) {
                        $handler->addCall('section_close', array(), $headingPosition);
                    }
                    $handler->addCall('header', array($textForId, $level, $headingPosition), $headingPosition);
                    // This call was added to correct the content of the heading generated by Dokuwiki
                    // if html
                    $handler->addPluginCall(
                        PluginUtility::getComponentName(syntax_plugin_combo_headingutil::PLUGIN_COMPONENT),
                        [
                            PluginUtility::PAYLOAD => $headingContent,
                            PluginUtility::STATE => DOKU_LEXER_SPECIAL
                        ],
                        DOKU_LEXER_SPECIAL,
                        0,
                        ""
                    );
                    $handler->addCall('section_open', array($level), $headingPosition);
                    $handler->setStatus('section', true);


                }

                /**
                 * Context is needed to delete the {@link syntax_plugin_combo_headingutil::TYPE_OUTLINE}
                 * in {@link action_plugin_combo_headingpostprocess}
                 */
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $openingTagAttributes,
                    PluginUtility::CONTEXT => $context
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

        /**
         * Note: This code will render only {@link syntax_plugin_combo_headingutil::TYPE_TITLE title heading}
         *
         * {@link syntax_plugin_combo_headingutil::TYPE_OUTLINE Outline heading}} are printed by Dokuwiki
         * and the outline atx calls are deleted via postprocessing at {@link action_plugin_combo_headingpostprocess}
         * because we can't delete the last call (ie {@link DOKU_LEXER_EXIT}) on return
         * (We can delete the first (enter) from the last (exit) but never the last
         */
        if ($format == 'xhtml') {

            /** @var Doku_Renderer_xhtml $renderer */
            $state = $data[PluginUtility::STATE];
            switch ($state) {

                case DOKU_LEXER_ENTER:

                    $attributes = $data[PluginUtility::ATTRIBUTES];
                    $context = $data[PluginUtility::CONTEXT];
                    $tagAttributes = TagAttributes::createFromCallStackArray($attributes);
                    syntax_plugin_combo_headingutil::renderOpeningTag($context, $tagAttributes, $renderer);
                    return true;

                case DOKU_LEXER_UNMATCHED:

                    $renderer->doc .= PluginUtility::renderUnmatched($data);
                    return true;

                case DOKU_LEXER_EXIT:

                    $attributes = $data[PluginUtility::ATTRIBUTES];
                    $tagAttributes = TagAttributes::createFromCallStackArray($attributes);
                    $level = $tagAttributes->getValue(syntax_plugin_combo_title::LEVEL);
                    $renderer->doc .= "</h$level>".DOKU_LF;
                    return true;

            }
        }

        return false;
    }


}

