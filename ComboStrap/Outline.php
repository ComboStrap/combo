<?php

namespace ComboStrap;


use syntax_plugin_combo_heading;
use syntax_plugin_combo_headingatx;
use syntax_plugin_combo_headingwiki;
use syntax_plugin_combo_media;

class Outline
{


    private OutlineSection $rootSection;

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
        $this->rootSection = OutlineSection::createOutlineRoot();
        $actualSection = $this->rootSection;
        $headingParsingState = DOKU_LEXER_EXIT; // enter if we have entered an heading, exit if not
        $actualLastPosition = 0;
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
                    // Should happen only on outline section
                    // we take over inside a component
                    $newSection = true;
                    break;
            }
            if ($newSection) {
                $actualSection->setEndPosition($actualLastPosition);
                $childSection = OutlineSection::createChildOutlineSection($actualSection, $actualCall);
                $childSection->addHeadingCall($actualCall);
                $actualSection = $childSection;
                $headingParsingState = DOKU_LEXER_ENTER;
                continue;
            }

            switch ($actualCall->getComponentName()){
                case \action_plugin_combo_headingpostprocessing::EDIT_SECTION_OPEN:
                case \action_plugin_combo_headingpostprocessing::EDIT_SECTION_CLOSE:
                    // we don't store them
                    continue 2;
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

                        if ($actualSection->getHeadingCall()->getTagName() == syntax_plugin_combo_headingatx::TAG) {
                            // A new p is the end of an atx call
                            switch ($actualCall->getComponentName()) {
                                case "p_open":
                                    // We don't take the p tag inside atx heading
                                    // therefore we continue
                                    continue 3;
                                case "p_close":
                                    $endAtxCall = Call::createComboCall(
                                        syntax_plugin_combo_headingatx::TAG,
                                        DOKU_LEXER_EXIT,
                                        $actualSection->getHeadingCall()->getAttributes()
                                    );
                                    $actualSection->addHeadingCall($endAtxCall);
                                    $headingParsingState = DOKU_LEXER_EXIT;
                                    // We don't take the p tag inside atx heading
                                    // therefore we continue
                                    continue 3;
                            }
                        }
                        break;

                }
            }
            if ($headingParsingState === DOKU_LEXER_EXIT) {
                $actualSection->addContentCall($actualCall);
            } else {
                $actualSection->addHeadingCall($actualCall);
            }
        }
        $actualSection->setEndPosition($actualLastPosition);
    }

    public function getRootSection(): OutlineSection
    {
        return $this->rootSection;

    }

    public function getInstructionCalls(): array
    {
        $totalInstructionCalls = [];
        $collectCalls = function (OutlineSection $outlineSection) use (&$totalInstructionCalls) {
            $instructionCalls = array_map(function (Call $element) {
                return $element->getInstructionCall();
            }, $outlineSection->getCalls());
            $totalInstructionCalls = array_merge($totalInstructionCalls, $instructionCalls);
        };
        TreeVisit::visit($this->rootSection, $collectCalls);
        return $totalInstructionCalls;
    }

    public function getDefaultTemplateInstructionCalls(): array
    {
        $totalInstructionCalls = [];
        $collectCalls = function (OutlineSection $outlineSection) use (&$totalInstructionCalls) {

            $wikiSectionOpen = Call::createNativeCall(
                \action_plugin_combo_headingpostprocessing::EDIT_SECTION_OPEN,
                array($outlineSection->getLevel()),
                $outlineSection->getStartPosition()
            );
            $wikiSectionClose = Call::createNativeCall(
                \action_plugin_combo_headingpostprocessing::EDIT_SECTION_CLOSE,
                array(),
                $outlineSection->getEndPosition()
            );
            if ($outlineSection->hasParent()) {
                $sectionCalls = array_merge(
                    $outlineSection->getHeadingCalls(),
                    [$wikiSectionOpen],
                    $outlineSection->getContentCalls(),
                    [$wikiSectionClose],
                );
            } else {
                // dokuwiki seems to have no section for the content before the first heading
                $sectionCalls = $outlineSection->getContentCalls();
            }

            $instructionCalls = array_map(function (Call $element) {
                return $element->getInstructionCall();
            }, $sectionCalls);
            $totalInstructionCalls = array_merge($totalInstructionCalls, $instructionCalls);
        };
        TreeVisit::visit($this->rootSection, $collectCalls);
        return $totalInstructionCalls;
    }


}
