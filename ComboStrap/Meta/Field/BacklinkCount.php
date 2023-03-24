<?php


namespace ComboStrap\Meta\Field;

use ComboStrap\ExceptionCompile;
use ComboStrap\LogUtility;
use ComboStrap\MarkupPath;
use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Api\MetadataInteger;
use ComboStrap\Meta\Store\MetadataDbStore;
use ComboStrap\Meta\Store\MetadataDokuWikiStore;
use ComboStrap\Sqlite;

/**
 * Class BacklinkCount
 * @package ComboStrap
 * Internal backlink count
 */
class BacklinkCount extends MetadataInteger
{
    const PROPERTY_NAME = 'backlink_count';

    public static function createFromResource(MarkupPath $page)
    {
        return (new BacklinkCount())
            ->setResource($page);
    }

    static public function getDescription(): string
    {
        return "The number of backlinks";
    }

    static public function getLabel(): string
    {
        return "Backlink Count";
    }

    public static function getName(): string
    {
        return self::PROPERTY_NAME;
    }

    static public function getPersistenceType(): string
    {
        return Metadata::DERIVED_METADATA;
    }

    static public function isMutable(): bool
    {
        return false;
    }

    public function buildFromReadStore(): Metadata
    {

        $storeClass = get_class($this->getReadStore());
        switch ($storeClass) {
            case MetadataDokuWikiStore::class:
                $resource = $this->getResource();
                if (!($resource instanceof MarkupPath)) {
                    LogUtility::msg("Backlink count is not yet supported on the resource type ({$resource->getType()}");
                    return $this;
                }
                $backlinks = $resource->getBacklinks();
                $this->value = sizeof($backlinks);
                return $this;
            case MetadataDbStore::class:
                $this->value = $this->calculateBacklinkCount();
                return $this;
            default:
                LogUtility::msg("The store ($storeClass) does not support backlink count");
                return $this;
        }


    }

    /**
     * Sqlite is much quicker than the Dokuwiki Internal Index
     * We use it every time that we can
     *
     * @return int|null
     */
    private function calculateBacklinkCount(): ?int
    {

        $sqlite = Sqlite::createOrGetSqlite();
        /** @noinspection SqlResolve */
        $request = $sqlite
            ->createRequest()
            ->setQueryParametrized("select count(1) from PAGE_REFERENCES where REFERENCE = ? ", [$this->getResource()->getPathObject()->toAbsoluteId()]);
        $count = 0;
        try {
            $count = $request
                ->execute()
                ->getFirstCellValue();
        } catch (ExceptionCompile $e) {
            LogUtility::error($e->getMessage(), self::PROPERTY_NAME, $e);
        } finally {
            $request->close();
        }
        return intval($count);

    }


    public function setFromStoreValueWithoutException($value): Metadata
    {
        /**
         * not used because
         * the data is not stored in the database
         * We overwrite and build the value {@link BacklinkCount::buildFromReadStore()}
         */
        return $this;
    }


}
