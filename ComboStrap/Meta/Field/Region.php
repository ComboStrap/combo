<?php


namespace ComboStrap\Meta\Field;


use ComboStrap\ExceptionCompile;
use ComboStrap\MarkupPath;
use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Api\MetadataText;
use ComboStrap\MetaManagerForm;
use ComboStrap\Site;
use ComboStrap\StringUtility;

class Region extends MetadataText
{


    public const PROPERTY_NAME = "region";
    public const OLD_REGION_PROPERTY = "country";
    public const CONF_SITE_LANGUAGE_REGION = "siteLanguageRegion";

    public static function createForPage(MarkupPath $page)
    {
        return (new Region())
            ->setResource($page);
    }

    static public function getTab(): ?string
    {
        return MetaManagerForm::TAB_LANGUAGE_VALUE;
    }

    /**
     * @throws ExceptionCompile
     */
    public function setFromStoreValue($value): Metadata
    {

        $this->validityCheck($value);
        return parent::setFromStoreValue($value);

    }

    /**
     * @param string|null $value
     * @return Metadata
     * @throws ExceptionCompile
     */
    public function setValue($value): Metadata
    {
        $this->validityCheck($value);
        return parent::setValue($value);
    }


    static public function getDescription(): string
    {
        return "The region of the language";
    }

    static public function getLabel(): string
    {
        return "Region";
    }

    static public function getName(): string
    {
        return self::PROPERTY_NAME;
    }

    static public function getPersistenceType(): string
    {
        return Metadata::PERSISTENT_METADATA;
    }

    static public function isMutable(): bool
    {
        return true;
    }

    public function getDefaultValue()
    {
        return Site::getLanguageRegion();
    }

    /**
     * @throws ExceptionCompile
     */
    private function validityCheck($value)
    {
        if ($value === "" || $value === null) {
            return;
        }
        if (!StringUtility::match($value, "^[a-zA-Z]{2}$")) {
            throw new ExceptionCompile("The region value ($value) for the page ({$this->getResource()}) does not have two letters (ISO 3166 alpha-2 region code)", $this->getCanonical());
        }
    }

    static public function getCanonical(): string
    {
        return "region";
    }


    static public function isOnForm(): bool
    {
        return true;
    }

}
