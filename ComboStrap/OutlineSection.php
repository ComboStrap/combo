<?php

namespace ComboStrap;


class OutlineSection extends TreeNode
{
    const CANONICAL = "outline";


    /**
     * Not to confound with header calls that are {@link OutlineSection::getContentCalls()}
     * of a section that has children
     *
     * @var Call[] $headingCalls
     */
    private array $headingCalls = [];
    /**
     *
     * @var Call[] $contentCalls
     */
    private array $contentCalls = [];


    private string $headingId;

    private int $startFileIndex;
    private ?int $endFileIndex = null;

    /**
     * @var Call|null - the first heading call for the section
     */
    private ?Call $headingEnterCall;
    /**
     * @var array an array to make sure that the id are unique
     */
    private array $tocUniqueId = [];

    /**
     * @var int - a best guess on the number of
     */
    private int $lineNumber;
    /**
     * @var Outline - the outline that created this section (only on root, this is to get the path for the heading)
     */
    private Outline $outlineContext;


    /**
     * @param Call|null $headingEnterCall - null if the section is the root
     */
    private function __construct(Outline $outlineContext,Call $headingEnterCall = null)
    {
        $this->outlineContext = $outlineContext;
        $this->headingEnterCall = $headingEnterCall;
        if ($headingEnterCall !== null) {
            $position = $headingEnterCall->getFirstMatchedCharacterPosition();
            if ($position === null) {
                $this->startFileIndex = 0;
            } else {
                $this->startFileIndex = $position;
            }
            $this->addHeaderCall($headingEnterCall);
            // We persist the id for level 1 because the heading tag may be deleted
            if ($this->getLevel() === 1) {
                $this->headingEnterCall->setAttribute("id", $this->getHeadingId());
            }
        } else {
            $this->startFileIndex = 0;
        }
        $this->lineNumber = 1; // the heading

    }


    public static function createOutlineRoot(Outline $outlineContext): OutlineSection
    {
        return new OutlineSection($outlineContext,null);
    }


    /**
     * Return a text to an HTML Id
     * @param string $fragment
     * @return string
     */
    public static function textToHtmlSectionId(string $fragment): string
    {
        $check = false;
        // for empty string, the below function returns `section`
        return sectionID($fragment, $check);
    }

    public static function createFromEnterHeadingCall(Outline $outline,Call $enterHeadingCall): OutlineSection
    {
        return new OutlineSection($outline, $enterHeadingCall);
    }

    public function getFirstChild(): OutlineSection
    {

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return parent::getFirstChild();

    }


    public function addContentCall(Call $actualCall): OutlineSection
    {

        $this->contentCalls[] = $actualCall;
        return $this;


    }

    public function addHeaderCall(Call $actualCall): OutlineSection
    {

        $this->headingCalls[] = $actualCall;
        return $this;
    }

    public function getLabel(): string
    {
        $label = "";
        foreach ($this->headingCalls as $call) {
            if ($call->getTagName() === Outline::DOKUWIKI_HEADING_CALL_NAME) {
                $label = $call->getInstructionCall()[1][0];
                // no more label call
                break;
            }
            if ($call->isTextCall()) {
                // Building the text for the toc
                // only cdata for now
                // no image, ...
                if ($label != "") {
                    $label .= " ";
                }
                $label .= trim($call->getCapturedContent());
            }
        }
        return trim($label);
    }

    public function setStartPosition(int $startPosition): OutlineSection
    {
        $this->startFileIndex = $startPosition;
        return $this;
    }

    public function setEndPosition(int $endFileIndex): OutlineSection
    {
        $this->endFileIndex = $endFileIndex;
        return $this;
    }

    /**
     * @return Call[]
     */
    public function getHeadingCalls(): array
    {
        if (
            $this->headingEnterCall !== null &&
            $this->headingEnterCall->isPluginCall() &&
            !$this->headingEnterCall->hasAttribute("id")
        ) {
            $this->headingEnterCall->addAttribute("id", $this->getHeadingId());
        }
        return $this->headingCalls;
    }


    public
    function getEnterHeadingCall(): ?Call
    {
        return $this->headingEnterCall;
    }


    public
    function getCalls(): array
    {
        return array_merge($this->headingCalls, $this->contentCalls);
    }

    public
    function getContentCalls(): array
    {
        return $this->contentCalls;
    }

    /**
     * @return int
     */
    public
    function getLevel(): int
    {
        if ($this->headingEnterCall === null) {
            return 0;
        }
        switch ($this->headingEnterCall->getTagName()) {
            case Outline::DOKUWIKI_HEADING_CALL_NAME:
                $level = $this->headingEnterCall->getInstructionCall()[1][1];
                break;
            default:
                $level = $this->headingEnterCall->getAttribute(HeadingTag::LEVEL);
                break;
        }

        try {
            return DataType::toInteger($level);
        } catch (ExceptionBadArgument $e) {
            // should not happen
            LogUtility::internalError("The level ($level) could not be cast to an integer", self::CANONICAL);
            return 0;
        }
    }

