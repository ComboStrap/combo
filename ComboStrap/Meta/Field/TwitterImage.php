<?php

namespace ComboStrap\Meta\Field;

use ComboStrap\MarkupPath;
use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Api\MetadataImage;
use ComboStrap\PageImageUsage;


class TwitterImage extends MetadataImage
{


    const PROPERTY_NAME = "twitter-image";

    public static function createFromResource(MarkupPath $page)
    {
        return (new TwitterImage())->setResource($page);
    }

    public function getDescription(): string
    {
        return "The twitter image used in twitter card";
    }

    public function getLabel(): string
    {
        return "Twitter Image";
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
                if (in_array(PageImageUsage::TWITTER, $pageImage->getUsages())) {
                    return parent::buildFromStoreValue($pageImage->getImagePath()->toAbsoluteString());
                }
            }
        }
        return parent::buildFromStoreValue($value);

    }

    public function getDefaultValue()
    {

        return FeaturedRasterImage::createFromResourcePage($this->getResource())
            ->getValueOrDefault();

    }


}
