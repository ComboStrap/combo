<?php


namespace ComboStrap;


class StartDate extends MetadataDateTime
{

    public const PROPERTY_NAME = "date_start";

    public static function createFromPage(Page $page)
    {
        return (new StartDate())
            ->setResource($page);
    }

    public function getTab(): string
    {
        return MetaManagerForm::TAB_TYPE_VALUE;
    }

    public function getDescription(): string
    {
        return "The start date of an event";
    }

    public function getLabel(): string
    {
        return "Start Date";
    }

    static public function getName(): string
    {
        return self::PROPERTY_NAME;
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

    public function getCanonical(): string
    {
        return PageType::EVENT_TYPE;
    }


}
