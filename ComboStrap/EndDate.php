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

    public function getTab(): string
    {
        return MetaManagerForm::TAB_TYPE_VALUE;
    }

    public function getDescription(): string
    {
        return "The end date of an event";
    }

    public function getLabel(): string
    {
        return "End Date";
    }

    public static function getName(): string
    {
        return self::PROPERTY_NAME;
    }

    public function getPersistenceType(): string
    {
        return Metadata::PERSISTENT_METADATA;
    }

    public function isMutable(): bool
    {
        return true;
    }

    public function getDefaultValue()
    {
        throw new ExceptionNotFound("The end date does not have any default value");
    }

    public function getCanonical(): string
    {
        return PageType::EVENT_TYPE;
    }


    public function isOnForm(): bool
    {
        return true;
    }

}
