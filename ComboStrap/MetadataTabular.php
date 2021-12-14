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
        return $this->rows;
    }

    public abstract function getColumnValues(Metadata $childMetadata);

    public abstract function getDefaultValueForColumn(Metadata $childMetadata);


    public function toStoreValue()
    {

    }

    public function buildFromStoreValue($value): Metadata
    {
        if ($value == null) {
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
                $identifierMetadata = Metadata::toMetadataObject($identifierMetadataClass)
                    ->setFromStoreValue($value);
                $this->rows[$item] = [$identifierName => $identifierMetadata];
                continue;
            }
            if (!is_array($item)) {
                LogUtility::msg("The tabular value is not a string nor an array");
            }
            $row = [];
            $idValue = null;
            foreach ($item as $colName => $colValue) {
                foreach ($this->getChildren() as $childClass) {
                    $childObject = Metadata::toMetadataObject($childClass);
                    if ($childObject::getPersistentName() === $colName) {
                        $childObject->setFromStoreValue($colValue);
                        $row[$childClass->getPersistentName()] = $childObject;
                        if ($childClass->getName() === $identifierName) {
                            $idValue = $colValue;
                        }
                    }
                }
                LogUtility::msg("The property name ($colName) is not a column name");
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
