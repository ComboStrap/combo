<?php


namespace ComboStrap;


use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Api\MetadataText;

class Locale extends MetadataText
{

    const PROPERTY_NAME = "locale";

    private string $separator = "_";

    public static function createForPage(MarkupPath $page, string $separator = "_"): Locale
    {
        return (new Locale())
            ->setSeparator($separator)
            ->setResource($page);
    }

    static public function getTab(): string
    {
        return MetaManagerForm::TAB_LANGUAGE_VALUE;
    }

    static public function getDescription(): string
    {
        return "The locale define the language and the formatting of numbers and time for the page. It's generated from the language and region metadata.";
    }

    static public function getLabel(): string
    {
        return "Locale";
    }

    /**
     * @return string
     */
    public function getValue(): string
    {

        $page = $this->getResource();
        if (!($page instanceof MarkupPath)) {
            LogUtility::internalError("The locale is only implemented for page resources");
            return $this->getDefaultValue();
        }
        $lang = $page->getLangOrDefault();
        $country = $page->getRegionOrDefault();

        return $lang . $this->separator . strtoupper($country);


    }


    public static function getName(): string
    {
        return self::PROPERTY_NAME;
    }

    static public function getPersistenceType(): string
    {
        return Metadata::DERIVED_METADATA;
    }

    static public function isMutable(): bool
    {
        return false;
    }

    /**
     * @return string
     */
    public function getDefaultValue(): string
    {
        /**
         * The value of {@link locale_get_default()} is with an underscore
         * We follow this lead
         */
        return Site::getLocale($this->separator);
    }

    static public function getCanonical(): string
    {
        return "locale";
    }

    /**
     * @return string
     */
    public function getValueOrDefault(): string
    {
        return $this->getValue();
    }

    public function setSeparator(string $separator): Locale
    {
        $this->separator = $separator;
        return $this;
    }


    static public function isOnForm(): bool
    {
        return true;
    }
}
