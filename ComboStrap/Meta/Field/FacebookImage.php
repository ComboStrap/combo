<?php

namespace ComboStrap\Meta\Field;

use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Api\MetadataWikiPath;


class FacebookImage extends MetadataWikiPath
{

    public function getDescription(): string
    {
        return "The facebook image used in facebook card";
    }

    public function getLabel(): string
    {
        return "Facebook Image";
    }

    public static function getName(): string
    {
        return "facebook-image";
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
