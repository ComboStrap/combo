<?php


namespace ComboStrap\Meta\Field;


use ComboStrap\ExceptionNotFound;
use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Store\MetadataDokuWikiStore;
use ComboStrap\Meta\Api\MetadataText;
use ComboStrap\MetaManagerForm;
use ComboStrap\PageTitle;
use ComboStrap\ResourceName;

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

    /**
     * @return string
     */
    public function getDefaultValue(): string
    {
        $store = $this->getReadStore();
        if ($store instanceof MetadataDokuWikiStore) {
            $h1Parsed = $store->getFromPersistentName(self::H1_PARSED);
            if (!empty($h1Parsed)) {
                return $h1Parsed;
            }
            // dokuwiki
            $h1 = $store->getCurrentFromName("title");
            if (!empty($h1)) {
                return $h1;
            }
        }
        try {
            return PageTitle::createForMarkup($this->getResource())
                ->getValue();
        } catch (ExceptionNotFound $e) {
            // ok
        }

        return ResourceName::createForResource($this->getResource())
            ->getValueOrDefault();

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


    public function getCanonical(): string
    {
        return $this->getName();
    }

    public function persistDefaultValue(string $defaultValue): PageH1
    {
        $store = $this->getWriteStore();
        if ($store instanceof MetadataDokuWikiStore) {
            $store
                ->setFromPersistentName(self::H1_PARSED, $defaultValue);
        }
        return $this;

    }


}
