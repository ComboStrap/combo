<?php


namespace ComboStrap;


class PageH1 extends MetadataText
{


    public const H1_PARSED = "h1_parsed";
    public const PROPERTY_NAME = "h1";

    public static function createForPage($page): ResourceName
    {
        return (new ResourceName())
            ->setResource($page);
    }

    public function getTab(): string
    {
        return \action_plugin_combo_metamanager::TAB_PAGE_VALUE;
    }

    public function getDescription(): string
    {
        return "The heading 1 (or H1) is the first heading of your page. It may be used in template to make a difference with the title.";
    }

    public function getLabel(): string
    {
        return "H1 (Heading 1)";
    }

    public function getName(): string
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
        $store = $this->getStore();
        if ($store instanceof MetadataDokuWikiStore) {
            $h1Parsed = $store->getFromName($this->getResource(), self::H1_PARSED);
            if (!empty($h1Parsed)) {
                return $h1Parsed;
            }
        }
        $title = PageTitle::createForPage($this->getResource())
            ->getValueOrDefault();
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
