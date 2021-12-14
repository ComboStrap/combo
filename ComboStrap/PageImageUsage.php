<?php


namespace ComboStrap;

/**
 * Class PageImageUsage
 * @package ComboStrap
 * The usage for the image of a page
 */
class PageImageUsage extends MetadataArray
{

    public const PERSISTENT_NAME = "usage";  // storage name
    const PROPERTY_NAME = "page-image-usage"; // unique property name

    /**
     * Constant values
     */
    public const ALL = "all";
    public const FACEBOOK = "facebook";
    public const SOCIAL = "social";
    public const ICON = "icon";
    public const TWITTER = "twitter";
    public const GOOGLE = "google";
    public const DEFAULT = PageImageUsage::ALL;



    public static function getUsageValues(): array
    {
        return [
            self::ALL,
            self::ICON,
            self::GOOGLE,
            self::FACEBOOK,
            PageImage::PAGE_IMAGE,
            self::SOCIAL,
            self::TWITTER,
        ];

    }

    public static function createFromParent(PageImages $param): PageImageUsage
    {
        return new PageImageUsage($param);
    }


    public function getDescription(): string
    {
        return "The possible usages of the image";
    }

    public function getLabel(): string
    {
        return "Usages";
    }

    public function getName(): string
    {
        return self::PROPERTY_NAME;
    }

    public function getPersistentName(): string
    {
        return self::PERSISTENT_NAME;
    }


    public function getMutable(): bool
    {
        return true;
    }


    function getDefaultValues(): array
    {
        return [self::ALL];
    }

    public function getPersistenceType(): string
    {
        return Metadata::PERSISTENT_METADATA;
    }

    function getStringSeparator(): string
    {
        return ",";
    }
}
