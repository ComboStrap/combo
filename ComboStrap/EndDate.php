<?php


namespace ComboStrap;


class EndDate extends MetadataDateTime
{


    public const DATE_END = "date_end";

    public static function createFromPage(Page $page)
    {
        return (new EndDate())
            ->setResource($page);
    }

    public function getTab(): string
    {
        return \action_plugin_combo_metamanager::TAB_TYPE_VALUE;
    }

    public function getDescription(): string
    {
        return "The end date of an event";
    }

    public function getLabel(): string
    {
        return "End Date";
    }

    public function getName(): string
    {
        return self::DATE_END;
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
