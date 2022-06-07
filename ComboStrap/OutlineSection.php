<?php

namespace ComboStrap;


use syntax_plugin_combo_heading;

class OutlineSection extends TreeNode
{
    const CANONICAL = "outline";
    const HEADER_DOKUWIKI_CALL = "header";


    /**
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
    private ?int $endFileIndex=null;

    private ?Call $headingEnterCall;
    /**
     * @var array an array to make sure that the id are unique
     */
    private array $tocUniqueId = [];


    /**
     * @param OutlineSection|null $parentSection
     * @param Call|null $headingEnterCall
     */
    public function __construct(?OutlineSection $parentSection, Call $headingEnterCall = null)
    {
        $this->headingEnterCall = $headingEnterCall;
        if ($headingEnterCall !== null) {
            $this->startFileIndex = $headingEnterCall->getFirstMatchedCharacterPosition();
        } else {
            $this->startFileIndex = 0;
        }
        parent::__construct($parentSection);
    }


    public static function createOutlineRoot(): OutlineSection
    {
        return new OutlineSection(null);
    }

    public static function createChildOutlineSection(OutlineSection $parentSection, Call $headingCall): OutlineSection
    {
        $outlineSection = new OutlineSection($parentSection, $headingCall);
        $parentSection->appendChild($outlineSection);
        return $outlineSection;
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

    public function addHeadingCall(Call $actualCall): OutlineSection
    {

        $this->headingCalls[] = $actualCall;
        return $this;
    }

    public function getLabel(): string
    {
        $label = "";
        foreach ($this->headingCalls as $call) {
            \action_plugin_combo_headingpostprocessing::addToTextHeading($label, $call);
        }
        return $label;
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

        if ($this->headingEnterCall !== null && $this->headingEnterCall->isPluginCall()) {
            $id = $this->headingEnterCall->getAttribute("id");
            if ($id === null) {
                $label = $this->getLabel();
                $id = sectionID($label, $this->tocUniqueId);
                $this->headingEnterCall->addAttribute("id", $id);
            }
        }
        return $this->headingCalls;
    }


    public function getHeadingCall(): ?Call
    {
        return $this->headingEnterCall;
    }


    public function getCalls(): array
    {
        return array_merge($this->headingCalls, $this->contentCalls);
    }

    public function getContentCalls(): array
    {
        return $this->contentCalls;
    }

    /**
     * @return int
     */
    public function getLevel(): int
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

    public function getStartPosition(): int
    {
        return $this->startFileIndex;
    }

    public function getEndPosition(): ?int
    {
        return $this->endFileIndex;
    }

    public function hasContentCall(): bool
    {
        return sizeof($this->contentCalls) > 0;
    }


}
