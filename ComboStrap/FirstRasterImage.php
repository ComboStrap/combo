<?php

namespace ComboStrap;

use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Api\MetadataImage;
use ComboStrap\Meta\Store\MetadataDokuWikiStore;

/**
 * A derived meta that captures the first raster image
 * via {@link \syntax_plugin_combo_media::registerFirstImage()}
 */
class FirstRasterImage extends MetadataImage
{

    /**
     * Our first image metadata
     * We can't overwrite the {@link \Doku_Renderer_metadata::$firstimage first image}
     * We put it then in directly under the root
     */
    public const PROPERTY_NAME = "first-image-raster";

    public static function createForPage(ResourceCombo $resource): FirstRasterImage
    {
        return (new FirstRasterImage())
            ->setResource($resource);
    }

    public function getDescription(): string
    {
        return "The first raster image of the page";
    }

    public function getLabel(): string
    {
        return "First Raster image";
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

        $store = $this->getReadStore();
        if (!($store instanceof MetadataDokuWikiStore)) {
            throw new ExceptionNotFound();
        }

        /**
         *
         * Image set by {@link \syntax_plugin_combo_media::registerFirstImage()}
         */
        $firstImageId = $store->getFromPersistentName(FirstRasterImage::PROPERTY_NAME);

        if($firstImageId!==null){
            return WikiPath::createMediaPathFromId($firstImageId);
        }

        throw new ExceptionNotFound();

    }


    public function getPersistenceType(): string
    {
        return Metadata::DERIVED_METADATA;
    }

}