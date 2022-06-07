<?php

namespace ComboStrap;


class OutlineSection extends TreeNode
{


    /**
     *
     * @var Call[] $calls
     */
    private array $calls = [];

    private array $headingTagNames = [\syntax_plugin_combo_heading::TAG, "header", \syntax_plugin_combo_headingwiki::TAG, \syntax_plugin_combo_headingatx::TAG];


    private int $startFileIndex;
    private int $endFileIndex;


    public static function createOutlineRoot(): OutlineSection
    {
        return new OutlineSection('root', null);
    }

    public static function createChildOutlineSection(string $identifier, OutlineSection $parentSection): OutlineSection
    {
        $outlineSection = new OutlineSection($identifier, $parentSection);
        $parentSection->appendChild($outlineSection);
        return $outlineSection;
    }

    public function getFirstChild(): OutlineSection
    {

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return parent::getFirstChild();

    }


    public function addCall(Call $actualCall): OutlineSection
    {
        $this->calls[] = $actualCall;
        return $this;
    }

    public function getLabel(): string
    {
        $label = "";
        foreach ($this->calls as $call) {
            if ($call->getState() === DOKU_LEXER_EXIT && in_array($call->getTagName(), $this->headingTagNames)) {
                break;
            }
            \action_plugin_combo_headingpostprocessing::addToTextHeading($label, $call);
        }
        return $label;
    }

    public function setStartPosition(int $startPosition)
    {
        $this->startFileIndex = $startPosition;
    }
    public function setEndPosition(int $endFileIndex)
    {
        $this->endFileIndex = $endFileIndex;
    }

    /**
     * @return Call[]
     */
    public function getCalls(): array
    {
        return $this->calls;
    }


}
