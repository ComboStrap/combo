<?php

namespace ComboStrap\Meta\Field;

use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Api\MetadataWikiPath;


class FeaturedRasterImage extends MetadataWikiPath
{

    public function getDescription(): string
    {
        return "A featured image in raster format";
    }

    public function getLabel(): string
    {
        return "Featured Raster Image";
    }

    public static function getName(): string
    {
        return "featured-raster-image";
    }

    public function getPersistenceType(): string
    {
        return Metadata::PERSISTENT_METADATA;
    }

    public function getMutable(): bool
    {
        return true;
    }

}
