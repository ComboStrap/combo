<?php


namespace ComboStrap;


/**
 * This class represents a page layout element.
 *
 * It wraps a DOM Element with extra properties needed in a page layout.
 *
 * It represents a:
 *   * a {@link PageLayoutElement::isSlot() slot} with {@link FetcherMarkup content}
 *   * or a {@link PageLayoutElement::isContainer() container} without content
 *
 * It's used in the {@link FetcherPage} as utility class
 */
class PageLayoutElement
{

    /**
     * @var PageLayout - the page layout of this page element
     */
    private PageLayout $pageLayout;
    /**
     * @var XmlElement - the xml element of this page element
     */
    private XmlElement $domElement;
    /**
     * @var FetcherMarkup - the fetcher if this is a slot and has a page fragment source
     */
    private FetcherMarkup $fetcherFragment;


    public function __construct(XmlElement $DOMElement, PageLayout $pageLayout)
    {

        $this->pageLayout = $pageLayout;
        $this->domElement = $DOMElement;

    }


    /**
     * @var array|null - the attributes of the element (null means that the default value will be used, ie when combo is not used)
     */
    private ?array $attributes = null;

    /**
     */
    public static function getSlotNameForElementId($elementId)
    {
        switch ($elementId) {
            case PageLayout::PAGE_HEADER_ELEMENT:
                return Site::getPageHeaderSlotName();
            case PageLayout::PAGE_FOOTER_ELEMENT:
                return Site::getPageFooterSlotName();
            case PageLayout::MAIN_CONTENT_ELEMENT:
                throw new ExceptionRuntimeInternal("Main content area is not a slot and does not have any last slot name");
            case PageLayout::PAGE_SIDE_ELEMENT:
                return Site::getSidebarName();
            case PageLayout::MAIN_SIDE_ELEMENT:
                return Site::getPageSideSlotName();
            case PageLayout::MAIN_HEADER_ELEMENT:
                return "slot_main_header";
            case PageLayout::MAIN_FOOTER_ELEMENT:
                return "slot_main_footer";
            default:
                throw new ExceptionRuntimeInternal("Internal: The element ($elementId) was unexpected, it's not a slot");
        }

    }

    /**
     * This function is static because it's also used to
     * put a default template when creating a new slot
     * @param string $areaId
     * @return WikiPath
     */
    public static function getDefaultElementContentPath(string $areaId): WikiPath
    {
        return WikiPath::createComboResource(":pages:$areaId.md");
    }


    public function setAttributes(array $attributes): PageLayoutElement
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
        return self::getSlotNameForElementId($elementId);
    }


    public
    function __toString()
    {
        return "Page layout element {$this->pageLayout} / {$this->getId()}";
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
        if ($this->getId() === PageLayout::PAGE_TOOL_ELEMENT) {
            return false;
        }
        return !$this->domElement->hasChildrenElement();
    }

    /**
     * @throws ExceptionNotFound - if the area is not a slot or there is no path found
     */
    private function getFragmentPath()
    {
        if (!$this->isSlot()) {
            throw new ExceptionNotFound("No fragment path for container element");
        }
        // Main content
        $requestedPath = $this->pageLayout->getRequestedContextPath();
        if ($this->getId() === PageLayout::MAIN_CONTENT_ELEMENT) {
            return $requestedPath;
        }
        // Slot
        try {
            return FileSystems::closest($requestedPath, $this->getLastFileNameForSlot() . WikiPath::PAGE_FILE_TXT_EXTENSION);
        } catch (ExceptionNotFound $e) {

            /**
             * Default page side is for page that are not in the root
             */
            $requestedPage = MarkupPath::createPageFromPathObject($this->pageLayout->getRequestedContextPath());
            switch ($this->getId()) {
                case PageLayout::PAGE_SIDE_ELEMENT:
                    try {
                        $requestedPage->getPathObject()->getParent();
                    } catch (ExceptionNotFound $e) {
                        // no parent page, no side bar
                        throw new ExceptionNotFound("No page side for pages in the root directory.");
                    }
                    break;
                case PageLayout::MAIN_HEADER_ELEMENT:
                    if ($requestedPage->isRootHomePage()) {
                        throw new ExceptionNotFound("No $this for the home");
                    }
                    break;
            }
            $closestPath = self::getDefaultElementContentPath($this->getId());
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

    /**
     * @throws ExceptionNotFound if the page/markup fragment was not found (a container element does not have any also)
     * @throws ExceptionBadArgument if the path can not be set as wiki path
     */
    public function getMarkupFetcher(): FetcherMarkup
    {
        if (isset($this->fetcherFragment)) {
            if (!$this->fetcherFragment->isClosed()) {
                return $this->fetcherFragment;
            }
        }
        /**
         * Rebuild the fragment if any
         */
        $fragmentPath = $this->getFragmentPath();
        $this->fetcherFragment = FetcherMarkup::createPageFragmentFetcherFromPath($fragmentPath)
            ->setRequestedPagePath($this->pageLayout->getRequestedContextPath());
        return $this->fetcherFragment;
    }


}
