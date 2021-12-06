<?php


namespace ComboStrap;


class Region extends MetadataText
{


    public const REGION_META_PROPERTY = "region";

    public static function createFroPage(Page $page)
    {
        return (new Region())
            ->setResource($page);
    }

    public function getTab()
    {
        return \action_plugin_combo_metamanager::TAB_LANGUAGE_VALUE;
    }

    /**
     * @throws ExceptionCombo
     */
    public function setFromStoreValue($value)
    {

        $this->validityCheck($value);
        return parent::setFromStoreValue($value);

    }

    public function setValue(?string $value): MetadataText
    {
        $this->validityCheck($value);
        return parent::setValue($value);
    }


    public function getDescription(): string
    {
        return "The region of the language";
    }

    public function getLabel(): string
    {
        return "Region";
    }

    public function getName(): string
    {
        return self::REGION_META_PROPERTY;
    }

    public function getPersistenceType(): string
    {
        return Metadata::DERIVED_METADATA;
    }

    public function getMutable(): bool
    {
        return true;
    }

    public function getDefaultValue()
    {
        return Site::getLanguageRegion();
    }

    /**
     * @throws ExceptionCombo
     */
    private function validityCheck($value)
    {
        if ($value === "" || $value === null) {
            return;
        }
        if (!StringUtility::match($value, "^[a-zA-Z]{2}$")) {
            throw new ExceptionCombo("The region value ($value) for the page ({$this->getResource()}) does not have two letters (ISO 3166 alpha-2 region code)", $this->getCanonical());
        }
    }
}