    public
    function getStartPosition(): int
    {
        return $this->startFileIndex;
    }

    public
    function getEndPosition(): ?int
    {
        return $this->endFileIndex;
    }

    public
    function hasContentCall(): bool
    {
        return sizeof($this->contentCalls) > 0;
    }

    /**
     */
    public
    function getHeadingId()
    {

        if (!isset($this->headingId)) {
            $id = $this->headingEnterCall->getAttribute("id");
            if ($id !== null) {
                return $id;
            }

            $label = $this->getLabel();

            /**
             * For Level 1 (ie Heading 1), we use the path as id and not the label
             * Why? because when we bundle all pages in a single page
             * (With {@link FetcherPageBundler}
             * we can transform a wiki link to an internal link
             */
            $level = $this->getLevel();
            if ($level === 1) {
                // id is the path id
                $markupPath = $this->getRoot()->outlineContext->getMarkupPath();
                if ($markupPath !== null) {
                    $label = $markupPath->toAbsoluteId();
                }
            }

            $this->headingId = sectionID($label, $this->tocUniqueId);
        }
        return $this->headingId;

    }

    /**
     * A HTML section should have a heading
     * but in a markup document, we may have data before the first
     * heading making a section without heading
     * @return bool
     */
    public
    function hasHeading(): bool
    {
        return $this->headingEnterCall !== null;
    }

    /**
     * @return OutlineSection[]
     */
    public
    function getChildren(): array
    {
        return parent::getChildren();
    }

    public function setLevel(int $level): OutlineSection
    {

        switch ($this->headingEnterCall->getTagName()) {
            case Outline::DOKUWIKI_HEADING_CALL_NAME:
                $this->headingEnterCall->getInstructionCall()[1][1] = $level;
                break;
            default:
                $this->headingEnterCall->setAttribute(HeadingTag::LEVEL, $level);
                $headingExitCall = $this->headingCalls[count($this->headingCalls) - 1];
                $headingExitCall->setAttribute(HeadingTag::LEVEL, $level);
                break;
        }

        /**
         * Update the descendants sections
         * @param OutlineSection $parentSection
         * @return void
         */
        $updateLevel = function (OutlineSection $parentSection) {
            foreach ($parentSection->getChildren() as $child) {
                $child->setLevel($parentSection->getLevel() + 1);
            }
        };
        TreeVisit::visit($this, $updateLevel);

        return $this;
    }


    public function deleteContentCalls(): OutlineSection
    {
        $this->contentCalls = [];
        return $this;
    }

    public function incrementLineNumber(): OutlineSection
    {
        $this->lineNumber++;
        return $this;
    }

    public function getLineCount(): int
    {
        return $this->lineNumber;
    }

    private function getRoot()
    {
        $actual = $this;
        while ($actual->hasParent()) {
            try {
                $actual = $actual->getParent();
            } catch (ExceptionNotFound $e) {
                // should not as we check before
            }
        }
        return $actual;
    }

    /**
     * @param MarkupPath|null $startPath - the path from where the page bundle is started to see if the link is of a page that was bundled
     * @return $this - when merging 2 page, we need to make sure that the link becomes internal
     * if the page was bundled
     * (ie a link to :page:yolo become #pageyolo)
     */
    public function updatePageLinkToInternal(?MarkupPath $startPath): OutlineSection
    {
        foreach ($this->contentCalls as $contentCall) {

            if (!$contentCall->isPluginCall()) {
                continue;
            }
            $componentName = $contentCall->getComponentName();
            if ($componentName === "combo_link" && $contentCall->getState() === DOKU_LEXER_ENTER) {
                $refString = $contentCall->getAttribute("ref");
                if ($refString === null) {
                    continue;
                }
                try {
                    $markupRef = MarkupRef::createLinkFromRef($refString);
                } catch (ExceptionBadArgument|ExceptionBadSyntax|ExceptionNotFound $e) {
                    // pffff
                    continue;
                }
                if ($markupRef->getSchemeType() !== MarkupRef::WIKI_URI) {
                    continue;
                }
                try {
                    $parentPath = $startPath->toWikiPath()->getParent()->toAbsoluteId();
                } catch (ExceptionNotFound $e) {
                    // root then
                    $parentPath = ":";
                }
                if (!StringUtility::startWiths($refString, $parentPath)) {
                    continue;
                }
                $noCheck = false;
                $expectedH1ID = sectionID($refString, $noCheck);
                $contentCall->setAttribute("ref", "#" . $expectedH1ID);

            }
        }

        /**
         * Update the links to internal
         */
        $updateLink = function (OutlineSection $parentSection) use ($startPath) {
            foreach ($parentSection->getChildren() as $child) {
                $child->updatePageLinkToInternal($startPath);
            }
        };
        TreeVisit::visit($this, $updateLink);
        return $this;
    }


}
