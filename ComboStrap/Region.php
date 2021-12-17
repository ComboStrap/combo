<?php


namespace ComboStrap;


class Region extends MetadataText
{


    public const PROPERTY_NAME = "region";
    public const OLD_REGION_PROPERTY = "country";
    public const CONF_SITE_LANGUAGE_REGION = "siteLanguageRegion";

    public static function createForPage(Page $page)
    {
        return (new Region())
            ->setResource($page);
    }

    public function getTab(): ?string
    {
        return MetaManagerForm::TAB_LANGUAGE_VALUE;
    }

    /**
     * @throws ExceptionCombo
     */
    public function setFromStoreValue($value): Metadata
    {

        $this->validityCheck($value);
        return parent::setFromStoreValue($value);

    }

    /**
     * @param string|null $value
     * @return Metadata
     * @throws ExceptionCombo
     */
    public function setValue($value): Metadata
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

    static public function getName(): string
    {
        return self::PROPERTY_NAME;
    }

    public function getPersistenceType(): string
    {
        return Metadata::PERSISTENT_METADATA;
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

    public function getCanonical(): string
    {
        return "region";
    }


}
