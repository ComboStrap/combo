<?php

namespace ComboStrap;

use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Api\MetadataImage;
use ComboStrap\Meta\Store\MetadataDokuWikiStore;

/**
 * A derived meta that captures the first raster image
 * via {@link \syntax_plugin_combo_media::registerFirstImage()}
 */
class IconImage extends MetadataImage
{

    public const PROPERTY_NAME = "icon-image";
    public const FIRST_ICON_PARSED = "first-icon-image-parsed";

    public static function createForPage(ResourceCombo $resource): IconImage
    {
        return (new IconImage())
            ->setResource($resource);
    }

    public function getDescription(): string
    {
        return "The icon image of the page";
    }

    public function getLabel(): string
    {
        return "Icon image";
    }

    public static function getName(): string
    {
        return self::PROPERTY_NAME;
    }


    public function getMutable(): bool
    {
        return true;
    }


    public function getPersistenceType(): string
    {
        return Metadata::PERSISTENT_METADATA;
    }

    public function getDefaultValue()
    {
        /**
         *
         * Image set by {@link \syntax_plugin_combo_media::registerFirstImage()}
         */
        $iconImageParsed = $this->getReadStore()->getFromPersistentName(IconImage::FIRST_ICON_PARSED);

        if($iconImageParsed!==null){
            return WikiPath::createMediaPathFromId($iconImageParsed);
        }

        throw new ExceptionNotFound();
    }


}
