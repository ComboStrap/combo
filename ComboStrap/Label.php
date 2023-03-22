<?php


namespace ComboStrap;


use ComboStrap\Meta\Api\MetadataText;
use ComboStrap\Meta\Field\PageH1;
use ComboStrap\Meta\Store\MetadataDokuWikiStore;

class Label extends MetadataText
{

    public const PROPERTY_NAME = 'label';


    public static function createForMarkup($page): Label
    {
        return (new Label())
            ->setResource($page);
    }

    public function getTab(): string
    {
        return MetaManagerForm::TAB_PAGE_VALUE;
    }

    public function getDescription(): string
    {
        return "A label is a short description of a couple of words used in a listing (table row)";
    }

    public function getLabel(): string
    {
        return "Label";
    }

    static public function getName(): string
    {
        return self::PROPERTY_NAME;
    }

    public function getPersistenceType(): string
    {
        return MetadataDokuWikiStore::PERSISTENT_METADATA;
    }

    public function getMutable(): bool
    {
        return true;
    }

    /**
     * @return string
     */
    public function getDefaultValue(): string
    {

        return PageTitle::createForMarkup($this->getResource())->getValueOrDefault();

    }

    /**
     * @return string
     */
    public function getValueOrDefault(): string
    {
        try {
            return $this->getValue();
        } catch (ExceptionNotFound $e) {
            return $this->getDefaultValue();
        }
    }




}
