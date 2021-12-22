<?php


namespace ComboStrap;


class BacklinkCount extends Metadata
{
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
        return 'backlink_count';
    }

    public function getPersistenceType(): string
    {
        return Metadata::DERIVED_METADATA;
    }

    public function getMutable(): bool
    {
        return false;
    }

    public function buildFromStoreValue($value): Metadata
    {
        try {
            $this->value = $this->toInt($value);
        } catch (ExceptionCombo $e) {
            LogUtility::msg($e->getMessage(),LogUtility::LVL_MSG_ERROR,$e->getCanonical());
        }
        return $this;
    }

    public function getValue()
    {
        return $this->value;
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
    public function calculateBacklinkCount(): ?int
    {

        $sqlite= Sqlite::createOrGetSqlite();
        if ($sqlite === null) {
            return null;
        }
        $request = $sqlite
            ->createRequest()
            ->setStatementParametrized("select count(1) from PAGE_REFERENCES where REFERENCE = ? ", [$this->getResource()->getPath()->toString()]);
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
}
