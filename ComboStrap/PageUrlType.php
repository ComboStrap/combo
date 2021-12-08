<?php


namespace ComboStrap;


class PageUrlType extends MetadataText
{

    public const CONF_CANONICAL_URL_TYPE = "pageUrlType";
    public const CONF_CANONICAL_URL_TYPE_DEFAULT = self::CONF_VALUE_PAGE_PATH;
    public const CONF_VALUE_PAGE_PATH = "page path";
    private static $urlTypeInstanceCache = [];
    public const CONF_VALUE_SLUG = "slug";
    public const CONF_VALUE_CANONICAL_PATH = "canonical path";
    public const CONF_VALUE_HIERARCHICAL_SLUG = "hierarchical slug";
    public const CONF_VALUE_PERMANENT_PAGE_PATH = "permanent page path";
    public const CONF_VALUE_PERMANENT_CANONICAL_PATH = "permanent canonical path";
    public const CONF_VALUE_HOMED_SLUG = "homed slug";
    public const CONF_VALUES = [
        PageUrlType::CONF_VALUE_PAGE_PATH,
        PageUrlType::CONF_VALUE_PERMANENT_PAGE_PATH,
        PageUrlType::CONF_VALUE_CANONICAL_PATH,
        PageUrlType::CONF_VALUE_PERMANENT_CANONICAL_PATH,
        PageUrlType::CONF_VALUE_SLUG,
        PageUrlType::CONF_VALUE_HOMED_SLUG,
        PageUrlType::CONF_VALUE_HIERARCHICAL_SLUG
    ];



    public static function getOrCreateForPage(ResourceCombo $page): PageUrlType
    {
        $path = $page->getPath()->toString();
        $urlType = self::$urlTypeInstanceCache[$path];
        if($urlType===null){
            $urlType = self::createFromPage($page);
            self::$urlTypeInstanceCache[$path] = $urlType;
        }
        return $urlType;

    }

    public static function createFromPage(ResourceCombo $page): PageUrlType
    {
        return (new PageUrlType())
            ->setResource($page);
    }

    public function getValue(): ?string
    {
        $resourceCombo = $this->getResource();
        if (!$resourceCombo->exists()) {
            return PageUrlType::CONF_VALUE_PAGE_PATH;
        }
        if(!($resourceCombo instanceof Page)){
            LogUtility::msg("The page type is only for page");
            return PageUrlType::CONF_VALUE_PAGE_PATH;
        }

        $confCanonicalType = $this->getName();
        $confDefaultValue = $this->getDefaultValue();
        $urlType = PluginUtility::getConfValue($confCanonicalType, $confDefaultValue);
        if (!in_array($urlType, self::CONF_VALUES)) {
            $urlType = $confDefaultValue;
            LogUtility::msg("The canonical configuration ($confCanonicalType) value ($urlType) is unknown and was set to the default one", LogUtility::LVL_MSG_ERROR, PageUrlPath::CANONICAL_PROPERTY);
        }

        // Not yet sync with the database
        // No permanent canonical url
        if ($resourceCombo->getPageIdAbbr() === null) {
            if ($urlType === self::CONF_VALUE_PERMANENT_CANONICAL_PATH) {
                $urlType = self::CONF_VALUE_CANONICAL_PATH;
            } else {
                $urlType = PageUrlType::CONF_VALUE_PAGE_PATH;
            }
        }
        return $urlType;

    }


    public function getTab(): string
    {
        return \action_plugin_combo_metamanager::TAB_PAGE_VALUE;
    }

    public function getDescription(): string
    {
        return "The type of Url for pages";
    }

    public function getLabel(): string
    {
        return "Page Url";
    }

    public function getName(): string
    {
        return PageUrlType::CONF_CANONICAL_URL_TYPE;
    }

    public function getPersistenceType(): string
    {
        return Metadata::PERSISTENT_METADATA;
    }

    public function getMutable(): bool
    {
        return true;
    }

    public function getDefaultValue(): string
    {
        return PageUrlType::CONF_CANONICAL_URL_TYPE_DEFAULT;
    }
}
