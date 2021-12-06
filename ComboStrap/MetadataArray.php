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
     * @throws ExceptionCombo
     */
    public function setValue(?array $array): MetadataArray
    {
        $this->array = $array;
        $this->sendToStore();
        return $this;
    }

    public function getDataType(): string
    {
        return DataType::TABULAR_TYPE_VALUE;
    }

    public function getValue(): ?array
    {
        $this->buildCheck();
        return $this->array;
    }

    abstract function getDefaultValues();

    public function getValueOrDefaults(): array
    {
        $value = $this->getValue();
        if($value !==null){
            return $value;
        }
        return $this->getDefaultValues();
    }


    public function valueIsNotNull(): bool
    {
        return $this->array!==null;
    }
}
