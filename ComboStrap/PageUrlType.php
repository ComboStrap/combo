<?php


namespace ComboStrap;


class PageUrlType extends MetadataText
{

    /**
     * The canonical page for the page url type
     */
    public const CANONICAL_PROPERTY = "page:url";

    public const CONF_CANONICAL_URL_TYPE = "pageUrlType";
    public const CONF_CANONICAL_URL_TYPE_DEFAULT = self::PAGE_PATH;
    public const PAGE_PATH = "page path";
    private static $urlTypeInstanceCache = [];
    public const CONF_CANONICAL_URL_TYPE_VALUE_SLUG = "slug";
    public const CONF_CANONICAL_URL_TYPE_VALUE_CANONICAL_PATH = "canonical path";
    public const CONF_CANONICAL_URL_TYPE_VALUE_HIERARCHICAL_SLUG = "hierarchical slug";
    public const CONF_CANONICAL_URL_TYPE_VALUE_PERMANENT_PAGE_PATH = "permanent page path";
    public const CONF_CANONICAL_URL_TYPE_VALUE_PERMANENT_CANONICAL_PATH = "permanent canonical path";
    public const CONF_CANONICAL_URL_TYPE_VALUE_HOMED_SLUG = "homed slug";
    public const CONF_CANONICAL_URL_TYPE_VALUES = [
        PageUrlType::PAGE_PATH,
        PageUrlType::CONF_CANONICAL_URL_TYPE_VALUE_PERMANENT_PAGE_PATH,
        PageUrlType::CONF_CANONICAL_URL_TYPE_VALUE_CANONICAL_PATH,
        PageUrlType::CONF_CANONICAL_URL_TYPE_VALUE_PERMANENT_CANONICAL_PATH,
        PageUrlType::CONF_CANONICAL_URL_TYPE_VALUE_SLUG,
        PageUrlType::CONF_CANONICAL_URL_TYPE_VALUE_HOMED_SLUG,
        PageUrlType::CONF_CANONICAL_URL_TYPE_VALUE_HIERARCHICAL_SLUG
    ];



    public static function getOrCreateForPage(Page $page): PageUrlType
    {
        $path = $page->getPath()->toString();
        $urlType = self::$urlTypeInstanceCache[$path];
        if($urlType===null){
            $urlType = self::createFromPage($page);
            self::$urlTypeInstanceCache[$path] = $urlType;
        }
        return $urlType;

    }

    public static function createFromPage(Page $page): PageUrlType
    {
        return (new PageUrlType())
            ->setResource($page);
    }

    public function getValue(): ?string
    {
        if (!$this->getResource()->exists()) {
            return PageUrlType::PAGE_PATH;
        }
        $confCanonicalType = $this->getName();
        $confDefaultValue = $this->getDefaultValue();
        $urlType = PluginUtility::getConfValue($confCanonicalType, $confDefaultValue);
        if (!in_array($urlType, self::CONF_CANONICAL_URL_TYPE_VALUES)) {
            $urlType = $confDefaultValue;
            LogUtility::msg("The canonical configuration ($confCanonicalType) value ($urlType) is unknown and was set to the default one", LogUtility::LVL_MSG_ERROR, self::CANONICAL_PROPERTY);
        }

        // Not yet sync with the database
        // No permanent canonical url
        if ($this->getResource()->getPageIdAbbr() === null) {
            if ($urlType === self::CONF_CANONICAL_URL_TYPE_VALUE_PERMANENT_CANONICAL_PATH) {
                $urlType = self::CONF_CANONICAL_URL_TYPE_VALUE_CANONICAL_PATH;
            } else {
                $urlType = PageUrlType::PAGE_PATH;
            }
        }
        return $urlType;

    }


    public function getTab(): string
    {
        return "Page";
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
        return MetadataDokuWikiStore::PERSISTENT_METADATA;
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
