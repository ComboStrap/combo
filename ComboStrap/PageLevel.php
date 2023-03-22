<?php


namespace ComboStrap;


use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Api\MetadataInteger;

/**
 * @package ComboStrap
 * Represents the level in the tree
 */
class PageLevel extends MetadataInteger
{


    public const PROPERTY_NAME = "level";


    public static function createForPage(ResourceCombo $page): PageLevel
    {
        return (new PageLevel())
            ->setResource($page);
    }


    /**
     * @return int
     */
    public function getValue(): int
    {
        return substr_count($this->getResource()->getPathObject()->toAbsoluteId(), WikiPath::NAMESPACE_SEPARATOR_DOUBLE_POINT) - 1;
    }


    static public function getName(): string
    {
        return self::PROPERTY_NAME;
    }


    static public function getPersistenceType(): string
    {
        return Metadata::DERIVED_METADATA;
    }


    static public function getTab(): string
    {
        return MetaManagerForm::TAB_PAGE_VALUE;
    }

    static public function getDescription(): string
    {
        return "The level of the page on the file system (The home page is at level 0)";
    }

    static public function getLabel(): string
    {
        return "Page Level";
    }

    static public function isMutable(): bool
    {
        return false;
    }

    static public function getCanonical(): string
    {
        return self::PROPERTY_NAME;
    }


    static public function isOnForm(): bool
    {
        return true;
    }

}
