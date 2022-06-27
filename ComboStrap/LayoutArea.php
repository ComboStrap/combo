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


    /**
     * @throws ExceptionNotFound - if there is no page
     * @throws ExceptionInternal - if this is an error
     */
    public function getPage(): PageFragment
    {
        // Main content
        $requestedPage = PageFragment::createPageFromRequestedPage();
        if ($this->areaId === Layout::MAIN_CONTENT_AREA) {
            return $requestedPage;
        }
        // Slot
        try {
            $closestPath = FileSystems::closest($requestedPage->getPath(), $this->slotName . DokuPath::PAGE_FILE_TXT_EXTENSION);
        } catch (ExceptionNotFound $e) {

            /**
             * Default page side is for page that are not in the root
             */
            switch ($this->areaId) {
                case Layout::PAGE_SIDE_AREA:
                    try {
                        $requestedPage->getPath()->getParent();
                    } catch (ExceptionNotFound $e) {
                        // no parent page, no side bar
                        throw new ExceptionNotFound("No page side for root pages.");
                    }
                    break;
                case Layout::MAIN_HEADER_AREA:
                    if ($requestedPage->isRootHomePage()) {
                        throw new ExceptionNotFound("No $this for the home");
                    }
                    break;
                case Layout::MAIN_FOOTER_AREA:
                    throw new ExceptionNotFound("No default for $this");
            }
            $closestPath = self::getDefaultAreaContentPath($this->areaId);
            if (!FileSystems::exists($closestPath)) {
                throw new ExceptionInternal("The default slot page for the area ($this) does not exist at ($closestPath)");
            }

        }
        return PageFragment::createPageFromPathObject($closestPath);
    }

    public function __toString()
    {
        return $this->areaId;
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
        try {
            try {
                $page = $this->getPage();
            } catch (ExceptionNotFound $e) {
                return "";
            }
            $html = $page->toXhtml();
            return EditButton::replaceOrDeleteAll($html);
        } catch (\Exception $e) {
            return "Rendering the area ($this), returns an error. {$e->getMessage()}";
        }

    }

    public function isContainer(): bool
    {
        return in_array($this->areaId, [Layout::PAGE_CORE_AREA, Layout::PAGE_MAIN_AREA]);
    }

    /**
     * Create a valid variable name
     * @return string
     */
    public function getVariableName(): string
    {
        return Template::toValidVariableName($this->areaId);
    }
}
