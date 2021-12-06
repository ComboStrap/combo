<?php


use ComboStrap\Metadata;
use ComboStrap\MetadataDateTime;
use ComboStrap\Page;

class ReplicationDate extends MetadataDateTime
{

    /**
     * The attribute in the metadata and in the database
     */
    public const DATE_REPLICATION = "date_replication";
    public const REPLICATION_CANONICAL = "replication";

    public static function createFromPage(Page $page)
    {
        return (new ReplicationDate())
            ->setResource($page);
    }

    public function getTab(): string
    {
        return action_plugin_combo_metamanager::TAB_INTEGRATION_VALUE;
    }

    public function getDescription(): string
    {
        return "The last date of database replication";
    }

    public function getLabel(): string
    {
        return "Database Replication Date";
    }

    public function getCanonical(): string
    {
        return self::REPLICATION_CANONICAL;
    }

    public function getName(): string
    {
        return self::DATE_REPLICATION;
    }

    public function getPersistenceType(): string
    {
        return Metadata::RUNTIME_METADATA;
    }

    public function getMutable(): bool
    {
        return false;
    }

    public function getDefaultValue()
    {
        // TODO: Implement getDefaultValue() method.
    }
}
