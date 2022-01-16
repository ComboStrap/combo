<?php


namespace ComboStrap;


/**
 * Class FormField
 * @package ComboStrap
 *
 * A class that represents a tree of form field.
 *
 * Each field can be a scalar, a list or
 * tabular by adding child fields.
 *
 *
 */
class FormMetaField
{


    /**
     * The JSON attribute
     */
    public const TAB_ATTRIBUTE = "tab";
    public const LABEL_ATTRIBUTE = "label";
    public const URL_ATTRIBUTE = "url";
    public const MUTABLE_ATTRIBUTE = "mutable";
    /**
     * A value may be a scalar or an array
     */
    public const VALUE_ATTRIBUTE = "value";
    public const DEFAULT_VALUE_ATTRIBUTE = "default";

    public const DOMAIN_VALUES_ATTRIBUTE = "domain-values";
    public const WIDTH_ATTRIBUTE = "width";
    public const CHILDREN_ATTRIBUTE = "children";
    const DESCRIPTION_ATTRIBUTE = "description";
    const NAME_ATTRIBUTE = "name";
    const MULTIPLE_ATTRIBUTE = "multiple";


    private $name;
    /**
     * @var bool
     */
    private $mutable;
    /**
     * @var string
     */
    private $tab;
    /**
     * @var string
     */
    private $label;
    private $description;
    /**
     * If canonical is set, an url is also send
     */
    private $canonical;
    private $values = [];
    private $defaults = [];
    /**
     * @var string
     */
    private $type;
    /**
     * @var array
     */
    private $domainValues;
    /**
     * @var FormMetaField[]
     */
    private $children;
    /**
     * @var int
     */
    private $width;
    /**
     * Multiple value can be chosen
     * @var bool
     */
    private $multiple = false;


    /**
     * FormField constructor.
     */
    public function __construct($name)
    {
        $this->name = $name;
        $this->label = ucfirst($name);
        $this->description = $name;
        $this->type = DataType::TEXT_TYPE_VALUE;
        $this->mutable = true;
    }

    public static function create(string $name): FormMetaField
    {
        return new FormMetaField($name);
    }

