<?php

namespace ComboStrap\Meta\Field;

use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Api\MetadataImage;
use ComboStrap\Meta\Api\MetadataWikiPath;


class FeaturedSvgImage extends MetadataImage
{

    public function getDescription(): string
    {
        return "A featured image in svg format";
    }

    public function getLabel(): string
    {
        return "Featured Svg Image";
    }

    public static function getName(): string
    {
        return "featured-svg-image";
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
