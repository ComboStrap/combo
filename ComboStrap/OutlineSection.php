<?php

namespace ComboStrap;


class OutlineSection
{


    /**
     *
     * @var Call[] $calls
     */
    private array $calls = [];

    private array $headingTagNames = [\syntax_plugin_combo_heading::TAG, "header", \syntax_plugin_combo_headingwiki::TAG, \syntax_plugin_combo_headingatx::TAG];

    /**
     */
    public function __construct()
    {
    }

    public static function create(): OutlineSection
    {
        return new OutlineSection();
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

}
