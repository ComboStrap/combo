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
     * @var array|null - the attributes of the element (null means that the default value will be used, ie when combo is not used)
     */
    private ?array $attributes = null;

    /**
     * @throws ExceptionBadArgument - when the area name is unknown
     * @throws ExceptionCompile - when the strap template is not available
     */
    public static function getSlotNameForArea($area)
    {
        switch ($area) {
            case Layout::PAGE_HEADER_AREA:
                return Site::getPageHeaderSlotName();
            case Layout::PAGE_FOOTER_AREA:
                return Site::getPageFooterSlotName();
            default:
                throw new ExceptionBadArgument("The area ($area) is unknown");
        }
    }

    public static function getDefaultAreaContentPath($areaName): DokuPath
    {
        return DokuPath::createComboResource(":pages:$areaName.md");
    }


    public function setAttributes(array $attributes): LayoutArea
    {
        $this->attributes = $attributes;
        return $this;
    }


    public function setSlotName($slotName): LayoutArea
    {
        $this->slotName = $slotName;
        return $this;
    }


    public function getPage(): Page
    {
        // Main content
        $requestedPage = Page::createPageFromRequestedPage();
        if ($this->areaId === Layout::MAIN_CONTENT_AREA) {
            return $requestedPage;
        }
        // Slot
        try {
            $closestPath = FileSystems::closest($requestedPage->getPath(), $this->slotName . DokuPath::PAGE_FILE_TXT_EXTENSION);
        } catch (ExceptionNotFound $e) {
            $closestPath = self::getDefaultAreaContentPath($this->areaId);
            if (!FileSystems::exists($closestPath)) {
                $closestPath = null;
                LogUtility::errorIfDevOrTest("The default $this->areaId page does not exist.");
            }
        }
        return Page::createPageFromPathObject($closestPath);
    }

    public function getHtml(): ?string
    {
        return $this->html;
    }

    public function getAttributes(): ?array
    {
        return $this->attributes;
    }

    public function render()
    {
        $page = $this->getPage();
        try {
            $html = $page->toXhtml();
            return EditButton::replaceOrDeleteAll($html);
        } catch (\Exception $e) {
            return "Rendering the slot ($page), returns an error. {$e->getMessage()}";
        }

    }
}
