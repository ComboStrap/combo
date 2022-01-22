<?php


namespace ComboStrap;

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



    public static function createFromResource(Page $page)
    {
        return (new Reference())
            ->setResource($page);
    }

    public function getDescription(): string
    {
        return "The path to the internal page";
    }

    public function getLabel(): string
    {
        return "Reference Path";
    }

    public static function getName(): string
    {
        return "reference";
    }

    public function getPersistenceType(): string
    {
        return Metadata::DERIVED_METADATA;
    }

    public function getMutable(): bool
    {
        return false;
    }

}
