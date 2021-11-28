<?php


namespace ComboStrap;


/**
 * Class MetadataScalar
 * @package ComboStrap
 * A metadata that holds only one value
 */
abstract class MetadataScalar extends Metadata
{

    public function toFormField(): FormMetaField
    {

        $formField = parent::toFormField();
        return $formField->addValue($this->toPersistentValue(), $this->toPersistentDefaultValue());

    }

}
