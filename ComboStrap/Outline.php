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


        /**
         * Processing variable about the context
         */
        $this->tree = TreeNode::createTreeRoot();
        $actualSection = OutlineSection::create();
        $this->tree->setContent($actualSection);
        $outlineParsingState = null; // null if root, enter if we have entered a section
        $headingParsingState = DOKU_LEXER_EXIT; // enter if we have entered an heading, exit if not
        $headingEnterCall = null; // the enter call to be able to get attribute back

        $headingText = ""; // text only content in the heading

        $actualLastPosition = 0;
        $actualTreeNode = $this->tree;
        $callStack->moveToStart();
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
            $newSection = false;
            switch ($tagName) {
                case syntax_plugin_combo_headingatx::TAG:
                    $actualCall->setState(DOKU_LEXER_ENTER);
                    if ($actualCall->getContext() === syntax_plugin_combo_heading::TYPE_OUTLINE) {
                        $newSection = true;
                    }
                    break;
                case syntax_plugin_combo_heading::TAG:
                case syntax_plugin_combo_headingwiki::TAG:
                    if ($actualCall->getState() == DOKU_LEXER_ENTER
                        && $actualCall->getContext() === syntax_plugin_combo_heading::TYPE_OUTLINE) {
                        $newSection = true;
                    }
                    break;
                case "header":
                    $newSection = true;
                    break;
            }
            if ($newSection) {
                $level = $actualCall->getAttribute(syntax_plugin_combo_heading::LEVEL);
                $actualTreeNode = $actualTreeNode->appendNode($level);
                $actualSection = OutlineSection::create();
                $actualSection->addCall($actualCall);
                $actualTreeNode->setContent($actualSection);
                $headingParsingState = DOKU_LEXER_ENTER;
                $headingEnterCall = $actualCall;
                continue;
            }

            /**
             * Still on the root ?
             */
            if (!$actualTreeNode->hasParent()) {
                $actualSection->addCall($actualCall);
                continue;
            }


            /**
             * Close/Process the heading description
             */
            if ($headingParsingState == DOKU_LEXER_ENTER) {
                switch ($actualCall->getTagName()) {

                    case syntax_plugin_combo_heading::TAG:
                    case syntax_plugin_combo_headingwiki::TAG:
                        if ($actualCall->getState() == DOKU_LEXER_EXIT) {
                            $headingParsingState = DOKU_LEXER_EXIT;
                        }
                        break;

                    case "internalmedia":
                        // no link for media in heading
                        $actualCall->getInstructionCall()[1][6] = MediaLink::LINKING_NOLINK_VALUE;
                        break;
                    case syntax_plugin_combo_media::TAG:
                        // no link for media in heading
                        $actualCall->addAttribute(MediaLink::LINKING_KEY, MediaLink::LINKING_NOLINK_VALUE);
                        break;

                    case "header":
                        if (PluginUtility::getConfValue(syntax_plugin_combo_headingwiki::CONF_WIKI_HEADING_ENABLE, syntax_plugin_combo_headingwiki::CONF_DEFAULT_WIKI_ENABLE_VALUE) == 1) {
                            LogUtility::msg("The combo heading wiki is enabled, we should not see `header` calls in the call stack");
                        }
                        break;

                    case "p":

                        if ($actualCall->getTagName() == syntax_plugin_combo_headingatx::TAG) {
                            // A new p is the end of an atx call
                            $headingParsingState = DOKU_LEXER_EXIT;
                            $endAtxCall = Call::createComboCall(
                                syntax_plugin_combo_headingatx::TAG,
                                DOKU_LEXER_EXIT,
                                $headingEnterCall->getAttributes()
                            );
                            $actualSection->addCall($endAtxCall);
                            // We don't take the p tag inside atx heading
                            // therefore we continue
                            continue 2;

                        }
                        break;

                }
            }
            $actualSection->addCall($actualCall);

        }
    }

    public function getTree(): TreeNode
    {
        return $this->tree;

    }


}
