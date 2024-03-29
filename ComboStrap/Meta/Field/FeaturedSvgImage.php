<?php

namespace ComboStrap\Meta\Field;

use ComboStrap\ExceptionNotFound;
use ComboStrap\FirstSvgIllustration;
use ComboStrap\MarkupPath;
use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Api\MetadataImage;
use ComboStrap\Meta\Store\MetadataDokuWikiStore;
use ComboStrap\WikiPath;


class FeaturedSvgImage extends MetadataImage
{

    const PROPERTY_NAME = "featured-svg-image";
    const ITEM_FEATURED_IMAGE_PARSED = "item-featured-svg-image-parsed";


    public static function createFromResourcePage(MarkupPath $markupPath): FeaturedSvgImage
    {
        return (new FeaturedSvgImage())->setResource($markupPath);
    }

    static public function getDescription(): string
    {
        return "A featured image in svg format";
    }

    static public function getLabel(): string
    {
        return "Featured Svg Image";
    }

    public static function getName(): string
    {
        return "featured-svg-image";
    }

    static public function getPersistenceType(): string
    {
        return Metadata::PERSISTENT_METADATA;
    }

    static public function isMutable(): bool
    {
        return true;
    }


    public function setParsedValue(string $path = null): FeaturedSvgImage
    {
        $store = $this->getWriteStore();
        if ($store instanceof MetadataDokuWikiStore) {
            $store->setFromPersistentName(self::ITEM_FEATURED_IMAGE_PARSED, $path);
        }
        return $this;
    }

    public function getDefaultValue(): WikiPath
    {

        /**
         * Parsed Feature Images
         */
        $parsedValue = $this->getReadStore()->getFromName(self::ITEM_FEATURED_IMAGE_PARSED);
        if ($parsedValue === null) {
            throw new ExceptionNotFound();
        }
        return WikiPath::createMediaPathFromPath($parsedValue);

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
        return FeaturedImage::getCanonical();
    }
}
