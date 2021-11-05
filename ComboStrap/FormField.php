<?php


namespace ComboStrap;


class FormField
{


    /**
     * The tabs attribute and value
     */
    public const TAB_ATTRIBUTE = "tab";
    public const LABEL_ATTRIBUTE = "label";
    public const DATA_TYPE_ATTRIBUTE = "type";
    public const HYPERLINK_ATTRIBUTE = "link";
    public const MUTABLE_ATTRIBUTE = "mutable";
    /**
     * The JSON attribute for each parameter
     */
    public const VALUE_ATTRIBUTE = "value";
    public const DEFAULT_VALUE_ATTRIBUTE = "default";

    /**
     * The constant value
     */
    public const TEXT_TYPE_VALUE = "text";
    public const TABULAR_TYPE_VALUE = "tabular";
    public const PARAGRAPH_TYPE_VALUE = "paragraph";
    public const DATETIME_TYPE_VALUE = "datetime";
    public const DOMAIN_VALUES_ATTRIBUTE = "domain-values";
    public const WIDTH_ATTRIBUTE = "width";
    public const COLUMNS_ATTRIBUTE = "columns";


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
     * @var FormField[]
     */
    private $columns;
    /**
     * @var int
     */
    private $width;


    /**
     * FormField constructor.
     */
    public function __construct($name)
    {
        $this->name = $name;
        $this->label = ucfirst($name);
        $this->canonical = $name;
        $this->description = $name;
        $this->type = self::TEXT_TYPE_VALUE;
        $this->mutable = true;
    }

    public static function create(string $name): FormField
    {
        return new FormField($name);
    }

    public function toAssociativeArray(): array
    {
        /**
         * Mandatory attributes
         */
        $associative = [
            Analytics::NAME => $this->name,
            self::LABEL_ATTRIBUTE => $this->label,
            self::HYPERLINK_ATTRIBUTE => $this->getHyperLink(),
            self::DATA_TYPE_ATTRIBUTE => $this->type,
            self::TAB_ATTRIBUTE => $this->tab,
            self::MUTABLE_ATTRIBUTE => $this->mutable,
        ];
        switch (sizeof($this->values)) {
            case 0:
                break;
            case 1:
                $value = $this->values[0];
                if(!blank($value)) {
                    $associative[self::VALUE_ATTRIBUTE] = $this->values[0];
                }
                break;
            default:
                $associative[self::VALUE_ATTRIBUTE] = $this->values;
                break;
        }
        switch (sizeof($this->defaults)) {
            case 0:
                break;
            case 1:
                $value = $this->defaults[0];
                if(!blank($value)) {
                    $associative[self::DEFAULT_VALUE_ATTRIBUTE] = $value;
                }
                break;
            default:
                $associative[self::DEFAULT_VALUE_ATTRIBUTE] = $this->defaults;
                break;
        }

        if ($this->domainValues !== null) {
            $associative[self::DOMAIN_VALUES_ATTRIBUTE] = $this->domainValues;
        }

        if ($this->width !== null) {
            $associative[self::WIDTH_ATTRIBUTE] = $this->width;
        }
        if ($this->columns !== null) {
            foreach ($this->columns as $column) {
                $associative[self::COLUMNS_ATTRIBUTE][] = $column->toAssociativeArray();
            }
        }
        return $associative;
    }

    public
    function setMutable(bool $bool): FormField
    {
        $this->mutable = $bool;
        return $this;
    }

    public
    function setTab(string $tabName): FormField
    {
        $this->tab = $tabName;
        return $this;
    }

    public
    function setLabel(string $label): FormField
    {
        $this->label = $label;
        return $this;
    }

    public
    function getHyperLink(): string
    {
        return PluginUtility::getDocumentationHyperLink(
            $this->canonical,
            $this->label,
            false,
            $this->description
        );
    }

    public
    function setCanonical(string $canonical): FormField
    {
        $this->canonical = $canonical;
        return $this;
    }

    public
    function setDescription(string $string): FormField
    {
        $this->description = $string;
        return $this;
    }

    public
    function addValue($value, $default = null): FormField
    {
        $this->values[] = $value;
        $this->defaults[] = $default;
        return $this;
    }

    public
    function setType(string $type): FormField
    {
        $this->type = $type;
        return $this;
    }

    public
    function setDomainValues(array $domainValues): FormField
    {
        $this->domainValues = $domainValues;
        return $this;
    }

    public
    function addColumn(FormField $formField): FormField
    {
        $this->type = self::TABULAR_TYPE_VALUE;
        // A parent node is not mutable
        $this->mutable = false;
        $this->columns[] = $formField;
        return $this;
    }

    public
    function setWidth(int $int): FormField
    {
        $this->width = $int;
        return $this;
    }


}
