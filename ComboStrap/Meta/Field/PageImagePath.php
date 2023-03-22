<?php


namespace ComboStrap\Meta\Field;


use ComboStrap\ExceptionCompile;
use ComboStrap\FileSystems;
use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Api\MetadataWikiPath;
use ComboStrap\WikiPath;

/**
 * @deprecated
 */
class PageImagePath extends MetadataWikiPath
{

    public const PERSISTENT_NAME = "path";
    const PROPERTY_NAME = "image-path";


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

    public function getFormControlWidth(): int
    {
        return 8;
    }


    public function setFromStoreValue($value): Metadata
    {
        WikiPath::addRootSeparatorIfNotPresent($value);
        $path = WikiPath::createMediaPathFromPath($value);
        if (!FileSystems::exists($path)) {
            throw new ExceptionCompile("The image ($value) does not exists", $this->getCanonical());
        }
        return parent::setFromStoreValue($value);
    }


    public function getDrive(): string
    {
        return WikiPath::MEDIA_DRIVE;
    }
}
