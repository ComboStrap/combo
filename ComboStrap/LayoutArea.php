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
    private ?bool $show = null;
    /**
     * @var array|null - the attributes of the element (null means that the default value will be used, ie when combo is not used)
     */
    private ?array $attributes = null;

    public function setShow(bool $show)
    {
        $this->show = $show;
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



}
