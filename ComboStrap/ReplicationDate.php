<?php

namespace ComboStrap;

use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Api\MetadataDateTime;

/**
 * Class ReplicationDate
 * Modification date of the database row
 */
class ReplicationDate extends MetadataDateTime
{

    /**
     * The attribute in the metadata and in the database
     */
    public const PROPERTY_NAME = "date_replication";
    public const REPLICATION_CANONICAL = "replication";

    public static function createFromPage(MarkupPath $page)
    {
        return (new ReplicationDate())
            ->setResource($page);
    }

    static public function getTab(): string
    {
        return MetaManagerForm::TAB_INTEGRATION_VALUE;
    }

    static public function getDescription(): string
    {
        return "The modification date of the database row";
    }

    static public function getLabel(): string
    {
        return "Database Replication Date";
    }

    static public function getCanonical(): string
    {
        return self::REPLICATION_CANONICAL;
    }

    static public function getName(): string
    {
        return self::PROPERTY_NAME;
    }

    static public function getPersistenceType(): string
    {
        return Metadata::RUNTIME_METADATA;
    }

    static public function isMutable(): bool
    {
        return false;
    }

    /**
     * @return mixed
     * @throws ExceptionNotFound
     */
    public function getDefaultValue()
    {
        throw new ExceptionNotFound("No default replication date");
    }

    static public function isOnForm(): bool
    {
        return true;
    }
}
