<?php

namespace ComboStrap;

use ComboStrap\Meta\Field\FeaturedRasterImage;
use ComboStrap\Meta\Field\FeaturedSvgImage;
use ComboStrap\Tag\AdTag;

class OutlineVisitor
{


    private Outline $outline;
    private ?MarkupPath $markupPath;
    private int $currentLineCountSinceLastAd;
    /**
     * @var int the order, number of sections
     */
    private int $sectionNumbers;
    /**
     * @var int the number of ads inserted
     */
    private int $adsCounter;
    /**
     * @var OutlineSection The last section to be printed
     */
    private OutlineSection $lastSectionToBePrinted;
    private bool $inArticleEnabled;

    public function __construct(Outline $outline)
    {
        $this->outline = $outline;
        $this->markupPath = $outline->getMarkupPath();

        $this->inArticleEnabled = ExecutionContext::getActualOrCreateFromEnv()
            ->getConfig()
            ->getBooleanValue(AdTag::CONF_IN_ARTICLE_ENABLED, AdTag::CONF_IN_ARTICLE_ENABLED_DEFAULT);
        // Running variables that permits to balance the creation of Ads
        $this->currentLineCountSinceLastAd = 0;
        $this->sectionNumbers = 0;
        $this->adsCounter = 0;
        $this->lastSectionToBePrinted = $this->getLastSectionToBePrinted();

    }

    public static function create(Outline $outline): OutlineVisitor
    {
        return new OutlineVisitor($outline);
    }

    public function getCalls()
    {

        $totalCalls = [];
        $sectionSequenceId = 0;

        /**
         * Header Metadata
         *
         * On template that have an header, the h1 and the featured image are
         * captured and deleted by default to allow complex header layout
         *
         * Delete the parsed value (runtime works only on rendering)
         * TODO: move that to the metadata rendering by adding attributes
         *   because if the user changes the template, the parsing will not work
         *   it would need to parse the document again
         */
        $markupPath = $this->outline->getMarkupPath();
        if ($markupPath !== null) {
            FeaturedRasterImage::createFromResourcePage($markupPath)->setParsedValue();
            FeaturedSvgImage::createFromResourcePage($markupPath)->setParsedValue();
        }
        $captureHeaderMeta = $this->outline->getMetaHeaderCapture();


        /**
         * Transform and collect the calls in Instructions calls
         */
        $this->toHtmlSectionOutlineCallsRecurse($this->outline->getRootOutlineSection(), $totalCalls, $sectionSequenceId, $captureHeaderMeta);

        return array_map(function (Call $element) {
            return $element->getInstructionCall();
        }, $totalCalls);
    }

    /**
     * The visitor, we don't use a visitor pattern for now
     * @param OutlineSection $outlineSection
     * @param array $totalComboCalls
     * @param int $sectionSequenceId
     * @param bool $captureHeaderMeta
     * @return void
     */
    private function toHtmlSectionOutlineCallsRecurse(OutlineSection $outlineSection, array &$totalComboCalls, int &$sectionSequenceId, bool $captureHeaderMeta): void
    {

        $totalComboCalls[] = Call::createComboCall(
            SectionTag::TAG,
            DOKU_LEXER_ENTER,
            array(HeadingTag::LEVEL => $outlineSection->getLevel()),
            null,
            null,
            null,
            null,
            \syntax_plugin_combo_xmlblocktag::TAG
        );

        /**
         * In Ads Content Slot Calculation
         */
        $adCalls = [];
        if($this->inArticleEnabled) {
            $adCall = $this->getAdCall($outlineSection);
            if ($adCall !== null) {
                $adCalls = [$adCall];
            }
        }

        $contentCalls = $outlineSection->getContentCalls();
        if ($outlineSection->hasChildren()) {


            $actualChildren = $outlineSection->getChildren();

            if ($captureHeaderMeta && $outlineSection->getLevel() === 0) {
                // should be only one 1
                if (count($actualChildren) === 1) {
                    $h1Section = $actualChildren[array_key_first($actualChildren)];
                    if ($h1Section->getLevel() === 1) {
                        $h1ContentCalls = $h1Section->getContentCalls();
                        /**
                         * Capture the image if any
                         */
                        if ($this->markupPath !== null) {
                            foreach ($h1ContentCalls as $h1ContentCall) {
                                $tagName = $h1ContentCall->getTagName();
                                switch ($tagName) {
                                    case "p":
                                        continue 2;
                                    case "media":
                                        $h1ContentCall->addAttribute(Display::DISPLAY, Display::DISPLAY_NONE_VALUE);
                                        try {
                                            $fetcher = MediaMarkup::createFromCallStackArray($h1ContentCall->getAttributes())->getFetcher();
                                            switch (get_class($fetcher)) {
                                                case FetcherRaster::class:
                                                    $path = $fetcher->getSourcePath()->toAbsoluteId();
                                                    FeaturedRasterImage::createFromResourcePage($this->markupPath)->setParsedValue($path);
                                                    break;
                                                case FetcherSvg::class:
                                                    $path = $fetcher->getSourcePath()->toAbsoluteId();
                                                    FeaturedSvgImage::createFromResourcePage($this->markupPath)->setParsedValue($path);
                                                    break;
                                            }
                                        } catch (\Exception $e) {
                                            LogUtility::error("Error while capturing the feature images. Error: " . $e->getMessage(), Outline::CANONICAL, $e);
                                        }
                                        continue 2;
                                    default:
                                        // only the images found just after h1
                                        break;
                                }
                            }
                        }
                        $contentCalls = array_merge($contentCalls, $h1ContentCalls);
                        $actualChildren = $h1Section->getChildren();
                    }
                }
            }


            /**
             * If header has content,
             * we add it
             */
            $headerHasContent = !(empty($contentCalls) && empty($outlineSection->getHeadingCalls()) && empty($adCall));
            if ($headerHasContent) {
                $totalComboCalls = array_merge(
                    $totalComboCalls,
                    [$this->getOpenHeaderCall()],
                    $outlineSection->getHeadingCalls(),
                    $contentCalls,
                    $adCalls
                );
                $this->addSectionEditButtonComboFormatIfNeeded($outlineSection, $sectionSequenceId, $totalComboCalls);
                $totalComboCalls[] = $this->getCloseHeaderCall();
            }

            foreach ($actualChildren as $child) {
                $this->toHtmlSectionOutlineCallsRecurse($child, $totalComboCalls, $sectionSequenceId, $captureHeaderMeta);
            }

        } else {

            $totalComboCalls = array_merge(
                $totalComboCalls,
                $adCalls,
                $outlineSection->getHeadingCalls(),
                $contentCalls
            );

            $this->addSectionEditButtonComboFormatIfNeeded($outlineSection, $sectionSequenceId, $totalComboCalls);
        }

        $totalComboCalls[] = Call::createComboCall(
            SectionTag::TAG,
            DOKU_LEXER_EXIT,
            [],
            null,
            null,
            null,
            null,
            \syntax_plugin_combo_xmlblocktag::TAG
        );


    }

