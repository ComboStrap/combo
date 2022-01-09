<?php


namespace ComboStrap;


class Locale extends MetadataText
{

    const PROPERTY_NAME = "locale";

    public static function createForPage(Page $page)
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

    public function getValue(): ?string
    {

        $resourceCombo = $this->getResource();
        if (!($resourceCombo instanceof Page)) {
            return null;
        }
        $lang = $resourceCombo->getLangOrDefault();
        if (!empty($lang)) {

            $country = $resourceCombo->getRegionOrDefault();
            if (empty($country)) {
                $country = $lang;
            }
            return $lang . "_" . strtoupper($country);
        }
        return null;
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

    public function getDefaultValue(): ?string
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


}
