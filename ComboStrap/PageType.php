<?php


namespace ComboStrap;


class PageType extends MetadataText
{


    /**
     * @link https://ogp.me/#types Facebook ogp
     * @link https://www.dublincore.org/specifications/dublin-core/dcmi-terms/#http://purl.org/dc/elements/1.1/type Dublin Core
     */
    public const PROPERTY_NAME = "type";
    public const BLOG_TYPE = "blog";
    public const WEB_PAGE_TYPE = "webpage";
    public const ARTICLE_TYPE = "article";
    public const ORGANIZATION_TYPE = "organization";
    public const NEWS_TYPE = "news";
    public const OTHER_TYPE = "other";
    public const WEBSITE_TYPE = "website";
    public const HOME_TYPE = "home";
    public const EVENT_TYPE = "event";
    /**
     * Default page type configuration
     */
    public const CONF_DEFAULT_PAGE_TYPE = "defaultPageType";
    public const CONF_DEFAULT_PAGE_TYPE_DEFAULT = PageType::ARTICLE_TYPE;

    public static function createForPage($page): PageType
    {
        return (new PageType())
            ->setResource($page);
    }

    public function getTab(): string
    {
        return MetaManagerForm::TAB_TYPE_VALUE;
    }

    public function getDescription(): string
    {
        return "The type of page";
    }

    public function getLabel(): string
    {
        return "Page Type";
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

    public function getDefaultValue(): ?string
    {
        $resource = $this->getResource();
        if(!($resource instanceof Page)){
            return null;
        }

        if ($resource->isRootHomePage()) {
            return PageType::WEBSITE_TYPE;
        } else if ($resource->isHomePage()) {
            return PageType::HOME_TYPE;
        } else {
            $defaultPageTypeConf = PluginUtility::getConfValue(PageType::CONF_DEFAULT_PAGE_TYPE, PageType::CONF_DEFAULT_PAGE_TYPE_DEFAULT);
            if (!empty($defaultPageTypeConf)) {
                return $defaultPageTypeConf;
            } else {
                return null;
            }
        }
    }

    /**
     * The canonical for page type
     */
    public function getCanonical(): string
    {
        return "page:type";
    }


    public function getPossibleValues(): ?array
    {
        $types = [
            self::ORGANIZATION_TYPE,
            self::ARTICLE_TYPE,
            self::NEWS_TYPE,
            self::BLOG_TYPE,
            self::WEBSITE_TYPE,
            self::EVENT_TYPE,
            self::HOME_TYPE,
            self::WEB_PAGE_TYPE,
            self::OTHER_TYPE
        ];
        sort($types);
        return $types;
    }
}
