<?php


namespace ComboStrap\Meta\Api;


use ComboStrap\DataType;
use ComboStrap\ExceptionBadArgument;
use ComboStrap\ExceptionNotFound;
use ComboStrap\ExceptionRuntime;
use ComboStrap\ExceptionRuntimeInternal;
use ComboStrap\LogUtility;
use ComboStrap\Meta\Field\Aliases;

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
     * In the array, the identifier may be the persistent one
     * or the identifier one
     */
    const PERSISTENT_NAME = "persistent";
    const IDENTIFIER_NAME = "identifier";


    /**
     * The rows
     * @var Metadata[][]
     * Each row has the key has value
     */
    protected ?array $rows = null;


    /**
     * @return array - the rows in associate array format
     * where the identifier value is the key
     * @throws ExceptionNotFound
     */
    public function getValue(): array
    {
        $this->buildCheck();
        if ($this->rows === null) {
            throw new ExceptionNotFound("No tabular data found.");
        }
        return $this->rows;
    }

    public function setValue($value): Metadata
    {
        if ($value === null) {
            return $this;
        }
        if (!is_array($value)) {
            throw new ExceptionRuntime("The data set is not an array (The tabular data is an array of rows)");
        }
        $keys = array_keys($value);
        foreach ($keys as $key) {
            if (!is_numeric($key)) {
                throw new ExceptionRuntime("The element of the array are not rows. The index ($key) should be numeric and is not");
            }
        }
        $this->rows = $value;
        return $this;

    }


    public function toStoreValue(): ?array
    {
        $this->buildCheck();
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

    /**
     * The value is:
     *   * a string for a unique identifier value
     *   * an array of columns or an array of rows
     */
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

        /**
         * Single value
         */
        if (is_string($value)) {
            /**
             * @var Metadata $identifierMetadata
             */
            $identifierMetadata = (new $identifierMetadataObject());
            $identifierMetadata->setValue($value);
            $this->rows[$value] = [$identifierPersistentName => $identifierMetadata];
            return $this;
        }

        /**
         * Array
         */
        if (!is_array($value)) {
            LogUtility::msg("The tabular value is nor a string nor an array");
            return $this;
        }


        /**
         * List of columns ({@link MetadataFormDataStore form html way}
         *
         * For example: for {@link Aliases}
         *  alias-path:
         *    0: path1
         *    1: path2
         *  alias-type:
         *    0: redirect
         *    1: redirect
         */
        $keys = array_keys($value);
        $firstElement = array_shift($keys);
        if (!is_numeric($firstElement)) {
            /**
             * Check which kind of key is used
             * Resistance to the property key !
             */
            $identifierName = $identifierMetadataObject::getPersistentName();
            $identifierNameType = self::PERSISTENT_NAME;
            if (!isset($value[$identifierName])) {
                $identifierNameType = self::IDENTIFIER_NAME;
                $identifierName = $identifierMetadataObject::getName();
            }
            $identifierValues = $value[$identifierName];
            if ($identifierValues === null || $identifierValues === "") {
                // No data
                return $this;
            }
            $index = 0;
            if (!is_array($identifierValues)) {
                // only one value
                $identifierValues = [$identifierValues];
            }
            foreach ($identifierValues as $identifierValue) {
                $row = [];
                if ($identifierValue === "") {
                    // an empty row in the table
                    continue;
                }
                $row[$identifierPersistentName] = MetadataSystem::toMetadataObject($identifierMetadataObject, $this)
                    ->setFromStoreValue($identifierValue);
                foreach ($this->getChildrenClass() as $childClass) {
                    if ($childClass === get_class($identifierMetadataObject)) {
                        continue;
                    }
                    $metadataChildObject = MetadataSystem::toMetadataObject($childClass, $this);
                    $name = $metadataChildObject::getPersistentName();
                    if ($identifierNameType === self::IDENTIFIER_NAME) {
                        $name = $metadataChildObject::getName();
                    }
                    $childValue = $value[$name];
                    if (is_array($childValue)) {
                        $childValue = $childValue[$index];
                        $index++;
                    }
                    $metadataChildObject->setFromStoreValue($childValue);
                    $row[$metadataChildObject::getPersistentName()] = $metadataChildObject;
                }
                $this->rows[] = $row;
            }

            return $this;
        }

        /**
         * List of row (frontmatter, dokuwiki, ...)
         */
        $childClassesByPersistentName = [];
        foreach ($this->getChildrenObject() as $childObject) {
            $childClassesByPersistentName[$childObject::getPersistentName()] = $childObject;
        }
        foreach ($value as $item) {

            /**
             * By default, the single value is the identifier
             *
             * (Note that from {@link MetadataFormDataStore}, tabular data
             * should be build before via the {@link self::buildFromReadStore()}
             * as tabular data is represented as a series of column)
             *
             */
            if (is_string($item)) {
                try {
                    $identifierMetadata = MetadataSystem::toMetadataObject($identifierMetadataObject, $this)->setFromStoreValue($item);
                } catch (ExceptionBadArgument $e) {
                    throw ExceptionRuntimeInternal::withMessageAndError("The $identifierMetadataObject should be known", $e);
                }
                try {
                    $identifierValue = $identifierMetadata->getValue(); // normalize if any
                } catch (ExceptionNotFound $e) {
                    throw ExceptionRuntimeInternal::withMessageAndError("The meta identifier ($identifierMetadata) should have a value", $e);
                }
                if (DataType::isObject($identifierValue)) {
                    /**
                     * An object cannot be the key of an array
                     */
                    $identifierValue = $identifierValue->__toString();
                }
                $this->rows[$identifierValue] = [$identifierPersistentName => $identifierMetadata];
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
                $childObject = MetadataSystem::toMetadataObject($childClass, $this);
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
    function remove($identifierValue): MetadataTabular
    {
        $this->buildCheck();
        if ($this->rows === null) {
            return $this;
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

    static public function getDataType(): string
    {
        return DataType::TABULAR_TYPE_VALUE;
    }

    /**
     * @throws ExceptionNotFound - if the identifier or it's value was not found
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
            throw new ExceptionNotFound("The identifier value was not found in the row");
        }
        $this->rows[$identifier] = $row;
        return $this;
    }

}