    /**
     * Add the edit button if needed
     * @param $outlineSection
     * @param $sectionSequenceId
     * @param array $totalInstructionCalls
     */
    private
    function addSectionEditButtonComboFormatIfNeeded(OutlineSection $outlineSection, int $sectionSequenceId, array &$totalInstructionCalls): void
    {
        if (!$outlineSection->hasParent()) {
            // no button for the root (ie the page)
            return;
        }
        if ($this->outline->isSectionEditingEnabled()) {

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


    private function getIsLastSectionToBePrinted(OutlineSection $outlineSection): bool
    {
        return $outlineSection === $this->lastSectionToBePrinted;
    }

    /**
     * @return OutlineSection - the last section to be printed
     */
    private function getLastSectionToBePrinted(): OutlineSection
    {
        return $this->getLastSectionToBePrintedRecurse($this->outline->getRootOutlineSection());
    }

    private function getLastSectionToBePrintedRecurse(OutlineSection $section): OutlineSection
    {
        $outlineSections = $section->getChildren();
        $array_key_last = array_key_last($outlineSections);
        if ($array_key_last !== null) {
            $lastSection = $outlineSections[$array_key_last];
            return $this->getLastSectionToBePrintedRecurse($lastSection);
        }
        return $section;
    }

    /**
     * Section Header Creation
     * If it has children and content, wrap the heading and the content
     * in a header tag
     * The header tag helps also to get the edit button to stay in place
     */
    private function getOpenHeaderCall(): Call
    {

        return Call::createComboCall(
            \syntax_plugin_combo_header::TAG,
            DOKU_LEXER_ENTER,
            array(
                TagAttributes::CLASS_KEY => StyleUtility::addComboStrapSuffix("outline-header"),
            ),
            Outline::CONTEXT
        );
    }

    private function getCloseHeaderCall(): Call
    {
        return Call::createComboCall(
            \syntax_plugin_combo_header::TAG,
            DOKU_LEXER_EXIT,
            [],
            Outline::CONTEXT
        );
    }

    private function getAdCall(OutlineSection $outlineSection): ?Call
    {
        $sectionLineCount = $outlineSection->getLineCount();
        $this->currentLineCountSinceLastAd = $this->currentLineCountSinceLastAd + $sectionLineCount;
        $this->sectionNumbers += 1;
        $isLastSection = $this->getIsLastSectionToBePrinted($outlineSection);
        if (AdTag::showAds(
            $sectionLineCount,
            $this->currentLineCountSinceLastAd,
            $this->sectionNumbers,
            $this->adsCounter,
            $isLastSection,
            $this->outline->getMarkupPath()
        )) {

            // Number of ads inserted
            $this->adsCounter += 1;
            // Reset the number of line betwee the last ad
            $this->currentLineCountSinceLastAd = 0;

            return Call::createComboCall(
                AdTag::MARKUP,
                DOKU_LEXER_SPECIAL,
                array(AdTag::NAME_ATTRIBUTE => AdTag::PREFIX_IN_ARTICLE_ADS . $this->adsCounter),
                Outline::CONTEXT,
                null,
                null,
                null,
                \syntax_plugin_combo_xmlblockemptytag::TAG
            );
        }
        return null;
    }
}
