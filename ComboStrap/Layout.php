<?php

namespace ComboStrap;


class Layout
{
    const PAGE_SIDE = "page-side";
    const MAIN_FOOTER = "main-footer";
    const MAIN_SIDE = "main-side";
    const PAGE_MAIN = "page-main";
    const PAGE_HEADER = "page-header";
    const PAGE_FOOTER = "page-footer";
    const MAIN_HEADER = "main-header";
    const MAIN_TOC = "main-toc";
    const MAIN_CONTENT = "main-content";

    /**
     * @var LayoutArea[]
     */
    private $layoutAreas;

    public static function create(): Layout
    {
        $layout = new Layout();
        $layout->getOrCreateArea(self::PAGE_HEADER)
            ->setSlotName(Site::getPageHeaderSlotName())
            ->setTag("header");
        $layout->getOrCreateArea(self::PAGE_FOOTER)
            ->setSlotName(Site::getPageFooterSlotName());

        $layout->getOrCreateArea(self::MAIN_CONTENT);
        $layout->getOrCreateArea(self::PAGE_SIDE)
            ->setSlotName(Site::getSidebarName());

        $layout->getOrCreateArea(self::MAIN_SIDE)
            ->setSlotName(Site::getPageSideSlotName());
        $layout->getOrCreateArea(self::MAIN_HEADER)
            ->setSlotName("slot_main_header");
        $layout->getOrCreateArea(self::MAIN_FOOTER)
            ->setSlotName("slot_main_footer");

        return $layout;

    }

    public function getOrCreateArea($areaName): LayoutArea
    {
        $layoutArea = $this->layoutAreas[$areaName];
        if ($layoutArea === null) {
            $layoutArea = new LayoutArea($areaName);
            $this->layoutAreas[$areaName] = $layoutArea;
        }
        return $layoutArea;
    }

    public function getHtmlPage(): string
    {
        return "";
    }


}