    /**
     * Almost because a form does not allow hierarchical data
     * We send an error in this case
     * @param Metadata $metadata
     * @return FormMetaField
     * @throws ExceptionCombo
     */
    public static function createFromMetadata(Metadata $metadata): FormMetaField
    {
        $field = FormMetaField::create($metadata->getName());

        self::setCommonDataToFieldFromMetadata($field, $metadata);

        $childrenMetadata = $metadata->getChildrenClass();

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

            static::setLeafDataToFieldFromMetadata($field, $metadata);

            // Value
            $value = $metadata->toStoreValue();
            $defaultValue = $metadata->toStoreDefaultValue();
            $field->addValue($value, $defaultValue);

        } else {

            if ($metadata instanceof MetadataTabular) {

                $childFields = [];
                foreach ($metadata->getChildrenClass() as $childMetadataClass) {

                    $childMetadata = Metadata::toMetadataObject($childMetadataClass, $metadata);
                    $childField = FormMetaField::create($childMetadata);
                    static::setCommonDataToFieldFromMetadata($childField, $childMetadata);
                    static::setLeafDataToFieldFromMetadata($childField, $childMetadata);
                    $field->addColumn($childField);
                    $childFields[$childMetadata::getPersistentName()] = $childField;
                }
                $rows = $metadata->getValue();
                if ($rows !== null) {

                    $defaultRow = null;
                    $defaultRows = $metadata->getDefaultValue();
                    if ($defaultRows !== null) {
                        $defaultRow = $defaultRows[0];
                    }

                    foreach ($rows as $row) {
                        foreach ($childFields as $childName => $childField) {
                            $colValue = $row[$childName];
                            if ($colValue === null) {
                                if ($defaultRow === null) {
                                    continue;
                                }
                                $colValue = $defaultRow[$childName];
                                if ($colValue === null) {
                                    continue;
                                }
                            }
                            $childField->addValue($colValue->toStoreValue(), $colValue->toStoreDefaultValue());
                        }

                    }

                    // Add an extra empty row to allow adding an image
                    if ($defaultRow !== null) {
                        foreach ($defaultRow as $colName => $colValue) {
                            $defaultColValue = null;
                            if ($colValue !== null) {
                                $defaultColValue = $colValue->toStoreDefaultValue();
                            }
                            $childField = $childFields[$colName];
                            $childField->addValue(null, $defaultColValue);
                        }
                    }

                } else {

                    // Show the default rows
                    $rows = $metadata->getDefaultValue();
                    if ($rows !== null) {
                        foreach ($rows as $row) {
                            foreach ($row as $colName => $colValue) {
                                if ($colValue === null) {
                                    continue;
                                }
                                $childField = $childFields[$colName];
                                $childField->addValue(null, $colValue->toStoreValue());
                            }
                        }
                    }

                }


            } else {

                LogUtility::msg("Hierarchical data is not supported in a form. Metadata ($metadata) has children and is not tabular");
            }
        }
        return $field;

    }


    public
    function toAssociativeArray(): array
    {
        /**
         * Mandatory attributes
         */
        $associative = [
            ResourceName::PROPERTY_NAME => $this->name,
            self::LABEL_ATTRIBUTE => $this->label,
            DataType::PROPERTY_NAME => $this->type
        ];
        if ($this->getUrl() != null) {
            $associative[self::URL_ATTRIBUTE] = $this->getUrl();
        }
        if ($this->description != null) {
            $associative[self::DESCRIPTION_ATTRIBUTE] = $this->description;
        }
        /**
         * For child form field (ie column), there is no tab
         */
        if ($this->tab != null) {
            $associative[self::TAB_ATTRIBUTE] = $this->tab;
        }


        if ($this->width !== null) {
            $associative[self::WIDTH_ATTRIBUTE] = $this->width;
        }
        if ($this->children !== null) {
            foreach ($this->children as $column) {
                $associative[self::CHILDREN_ATTRIBUTE][] = $column->toAssociativeArray();
            }
        } else {

            /**
             * Only valid for leaf field
             */
            if ($this->getValue() !== null) {
                $associative[self::VALUE_ATTRIBUTE] = $this->getValue();
            }

            if ($this->getDefaultValue() !== null) {
                $associative[self::DEFAULT_VALUE_ATTRIBUTE] = $this->getDefaultValue();
            }

            if ($this->domainValues !== null) {
                $associative[self::DOMAIN_VALUES_ATTRIBUTE] = $this->domainValues;
                if ($this->multiple) {
                    $associative[self::MULTIPLE_ATTRIBUTE] = $this->multiple;
                }
            }

            $associative[self::MUTABLE_ATTRIBUTE] = $this->mutable;


        }


        return $associative;
    }

    public
    function setMutable(bool $bool): FormMetaField
    {
        $this->mutable = $bool;
        return $this;
    }


    public
    function setTab(string $tabName): FormMetaField
    {
        Html::validNameGuard($tabName);
        $this->tab = $tabName;
        return $this;
    }

    public
    function setLabel(string $label): FormMetaField
    {
        $this->label = $label;
        return $this;
    }

    public
    function getUrl(): ?string
    {
        if ($this->canonical == null) {
            return null;
        }
        $url = PluginUtility::$URL_APEX;
        $url .= "/" . str_replace(":", "/", $this->canonical);
        return $url;
    }

    public
    function setCanonical(string $canonical): FormMetaField
    {
        $this->canonical = $canonical;
        return $this;
    }

    public
    function setDescription(string $string): FormMetaField
    {
        $this->description = $string;
        return $this;
    }

    /**
     * @param $value
     * @param null $defaultValuePlaceholderOrReturned - the value set as placeholder or return value for a checked checkbox
     * @return $this
     */
    public
    function addValue($value, $defaultValuePlaceholderOrReturned = null): FormMetaField
    {
        $this->values[] = $value;
        $this->defaults[] = $defaultValuePlaceholderOrReturned;
        return $this;
    }

    public
    function setType(string $type): FormMetaField
    {
        if (!in_array($type, DataType::TYPES)) {
            LogUtility::msg("The type ($type) is not a known field type");
            return $this;
        }
        $this->type = $type;
        return $this;
    }

    public
    function setDomainValues(array $domainValues): FormMetaField
    {
        $this->domainValues = $domainValues;
        return $this;
    }

    public
    function addColumn(FormMetaField $formField): FormMetaField
    {
        $this->type = DataType::TABULAR_TYPE_VALUE;
        // A parent node is not mutable
        $this->mutable = false;
        $this->children[] = $formField;
        return $this;
    }

    public
    function setWidth(int $int): FormMetaField
    {
        $this->width = $int;
        return $this;
    }

    public
    function getTab(): string
    {
        return $this->tab;
    }

    public
    function getName()
    {
        return $this->name;
    }

    public
    function getValue()
    {
        switch (sizeof($this->values)) {
            case 0:
                return null;
            case 1:
                $value = $this->values[0];
                if ($value !== null) {
                    return $this->values[0];
                }
                return null;
            default:
                return $this->values;
        }
    }

    public
    function isMutable(): bool
    {
        return $this->mutable;
    }

    public
    function getChildren(): ?array
    {
        return $this->children;
    }

    public
    function getType(): string
    {
        return $this->type;
    }

    public
    function getDefaultValue()
    {
        switch (sizeof($this->defaults)) {
            case 0:
                return null;
            case 1:
                $value = $this->defaults[0];
                if ($value !== null) {
                    return $value;
                }
                return null;
            default:
                return $this->defaults;
        }
    }

    public
    function setMultiple(bool $bool): FormMetaField
    {
        $this->multiple = $bool;
        return $this;
    }

    public
    function isMultiple(): bool
    {
        return $this->multiple;
    }

    /**
     * If this is a scalar value, you can set/overwrite the value
     * with this function
     * @param $value
     * @param null $default
     * @return $this
     */
    public
    function setValue($value, $default = null): FormMetaField
    {
        $this->values = [];
        $this->defaults = [];
        return $this->addValue($value, $default);

    }

    /**
     * Common metadata to all field from a leaf to a tabular
     * @param FormMetaField $field
     * @param Metadata $metadata
     */
    private
    static
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
    static
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
            if ($metadata instanceof MetadataMultiple) {
                $field->setMultiple(true);
            }
        }

    }

}
