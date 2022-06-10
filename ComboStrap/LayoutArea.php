<?php


namespace ComboStrap;

/**
 * Represents a layout area
 */
class LayoutArea
{
    /**
     * @var string
     */
    private $areaId;
    /**
     * @var string
     * The html may be null to set
     * the default (for instance, with a page header)
     */
    private $html = null;
    private $slotName = "";
    /**
     * @var string
     */
    private $tag;

    public function __construct(string $areaId)
    {
        $this->areaId = $areaId;
    }


    /**
     * @var bool show or not the area
     * null means that combo is not installed
     * because there is no true/false
     * and the rendering is done at the dokuwiki way
     */
    private $show = null;
    /**
     * @var array|null - the attributes of the element (null means that the default value will be used, ie when combo is not used)
     */
    private $attributes = null;

    public function setShow(bool $show)
    {
        $this->show = $show;
    }

    public function toEnterHtmlTag(string $tag = null): string
    {

        if ($tag === null) {
            $tag = $this->getTagOrDefault();
        }
        $htmlAttributesAsArray = [];
        $attributes = $this->attributes;
        if ($attributes === null) {
            $attributes = [];
        }
        foreach ($attributes as $attribute => $value) {
            $attribute = htmlspecialchars($attribute, ENT_XHTML | ENT_QUOTES);
            $value = htmlspecialchars($value, ENT_XHTML | ENT_QUOTES);
            $htmlAttributesAsArray[] = "$attribute=\"$value\"";
        };
        $htmlAttributesAsString = "";
        if (sizeof($htmlAttributesAsArray) > 0) {
            $htmlAttributesAsString = " " . implode(" ", $htmlAttributesAsArray);
        }
        return "<$tag id=\"$this->areaId\"$htmlAttributesAsString>";
    }

    public function setAttributes(array $attributes): LayoutArea
    {
        $this->attributes = $attributes;
        return $this;
    }

    public function setHtml(string $html): LayoutArea
    {
        $this->html = $html;
        return $this;
    }

    public function getSlotName(): string
    {
        return $this->slotName;
    }

    public function setSlotName($slotName): LayoutArea
    {
        $this->slotName = $slotName;
        return $this;
    }

    public function show(): ?bool
    {
        return $this->show;
    }

    public function getHtml(): ?string
    {
        return $this->html;
    }

    public function getAttributes(): ?array
    {
        return $this->attributes;
    }

    public function setTag(string $string): LayoutArea
    {
        $this->tag = $string;
        return $this;
    }

    public function getTagOrDefault($default = "div")
    {
        if ($this->tag !== null) {
            return $this->tag;
        }
        return $default;

    }

    public function toExitTag(string $tag = null): string
    {
        if ($tag === null) {
            $tag = $this->getTagOrDefault();
        }
        return "</$tag>";

    }

}
