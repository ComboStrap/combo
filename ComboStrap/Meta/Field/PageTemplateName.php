<?php


namespace ComboStrap\Meta\Field;


use ComboStrap\ExceptionNotFound;
use ComboStrap\ExecutionContext;
use ComboStrap\FileSystems;
use ComboStrap\LogUtility;
use ComboStrap\MarkupPath;
use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Api\MetadataText;
use ComboStrap\MetaManagerForm;
use ComboStrap\PageTemplateEngine;
use ComboStrap\Site;
use ComboStrap\SlotSystem;

class PageTemplateName extends MetadataText
{


    public const PROPERTY_NAME = "template";
    public const PROPERTY_NAME_OLD = "layout";
    public const HOLY_TEMPLATE_VALUE = "holy";
    public const MEDIAN_TEMPLATE_VALUE = "median";
    public const LANDING_TEMPLATE_VALUE = "landing";
    public const INDEX_TEMPLATE_VALUE = "index";
    public const HAMBURGER_TEMPLATE_VALUE = "hamburger";
    public const BLANK_TEMPLATE_VALUE = "blank";

    /**
     * Not public, used in test to overwrite it to {@link PageTemplateName::BLANK_TEMPLATE_VALUE}
     * to speed up test
     */
    const CONF_DEFAULT_NAME = "defaultLayoutName";
    const ROOT_ITEM_LAYOUT = "root-item";

    public static function createFromPage(MarkupPath $page): PageTemplateName
    {
        return (new PageTemplateName())
            ->setResource($page);
    }

    static public function getTab(): string
    {
        return MetaManagerForm::TAB_PAGE_VALUE;
    }

    static public function getDescription(): string
    {
        return "A template applies a layout on your page";
    }

    static public function getLabel(): string
    {
        return "Template";
    }

    public function getPossibleValues(): ?array
    {
        try {
            $templateNames = [];
            $directories = PageTemplateEngine::createFromContext()
                ->getTemplateSearchDirectories();
            foreach ($directories as $directory) {
                $files = FileSystems::getChildrenLeaf($directory);
                foreach ($files as $file) {
                    if ($file->getExtension() === PageTemplateEngine::EXTENSION_HBS) {
                        $templateNames[] = $file->getLastNameWithoutExtension();
                    }
                }
            }
            sort($templateNames);
            return $templateNames;
        } catch (ExceptionNotFound $e) {
            LogUtility::error("No template could be found", self::CANONICAL, $e);
            return [];
        }
    }


    static public function getName(): string
    {
        return self::PROPERTY_NAME;
    }

    static public function getPersistenceType(): string
    {
        return Metadata::PERSISTENT_METADATA;
    }

    static public function isMutable(): bool
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
        if ($page->isSlot()) {
            return self::HAMBURGER_TEMPLATE_VALUE;
        }
        if ($page->isRootHomePage()) {
            return self::HAMBURGER_TEMPLATE_VALUE;
        }
        if ($page->isRootItemPage()) {
            return self::ROOT_ITEM_LAYOUT;
        }
        try {
            switch ($page->getPathObject()->getLastNameWithoutExtension()) {
                case SlotSystem::getSidebarName():
                case SlotSystem::getMainHeaderSlotName():
                case SlotSystem::getMainFooterSlotName():
                case SlotSystem::getMainSideSlotName():
                    return self::MEDIAN_TEMPLATE_VALUE;
                case SlotSystem::getPageHeaderSlotName():
                case SlotSystem::getPageFooterSlotName():
                    /**
                     * Header and footer contains bar
                     * {@link \syntax_plugin_combo_menubar menubar} or
                     * {@link \syntax_plugin_combo_bar}
                     * They therefore should not be constrained
                     * Landing page is perfect
                     */
                    return self::LANDING_TEMPLATE_VALUE;
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
            return self::INDEX_TEMPLATE_VALUE;
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

    static public function getCanonical(): string
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

    /** @noinspection PhpMissingReturnTypeInspection */
    public function buildFromReadStore()
    {

        $metaDataStore = $this->getReadStore();
        $value = $metaDataStore->getFromPersistentName(self::PROPERTY_NAME);
        if ($value === null) {
            $value = $metaDataStore->getFromPersistentName(self::PROPERTY_NAME_OLD);
        }
        parent::setFromStoreValueWithoutException($value);
        return $this;
    }

    public function sendToWriteStore(): Metadata
    {

        parent::sendToWriteStore();
        $writeStore = $this->getWriteStore();
        $value = $writeStore->getFromPersistentName(self::PROPERTY_NAME_OLD);
        if ($value !== null) {
            // delete the old value
            $writeStore->setFromPersistentName(self::PROPERTY_NAME_OLD, null);
        }

        return $this;
    }

    public static function getOldPersistentNames(): array
    {
        return [self::PROPERTY_NAME_OLD];
    }


    static public function isOnForm(): bool
    {
        return true;
    }
}
