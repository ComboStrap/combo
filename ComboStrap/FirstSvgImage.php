<?php

namespace ComboStrap;

use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Api\MetadataImage;
use ComboStrap\Meta\Store\MetadataDokuWikiStore;

/**
 * A derived meta that captures the first svg image
 * via {@link \syntax_plugin_combo_media::registerFirstImage()}
 */
class FirstSvgImage extends MetadataImage
{


    public const PROPERTY_NAME = "first-image-svg";

    public static function createForPage(ResourceCombo $resource): FirstSvgImage
    {
        return (new FirstSvgImage())
            ->setResource($resource);
    }

    public function getDescription(): string
    {
        return "The first svg image of the page";
    }

    public function getLabel(): string
    {
        return "First Svg image";
    }

    public static function getName(): string
    {
        return self::PROPERTY_NAME;
    }


    public function getMutable(): bool
    {
        return false;
    }

    public function buildFromReadStore(): FirstSvgImage
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
        $firstImageId = $store->getFromPersistentName(FirstSvgImage::PROPERTY_NAME);


        $this->buildFromStoreValue($firstImageId);

        return $this;
    }


    public function getPersistenceType(): string
    {
        return Metadata::DERIVED_METADATA;
    }
}
