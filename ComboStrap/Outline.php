<?php

namespace ComboStrap;


use syntax_plugin_combo_heading;
use syntax_plugin_combo_headingatx;
use syntax_plugin_combo_headingwiki;
use syntax_plugin_combo_media;
use syntax_plugin_combo_section;

class Outline
{


    const CANONICAL = "outline";
    private OutlineSection $rootSection;

    private OutlineSection $actualSection; // the actual section that is created
    private Call $actualHeadingCall; // the heading that is parsed
    private int $actualHeadingParsingState = DOKU_LEXER_EXIT;  // the state of the heading parsed (enter, closed), enter if we have entered an heading, exit if not;

    public function __construct(CallStack $callStack)
    {

        $this->buildOutline($callStack);
    }

    public static function createFromCallStack(CallStack $callStack): Outline
    {
        return new Outline($callStack);
    }

    private function buildOutline(CallStack $callStack)
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
                        $this->enterHeading($actualCall);
                    }
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
                    $this->actualSection->setEndPosition($actualCall->getFirstMatchedCharacterPosition() - 1);
                }
                $actualSectionLevel = $this->actualSection->getLevel();
                try {
                    $newSectionLevel = DataType::toInteger($actualCall->getAttribute(syntax_plugin_combo_heading::LEVEL));
                } catch (ExceptionBadArgument $e) {
                    LogUtility::internalError("The level was not present on the heading call", self::CANONICAL);
                    $newSectionLevel = $actualSectionLevel;
                }
                if ($newSectionLevel > $actualSectionLevel) {

                    /**
                     * A child of the actual section
                     */
                    $childSection = OutlineSection::createChildOutlineSection($this->actualSection, $actualCall);

                } else {

                    /**
                     * A child of the parent section, A sibling of the actual session
                     * @var OutlineSection $parentSection
                     */
                    $parentSection = $this->actualSection->getParent();
                    $childSection = OutlineSection::createChildOutlineSection($parentSection, $actualCall);
                    if ($newSectionLevel < $actualSectionLevel) {
                        LogUtility::error("The section ($childSection) has a level ($newSectionLevel) lower than its parent ($actualSectionLevel).");
                    }

                }


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
                            $this->addCallToSection($actualCall);
                            $this->exitHeading();
                            continue 2;
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
        $sectionSequenceId = 0;
        $collectCalls = function (OutlineSection $outlineSection) use (&$totalInstructionCalls, &$sectionSequenceId) {

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

                    /**
                     * Adding sectionedit class to be conform
                     * with the Dokuwiki {@link \Doku_Renderer_xhtml::header()} function
                     */
                    $sectionSequenceId++;
                    $headingCall = $outlineSection->getHeadingCall();
                    if ($headingCall->isPluginCall()) {
                        $level = DataType::toIntegerOrDefaultIfNull($headingCall->getAttribute(syntax_plugin_combo_heading::LEVEL), 0);
                        if ($level <= TocUtility::getTocMax()) {
                            $headingCall->addClassName("sectionedit$sectionSequenceId");
                        }
                    }

                    $editButton = EditButton::create($outlineSection->getLabel())
                        ->setStartPosition($outlineSection->getStartPosition())
                        ->setEndPosition($outlineSection->getEndPosition())
                        ->setOutlineHeadingId($outlineSection->getHeadingId())
                        ->setOutlineSectionId($sectionSequenceId)
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

    /**
     * @return array - Dokuwiki TOC array format
     */
    public function getTocDokuwikiFormat(): array
    {
        $tableOfContent = [];
        $collectTableOfContent = function (OutlineSection $outlineSection) use (&$tableOfContent) {

            if (!$outlineSection->hasParent()) {
                // Root Section, no heading
                return;
            }
            $tableOfContent[] = [
                'link' => '#' . $outlineSection->getHeadingId(),
                'title' => $outlineSection->getLabel(),
                'type' => 'ul',
                'level' => $outlineSection->getLevel()
            ];

        };
        TreeVisit::visit($this->rootSection, $collectTableOfContent);
        return $tableOfContent;

    }

    private function toStrapTemplateInstructionCallsRecurse(OutlineSection $outlineSection, array &$totalComboCalls, int &$sectionSequenceId): void
    {

        $totalComboCalls[] = Call::createComboCall(
            syntax_plugin_combo_section::TAG,
            DOKU_LEXER_ENTER,
            array(syntax_plugin_combo_heading::LEVEL => $outlineSection->getLevel())
        );
        $contentCalls = $outlineSection->getContentCalls();
        if ($outlineSection->hasChildren()) {
            /**
             * If it has children and content, wrap the heading and the content
             * in a header tag
             * The header tag helps also to get the edit button to stay in place
             */
            $openHeader = Call::createComboCall(
                \syntax_plugin_combo_box::TAG,
                DOKU_LEXER_ENTER,
                array(\syntax_plugin_combo_box::TAG_ATTRIBUTE => "header",
                    TagAttributes::CLASS_KEY => "outline-header",
                )
            );
            $closeHeader = Call::createComboCall(
                \syntax_plugin_combo_box::TAG,
                DOKU_LEXER_EXIT,
                array(\syntax_plugin_combo_box::TAG_ATTRIBUTE => "header")
            );
            $totalComboCalls = array_merge(
                $totalComboCalls,
                [$openHeader],
                $outlineSection->getHeadingCalls(),
                $contentCalls,
            );
            $this->addSectionEditButtonComboFormatIfNeeded($outlineSection, $sectionSequenceId, $totalComboCalls);
            $totalComboCalls[] = $closeHeader;

            foreach ($outlineSection->getChildren() as $child) {
                /**
                 * @var OutlineSection $child
                 */
                $this->toStrapTemplateInstructionCallsRecurse($child, $totalComboCalls, $sectionSequenceId);
            }

        } else {
            $totalComboCalls = array_merge(
                $totalComboCalls,
                $outlineSection->getHeadingCalls(),
                $contentCalls,
            );
            $this->addSectionEditButtonComboFormatIfNeeded($outlineSection, $sectionSequenceId, $totalComboCalls);
        }
        $totalComboCalls[] = Call::createComboCall(
            syntax_plugin_combo_section::TAG,
            DOKU_LEXER_EXIT
        );


    }

    public function toStrapTemplateInstructionCalls(): array
    {
        $totalCalls = [];
        $sectionSequenceId = 0;

        /**
         * Transform and collect the calls in Instructions calls
         */
        $this->toStrapTemplateInstructionCallsRecurse($this->rootSection, $totalCalls, $sectionSequenceId);

        return array_map(function (Call $element) {
            return $element->getInstructionCall();
        }, $totalCalls);
    }

    /**
     * Add the edit button if needed
     * @param $outlineSection
     * @param $sectionSequenceId
     * @param array $totalInstructionCalls
     */
    private function addSectionEditButtonComboFormatIfNeeded(OutlineSection $outlineSection, int $sectionSequenceId, array &$totalInstructionCalls): void
    {
        if (!$outlineSection->hasParent()) {
            // no button for the root (ie the page)
            return;
        }
        if (Site::isSectionEditingEnabled()) {

            $editButton = EditButton::create("Edit the section `{$outlineSection->getLabel()}`")
                ->setStartPosition($outlineSection->getStartPosition())
                ->setEndPosition($outlineSection->getEndPosition());
            if ($outlineSection->hasHeading()) {
                $editButton->setOutlineHeadingId($outlineSection->getHeadingId());
            }

            $totalInstructionCalls[] = $editButton
                ->setOutlineSectionId($sectionSequenceId)
                ->toComboCallComboFormat();

        }

    }

    /**
     * Dynamic Rendering does not have any section/edit button
     *
     * The outline processing ({@link Outline::buildOutline()} just close the atx heading
     *
     * @return array
     */
    public function toDynamicInstructionCalls(): array
    {
        $totalInstructionCalls = [];
        $collectCalls = function (OutlineSection $outlineSection) use (&$totalInstructionCalls) {

            $sectionCalls = array_merge(
                $outlineSection->getHeadingCalls(),
                $outlineSection->getContentCalls()
            );

            $instructionCalls = array_map(function (Call $element) {
                return $element->getInstructionCall();
            }, $sectionCalls);
            $totalInstructionCalls = array_merge($totalInstructionCalls, $instructionCalls);
        };
        TreeVisit::visit($this->rootSection, $collectCalls);
        return $totalInstructionCalls;

    }


}
