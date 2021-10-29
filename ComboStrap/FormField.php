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
    public const LIST_TYPE_VALUE = "list";
    public const DOMAIN_VALUES_ATTRIBUTE = "domain-values";

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
    private $value;
    private $default;
    /**
     * @var string
     */
    private $type;
    /**
     * @var array
     */
    private $domainValues;


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
        return [
            Analytics::NAME => $this->name,
            self::LABEL_ATTRIBUTE => $this->label,
            self::HYPERLINK_ATTRIBUTE => $this->getHyperLink(),
            self::MUTABLE_ATTRIBUTE => $this->mutable,
            self::TAB_ATTRIBUTE => $this->tab,
            self::VALUE_ATTRIBUTE => $this->value,
            self::DEFAULT_VALUE_ATTRIBUTE => $this->default,
            self::DATA_TYPE_ATTRIBUTE => $this->type,
            self::DOMAIN_VALUES_ATTRIBUTE => $this->domainValues
        ];
    }

    public function setMutable(bool $bool): FormField
    {
        $this->mutable = $bool;
        return $this;
    }

    public function setTab(string $tabName): FormField
    {
        $this->tab = $tabName;
        return $this;
    }

    public function setLabel(string $label): FormField
    {
        $this->label = $label;
        return $this;
    }

    public function getHyperLink(): string
    {
        return PluginUtility::getDocumentationHyperLink(
            $this->canonical,
            $this->label,
            false,
            $this->description
        );
    }

    public function setCanonical(string $canonical): FormField
    {
        $this->canonical = $canonical;
        return $this;
    }

    public function setDescription(string $string): FormField
    {
        $this->description = $string;
        return $this;
    }

    public function addValue($value, $default): FormField
    {
        $this->value = $value;
        $this->default = $default;
        return $this;
    }

    public function setType(string $type): FormField
    {
        $this->type = $type;
        return $this;
    }

    public function setDomainValues(array $domainValues)
    {
        $this->domainValues = $domainValues;
        return $this;
    }


}
