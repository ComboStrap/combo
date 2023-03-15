<?php


namespace ComboStrap;


class PageLayoutName extends MetadataText
{


    public const PROPERTY_NAME = "layout";
    public const HOLY_LAYOUT_VALUE = "holy";
    public const MEDIAN_LAYOUT_VALUE = "median";
    public const LANDING_LAYOUT_VALUE = "landing";
    public const INDEX_LAYOUT_VALUE = "index";
    public const HAMBURGER_LAYOUT_VALUE = "hamburger";
    public const BLANK_LAYOUT = "blank";

    /**
     * Not public, used in test to overwrite it to {@link PageLayoutName::BLANK_LAYOUT}
     * to speed up test
     */
    const CONF_DEFAULT_NAME = "defaultLayoutName";
    const ROOT_ITEM_LAYOUT = "root-item";

    public static function createFromPage(MarkupPath $page): PageLayoutName
    {
        return (new PageLayoutName())
            ->setResource($page);
    }

    public function getTab(): string
    {
        return MetaManagerForm::TAB_PAGE_VALUE;
    }

    public function getDescription(): string
    {
        return "A layout chooses the layout of your page (such as the slots and placement of the main content)";
    }

    public function getLabel(): string
    {
        return "Page Layout";
    }

    public function getPossibleValues(): ?array
    {
        return [
            self::HOLY_LAYOUT_VALUE,
            self::MEDIAN_LAYOUT_VALUE,
            self::LANDING_LAYOUT_VALUE,
            self::INDEX_LAYOUT_VALUE,
            self::HAMBURGER_LAYOUT_VALUE,
            self::BLANK_LAYOUT,
            self::ROOT_ITEM_LAYOUT
        ];
    }


    static public function getName(): string
    {
        return self::PROPERTY_NAME;
    }

    public function getPersistenceType(): string
    {
        return Metadata::PERSISTENT_METADATA;
    }

    public function getMutable(): bool
    {
        return true;
    }

    /**
     * @return string
     */
    public function getDefaultValue(): string
    {
        /**
         * @var MarkupPath $page
         */
        $page = $this->getResource();
        if ($page->isRootHomePage()) {
            return self::HAMBURGER_LAYOUT_VALUE;
        }
        if ($page->isRootItemPage()) {
            return self::ROOT_ITEM_LAYOUT;
        }
        try {
            switch ($page->getPathObject()->getLastNameWithoutExtension()) {
                case Site::getSidebarName():
                case Site::getMainHeaderSlotName():
                case Site::getMainFooterSlotName():
                case Site::getMainSideSlotName():
                    return self::MEDIAN_LAYOUT_VALUE;
                case Site::getPageHeaderSlotName():
                case Site::getPageFooterSlotName():
                    /**
                     * Header and footer contains bar
                     * {@link \syntax_plugin_combo_menubar menubar} or
                     * {@link \syntax_plugin_combo_bar}
                     * They therefore should not be constrained
                     * Landing page is perfect
                     */
                    return self::LANDING_LAYOUT_VALUE;
            }
        } catch (ExceptionNotFound $e) {
            // No last name not installed
        }

        /**
         * Calculate the possible template
         * prefix in order
         */
        try {
            $parentNames = $page->getPathObject()->getParent()->getNames();
            $templatePrefixes = [];
            $hierarchicalName = '';
            foreach ($parentNames as $name) {
                if (empty($hierarchicalName)) {
                    $hierarchicalName .= $name;
                } else {
                    $hierarchicalName .= "-$name";
                }
                $templatePrefixes[] = $name;
                if ($hierarchicalName !== $name) {
                    $templatePrefixes[] = $hierarchicalName;
                }
            }
            $templatePrefixes = array_reverse($templatePrefixes);
        } catch (ExceptionNotFound $e) {
            // no parent, root
            $templatePrefixes = [];
        }

        $pageTemplateEngine = PageTemplateEngine::createFromContext();


        if ($page->isIndexPage()) {
            foreach ($templatePrefixes as $templatePrefix) {
                $templateName = "$templatePrefix-index";
                if ($pageTemplateEngine->templateExists($templateName)) {
                    return $templateName;
                }
            }
            return self::INDEX_LAYOUT_VALUE;
        }

        /**
         * Item page
         */
        foreach ($templatePrefixes as $templatePrefix) {
            $templateName = "$templatePrefix-item";
            if ($pageTemplateEngine->templateExists($templateName)) {
                return $templateName;
            }
        }

        return ExecutionContext::getActualOrCreateFromEnv()->getConfig()->getDefaultLayoutName();


    }

    public function getCanonical(): string
    {
        return self::PROPERTY_NAME;
    }

    /**
     * @return string
     */
    public function getValueOrDefault(): string
    {

        try {
            $value = $this->getValue();
            if ($value === "") {
                return $this->getDefaultValue();
            }
            return $value;
        } catch (ExceptionNotFound $e) {
            return $this->getDefaultValue();
        }


    }


}
