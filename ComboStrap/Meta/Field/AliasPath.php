<?php


namespace ComboStrap\Meta\Field;


use ComboStrap\DataType;
use ComboStrap\Meta\Api\MetadataWikiPath;
use ComboStrap\WikiPath;

class AliasPath extends MetadataWikiPath
{

    public const PERSISTENT_NAME = "path";
    const PROPERTY_NAME = "alias-path";

    public function getDescription(): string
    {
        return "The path of the alias";
    }

    public function getLabel(): string
    {
        return "Alias Path";
    }

    public static function getName(): string
    {
        return self::PROPERTY_NAME;
    }

    public static function getPersistentName(): string
    {
        return self::PERSISTENT_NAME;
    }


    public function getPersistenceType(): string
    {
        return DataType::TEXT_TYPE_VALUE;
    }

    public function isMutable(): bool
    {
        return true;
    }

    public function getDrive(): string
    {
        return WikiPath::MARKUP_DRIVE;
    }
}
