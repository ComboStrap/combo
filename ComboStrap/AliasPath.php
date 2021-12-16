<?php


namespace ComboStrap;


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
        return DataTYpe::TEXT_TYPE_VALUE;
    }

    public function getMutable(): bool
    {
        return true;
    }
}
