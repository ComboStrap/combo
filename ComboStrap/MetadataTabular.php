<?php


namespace ComboStrap;


/**
 * Class MetadataTabular
 * @package ComboStrap
 * A list of row represented as a list of column
 * ie an entity with a map that has a key
 *
 * The value of a tabular is an array of row (where a row is an associative array)
 */
abstract class MetadataTabular extends Metadata
{


    /**
     * The rows
     * @var Metadata[][]
     * Each row has the key has value
     */
    protected $rows;


    /**
     * @return array - the rows in associate array format
     * where the identifier value is the key
     */
    public function getValue(): ?array
    {
        $this->buildCheck();
        if ($this->rows === null) {
            return null;
        }
        return $this->rows;
    }

    public function setValue($value): Metadata
    {
        if ($value === null) {
            return $this;
        }
        if (!is_array($value)) {
            throw new ExceptionComboRuntime("The data set is not an array (The tabular data is an array of rows)");
        }
        $keys = array_keys($value);
        foreach ($keys as $key) {
            if (!is_numeric($key)) {
                throw new ExceptionComboRuntime("The element of the array are not rows. The index ($key) should be numeric and is not");
            }
        }
        $this->rows = $value;
        return $this;

    }


    public function toStoreValue(): ?array
    {
        if ($this->rows === null) {
            return null;
        }

        $rowsArray = [];
        ksort($this->rows);
        foreach ($this->rows as $row) {
            $rowArray = [];
            foreach ($row as $metadata) {
                $toStoreValue = $metadata->toStoreValue();
                $toDefaultStoreValue = $metadata->toStoreDefaultValue();
                if (
                    $toStoreValue !== null
                    && $toStoreValue !== $toDefaultStoreValue
                ) {
                    $rowArray[$metadata::getPersistentName()] = $toStoreValue;
                }
            }
            $rowsArray[] = $rowArray;
        }
        return $rowsArray;
    }

    public function buildFromStoreValue($value): Metadata
    {
        if ($value === null) {
            return $this;
        }
        /**
         * Value of the metadata id
         */
        $identifierMetadataObject = $this->getUidObject();
        $identifierPersistentName = $identifierMetadataObject::getPersistentName();
        if (is_string($value)) {
            /**
             * @var Metadata $identifierMetadata
             */
            $identifierMetadata = (new $identifierMetadataObject());
            $identifierMetadata->setValue($value);
            $this->rows[$value] = [$identifierPersistentName => $identifierMetadata];
            return $this;
        }
        if (!is_array($value)) {
            LogUtility::msg("The tabular value is not a string nor an array");
            return $this;
        }


        /**
         * Determine the format of the tabular
         */
        $keys = array_keys($value);
        $firstElement = array_shift($keys);

        if (!is_numeric($firstElement)) {
            /**
             * List of row (Storage way)
             */
            $identifierName = $identifierMetadataObject::getName();
            $identifierValues = $value[$identifierName];
            if ($identifierValues === null || $identifierValues === "") {
                // No data
                return $this;
            }
            $i = 0;
            foreach ($identifierValues as $identifierValue) {
                $row = [];
                if ($identifierValue === "") {
                    // an empty row in the table
                    continue;
                }
                $row[$identifierPersistentName] = Metadata::toMetadataObject($identifierMetadataObject, $this)
                    ->setFromStoreValue($identifierValue);
                foreach ($this->getChildren() as $childClass) {
                    if ($childClass === $identifierMetadataObject) {
                        continue;
                    }
                    $metadataChildObject = Metadata::toMetadataObject($childClass, $this);
                    $name = $metadataChildObject::getName();
                    $childValue = $value[$name][$i];
                    $metadataChildObject->setFromStoreValue($childValue);
                    $row[$metadataChildObject::getPersistentName()] = $metadataChildObject;
                }
                $this->rows[] = $row;
            }

            return $this;
        }

        /**
         * List of columns (HTML way)
         */
        // child object building
        $childClassesByPersistentName = [];
        foreach ($this->getChildren() as $childClass) {
            $childClassesByPersistentName[$childClass::getPersistentName()] = $childClass;
        }
        foreach ($value as $item) {

            // Single value
            if (is_string($item)) {
                $identifierMetadata = Metadata::toMetadataObject($identifierMetadataObject, $this)
                    ->buildFromStoreValue($item);
                $this->rows[$item] = [$identifierPersistentName => $identifierMetadata];
                continue;
            }
            if (!is_array($item)) {
                LogUtility::msg("The tabular value is not a string nor an array");
            }
            $row = [];
            $idValue = null;
            foreach ($item as $colName => $colValue) {
                $childClass = $childClassesByPersistentName[$colName];
                if ($childClass === null) {
                    LogUtility::msg("The column ($colName) does not have a metadata definition");
                    continue;
                }
                $childObject = Metadata::toMetadataObject($childClass, $this);
                $childObject->buildFromStoreValue($colValue);
                $row[$childObject::getPersistentName()] = $childObject;
                if ($childObject::getPersistentName() === $identifierPersistentName) {
                    $idValue = $colValue;
                }
            }
            if ($idValue === null) {
                LogUtility::msg("The value for the identifier ($identifierPersistentName) was not found");
                continue;
            }
            $this->rows[$idValue] = $row;

        }
        return $this;
    }


    public function valueIsNotNull(): bool
    {
        return $this->rows !== null;
    }

    public
    function remove($identifierValue)
    {
        $this->buildCheck();
        if ($this->rows === null) {
            return;
        }
        unset($this->rows[$identifierValue]);
        return $this;
    }


    public
    function has($identifierValue): bool
    {
        $this->buildCheck();
        if ($this->rows === null) {
            return false;
        }
        return isset($this->rows[$identifierValue]);
    }

    public
    function getSize(): int
    {
        $this->buildCheck();
        if ($this->rows === null) {
            return 0;
        }
        return sizeof($this->rows);
    }

    public function getRow($id): ?array
    {
        $this->buildCheck();
        $normalizedValue = $this->getUidObject()
            ->buildFromStoreValue($id)
            ->getValue();
        return $this->rows[$normalizedValue];
    }

    public function getDataType(): string
    {
        return DataType::TABULAR_TYPE_VALUE;
    }

    /**
     * @var Metadata[] $metas
     */
    public function addRow(array $metas): MetadataTabular
    {
        $row = [];
        $identifier = null;
        foreach ($metas as $meta) {
            $row[$meta::getPersistentName()] = $meta;
            if (get_class($meta) === $this->getUidClass()) {
                $identifier = $meta->getValue();
            }
        }
        if ($identifier === null) {
            LogUtility::msg("The identifier value was not found in the row");
            return $this;
        }
        $this->rows[$identifier] = $row;
        return $this;
    }

}
