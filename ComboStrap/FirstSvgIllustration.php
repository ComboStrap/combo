<?php

namespace ComboStrap;

use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Api\MetadataImage;
use ComboStrap\Meta\Store\MetadataDokuWikiStore;

/**
 * A derived meta that captures the first svg image
 * via {@link \syntax_plugin_combo_media::registerFirstImage()}
 *
 * Note: The first svg icon is captured at {@link FeaturedIcon::FIRST_ICON_PARSED}
 */
class FirstSvgIllustration extends MetadataImage
{


    public const PROPERTY_NAME = "first-svg-illustration";

    public static function createForPage(ResourceCombo $resource): FirstSvgIllustration
    {
        return (new FirstSvgIllustration())
            ->setResource($resource);
    }

    static public function getDescription(): string
    {
        return "The first svg illustration of the page";
    }

    static public function getLabel(): string
    {
        return "First Svg illustration";
    }

    public static function getName(): string
    {
        return self::PROPERTY_NAME;
    }


    static public function isMutable(): bool
    {
        return false;
    }

    public function buildFromReadStore(): FirstSvgIllustration
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
        $firstImageId = $store->getFromPersistentName(FirstSvgIllustration::PROPERTY_NAME);


        $this->setFromStoreValueWithoutException($firstImageId);

        return $this;
    }


    static public function getPersistenceType(): string
    {
        return Metadata::DERIVED_METADATA;
    }

    static public function getDrive(): string
    {
        return WikiPath::MEDIA_DRIVE;
    }
}
