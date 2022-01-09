<?php


namespace ComboStrap;


class FormMetaTab
{
    private $name;
    /**
     * @var string
     */
    private $label;
    /**
     * @var int
     */
    private $widthField;
    /**
     * @var int
     */
    private $widthLabel;


    /**
     * FormTab constructor.
     */
    public function __construct($tabName)
    {
        $this->name = $tabName;
    }

    public static function create(string $tabName): FormMetaTab
    {
        return new FormMetaTab($tabName);
    }

    public function setLabel(string $label): FormMetaTab
    {
        $this->label = $label;
        return $this;
    }

    /**
     * @param int $width2 - the width of the field column
     */
    public function setWidthField(int $width): FormMetaTab
    {
        $this->widthField = $width;
        return $this;
    }
    public function setWidthLabel(int $width): FormMetaTab
    {
        $this->widthLabel = $width;
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function toAssociativeArray(): array
    {
        $array["name"]= $this->name;
        if(!blank($this->label)) {
            $array["label"] = $this->label;
        }
        if(!blank($this->widthField)) {
            $array["width-field"] = $this->widthField;
        }
        if(!blank($this->widthLabel)) {
            $array["width-label"] = $this->widthLabel;
        }
        return $array;
    }
}
