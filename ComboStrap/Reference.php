<?php


namespace ComboStrap;

use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Api\MetadataWikiPath;

/**
 * Class Reference
 * @package ComboStrap
 * Reference
 *
 * Because they may not exist and this data is derive, we store only the value
 * and not the page id for instance
 */
class Reference extends MetadataWikiPath
{


    public static function createFromResource(MarkupPath $page)
    {
        return (new Reference())
            ->setResource($page);
    }

    static public function getDescription(): string
    {
        return "The path to the internal page";
    }

    static public function getLabel(): string
    {
        return "Reference Path";
    }

    public static function getName(): string
    {
        return "reference";
    }

    static public function getPersistenceType(): string
    {
        return Metadata::DERIVED_METADATA;
    }

    static public function isMutable(): bool
    {
        return false;
    }

    static public function getDrive(): string
    {
        return WikiPath::MARKUP_DRIVE;
    }

}
