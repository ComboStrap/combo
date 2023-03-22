<?php

namespace ComboStrap;

use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Api\MetadataImage;

/**
 * A derived meta that captures the first raster image
 * via {@link \syntax_plugin_combo_media::registerFirstImage()}
 */
class FeaturedIcon extends MetadataImage
{

    public const PROPERTY_NAME = "featured-icon";
    public const FIRST_ICON_PARSED = "first-icon-image-parsed";

    public static function createForPage(ResourceCombo $resource): FeaturedIcon
    {
        return (new FeaturedIcon())
            ->setResource($resource);
    }

    static public function getDescription(): string
    {
        return "An illustrative icon for the page";
    }

    static public function getLabel(): string
    {
        return "Featured Icon";
    }

    public static function getName(): string
    {
        return self::PROPERTY_NAME;
    }


    static public function isMutable(): bool
    {
        return true;
    }


    static public function getPersistenceType(): string
    {
        return Metadata::PERSISTENT_METADATA;
    }

    public function getDefaultValue()
    {
        /**
         *
         * Image set by {@link \syntax_plugin_combo_media::registerFirstImage()}
         */
        $iconImageParsed = $this->getReadStore()->getFromPersistentName(FeaturedIcon::FIRST_ICON_PARSED);

        if ($iconImageParsed !== null) {
            return WikiPath::createMediaPathFromId($iconImageParsed);
        }

        throw new ExceptionNotFound();
    }

    static public function getDrive(): string
    {
        return WikiPath::MEDIA_DRIVE;
    }

    static public function isOnForm(): bool
    {
        return true;
    }
}
