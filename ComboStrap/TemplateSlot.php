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


    /**
     * @var TemplateForWebPage - the page layout of this page element
     */
    private TemplateForWebPage $pageTemplate;
    /**
     * @var FetcherMarkup - the fetcher if this is a slot and has a page fragment source
     */
    private FetcherMarkup $fetcherFragment;
    private string $slotId;


    public function __construct(string $slotId, TemplateForWebPage $pageTemplate)
    {

        $this->slotId = $slotId;
        if(!in_array($slotId,self::SLOT_IDS)){
            throw new ExceptionRuntimeInternal("$slotId is not a valid slot id. Valid ids are (".ArrayUtility::formatAsString(self::SLOT_IDS).").");
        }
        $this->pageTemplate = $pageTemplate;


    }

    public static function createFor(string $slotId, TemplateForWebPage $pageTemplate): TemplateSlot
    {
        return new TemplateSlot($slotId, $pageTemplate);
    }


    /**
     */
    public static function getSlotNameFromId($slotId)
    {
        switch ($slotId) {
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
                return "slot_main_header";
            case self::MAIN_FOOTER_ID:
                return "slot_main_footer";
            default:
                throw new ExceptionRuntimeInternal("Internal: The element ($slotId) was unexpected, it's not a slot");
        }

    }

    /**
     * This function is static because it's used to put a default template when creating a new slot
     * @param string $areaId
     * @return WikiPath
     */
    public static function getDefaultSlotContentPath(string $areaId): WikiPath
    {
        return WikiPath::createComboResource(":pages:$areaId.md");
    }


    /**
     */
    public function getLastFileNameForFragment()
    {
        $elementId = $this->getName();
        return self::getSlotNameFromId($elementId);
    }


    public
    function __toString()
    {
        return "Fragment {$this->pageTemplate} / {$this->getName()}";
    }



    /**
     * @throws ExceptionNotFound - if the area is not a slot or there is no path found
     */
    private function getFragmentPath()
    {

        // Main content
        $requestedPath = $this->pageTemplate->getRequestedContextPath();
        if ($this->getName() === self::MAIN_CONTENT_ID) {
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

        /**
         * The default content is in the theme
         */
        throw new ExceptionNotFound("No slot page for the area ($this) found");


    }


    /**
     * @throws ExceptionNotFound if the page/markup fragment was not found (a container element does not have any also)
     */
    public function getMarkupFetcher(): FetcherMarkup
    {
        if (isset($this->fetcherFragment)) {
            return $this->fetcherFragment;
        }
        /**
         * Rebuild the fragment if any
         */
        $fragmentPath = $this->getFragmentPath();
        $contextPath = $this->pageTemplate->getRequestedContextPath();
        try {
            $this->fetcherFragment = FetcherMarkup::createXhtmlMarkupFetcherFromPath($fragmentPath, $contextPath);
        } catch (ExceptionNotExists $e) {
            throw new ExceptionNotFound("The fragment path ($fragmentPath) was no found");
        }
        return $this->fetcherFragment;
    }

    public function getName(): string
    {
        return $this->slotId;
    }


}
