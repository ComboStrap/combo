<?php


namespace ComboStrap;


class References extends MetadataTabular
{


    public static function createFromResource(Page $page)
    {
        return (new References())
            ->setResource($page);
    }

    public function getDescription(): string
    {
        return "The link of the page that references another resources";
    }

    public function getLabel(): string
    {
        return "References";
    }

    public static function getName(): string
    {
        return "references";
    }

    public function getPersistenceType(): string
    {
        return Metadata::DERIVED_METADATA;
    }

    public function getMutable(): bool
    {
        return false;
    }

    public function getDefaultValue()
    {
        return null;
    }

}
