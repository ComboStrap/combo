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

    static public function getTab(): string
    {
        return MetaManagerForm::TAB_PAGE_VALUE;
    }

    static public function getDescription(): string
    {
        return "A label is a short description of a couple of words used in a listing (table row)";
    }

    static public function getLabel(): string
    {
        return "Label";
    }

    static public function getName(): string
    {
        return self::PROPERTY_NAME;
    }

    static public function getPersistenceType(): string
    {
        return MetadataDokuWikiStore::PERSISTENT_DOKUWIKI_KEY;
    }

    static public function isMutable(): bool
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


    static public function isOnForm(): bool
    {
        return true;
    }
}
