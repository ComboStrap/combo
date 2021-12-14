<?php


namespace ComboStrap;


class PageImagePath extends MetadataWikiPath
{

    public const PERSISTENT_NAME = "path";
    const PROPERTY_NAME = "page-image-path";


    public static function createFromParent(Metadata $metadata): PageImagePath
    {
        return (new PageImagePath($metadata));
    }


    public function getDescription(): string
    {
        return "The path of the image";
    }

    public function getLabel(): string
    {
        return "Path";
    }

    static public function getName(): string
    {
        return self::PROPERTY_NAME;
    }

    static public function getPersistentName(): string
    {
        return self::PERSISTENT_NAME;
    }


    public function getPersistenceType(): string
    {
        return Metadata::PERSISTENT_METADATA;
    }

    public function getMutable(): bool
    {
        return true;
    }

    public function getDefaultValue()
    {
        return null;
    }

    public function getValue(): ?string
    {
        return null;
    }

    public function getFormControlWidth()
    {
        return 8;
    }


}
