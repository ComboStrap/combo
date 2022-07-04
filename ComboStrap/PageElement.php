<?php


namespace ComboStrap;


/**
 * This class represents a page layout element.
 *
 * It wraps a DOM Element with extra properties needed in a page layout.
 *
 * It represents a:
 *   * a {@link PageElement::isSlot() slot} with content
 *   * or a {@link PageElement::isContainer() container} without content
 *
 */
class PageElement
{

    /**
     * @var string
     * The html may be null to set
     * the default (for instance, with a page header)
     */
    private $html = null;

    private FetcherPage $fetcherPage;
    private XmlElement $domElement;


    public function __construct(XmlElement $DOMElement, FetcherPage $fetcherPage)
    {

        $this->fetcherPage = $fetcherPage;
        $this->domElement = $DOMElement;
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
            case FetcherPage::PAGE_HEADER_AREA:
                return Site::getPageHeaderSlotName();
            case FetcherPage::PAGE_FOOTER_AREA:
                return Site::getPageFooterSlotName();
            default:
                throw new ExceptionBadArgument("The area ($area) is unknown");
        }
    }

    /**
     * This function is static because it's also used to
     * put a default template when creating a new slot
     * @param string $areaId
     * @return WikiPath
     */
    public static function getDefaultAreaContentPath(string $areaId): WikiPath
    {
        return WikiPath::createComboResource(":pages:$areaId.md");
    }


    public function setAttributes(array $attributes): PageElement
    {
        $this->attributes = $attributes;
        return $this;
    }

    /**
     * @throws ExceptionNotFound
     */
    public function getLastFileNameForSlot()
    {
        $elementId = $this->getId();
        if (!$this->isSlot()) {
            throw new ExceptionNotFound("No slot name for container element");
        }
        switch ($elementId) {
            case FetcherPage::PAGE_HEADER_AREA:
                return Site::getPageHeaderSlotName();
            case FetcherPage::PAGE_FOOTER_AREA:
                return Site::getPageFooterSlotName();
            case FetcherPage::MAIN_CONTENT_AREA:
                throw new ExceptionRuntimeInternal("Main content area is not a slot and does not have any last slot name");
            case FetcherPage::PAGE_SIDE_AREA:
                return Site::getSidebarName();
            case FetcherPage::MAIN_SIDE_AREA:
                return Site::getPageSideSlotName();
            case FetcherPage::MAIN_HEADER_AREA:
                return "slot_main_header";
            case FetcherPage::MAIN_FOOTER_AREA:
                return "slot_main_footer";
            default:
                throw new ExceptionRuntimeInternal("Internal: The element ($elementId) was unexpected.");
        }
    }


    public
    function __toString()
    {
        return "Page layout element {$this->fetcherPage->getRequestedPath()} / {$this->getId()}";
    }


    public
    function getHtml(): ?string
    {
        return $this->html;
    }

    public
    function getAttributes(): ?array
    {
        return $this->attributes;
    }


    public
    function isContainer(): bool
    {
        return !$this->isSlot();
    }

    /**
     * Create a valid variable name
     * @return string
     */
    public
    function getVariableName(): string
    {
        return Template::toValidVariableName($this->getId());
    }

    /**
     * @return bool - a slot area expect content
     */
    public
    function isSlot(): bool
    {
        return !$this->domElement->hasChildrenElement();
    }

    /**
     * @throws ExceptionNotFound - if the area is not a slot or there is no path found
     */
    public function getFragmentPath()
    {
        if (!$this->isSlot()) {
            throw new ExceptionNotFound("No fragment path for container element");
        }
        // Main content
        $requestedPath = $this->fetcherPage->getRequestedPath();
        if ($this->getId() === FetcherPage::MAIN_CONTENT_AREA) {
            return $requestedPath;
        }
        // Slot
        try {
            return FileSystems::closest($requestedPath, $this->getLastFileNameForSlot() . WikiPath::PAGE_FILE_TXT_EXTENSION);
        } catch (ExceptionNotFound $e) {

            /**
             * Default page side is for page that are not in the root
             */
            $requestedPage = PageFragment::createPageFromPathObject($this->fetcherPage->getRequestedPath());
            switch ($this->getId()) {
                case FetcherPage::PAGE_SIDE_AREA:
                    try {
                        $requestedPage->getPath()->getParent();
                    } catch (ExceptionNotFound $e) {
                        // no parent page, no side bar
                        throw new ExceptionNotFound("No page side for pages in the root directory.");
                    }
                    break;
                case FetcherPage::MAIN_HEADER_AREA:
                    if ($requestedPage->isRootHomePage()) {
                        throw new ExceptionNotFound("No $this for the home");
                    }
                    break;
            }
            $closestPath = self::getDefaultAreaContentPath($this->getId());
            if (!FileSystems::exists($closestPath)) {
                throw new ExceptionNotFound("The default slot page for the area ($this) does not exist at ($closestPath)");
            }
            return $closestPath;
        }
    }

    public function getId(): string
    {
        return $this->domElement->getId();
    }

    public function getDomElement(): XmlElement
    {
        return $this->domElement;
    }
}
