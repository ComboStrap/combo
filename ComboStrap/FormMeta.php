<?php


namespace ComboStrap;

use http\Exception\RuntimeException;

/**
 * Class FormMeta
 * @package ComboStrap
 *
 * Represents form metadata sends via an ajax request
 *
 * It makes sure that the data send
 * is coherent
 */
class FormMeta
{

    const FORM_TYPES = [self::FORM_NAV_TABS_TYPE, self::FORM_LIST_GROUP_TYPE];
    const FORM_NAV_TABS_TYPE = "nav-tabs";
    const FORM_LIST_GROUP_TYPE = "list-group";
    const FIELDS_ATTRIBUTE = "fields";

    private $name;

    /**
     * @var FormMetaField[]
     */
    private $fields;
    /**
     * @var FormMetaTab[]|string[]
     */
    private $tabs;
    /**
     * @var string
     */
    private $type;

    /**
     * @throws ExceptionComboRuntime
     */
    public function __construct($name)
    {
        Html::validNameGuard($name);
        $this->name = $name;
    }


    public static function create($name): FormMeta
    {
        return new FormMeta($name);
    }

    public function addField(FormMetaField $formField): FormMeta
    {
        $this->fields[] = $formField;
        $tab = $formField->getTab();
        if (!empty($tab) && !isset($this->tabs[$tab])) {
            $this->tabs[$tab] = $tab;
        }
        return $this;
    }

    public function addTab(FormMetaTab $tab): FormMeta
    {
        $this->tabs[$tab->getName()] = $tab;
        return $this;
    }

    public function toAssociativeArray(): array
    {

        $fieldsArray = [];
        foreach ($this->fields as $element) {
            /**
             * The order is kept even if we add a key
             */
            $fieldsArray[$element->getName()] = $element->toAssociativeArray();
        }
        $tabs = [];
        foreach ($this->tabs as $element) {
            if (!($element instanceof FormMetaTab)) {
                $tab = FormMetaTab::create($element);
            } else {
                $tab = $element;
            }
            /**
             * The order is kept even if we add a key
             */
            $tabs[$tab->getName()] = $tab->toAssociativeArray();
        }
        return [
            self::FIELDS_ATTRIBUTE => $fieldsArray,
            "tabs" => $tabs
        ];
    }

    /**
     * ie nav-tabs versus list-group:
     * https://getbootstrap.com/docs/5.0/components/list-group/#javascript-behavior
     *
     * @param string $type
     * @return FormMeta
     */
    public function setType(string $type): FormMeta
    {
        if (!in_array($type, self::FORM_TYPES)) {
            LogUtility::msg("The form type ($type) is unknown");
            return $this;
        }
        $this->type = $type;
        return $this;
    }

    /**
     * The data as if it was send by a HTML form to the
     * post endpoint
     *
     * It transforms the fields to an associative array
     * that should be send with a post request
     *
     * ie Equivalent to the javascript api formdata output
     * (Used in test to simulate a post)
     */
    public function toFormData(): array
    {
        $data = [];
        $this->toFormDataRecurse($data, $this->fields);
        return $data;
    }

    /**
     * @param $data
     * @param FormMetaField[] $fields
     */
    private function toFormDataRecurse(&$data, array $fields)
    {

        foreach ($fields as $field) {

            if ($field->isMutable()) {

                $value = $field->getValue();
                if ($field->getType() === DataType::BOOLEAN_TYPE_VALUE) {
                    if ($value === $field->getDefaultValue()) {
                        continue;
                    }
                }
                if ($value === null) {
                    // A form would return empty string
                    $value = "";
                }
                if (is_array($value)) {
                    $temp = [];
                    foreach ($value as $subValue) {
                        if ($subValue === null) {
                            $temp[] = "";
                        } else {
                            if (is_array($subValue) && $field->isMultiple()) {
                                $temp[] = implode(",", $subValue);
                            } else {
                                $temp[] = $subValue;
                            }
                        }
                    }
                    $value = $temp;
                }
                $data[$field->getName()] = $value;

            }
            $formMetaChildren = $field->getChildren();
            if ($formMetaChildren != null) {
                $this->toFormDataRecurse($data, $formMetaChildren);
            }
        }

    }

    /**
     * Almost because a form does not allow hierarchical data
     * We send an error in this case
     * @param Metadata $metadata
     * @return FormMeta
     */
    public function addFormFieldFromMetadata(Metadata $metadata): FormMeta
    {
        $field = FormMetaField::create($metadata->getName());

        $this->setCommonDataToFieldFromMetadata($field, $metadata);

        $childrenMetadata = $metadata->getChildren();

        if ($metadata->getParent() === null) {
            /**
             * Only the top field have a tab value
             */
            $field->setTab($metadata->getTab());
        }


        /**
         * No children
         */
        if ($childrenMetadata === null) {

            $this->setLeafDataToFieldFromMetadata($field, $metadata);

            // Value
            $value = $metadata->toStoreValue();
            $defaultValue = $metadata->toStoreDefaultValue();
            $field->addValue($value, $defaultValue);

        } else {
            if ($metadata instanceof MetadataTabular) {

                $childFields = [];
                foreach ($metadata->getChildren() as $childMetadataClass) {

                    $childMetadata = Metadata::toChildMetadataObject($childMetadataClass, $metadata);
                    $childField = FormMetaField::create($childMetadata);
                    $this->setCommonDataToFieldFromMetadata($childField, $childMetadata);
                    $this->setLeafDataToFieldFromMetadata($childField, $childMetadata);
                    $field->addColumn($childField);
                    $childFields[$childMetadata::getPersistentName()] = $childField;

                }
                $rows = $metadata->getValue();
                foreach ($rows as $row) {
                    foreach ($row as $colName => $colValue) {
                        $childField = $childFields[$colName];
                        $childField->addValue($colValue->toStoreValue(), $colValue->toStoreDefaultValue());
                    }
                }
                foreach ($childFields as $childField){
                    $childField->addValue(null, null);
                }


            } else {

                LogUtility::msg("Hierarchical data is not supported in a form. Metadata ($metadata) has children and is not tabular");
            }
        }


        $this->addField($field);
        return $this;
    }

    /**
     * Common metadata to all field from a leaf to a tabular
     * @param FormMetaField $field
     * @param Metadata $metadata
     */
    private
    function setCommonDataToFieldFromMetadata(FormMetaField $field, Metadata $metadata)
    {
        $field->setType($metadata->getDataType())
            ->setCanonical($metadata->getCanonical())
            ->setLabel($metadata->getLabel())
            ->setDescription($metadata->getDescription());
    }

    /**
     * @param FormMetaField $field
     * @param Metadata $metadata
     * Add the field metadata that are only available for leaf metadata
     */
    private
    function setLeafDataToFieldFromMetadata(FormMetaField $field, Metadata $metadata)
    {
        $field->setMutable($metadata->getMutable());

        $formControlWidth = $metadata->getFormControlWidth();
        if ($formControlWidth !== null) {
            $field->setWidth($formControlWidth);
        }
        $possibleValues = $metadata->getPossibleValues();
        if ($possibleValues !== null) {
            $field->setDomainValues($possibleValues);
            if ($metadata instanceof MetadataArray) {
                $field->setMultiple(true);
            }
        }

    }

}
