<?php

namespace ComboStrap\Meta\Field;

use ComboStrap\MarkupPath;
use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Api\MetadataImage;
use ComboStrap\Meta\Api\MetadataWikiPath;
use ComboStrap\PageImageUsage;
use ComboStrap\SiteConfig;


class FacebookImage extends MetadataImage
{


    const PROPERTY_NAME = "facebook-image";


    public static function createFromResource(MarkupPath $page)
    {
        return (new FacebookImage())->setResource($page);
    }

    public function getDescription(): string
    {
        return "The Facebook/OpenGraph image used in Facebook and OpenGraph card (Signal, ...)";
    }

    public function getLabel(): string
    {
        return "Facebook/OpenGraph";
    }

    public static function getName(): string
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

    public function buildFromStoreValue($value): Metadata
    {

        if ($value === null) {
            $pageImages = PageImages::createForPage($this->getResource())
                ->setReadStore($this->getReadStore())
                ->getValueAsPageImages();
            foreach ($pageImages as $pageImage) {
                if (in_array(PageImageUsage::FACEBOOK, $pageImage->getUsages())) {
                    return parent::buildFromStoreValue($pageImage->getImagePath()->toAbsoluteId());
                }
            }
        }
        return parent::buildFromStoreValue($value);

    }


    public function getDefaultValue()
    {

        return SocialCardImage::createFromResourcePage($this->getResource())
            ->getValueOrDefault();

    }


}
