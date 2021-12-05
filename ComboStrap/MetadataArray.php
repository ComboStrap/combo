<?php


namespace ComboStrap;

/**
 * Class MetadataArray
 * @package ComboStrap
 * An array metadata
 */
abstract class MetadataArray extends Metadata
{

    /**
     * @var array|null
     */
    private $array;
    /**
     * @var bool
     */
    private $wasBuild;

    public function buildFromStore(): MetadataArray
    {
        try {
            $this->setValue($this->getStoreValue());
        } catch (ExceptionCombo $e) {
            LogUtility::msg("Error while building the value:", $e->getCanonical());
        }
        return $this;
    }

    /**
     * @throws ExceptionCombo
     */
    public function setValue(?array $array): MetadataArray
    {
        $this->array = $array;
        $this->getStore()->set($this);
        return $this;
    }

    public function getDataType(): string
    {
        return DataType::TABULAR_TYPE_VALUE;
    }

    public function getValue(): array
    {
        $this->buildCheck();
        return $this->array;
    }

    private function buildCheck()
    {
        if(
            $this->array===null
            && $this->wasBuild===false
        ){
            $this->wasBuild = true;
            $this->buildFromStore();
        }
    }
}
