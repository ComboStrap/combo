<?php

namespace ComboStrap;

use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Api\MetadataImage;

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

    static public function getDescription(): string
    {
        return "The first image";
    }

    static public function getLabel(): string
    {
        return "First Image";
    }

    public static function getName(): string
    {
        return self::PROPERTY_NAME;
    }


    static public function isMutable(): bool
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


    static public function getPersistenceType(): string
    {
        return Metadata::DERIVED_METADATA;
    }

    static     public function getDrive(): string
    {
        return WikiPath::MEDIA_DRIVE;
    }

    static public function isOnForm(): bool
    {
        return true;
    }
}
