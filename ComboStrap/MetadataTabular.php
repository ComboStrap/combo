<?php


namespace ComboStrap;


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
    protected $rows;

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
        if($this->rows===null){
            return null;
        }

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
        $identifierPersistentName = $identifierMetadataClass::getPersistentName();
        if (is_string($value)) {
            /**
             * @var MetadataScalar $identifierMetadata
             */
            $identifierMetadata = (new $identifierMetadataClass());
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
            $identifierName = $identifierMetadataClass::getName();
            $identifierValues = $value[$identifierName];
            if($identifierValues===null || $identifierValues===""){
                // No data
                return $this;
            }
            $i = 0;
            foreach ($identifierValues as $identifierValue) {
                $row = [];
                $row[$identifierPersistentName] = Metadata::toChildMetadataObject($identifierMetadataClass, $this)
                    ->setFromStoreValue($identifierValue);
                foreach ($this->getChildren() as $childClass) {
                    if($childClass===$identifierMetadataClass){
                        continue;
                    }
                    $metadataChildObject = Metadata::toChildMetadataObject($childClass, $this);
                    $name = $metadataChildObject::getName();
                    $childValue = $value[$name][$i];
                    $metadataChildObject->setFromStoreValue($childValue);
                    $row[$metadataChildObject::getPersistentName()]=$metadataChildObject;
                }
                $this->rows[] = $row;
            }

            return $this;
        }

        /**
         * List of columns (HTML way)
         */
        // child object building
        $childObjectsByPersistentName = [];
        foreach ($this->getChildren() as $childClass) {
            $metadataChildObject = Metadata::toChildMetadataObject($childClass, $this);
            $childObjectsByPersistentName[$metadataChildObject::getPersistentName()] = $metadataChildObject;
        }
        foreach ($value as $item) {

            // Single value
            if (is_string($item)) {
                $identifierMetadata = Metadata::toChildMetadataObject($identifierMetadataClass, $this)
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
                $childObject = $childObjectsByPersistentName[$colName];
                if ($childObject === null) {
                    LogUtility::msg("The column does not have a metadata definition");
                    continue;
                }
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
}
