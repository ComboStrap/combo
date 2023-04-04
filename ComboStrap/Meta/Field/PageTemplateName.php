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
use ComboStrap\TemplateEngine;
use ComboStrap\Site;
use ComboStrap\SlotSystem;
use ComboStrap\Tag\BarTag;

class PageTemplateName extends MetadataText
{


    public const PROPERTY_NAME = "template";
    public const PROPERTY_NAME_OLD = "layout";
    public const HOLY_TEMPLATE_VALUE = "holy";
    public const MEDIUM_TEMPLATE_VALUE = "medium";
    public const LANDING_TEMPLATE_VALUE = "landing";
    public const INDEX_TEMPLATE_VALUE = "index";
    public const HAMBURGER_TEMPLATE_VALUE = "hamburger";
    public const BLANK_TEMPLATE_VALUE = "blank";

    /**
     * Not public, used in test to overwrite it to {@link PageTemplateName::BLANK_TEMPLATE_VALUE}
     * to speed up test
     */
    const CONF_DEFAULT_NAME = "defaultLayoutName";


    /**
     * App page
     */
    const APP_PREFIX = "app-";
    const APP_EDIT = self::APP_PREFIX . ExecutionContext::EDIT_ACTION;
    const APP_LOGIN = self::APP_PREFIX . ExecutionContext::LOGIN_ACTION;
    const APP_SEARCH = self::APP_PREFIX . ExecutionContext::SEARCH_ACTION;
    const APP_REGISTER = self::APP_PREFIX . ExecutionContext::REGISTER_ACTION;
    const APP_RESEND_PWD = self::APP_PREFIX . ExecutionContext::RESEND_PWD_ACTION;
    const APP_REVISIONS = self::APP_PREFIX . ExecutionContext::REVISIONS_ACTION;
    const APP_DIFF = self::APP_PREFIX . ExecutionContext::DIFF_ACTION;
    const APP_INDEX = self::APP_PREFIX . ExecutionContext::INDEX_ACTION;
    const APP_PROFILE = self::APP_PREFIX . ExecutionContext::PROFILE_ACTION;

    /**
     * @deprecated for {@link self::MEDIUM_TEMPLATE_VALUE}
     * changed to medium (median has too much mathematics connotation)
     * medium: halfway between two extremes
     */
    const MEDIAN_OLD_TEMPLATE = "median";
    const HOLY_MEDIUM_LAYOUT = "holy-medium";
    const INDEX_MEDIUM_LAYOUT = "index-medium";


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
            $directories = TemplateEngine::createFromContext()
                ->getTemplateSearchDirectories();
            foreach ($directories as $directory) {
                $files = FileSystems::getChildrenLeaf($directory);
                foreach ($files as $file) {
                    $lastNameWithoutExtension = $file->getLastNameWithoutExtension();
                    if (strpos($lastNameWithoutExtension, self::APP_PREFIX) === 0) {
                        continue;
                    }
                    if ($file->getExtension() === TemplateEngine::EXTENSION_HBS) {

                        $templateNames[] = $lastNameWithoutExtension;
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

        /**
         * Slot first
         * because they are also root item page
         */
        try {
            switch ($page->getPathObject()->getLastNameWithoutExtension()) {
                case SlotSystem::getSidebarName():
                case SlotSystem::getMainHeaderSlotName():
                case SlotSystem::getMainFooterSlotName():
                case SlotSystem::getMainSideSlotName():
                    return self::INDEX_MEDIUM_LAYOUT;
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


        if ($page->isRootHomePage()) {
            /**
             * Ultimattely a {@link self::LANDING_TEMPLATE_VALUE}
             * but for that the user needs to add {@link BarTag}
             *
             */
            return self::HAMBURGER_TEMPLATE_VALUE;
        }
        if ($page->isRootItemPage()) {
            /**
             * Home/Root item does not really belongs to the same
             * namespace, we don't show therefore a sidebar
             */
            return self::INDEX_MEDIUM_LAYOUT;
        }


        /**
         * Default by namespace
         *
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

        $pageTemplateEngine = TemplateEngine::createFromContext();


        /**
         * Index pages
         */
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
        if ($value === self::MEDIAN_OLD_TEMPLATE) {
            $value = self::MEDIUM_TEMPLATE_VALUE;
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
