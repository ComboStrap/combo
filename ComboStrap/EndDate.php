<?php


namespace ComboStrap;


use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Api\MetadataDateTime;

class EndDate extends MetadataDateTime
{


    public const PROPERTY_NAME = "date_end";

    public static function createFromPage(MarkupPath $page)
    {
        return (new EndDate())
            ->setResource($page);
    }

    static public function getTab(): string
    {
        return MetaManagerForm::TAB_TYPE_VALUE;
    }

    static public function getDescription(): string
    {
        return "The end date of an event";
    }

    static public function getLabel(): string
    {
        return "End Date";
    }

    public static function getName(): string
    {
        return self::PROPERTY_NAME;
    }

    static public function getPersistenceType(): string
    {
        return Metadata::PERSISTENT_METADATA;
    }

    static public function isMutable(): bool
    {
        return true;
    }

    public function getDefaultValue()
    {
        throw new ExceptionNotFound("The end date does not have any default value");
    }

    static public function getCanonical(): string
    {
        return PageType::EVENT_TYPE;
    }


    static public function isOnForm(): bool
    {
        return true;
    }

}
