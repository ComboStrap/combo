<?php


namespace ComboStrap;


class PageH1 extends MetadataText
{


    public const H1_PARSED = "h1_parsed";
    public const PROPERTY_NAME = "h1";

    public static function createForPage($page): PageH1
    {
        return (new PageH1())
            ->setResource($page);
    }

    public function getTab(): string
    {
        return MetaManagerForm::TAB_PAGE_VALUE;
    }

    public function getDescription(): string
    {
        return "The heading 1 (or H1) is the first heading of your page. It may be used in template to make a difference with the title.";
    }

    public function getLabel(): string
    {
        return "H1 (Heading 1)";
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

    public function getDefaultValue(): string
    {
        $store = $this->getReadStore();
        if ($store instanceof MetadataDokuWikiStore) {
            $h1Parsed = $store->getCurrentFromName( self::H1_PARSED);
            if (!empty($h1Parsed)) {
                return $h1Parsed;
            }
            // dokuwiki
            $h1 = $store->getCurrentFromName( "title");
            if (!empty($h1)) {
                return $h1;
            }
        }
        $title = PageTitle::createForPage($this->getResource())
            ->getValue();
        if (!empty($title)) {
            return $title;
        }

        return ResourceName::createForResource($this->getResource())
            ->getValueOrDefault();

    }

    public function getCanonical(): string
    {
        return $this->getName();
    }


}
