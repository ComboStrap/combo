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

    public function getTab(): string
    {
        return MetaManagerForm::TAB_INTEGRATION_VALUE;
    }

    public function getDescription(): string
    {
        return "The modification date of the database row";
    }

    public function getLabel(): string
    {
        return "Database Replication Date";
    }

    public function getCanonical(): string
    {
        return self::REPLICATION_CANONICAL;
    }

    static public function getName(): string
    {
        return self::PROPERTY_NAME;
    }

    public function getPersistenceType(): string
    {
        return Metadata::RUNTIME_METADATA;
    }

    public function getMutable(): bool
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
}
