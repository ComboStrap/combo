<?php

namespace ComboStrap;


use syntax_plugin_combo_heading;

class OutlineSection extends TreeNode
{
    const CANONICAL = "outline";
    const HEADER_DOKUWIKI_CALL = "header";


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


    private int $startFileIndex;
    private ?int $endFileIndex = null;

    private ?Call $headingEnterCall;
    /**
     * @var array an array to make sure that the id are unique
     */
    private array $tocUniqueId = [];



    /**
     * @param Call|null $headingEnterCall - null if the section is the root
     */
    private function __construct(Call $headingEnterCall = null)
    {
        $this->headingEnterCall = $headingEnterCall;
        if ($headingEnterCall !== null) {
            $this->startFileIndex = $headingEnterCall->getFirstMatchedCharacterPosition();
            $this->addHeaderCall($headingEnterCall);
        } else {
            $this->startFileIndex = 0;
        }

    }


    public static function createOutlineRoot(): OutlineSection
    {
        return new OutlineSection(null);
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

    public static function createFromEnterHeadingCall(Call $enterHeadingCall): OutlineSection
    {
        return new OutlineSection($enterHeadingCall);
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
            case self::HEADER_DOKUWIKI_CALL:
                $level = $this->headingEnterCall->getInstructionCall()[1][1];
                break;
            default:
                $level = $this->headingEnterCall->getAttribute(syntax_plugin_combo_heading::LEVEL);
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
            case self::HEADER_DOKUWIKI_CALL:
                $this->headingEnterCall->getInstructionCall()[1][1] = $level;
                break;
            default:
                $this->headingEnterCall->setAttribute(syntax_plugin_combo_heading::LEVEL, $level);
                $headingExitCall = $this->headingCalls[count($this->headingCalls) - 1];
                $headingExitCall->setAttribute(syntax_plugin_combo_heading::LEVEL, $level);
                break;
        }

        /**
         * Update the descdenants sections
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


}
