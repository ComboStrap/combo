<?php

namespace ComboStrap;

use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Api\MetadataImage;
use ComboStrap\Meta\Store\MetadataDokuWikiStore;

/**
 * The first image used as blog image (ie svg first then raster)
 * (Used to get feedback information to the user in the metadata manager)
 */
class FirstImage extends MetadataImage
{


    public const PROPERTY_NAME = "first-image";

    public static function createForPage(ResourceCombo $resource): FirstRasterImage
    {
        return (new FirstRasterImage())
            ->setResource($resource);
    }

    public function getDescription(): string
    {
        return "The first image";
    }

    public function getLabel(): string
    {
        return "First Image";
    }

    public static function getName(): string
    {
        return self::PROPERTY_NAME;
    }


    public function getMutable(): bool
    {
        return false;
    }

    /**
     * @return WikiPath
     * @throws ExceptionNotFound
     */
    public function getValue(): WikiPath
    {
        $contextPage = $this->getResource();
        try {
            return FirstSvgImage::createForPage($contextPage)->getValue();
        } catch (ExceptionNotFound $e) {
            return FirstRasterImage::createForPage($contextPage)->getValue();
        }

    }


    public function getPersistenceType(): string
    {
        return Metadata::DERIVED_METADATA;
    }

}
