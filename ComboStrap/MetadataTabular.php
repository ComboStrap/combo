<?php


namespace ComboStrap;


use http\Exception\RuntimeException;

/**
 * Class MetadataTabular
 * @package ComboStrap
 * A list of row represented as a list of column
 * ie an entity with a map that has a key
 */
abstract class MetadataTabular extends Metadata
{


    /**
     * The rows
     * @var Metadata[][]
     * Each row has the key has value
     */
    private $rows;

    public function getDefaultValue()
    {
        return null;
    }

    /**
     * @return array - the rows in array format
     */
    public function getValue(): ?array
    {
        $this->buildCheck();
        return $this->rows;
    }

    public abstract function getColumnValues(Metadata $childMetadata);

    public abstract function getDefaultValueForColumn(Metadata $childMetadata);


    public function toStoreValue(): ?array
    {
        $rowsArray = [];
        foreach ($this->rows as $row) {
            $rowArray = [];
            foreach ($row as $col => $metadata) {
                $toStoreValue = $metadata->toStoreValue();
                if ($toStoreValue !== null) {
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
        $identifierMetadataClass = $this->getUid();
        $identifierName = $identifierMetadataClass::getPersistentName();
        if (is_string($value)) {
            /**
             * @var MetadataScalar $identifierMetadata
             */
            $identifierMetadata = (new $identifierMetadataClass());
            $identifierMetadata->setValue($value);
            $this->rows[$value] = [$identifierName => $identifierMetadata];
            return $this;
        }
        if (!is_array($value)) {
            LogUtility::msg("The tabular value is not a string nor an array");
            return $this;
        }
        /**
         * Loop
         */
        foreach ($value as $key => $item) {
            // not a row (ie array of array), a single array
            if (!is_numeric($key)) {
                throw new RuntimeException("To implement");
            }
            // Single value
            if (is_string($item)) {
                $identifierMetadata = Metadata::toChildMetadataObject($identifierMetadataClass, $this)
                    ->buildFromStoreValue($value);
                $this->rows[$item] = [$identifierName => $identifierMetadata];
                continue;
            }
            if (!is_array($item)) {
                LogUtility::msg("The tabular value is not a string nor an array");
            }
            $row = [];
            $idValue = null;
            $childObjects = [];
            foreach ($this->getChildren() as $childClass) {
                $objects = Metadata::toChildMetadataObject($childClass, $this);
                $childObjects[$objects::getPersistentName()] = $objects;
            }
            foreach ($item as $colName => $colValue) {
                $childObject = $childObjects[$colName];
                if ($childObject === null) {
                    LogUtility::msg("The column does not have a metadata definition");
                    continue;
                }
                $childObject->buildFromStoreValue($colValue);
                $row[$childObject::getPersistentName()] = $childObject;
                if ($childObject::getPersistentName() === $identifierName) {
                    $idValue = $colValue;
                }
            }
            if ($idValue === null) {
                LogUtility::msg("The value for the identifier ($identifierName) was not found");
                continue;
            }
            $this->rows[$idValue] = $row;

        }
        return $this;
    }


}
