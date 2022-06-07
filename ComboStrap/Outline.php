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
        $this->tree->setContent(OutlineNode::create());
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
                    $newSection = true;
                    break;
                case syntax_plugin_combo_heading::TAG:
                case syntax_plugin_combo_headingwiki::TAG:
                    if ($actualCall->getState() == DOKU_LEXER_ENTER) {
                        $newSection = true;
                    }
                    break;
                case "header":
                    $newSection = true;
                    break;
            }
            if ($newSection) {
                $headingEnterCall = $callStack->getActualCall();
                $level = $actualCall->getAttribute("level");
                $actualTreeNode = $actualTreeNode->appendNode($level);
                $actualTreeNode->setContent(OutlineNode::create());
                continue;
            }

            /**
             * Still on the root ?
             */
            if (!$actualTreeNode->hasParent()) {
                /**
                 * @var OutlineNode $outlineNode
                 */
                $outlineNode = $actualTreeNode->getContent();
                $outlineNode->addCall($actualCall);
            }

            /**
             * Close and Inside the heading description
             */
            if ($headingParsingState === DOKU_LEXER_ENTER) {

                switch ($actualCall->getTagName()) {

                    case "internalmedia":
                        // no link for media in heading
                        $actualCall->getInstructionCall()[1][6] = MediaLink::LINKING_NOLINK_VALUE;
                        continue 2;
                    case syntax_plugin_combo_media::TAG:
                        // no link for media in heading
                        $actualCall->addAttribute(MediaLink::LINKING_KEY, MediaLink::LINKING_NOLINK_VALUE);
                        continue 2;

                    case "header":
                        if (PluginUtility::getConfValue(syntax_plugin_combo_headingwiki::CONF_WIKI_HEADING_ENABLE, syntax_plugin_combo_headingwiki::CONF_DEFAULT_WIKI_ENABLE_VALUE) == 1) {
                            LogUtility::msg("The combo heading wiki is enabled, we should not see `header` calls in the call stack");
                        }
                        break;

                }


            }

        }
    }

    public function getTree(): TreeNode
    {
        return $this->tree;

    }


}
