<?php


namespace ComboStrap;


use action_plugin_combo_metagoogle;

class LdJson extends MetadataJson
{

    public const JSON_LD_META_PROPERTY = "json-ld";

    public static function createFromPage(Page $page): LdJson
    {
        return new LdJson($page);
    }

    public function getName(): string
    {
        return self::JSON_LD_META_PROPERTY;
    }

    public function getPersistenceType()
    {
        return Metadata::PERSISTENT_METADATA;
    }

    public function getCanonical(): string
    {
        return action_plugin_combo_metagoogle::CANONICAL;
    }


    public function getDescription(): string
    {
        return "Advanced Page metadata definition with the json-ld format";
    }

    public function getLabel(): string
    {
        return "Json-ld";
    }

    public function getTab(): string
    {
        return \action_plugin_combo_metamanager::TAB_TYPE_VALUE;
    }
}
