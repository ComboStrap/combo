<?php

namespace ComboStrap;


use syntax_plugin_combo_heading;
use syntax_plugin_combo_headingatx;
use syntax_plugin_combo_headingwiki;
use syntax_plugin_combo_media;

class Outline
{


    private TreeNode $tree;

    public function __construct(CallStack $callStack)
    {

        $this->process($callStack);
    }

    public static function createFromCallStack(CallStack $callStack): Outline
    {
        return new Outline($callStack);
    }

    private function process(CallStack $callStack)
    {

        $callStack->moveToStart();

        $this->tree = TreeNode::createTreeRoot();
        $this->tree->setContent(OutlineNode::create());

        /**
         * Processing variable about the context
         */
        $actualHeadingParsingState = null; // null if root, enter if we have entered a heading
        $actualSectionState = null; // enter if we have created a section
        $headingEnterCall = null; // the enter call

        $headingText = ""; // text only content in the heading
        $previousHeadingLevel = 0; // A pointer to the actual heading level
        $headingComboCounter = 0; // The number of combo heading found (The first one that is not the first one should close)
        $headingTotalCounter = 0; // The number of combo heading found (The first one that is not the first one should close)

        $actualLastPosition = 0;

        $actualTreeNode = $this->tree;
        while ($actualCall = $callStack->next()) {

            $tagName = $actualCall->getTagName();


            /**
             * Track the position in the file
             */
            $currentLastPosition = $actualCall->getLastMatchedCharacterPosition();
            if ($currentLastPosition > $actualLastPosition) {
                // the position in the stack is not always good
                $actualLastPosition = $currentLastPosition;
            }

            /**
             * Enter new section ?
             */
            switch ($tagName) {
                case syntax_plugin_combo_headingatx::TAG:
                    $actualCall->setState(DOKU_LEXER_ENTER);
                    $actualHeadingParsingState = DOKU_LEXER_ENTER;
                    $headingEnterCall = $callStack->getActualCall();
                    $headingComboCounter++;
                    $headingTotalCounter++;

                    continue 2;
                case syntax_plugin_combo_heading::TAG:
                case syntax_plugin_combo_headingwiki::TAG:
                    if ($actualCall->getState() == DOKU_LEXER_ENTER) {
                        $actualHeadingParsingState = DOKU_LEXER_ENTER;
                        $headingEnterCall = $callStack->getActualCall();
                        $headingComboCounter++;
                        $headingTotalCounter++;
                        $previousHeadingLevel = $headingEnterCall->getAttribute("level");
                        continue 2;
                    }
                    break;
                case "header":
                    $headingTotalCounter++;
                    break;
            }

            /**
             * If we are not in a heading, this is a root instructions
             */
            if ($actualHeadingParsingState === null) {
                /**
                 * @var OutlineNode $outlineNode
                 */
                $outlineNode = $actualTreeNode->getContent();
                $outlineNode->addCall($actualCall);
            }

            /**
             * Close and Inside the heading description
             */
            if ($actualHeadingParsingState === DOKU_LEXER_ENTER) {

                switch ($actualCall->getTagName()) {

                    case syntax_plugin_combo_heading::TAG:
                    case syntax_plugin_combo_headingwiki::TAG:
                        if ($actualCall->getState() == DOKU_LEXER_EXIT) {
                            // close
                        } else {
                            // unmatched
                            self::addToTextHeading($headingText, $actualCall);
                        }
                        continue 2;

                    case "internalmedia":
                        // no link for media in heading
                        $actualCall->getInstructionCall()[1][6] = MediaLink::LINKING_NOLINK_VALUE;
                        continue 2;

                    case "header":
                        if (PluginUtility::getConfValue(syntax_plugin_combo_headingwiki::CONF_WIKI_HEADING_ENABLE, syntax_plugin_combo_headingwiki::CONF_DEFAULT_WIKI_ENABLE_VALUE) == 1) {
                            LogUtility::msg("The combo heading wiki is enabled, we should not see `header` calls in the call stack");
                        }
                        break;

                    case syntax_plugin_combo_media::TAG:
                        // no link for media in heading
                        $actualCall->addAttribute(MediaLink::LINKING_KEY, MediaLink::LINKING_NOLINK_VALUE);
                        continue 2;

                    default:
                        self::addToTextHeading($headingText, $actualCall);
                        continue 2;

                    case "p":
                        if ($headingEnterCall->getTagName() == syntax_plugin_combo_headingatx::TAG) {

                            /**
                             * Delete the p_enter / close
                             */
                            $callStack->deleteActualCallAndPrevious();

                            /**
                             * If this was a close tag
                             */
                            if ($actualCall->getComponentName() == "p_close") {

                                $callStack->next();

                                /**
                                 * Create the exit call
                                 * and open the section
                                 * Code extracted and adapted from the end of {@link Doku_Handler::header()}
                                 */
                                $callStack->insertBefore(
                                    Call::createComboCall(
                                        syntax_plugin_combo_headingatx::TAG,
                                        DOKU_LEXER_EXIT,
                                        $headingEnterCall->getAttributes()
                                    )
                                );
                                $callStack->previous();


                            }

                        }
                        continue 2;

                }


            }
            /**
             * when a heading of dokuwiki is mixed with
             * an atx heading, there is already a section close
             * at the end or in the middle
             */
            if ($actualCall->getComponentName() == \action_plugin_combo_headingpostprocessing::EDIT_SECTION_CLOSE) {
                $actualSectionState = DOKU_LEXER_EXIT;
            }

        }
    }

    public function getTree(): TreeNode
    {
        return $this->tree;

    }


}
