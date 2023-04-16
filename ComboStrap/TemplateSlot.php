<?php


namespace ComboStrap;


/**
 * This class represents a page layout slots
 */
class TemplateSlot
{
    public const SLOT_IDS = [
        self::PAGE_SIDE_ID,
        self::PAGE_HEADER_ID,
        self::PAGE_MAIN_ID,
        self::PAGE_FOOTER_ID,
        self::MAIN_HEADER_ID,
        self::MAIN_CONTENT_ID,
        self::MAIN_SIDE_ID,
        self::MAIN_FOOTER_ID
    ];
    public const PAGE_HEADER_ID = "page-header";
    public const PAGE_FOOTER_ID = "page-footer";
    public const MAIN_SIDE_ID = "main-side";
    public const PAGE_SIDE_ID = "page-side";
    public const MAIN_CONTENT_ID = "main-content";
    public const MAIN_FOOTER_ID = "main-footer";
    public const MAIN_HEADER_ID = "main-header";
    public const PAGE_MAIN_ID = "page-main";
    public const CONF_PAGE_MAIN_SIDEKICK_NAME_DEFAULT = Site::SLOT_MAIN_SIDE_NAME;
    public const CONF_PAGE_HEADER_NAME = "headerSlotPageName";
    public const CONF_PAGE_FOOTER_NAME = "footerSlotPageName";
    public const CONF_PAGE_HEADER_NAME_DEFAULT = "slot_header";
    public const CONF_PAGE_FOOTER_NAME_DEFAULT = "slot_footer";
    public const CONF_PAGE_MAIN_SIDEKICK_NAME = "sidekickSlotPageName";
    const MAIN_TOC_ID = "main-toc";
    const SLOT_MAIN_HEADER_PATH_NAME = "slot_main_header";
    const SLOT_MAIN_FOOTER_PATH_NAME = "slot_main_footer";


    /**
     * @var WikiPath - the context path of this slot
     */
    private WikiPath $contextPath;
    /**
     * @var FetcherMarkup - the fetcher if this is a slot and has a page fragment source
     */
    private FetcherMarkup $fetcherFragment;
    private string $elementId;


    public function __construct(string $elementId, WikiPath $contextPath)
    {

        $this->elementId = $elementId;
        if (!in_array($elementId, self::SLOT_IDS)) {
            throw new ExceptionRuntimeInternal("$elementId is not a valid slot id. Valid ids are (" . ArrayUtility::formatAsString(self::SLOT_IDS) . ").");
        }
        $this->contextPath = $contextPath;


    }

    public static function createFromElementId(string $elementId, WikiPath $contextPath = null): TemplateSlot
    {

        if ($contextPath === null) {
            $contextPath = ExecutionContext::getActualOrCreateFromEnv()
                ->getConfig()
                ->getDefaultContextPath();
        }
        return new TemplateSlot($elementId, $contextPath);

    }

    public static function createFromPathName($pathNameWithoutExtension): TemplateSlot
    {
        return self::createFromElementId(self::getElementIdFromPathName($pathNameWithoutExtension));
    }

    private static function getElementIdFromPathName($pathNameWithoutExtension): string
    {
        if ($pathNameWithoutExtension === SlotSystem::getPageHeaderSlotName()) {
            return self::PAGE_HEADER_ID;
        }

        if ($pathNameWithoutExtension === SlotSystem::getPageFooterSlotName()) {
            return self::PAGE_FOOTER_ID;
        }
        if ($pathNameWithoutExtension === SlotSystem::getSidebarName()) {
            return self::PAGE_SIDE_ID;
        }

        if ($pathNameWithoutExtension === SlotSystem::getMainSideSlotName()) {
            return self::MAIN_SIDE_ID;
        }
        if ($pathNameWithoutExtension === self::SLOT_MAIN_HEADER_PATH_NAME) {
            return self::MAIN_HEADER_ID;
        }
        if ($pathNameWithoutExtension === self::SLOT_MAIN_FOOTER_PATH_NAME) {
            return self::MAIN_FOOTER_ID;
        }
        throw new ExceptionRuntimeInternal("Internal: The markup name ($pathNameWithoutExtension) was unexpected, it's not a slot");

    }


    public
    static function getPathNameFromElementId($elementId)
    {
        switch ($elementId) {
            case self::PAGE_HEADER_ID:
                return SlotSystem::getPageHeaderSlotName();
            case self::PAGE_FOOTER_ID:
                return SlotSystem::getPageFooterSlotName();
            case self::MAIN_CONTENT_ID:
                throw new ExceptionRuntimeInternal("Main content area is not a slot and does not have any last slot name");
            case self::PAGE_SIDE_ID:
                return SlotSystem::getSidebarName();
            case self::MAIN_SIDE_ID:
                return SlotSystem::getMainSideSlotName();
            case self::MAIN_HEADER_ID:
                return self::SLOT_MAIN_HEADER_PATH_NAME;
            case self::MAIN_FOOTER_ID:
                return self::SLOT_MAIN_FOOTER_PATH_NAME;
            default:
                throw new ExceptionRuntimeInternal("Internal: The element ($elementId) was unexpected, it's not a slot");
        }

    }

    /**
     *
     * @return WikiPath
     */
    public
     function getDefaultSlotContentPath(): WikiPath
    {
        return WikiPath::createComboResource(":slot:{$this->getElementId()}.md");
    }


    /**
     */
    public
    function getLastFileNameForFragment()
    {
        $elementId = $this->getElementId();
        return self::getPathNameFromElementId($elementId);
    }


    public
    function __toString()
    {
        return "Slot {$this->getElementId()} for {$this->contextPath}";
    }


    /**
     * @throws ExceptionNotFound - if the area is not a slot or there is no path found
     */
    private
    function getFragmentPath()
    {

        // Main content
        $requestedPath = $this->contextPath;
        if ($this->getElementId() === self::MAIN_CONTENT_ID) {
            return $requestedPath;
        }
        // Slot
        $contextExtension = $requestedPath->getExtension();
        try {
            return FileSystems::closest($requestedPath, $this->getLastFileNameForFragment() . '.' . $contextExtension);
        } catch (ExceptionNotFound $e) {
            foreach (WikiPath::ALL_MARKUP_EXTENSIONS as $markupExtension) {
                if ($markupExtension == $contextExtension) {
                    continue;
                }
                try {
                    return FileSystems::closest($requestedPath, $this->getLastFileNameForFragment() . '.' . $contextExtension);
                } catch (ExceptionNotFound $e) {
                    // not found, we let it go to the default if needed
                }
            }
        }


        return $this->getDefaultSlotContentPath();



    }


    /**
     * @throws ExceptionNotFound if the page/markup fragment was not found (a container element does not have any also)
     */
    public
    function getMarkupFetcher(): FetcherMarkup
    {
        if (isset($this->fetcherFragment)) {
            return $this->fetcherFragment;
        }
        /**
         * Rebuild the fragment if any
         */
        $fragmentPath = $this->getFragmentPath();
        $contextPath = $this->contextPath;
        try {
            $this->fetcherFragment = FetcherMarkup::createXhtmlMarkupFetcherFromPath($fragmentPath, $contextPath);
        } catch (ExceptionNotExists $e) {
            throw new ExceptionNotFound("The fragment path ($fragmentPath) was no found");
        }
        return $this->fetcherFragment;
    }

    public
    function getElementId(): string
    {
        return $this->elementId;
    }

    public function getPathName()
    {
        return self::getPathNameFromElementId($this->getElementId());
    }


}
