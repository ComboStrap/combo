<?php


namespace ComboStrap\Meta\Field;


use ComboStrap\DataType;
use ComboStrap\Meta\Api\MetadataText;

class AliasType extends MetadataText
{


    public const PROPERTY_NAME = "alias-type";
    public const PERSISTENT_NAME = "type";
    const REDIRECT = "redirect";
    const ALIAS_TYPE_VALUES = [AliasType::SYNONYM, AliasType::REDIRECT];
    const SYNONYM = "synonym";
    const DEFAULT = self::REDIRECT;

    public static function createForParent(Aliases $parent): AliasType
    {
        return new AliasType($parent);
    }

    static     public function getDescription(): string
    {
        return "The type of the alias";
    }

    static public function getLabel(): string
    {
        return "Alias Type";
    }

    public static function getName(): string
    {
        return self::PROPERTY_NAME;
    }

    public static function getPersistentName(): string
    {
        return self::PERSISTENT_NAME;
    }


    static public function getPersistenceType(): string
    {
        return DataType::TEXT_TYPE_VALUE;
    }

    static public function isMutable(): bool
    {
        return true;
    }

    public function getPossibleValues(): ?array
    {
        return AliasType::ALIAS_TYPE_VALUES;
    }


    public function getDefaultValue(): string
    {
        return AliasType::DEFAULT;
    }


}
