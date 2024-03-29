<?php


namespace ComboStrap;


use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Api\MetadataText;

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

    public function setWriteStore($store): PageType
    {
        // Just to return the good type
        return parent::setWriteStore($store);
    }

    /**
     * @return string
     */
    public function getValueOrDefault(): string
    {
        try {
            return $this->getValue();
        } catch (ExceptionNotFound $e) {
            return $this->getDefaultValue();
        }
    }


    public function setReadStore($store): PageType
    {
        // Just to return the good type
        return parent::setReadStore($store);
    }


    static public function getTab(): string
    {
        return MetaManagerForm::TAB_TYPE_VALUE;
    }

    static public function getDescription(): string
    {
        return "The type of page";
    }

    static public function getLabel(): string
    {
        return "Page Type";
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
        $resource = $this->getResource();
        if (!($resource instanceof MarkupPath)) {
            return self::OTHER_TYPE;
        }

        if ($resource->isRootHomePage()) {
            return PageType::WEBSITE_TYPE;
        } else if ($resource->isIndexPage()) {
            return PageType::HOME_TYPE;
        } else {
            $defaultPageTypeConf = SiteConfig::getConfValue(PageType::CONF_DEFAULT_PAGE_TYPE, PageType::CONF_DEFAULT_PAGE_TYPE_DEFAULT);
            if (!empty($defaultPageTypeConf)) {
                return $defaultPageTypeConf;
            } else {
                return self::ARTICLE_TYPE;
            }
        }
    }

    /**
     * The canonical for page type
     */
    static public function getCanonical(): string
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

    static public function isOnForm(): bool
    {
        return true;
    }
}
