<?php

namespace ComboStrap;


use syntax_plugin_combo_heading;
use syntax_plugin_combo_headingatx;
use syntax_plugin_combo_headingwiki;
use syntax_plugin_combo_media;

class Outline
{


    private OutlineSection $rootSection;

    private OutlineSection $actualSection; // the actual section that is created
    private Call $actualHeadingCall; // the heading that is parsed
    private int $actualHeadingParsingState = DOKU_LEXER_EXIT;  // the state of the heading parsed (enter, closed), enter if we have entered an heading, exit if not;

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
        $this->rootSection = OutlineSection::createOutlineRoot()
            ->setStartPosition(0);
        $this->actualSection = $this->rootSection;
        $actualLastPosition = 0;
        $callStack->moveToStart();
        while ($actualCall = $callStack->next()) {

            $tagName = $actualCall->getTagName();


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
                    $this->enterHeading($actualCall);
                    break;
                case syntax_plugin_combo_heading::TAG:
                case syntax_plugin_combo_headingwiki::TAG:
                    if ($actualCall->getState() == DOKU_LEXER_ENTER
                        && $actualCall->getContext() === syntax_plugin_combo_heading::TYPE_OUTLINE) {
                        $newSection = true;
                    }
                    $this->enterHeading($actualCall);
                    break;
                case "header":
                    // Should happen only on outline section
                    // we take over inside a component
                    $newSection = true;
                    $this->enterHeading($actualCall);
                    break;
            }
            if ($newSection) {
                if ($this->actualSection->hasParent()) {
                    // -1 because the actual position is the start of the next section
                    $this->actualSection->setEndPosition($actualLastPosition - 1);
                }
                $childSection = OutlineSection::createChildOutlineSection($this->actualSection, $actualCall);
                $childSection->addHeadingCall($actualCall);
                $this->actualSection = $childSection;
                continue;
            }

            /**
             * Track the position in the file
             */
            $currentLastPosition = $actualCall->getLastMatchedCharacterPosition();
            if ($currentLastPosition > $actualLastPosition) {
                // the position in the stack is not always good
                $actualLastPosition = $currentLastPosition;
            }


            switch ($actualCall->getComponentName()) {
                case \action_plugin_combo_headingpostprocessing::EDIT_SECTION_OPEN:
                case \action_plugin_combo_headingpostprocessing::EDIT_SECTION_CLOSE:
                    // we don't store them
                    continue 2;
            }

            /**
             * Close/Process the heading description
             */
            if ($this->actualHeadingParsingState === DOKU_LEXER_ENTER) {
                switch ($actualCall->getTagName()) {

                    case syntax_plugin_combo_heading::TAG:
                    case syntax_plugin_combo_headingwiki::TAG:
                        if ($actualCall->getState() == DOKU_LEXER_EXIT) {
                            $this->exitHeading();
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

                        if ($this->actualHeadingCall->getTagName() === syntax_plugin_combo_headingatx::TAG) {
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
                                        $this->actualHeadingCall->getAttributes()
                                    );
                                    $this->addCallToSection($endAtxCall);
                                    $this->exitHeading();
                                    // We don't take the p tag inside atx heading
                                    // therefore we continue
                                    continue 3;
                            }
                        }
                        break;

                }
            }
            $this->addCallToSection($actualCall);
        }

    }

    public function getRootOutlineSection(): OutlineSection
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

    public function toDefaultTemplateInstructionCalls(): array
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

                if (Site::isSectionEditingEnabled()) {
                    $editButton = EditButton::create($outlineSection->getLabel())
                        ->setStartPosition($outlineSection->getStartPosition())
                        ->setEndPosition($outlineSection->getEndPosition())
                        ->setHeadingId($outlineSection->getHeadingId())
                        ->toComboCallDokuWikiForm();
                    $sectionCalls[] = $editButton;
                }

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

    private function addCallToSection(Call $actualCall)
    {
        if ($this->actualHeadingParsingState === DOKU_LEXER_ENTER && !$this->actualSection->hasContentCall()) {
            $this->actualSection->addHeadingCall($actualCall);
        } else {
            // an content heading (not outline) or another call
            $this->actualSection->addContentCall($actualCall);
        }
    }

    private function enterHeading(Call $actualCall)
    {
        $this->actualHeadingParsingState = DOKU_LEXER_ENTER;
        $this->actualHeadingCall = $actualCall;
    }

    private function exitHeading()
    {
        $this->actualHeadingParsingState = DOKU_LEXER_EXIT;
    }


}
