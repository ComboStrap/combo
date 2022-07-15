<?php


namespace ComboStrap;


class Locale extends MetadataText
{

    const PROPERTY_NAME = "locale";

    private string $separator = "_";

    public static function createForPage(Markup $page, string $separator = "_"): Locale
    {
        return (new Locale())
            ->setSeparator($separator)
            ->setResource($page);
    }

    public function getTab(): string
    {
        return MetaManagerForm::TAB_LANGUAGE_VALUE;
    }

    public function getDescription(): string
    {
        return "The locale define the language and the formatting of numbers and time for the page. It's generated from the language and region metadata.";
    }

    public function getLabel(): string
    {
        return "Locale";
    }

    /**
     * @return string
     */
    public function getValue(): string
    {

        $page = $this->getResource();
        if (!($page instanceof Markup)) {
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

    public function getPersistenceType(): string
    {
        return Metadata::DERIVED_METADATA;
    }

    public function getMutable(): bool
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

    public function getCanonical(): string
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


}
