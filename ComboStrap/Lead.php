<?php


namespace ComboStrap;


use ComboStrap\Meta\Api\MetadataText;
use ComboStrap\Meta\Field\PageH1;
use ComboStrap\Meta\Store\MetadataDokuWikiStore;

class Lead extends MetadataText
{

    public const PROPERTY_NAME = 'lead';


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
        return "The lead is a tagline for a page";
    }

    public function getLabel(): string
    {
        return "Lead";
    }

    static public function getName(): string
    {
        return self::PROPERTY_NAME;
    }

    public function getPersistenceType(): string
    {
        return MetadataDokuWikiStore::PERSISTENT_DOKUWIKI_KEY;
    }

    public function isMutable(): bool
    {
        return true;
    }

    public function getCanonical(): string
    {
        return self::getName();
    }

    public function getDefaultValue()
    {
        if ($this->getResource()->isRootHomePage()) {
            return Site::getTagLine();
        }
        throw new ExceptionNotFound();
    }


    public function isOnForm(): bool
    {
        return true;
    }
}
