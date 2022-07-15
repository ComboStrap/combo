<?php

namespace ComboStrap;


use syntax_plugin_combo_header;
use syntax_plugin_combo_heading;
use syntax_plugin_combo_headingatx;
use syntax_plugin_combo_headingwiki;
use syntax_plugin_combo_media;
use syntax_plugin_combo_section;

class Outline
{


    const CANONICAL = "outline";
    private const OUTLINE_HEADING_PREFIX = "outline-heading";
    const CONTEXT = self::CANONICAL;
    private OutlineSection $rootSection;

    private OutlineSection $actualSection; // the actual section that is created
    private Call $actualHeadingCall; // the heading that is parsed
    private int $actualHeadingParsingState = DOKU_LEXER_EXIT;  // the state of the heading parsed (enter, closed), enter if we have entered an heading, exit if not;

    public function __construct(CallStack $callStack)
    {
        $this->buildOutline($callStack);
        $this->storeH1();
        $this->storeToc();
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
             * We don't take the outline and document call if any
             * This is the case when we build from an actual stored instructions
             * (to bundle multiple page for instance)
             */
            switch ($tagName) {
                case "document_start":
                case "document_end":
                case syntax_plugin_combo_section::TAG:
                    continue 2;
                case syntax_plugin_combo_header::TAG:
                    if ($actualCall->getContext() === self::CONTEXT) {
                        continue 2;
                    }
            }

            /**
             * Enter new section ?
             */
            $shouldWeCreateASection = false;
            switch ($tagName) {
                case syntax_plugin_combo_headingatx::TAG:
                    $actualCall->setState(DOKU_LEXER_ENTER);
                    if ($actualCall->getContext() === syntax_plugin_combo_heading::TYPE_OUTLINE) {
                        $shouldWeCreateASection = true;
                    }
                    $this->enterHeading($actualCall);
                    break;
                case syntax_plugin_combo_heading::TAG:
                case syntax_plugin_combo_headingwiki::TAG:
                    if ($actualCall->getState() == DOKU_LEXER_ENTER
                        && $actualCall->getContext() === syntax_plugin_combo_heading::TYPE_OUTLINE) {
                        $shouldWeCreateASection = true;
                        $this->enterHeading($actualCall);
                    }
                    break;
                case "header":
                    // Should happen only on outline section
                    // we take over inside a component
                    $shouldWeCreateASection = true;
                    $this->enterHeading($actualCall);
                    break;
            }
            if ($shouldWeCreateASection) {
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

                $newOutlineSection = new OutlineSection($actualCall);
                $sectionDiff = $newSectionLevel - $actualSectionLevel;
                if ($sectionDiff > 0) {

                    /**
                     * A child of the actual section
                     * We append it first before the message check to
                     * build the {@link TreeNode::getTreeIdentifier()}
                     */
                    try {
                        $this->actualSection->appendChild($newOutlineSection);
                    } catch (ExceptionBadState $e) {
                        throw new ExceptionRuntimeInternal("The node is not added multiple time, this error should not fired. Error:{$e->getMessage()}", self::CANONICAL, 1, $e);
                    }

                    if ($sectionDiff > 1 & !($actualSectionLevel === 0 && $newSectionLevel === 2)) {
                        $expectedLevel = $actualSectionLevel + 1;
                        if ($actualSectionLevel === 0) {
                            $message = "The first section heading should have the level 1 or 2 (not $newSectionLevel).";
                        } else {
                            $message = "The child section heading ($actualSectionLevel) has the level ($newSectionLevel) but is parent ({$this->actualSection->getLabel()}) has the level ($actualSectionLevel). The expected level is ($expectedLevel).";
                        }
                        LogUtility::error($message, self::CANONICAL);
                        $actualCall->setAttribute(syntax_plugin_combo_heading::LEVEL, $expectedLevel);
                    }

                } else {

                    /**
                     * A child of the parent section, A sibling of the actual session
                     */
                    try {
                        $parent = $this->actualSection->getParent();
                        for ($i = 0; $i < abs($sectionDiff); $i++) {
                            $parent = $parent->getParent();
                        }
                        try {
                            $parent->appendChild($newOutlineSection);
                        } catch (ExceptionBadState $e) {
                            throw new ExceptionRuntimeInternal("The node is not added multiple time, this error should not fired. Error:{$e->getMessage()}", self::CANONICAL, 1, $e);
                        }
                    } catch (ExceptionNotFound $e) {
                        // no parent
                        LogUtility::internalError("Due to the level logic, the actual section should have a parent");
                        try {
                            $this->actualSection->appendChild($newOutlineSection);
                        } catch (ExceptionBadState $e) {
                            throw new ExceptionRuntimeInternal("The node is not added multiple time, this error should not fired. Error:{$e->getMessage()}", self::CANONICAL, 1, $e);
                        }
                    }


                }

                $newOutlineSection->addHeadingCall($actualCall);
                $this->actualSection = $newOutlineSection;
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
                        $actualCall->getInstructionCall()[1][6] = MediaMarkup::LINKING_NOLINK_VALUE;
                        break;
                    case syntax_plugin_combo_media::TAG:
                        // no link for media in heading
                        $actualCall->addAttribute(MediaMarkup::LINKING_KEY, MediaMarkup::LINKING_NOLINK_VALUE);
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

        // Add label the heading text to the metadata
        $this->saveOutlineToMetadata();


    }

    public static function getOutlineHeadingClass(): string
    {
        return StyleUtility::addComboStrapSuffix(self::OUTLINE_HEADING_PREFIX);
    }

    public function getRootOutlineSection(): OutlineSection
    {
        return $this->rootSection;

    }

    /**
     */
    public static function merge(Outline $inner, Outline $outer)
    {
        /**
         * Get the inner section where the outer section will be added
         */
        $innerRootOutlineSection = $inner->getRootOutlineSection();
        $innerTopSections = $innerRootOutlineSection->getChildren();
        if (count($innerTopSections) === 0) {
            $firstInnerSection = $innerRootOutlineSection;
        } else {
            $firstInnerSection = $innerTopSections[count($innerTopSections)];
        }
        $firstInnerSectionLevel = $firstInnerSection->getLevel();

        /**
         * Add the outer sections
         */
        $outerRootOutlineSection = $outer->getRootOutlineSection();
        foreach ($outerRootOutlineSection->getChildren() as $childOuterSection) {
            /**
             * One level less than where the section is included
             */
            $childOuterSection->setLevel($firstInnerSectionLevel + 1);
            $childOuterSection->resetForImport();
            try {
                $firstInnerSection->appendChild($childOuterSection);
            } catch (ExceptionBadState $e) {
                // We add the node only once. This error should not happen
                throw new ExceptionRuntimeInternal("Error while adding a section during the outline merge. Error: {$e->getMessage()}", self::CANONICAL, 1, $e);
            }
        }

    }

    public static function mergeRecurse(Outline $inner, Outline $outer)
    {
        $innerRootOutlineSection = $inner->getRootOutlineSection();
        $outerRootOutlineSection = $outer->getRootOutlineSection();

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
                        if ($level <= Site::getTocMax()) {
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

    private function toHtmlSectionOutlineCallsRecurse(OutlineSection $outlineSection, array &$totalComboCalls, int &$sectionSequenceId, bool $contentHeaderDisplayToNone): void
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

            $isContentHeader = in_array($outlineSection->getLevel(), [0, 1]);
            if (!($contentHeaderDisplayToNone && $isContentHeader)) {

                $openHeader = Call::createComboCall(
                    \syntax_plugin_combo_header::TAG,
                    DOKU_LEXER_ENTER,
                    array(
                        TagAttributes::CLASS_KEY => StyleUtility::addComboStrapSuffix("outline-header"),
                    ),
                    self::CONTEXT
                );
                $closeHeader = Call::createComboCall(
                    \syntax_plugin_combo_header::TAG,
                    DOKU_LEXER_EXIT,
                    [],
                    self::CONTEXT

                );
                $totalComboCalls = array_merge(
                    $totalComboCalls,
                    [$openHeader],
                    $outlineSection->getHeadingCalls(),
                    $contentCalls,
                );
                $this->addSectionEditButtonComboFormatIfNeeded($outlineSection, $sectionSequenceId, $totalComboCalls);
                $totalComboCalls[] = $closeHeader;

            }

            foreach ($outlineSection->getChildren() as $child) {
                $this->toHtmlSectionOutlineCallsRecurse($child, $totalComboCalls, $sectionSequenceId, $contentHeaderDisplayToNone);
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

    public function toHtmlSectionOutlineCalls(): array
    {
        $totalCalls = [];
        $sectionSequenceId = 0;
        $headerDisplayToNone = false;

        /**
         * Transform and collect the calls in Instructions calls
         */

        $this->toHtmlSectionOutlineCallsRecurse($this->rootSection, $totalCalls, $sectionSequenceId, $headerDisplayToNone);

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

    /**
     * Add the label (ie heading text to the cal attribute)
     *
     * @return void
     */
    private function saveOutlineToMetadata()
    {
        try {
            $firstChild = $this->rootSection->getFirstChild();
        } catch (ExceptionNotFound $e) {
            // no child
            return;
        }
        if ($firstChild->getLevel() === 1) {
            $headingCall = $firstChild->getHeadingCall();
            $headingCall->setAttribute(syntax_plugin_combo_heading::HEADING_TEXT_ATTRIBUTE, $firstChild->getLabel());
        }

    }


    public function toHtmlSectionOutlineCallsWithoutHeader(): array
    {

        $totalCalls = [];
        $sectionSequenceId = 0;
        $headerToNone = true;

        /**
         * Transform and collect the calls in Instructions calls
         */
        $this->toHtmlSectionOutlineCallsRecurse($this->rootSection, $totalCalls, $sectionSequenceId, $headerToNone);

        return array_map(function (Call $element) {
            return $element->getInstructionCall();
        }, $totalCalls);

    }

    private function storeH1()
    {
        try {
            $outlineSection = $this->getRootOutlineSection()->getFirstChild();
        } catch (ExceptionNotFound $e) {
            //
            return;
        }
        if ($outlineSection->getLevel() === 1) {
            try {
                PageH1::createForPage(Markup::createFromRequestedPage())
                    ->setValue($outlineSection->getLabel())
                    ->persist();
            } catch (ExceptionBadArgument $e) {
                LogUtility::internalError("We were unable to store the scanned heading 1. Error: {$e->getMessage()}", self::CANONICAL);
            }
        }
    }

    private function storeToc()
    {
        $toc = $this->getTocDokuwikiFormat();
        try {
            Toc::createForRequestedPage()
                ->setValue($toc)
                ->persist();
        } catch (ExceptionBadArgument $e) {
            LogUtility::error("The Toc could not be persisted. Error:{$e->getMessage()}");
        }
    }


}
