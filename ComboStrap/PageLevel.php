<?php


namespace ComboStrap;


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


    public function getValue(): int
    {
        return substr_count($this->getResource()->getPathObject()->toPathString(), WikiPath::NAMESPACE_SEPARATOR_DOUBLE_POINT) - 1;
    }


    static public function getName(): string
    {
        return self::PROPERTY_NAME;
    }


    public function getPersistenceType(): string
    {
        return Metadata::DERIVED_METADATA;
    }


    public function getTab(): string
    {
        return MetaManagerForm::TAB_PAGE_VALUE;
    }

    public function getDescription(): string
    {
        return "The level of the page on the file system (The home page is at level 0)";
    }

    public function getLabel(): string
    {
        return "Page Level";
    }

    public function getMutable(): bool
    {
        return false;
    }

    public function getCanonical(): string
    {
        return self::PROPERTY_NAME;
    }


}
