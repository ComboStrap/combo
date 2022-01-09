<?php


namespace ComboStrap;

/**
 * Class BacklinkCount
 * @package ComboStrap
 * Internal backlink count
 */
class BacklinkCount extends Metadata
{
    const PROPERTY_NAME = 'backlink_count';


    /**
     * @var int
     */
    private $value;

    public static function createFromResource(Page $page)
    {
        return (new BacklinkCount())
            ->setResource($page);
    }


    /**
     * @throws ExceptionCombo
     */
    public function setValue($value): Metadata
    {
        $this->value = $this->toInt($value);
        return $this;
    }

    public function valueIsNotNull(): bool
    {
        return $this->value !== null;
    }

    public function getDataType(): string
    {
        return DataType::INTEGER_TYPE_VALUE;
    }

    public function getDescription(): string
    {
        return "The number of backlinks";
    }

    public function getLabel(): string
    {
        return "Backlink Count";
    }

    public static function getName(): string
    {
        return self::PROPERTY_NAME;
    }

    public function getPersistenceType(): string
    {
        return Metadata::DERIVED_METADATA;
    }

    public function getMutable(): bool
    {
        return false;
    }

    public function buildFromReadStore(): Metadata
    {

        $storeClass = get_class($this->getReadStore());
        switch ($storeClass) {
            case MetadataDokuWikiStore::class:
                $resource = $this->getResource();
                if (!($resource instanceof Page)) {
                    LogUtility::msg("Backlink count is not supported on the resource type ({$resource->getType()}");
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


    public function getDefaultValue(): int
    {
        return 0;
    }

    /**
     * @throws ExceptionCombo
     */
    private function toInt($value): int
    {
        if (!is_numeric($value)) {
            throw new ExceptionCombo("The value is not a numeric");
        }
        if (!is_int($value)) {
            throw new ExceptionCombo("The value is not an integer");
        }
        return intval($value);
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
        if ($sqlite === null) {
            return null;
        }
        $request = $sqlite
            ->createRequest()
            ->setQueryParametrized("select count(1) from PAGE_REFERENCES where REFERENCE = ? ", [$this->getResource()->getPath()->toString()]);
        $count = 0;
        try {
            $count = $request
                ->execute()
                ->getFirstCellValue();
        } catch (ExceptionCombo $e) {
            LogUtility::msg($e->getMessage(), LogUtility::LVL_MSG_ERROR);
        } finally {
            $request->close();
        }
        return intval($count);

    }

    public function getValue()
    {
        $this->buildCheck();
        return $this->value;
    }

    public function buildFromStoreValue($value): Metadata
    {
        /**
         * not used because
         * the data is not stored in the database
         * We overwrite and build the value {@link BacklinkCount::buildFromReadStore()}
         */
        return $this;
    }



}
