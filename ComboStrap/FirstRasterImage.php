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
        return "First raster image";
    }

    public static function getName(): string
    {
        return self::PROPERTY_NAME;
    }


    public function getMutable(): bool
    {
        return false;
    }

    public function buildFromReadStore(): FirstRasterImage
    {
        $this->wasBuild = true;
        $store = $this->getReadStore();
        if (!($store instanceof MetadataDokuWikiStore)) {
            return $this;
        }

        /**
         * Dokuwiki stores the first image in under relation
         * but as we can't take over the renderer code to enable svg as first image
         * we write it in the root to overcome a conflict
         *
         * Image set by {@link \syntax_plugin_combo_media::registerFirstImage()}
         */
        $firstImageId = $store->getFromPersistentName(FirstRasterImage::PROPERTY_NAME);

        /**
         * Image Id check
         */
        if (media_isexternal($firstImageId)) {
            // The first image is not a local image
            // Don't set
            return $this;
        }
        $this->buildFromStoreValue($firstImageId);

        return $this;
    }


    public function getPersistenceType(): string
    {
        return Metadata::DERIVED_METADATA;
    }
}
