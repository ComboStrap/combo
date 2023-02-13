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
    public const OUTLINE_HEADING_NUMBERING = "outline-heading-numbering";
    public const TOC_NUMBERING = "toc-numbering";
    /**
     * As seen on
     * https://drafts.csswg.org/css-counter-styles-3/#predefined-counters
     */
    public const CONF_COUNTER_STYLES_CHOICES = [
        'arabic-indic',
        'bengali',
        'cambodian/khmer',
        'cjk-decimal',
        'decimal',
        'decimal-leading-zero',
        'devanagari',
        'georgian',
        'gujarati',
        'gurmukhi',
        'hebrew',
        'hiragana',
        'hiragana-iroha',
        'kannada',
        'katakana',
        'katakana-iroha',
        'lao',
        'lower-alpha',
        'lower-armenian',
        'lower-greek',
        'lower-roman',
        'malayalam',
        'mongolian',
        'myanmar',
        'oriya',
        'persian',
        'tamil',
        'telugu',
        'thai',
        'tibetan',
        'upper-alpha',
        'upper-armenian',
        'upper-roman'
    ];
    public const CONF_OUTLINE_NUMBERING_COUNTER_STYLE_LEVEL4 = "outlineNumberingCounterStyleLevel4";
    public const CONF_OUTLINE_NUMBERING_COUNTER_STYLE_LEVEL3 = "outlineNumberingCounterStyleLevel3";
    public const CONF_OUTLINE_NUMBERING_SUFFIX = "outlineNumberingSuffix";
    public const CONF_OUTLINE_NUMBERING_COUNTER_STYLE_LEVEL2 = "outlineNumberingCounterStyleLevel2";
    public const CONF_OUTLINE_NUMBERING_COUNTER_SEPARATOR = "outlineNumberingCounterSeparator";
    public const CONF_OUTLINE_NUMBERING_COUNTER_STYLE_LEVEL6 = "outlineNumberingCounterStyleLevel6";
    public const CONF_OUTLINE_NUMBERING_COUNTER_STYLE_LEVEL5 = "outlineNumberingCounterStyleLevel5";
    public const CONF_OUTLINE_NUMBERING_PREFIX = "outlineNumberingPrefix";
    public const CONF_OUTLINE_NUMBERING_ENABLE = "outlineNumberingEnable";
    /**
     * To add hash tag to heading
     */
    public const OUTLINE_ANCHOR = "outline-anchor";
    const CONF_OUTLINE_NUMBERING_ENABLE_DEFAULT = 1;
    private OutlineSection $rootSection;

    private OutlineSection $actualSection; // the actual section that is created
    private Call $actualHeadingCall; // the heading that is parsed
    private int $actualHeadingParsingState = DOKU_LEXER_EXIT;  // the state of the heading parsed (enter, closed), enter if we have entered an heading, exit if not;
    private ?MarkupPath $markupPath = null;

    public function __construct(CallStack $callStack, MarkupPath $markup = null)
    {
        if ($markup !== null) {
            $this->markupPath = $markup;
        }
        $this->buildOutline($callStack);
        $this->storeH1();
        $this->storeTocForMarkupIfAny();
    }

    /**
     * @param CallStack $callStack
     * @param MarkupPath|null $markupPath - null if the markup is dynamic
     * @return Outline
     */
    public static function createFromCallStack(CallStack $callStack, MarkupPath $markupPath = null): Outline
    {
        return new Outline($callStack, $markupPath);
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

                $newOutlineSection = OutlineSection::createFromEnterHeadingCall($actualCall);
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
                        LogUtility::warning($message, self::CANONICAL);
                        $actualCall->setAttribute(syntax_plugin_combo_heading::LEVEL, $newSectionLevel);
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
                        if (Site::getConfValue(syntax_plugin_combo_headingwiki::CONF_WIKI_HEADING_ENABLE, syntax_plugin_combo_headingwiki::CONF_DEFAULT_WIKI_ENABLE_VALUE) == 1) {
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
            $childOuterSection->detachBeforeAppend();
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

    /**
     * Utility class to create a outline from a markup string
     * @param string $content
     * @return Outline
     */
    public static function createFromMarkup(string $content, Path $contentPath, WikiPath $contextPath): Outline
    {
        $instructions = MarkupRenderer::createFromMarkup($content, $contentPath, $contextPath)
            ->setRequestedMimeToInstruction()
            ->getOutput();
        $callStack = CallStack::createFromInstructions($instructions);
        return Outline::createFromCallStack($callStack);
    }

    /**
     * Get the heading numbering snippet
     * @param string $type heading or toc - for {@link Outline::TOC_NUMBERING} or {@link Outline::OUTLINE_HEADING_NUMBERING}
     * @return string - the css internal stylesheet
     * @throws ExceptionNotEnabled
     * @throws ExceptionBadSyntax
     * Page on DokuWiki
     * https://www.dokuwiki.org/tips:numbered_headings
     */
    public static function getCssNumberingRulesFor(string $type): string
    {

        $enable = Site::getConfValue(self::CONF_OUTLINE_NUMBERING_ENABLE, Outline::CONF_OUTLINE_NUMBERING_ENABLE_DEFAULT);
        if (!$enable) {
            throw new ExceptionNotEnabled();
        }

        $level2CounterStyle = Site::getConfValue(self::CONF_OUTLINE_NUMBERING_COUNTER_STYLE_LEVEL2, "decimal");
        $level3CounterStyle = Site::getConfValue(self::CONF_OUTLINE_NUMBERING_COUNTER_STYLE_LEVEL3, "decimal");
        $level4CounterStyle = Site::getConfValue(self::CONF_OUTLINE_NUMBERING_COUNTER_STYLE_LEVEL4, "decimal");
        $level5CounterStyle = Site::getConfValue(self::CONF_OUTLINE_NUMBERING_COUNTER_STYLE_LEVEL5, "decimal");
        $level6CounterStyle = Site::getConfValue(self::CONF_OUTLINE_NUMBERING_COUNTER_STYLE_LEVEL6, "decimal");
        $counterSeparator = Site::getConfValue(self::CONF_OUTLINE_NUMBERING_COUNTER_SEPARATOR, ".");
        $prefix = Site::getConfValue(self::CONF_OUTLINE_NUMBERING_PREFIX, "");
        $suffix = Site::getConfValue(self::CONF_OUTLINE_NUMBERING_SUFFIX, " - ");

        switch ($type) {

            case self::OUTLINE_HEADING_NUMBERING:
                global $ACT;
                if ($ACT === "preview") {
                    $mainContainerSelector = ".pad";
                } else {
                    $mainContainerSelector = "#" . PageTemplate::MAIN_CONTENT_ELEMENT;
                }
                /**
                 * Because the HTML file structure is not really fixed
                 * (we may have section HTML element with a bar, the sectioning heading
                 * may be not enabled)
                 * We can't select via html structure
                 * the outline heading consistently
                 * We do it then with the class value
                 */
                $outlineClass = Outline::getOutlineHeadingClass();
                return <<<EOF
$mainContainerSelector { counter-set: h2 0 h3 0 h4 0 h5 0 h6 0; }
$mainContainerSelector h2.$outlineClass::before { counter-increment: h2; counter-set: h3 0 h4 0 h5 0 h6 0; content: "$prefix" counter(h2, $level2CounterStyle) "$suffix\A"; }
$mainContainerSelector h3.$outlineClass::before { counter-increment: h3; counter-set: h4 0 h5 0 h6 0; content: "$prefix" counter(h2, $level2CounterStyle) "$counterSeparator" counter(h3,$level3CounterStyle) "$suffix\A"; }
$mainContainerSelector h4.$outlineClass::before { counter-increment: h4; counter-set: h5 0 h6 0; content: "$prefix" counter(h2, $level2CounterStyle) "$counterSeparator" counter(h3,$level3CounterStyle) "$counterSeparator" counter(h4,$level4CounterStyle) "$suffix\A"; }
$mainContainerSelector h5.$outlineClass::before { counter-increment: h5; counter-set: h6 0; content: "$prefix" counter(h2, $level2CounterStyle) "$counterSeparator" counter(h3,$level3CounterStyle) "$counterSeparator" counter(h4,$level4CounterStyle) "$counterSeparator" counter(h5,$level5CounterStyle) "$suffix\A"; }
$mainContainerSelector h6.$outlineClass::before { counter-increment: h6; content: "$prefix" counter(h2, $level2CounterStyle) "$counterSeparator" counter(h3,$level3CounterStyle) "$counterSeparator" counter(h4,$level4CounterStyle) "$counterSeparator" counter(h5,$level5CounterStyle) "$counterSeparator" counter(h6,$level6CounterStyle) "$suffix\A"; }
EOF;
            case self::TOC_NUMBERING:
                /**
                 * The level counter on the toc are based
                 * on the https://www.dokuwiki.org/config:toptoclevel
                 * configuration
                 * if toptoclevel = 2, then level1 = h2 and not h1
                 * @deprecated
                 */
                // global $conf;
                // $topTocLevel = $conf['toptoclevel'];

                $tocSelector = "." . Toc::getClass() . " ul";
                return <<<EOF
$tocSelector li { counter-increment: toc2; }
$tocSelector li li { counter-increment: toc3; }
$tocSelector li li li { counter-increment: toc4; }
$tocSelector li li li li { counter-increment: toc5; }
$tocSelector li li li li li { counter-increment: toc6; }
$tocSelector li a::before { content: "$prefix" counter(toc2, $level2CounterStyle) "$suffix\A"; }
$tocSelector li li a::before { content: "$prefix" counter(toc2, $level2CounterStyle) "$counterSeparator" counter(toc3,$level3CounterStyle) "$suffix\A"; }
$tocSelector li li li a::before { content: "$prefix" counter(toc2, $level2CounterStyle) "$counterSeparator" counter(toc3,$level3CounterStyle) "$counterSeparator" counter(toc4,$level4CounterStyle) "$suffix\A"; }
$tocSelector li li li li a::before { content: "$prefix" counter(toc2, $level2CounterStyle) "$counterSeparator" counter(toc3,$level3CounterStyle) "$counterSeparator" counter(toc4,$level4CounterStyle) "$counterSeparator" counter(toc5,$level5CounterStyle) "$suffix\A"; }
$tocSelector li li li li li a::before { content: "$prefix" counter(toc2, $level2CounterStyle) "$counterSeparator" counter(toc3,$level3CounterStyle) "$counterSeparator" counter(toc4,$level4CounterStyle) "$counterSeparator" counter(toc5,$level5CounterStyle) "$counterSeparator" counter(toc6,$level6CounterStyle) "$suffix\A"; }
EOF;

            default:
                throw new ExceptionBadSyntax("The type ($type) is unknown");
        }


    }

    /**
     * @throws ExceptionNotFound
     */
    public static function createFromMarkupPath(MarkupPath $markupPath): Outline
    {
        $path = $markupPath->getPathObject();
        if(!($path instanceof WikiPath)){
            throw new ExceptionRuntimeInternal("The path is not a wiki path");
        }
        $markup = FileSystems::getContent($path);
        $instructions = MarkupRenderer::createFromMarkup($markup, $path, $path)
            ->setRequestedMimeToInstruction()
            ->getOutput();
        $callStack = CallStack::createFromInstructions($instructions);
        return new Outline($callStack, $markupPath);
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
                    $headingCall = $outlineSection->getEnterHeadingCall();
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
            $this->actualSection->addHeaderCall($actualCall);
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
    public function toTocDokuwikiFormat(): array
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
            $headingCall = $firstChild->getEnterHeadingCall();
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
        if ($this->markupPath != null && $outlineSection->getLevel() === 1) {
            $label = $outlineSection->getLabel();
            PageH1::createForPage($this->markupPath)
                ->persistDefaultValue($label);
        }
    }

    private
    function storeTocForMarkupIfAny()
    {
        if (!isset($this->markupPath)) {
            return;
        }
        $toc = $this->toTocDokuwikiFormat();
        try {
            Toc::createForPage($this->markupPath)
                ->setValue($toc)
                ->persist();
        } catch (ExceptionBadArgument $e) {
            LogUtility::error("The Toc could not be persisted. Error:{$e->getMessage()}");
        }
    }

    public
    function getMarkupPath(): MarkupPath
    {
        return $this->markupPath;
    }


}
