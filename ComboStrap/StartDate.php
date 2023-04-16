<?php


namespace ComboStrap;


use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Api\MetadataDateTime;

class StartDate extends MetadataDateTime
{

    public const PROPERTY_NAME = "date_start";

    public static function createFromPage(MarkupPath $page)
    {
        return (new StartDate())
            ->setResource($page);
    }

    static public function getTab(): string
    {
        return MetaManagerForm::TAB_TYPE_VALUE;
    }

    static public function getDescription(): string
    {
        return "The start date of an event";
    }

    static public function getLabel(): string
    {
        return "Start Date";
    }

    static public function getName(): string
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
        throw new ExceptionNotFound("Start date does not have any default value");
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
