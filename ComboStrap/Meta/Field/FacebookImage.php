<?php

namespace ComboStrap\Meta\Field;

use ComboStrap\MarkupPath;
use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Api\MetadataImage;
use ComboStrap\Meta\Api\MetadataWikiPath;
use ComboStrap\PageImageUsage;
use ComboStrap\SiteConfig;
use ComboStrap\WikiPath;


class FacebookImage extends MetadataImage
{


    const PROPERTY_NAME = "facebook-image";


    public static function createFromResource(MarkupPath $page)
    {
        return (new FacebookImage())->setResource($page);
    }

    static public function getDescription(): string
    {
        return "The Facebook/OpenGraph image used in Facebook and OpenGraph card (Signal, ...)";
    }

    static public function getLabel(): string
    {
        return "Facebook/OpenGraph";
    }

    public static function getName(): string
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

    public function setFromStoreValueWithoutException($value): Metadata
    {

        if ($value === null) {
            $pageImages = PageImages::createForPage($this->getResource())
                ->setReadStore($this->getReadStore())
                ->getValueAsPageImages();
            foreach ($pageImages as $pageImage) {
                if (in_array(PageImageUsage::FACEBOOK, $pageImage->getUsages())) {
                    return parent::setFromStoreValueWithoutException($pageImage->getImagePath()->toAbsoluteId());
                }
            }
        }
        return parent::setFromStoreValueWithoutException($value);

    }


    public function getDefaultValue()
    {

        return SocialCardImage::createFromResourcePage($this->getResource())
            ->getValueOrDefault();

    }

    static public function getDrive(): string
    {
        return WikiPath::MEDIA_DRIVE;
    }

    static public function isOnForm(): bool
    {
        return true;
    }

    public static function getCanonical(): string
    {
        return "facebook";
    }


}
