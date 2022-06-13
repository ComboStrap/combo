<?php


namespace ComboStrap;


class Locale extends MetadataText
{

    const PROPERTY_NAME = "locale";

    public static function createForPage(Page $page): Locale
    {
        return (new Locale())
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
        if (!($page instanceof Page)) {
            LogUtility::internalError("The locale is only implemented for page resources");
            return $this->getDefaultValue();
        }
        $lang = $page->getLangOrDefault();
        $country = $page->getRegionOrDefault();
        return $lang . "_" . strtoupper($country);


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
        return Site::getLocale("_");
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


}
