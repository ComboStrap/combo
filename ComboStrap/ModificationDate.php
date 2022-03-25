<?php


use ComboStrap\ExceptionCompile;
use ComboStrap\ExceptionNotFound;
use ComboStrap\FileSystems;
use ComboStrap\MetaManagerForm;
use ComboStrap\LogUtility;
use ComboStrap\Metadata;
use ComboStrap\MetadataDateTime;
use ComboStrap\MetadataDokuWikiStore;
use ComboStrap\Page;
use ComboStrap\PageCreationDate;

class ModificationDate extends MetadataDateTime
{

    public const PROPERTY_NAME = 'date_modified';

    public static function createForPage(Page $page)
    {
        return (new ModificationDate())
            ->setResource($page);
    }

    public function getTab(): string
    {
        return MetaManagerForm::TAB_PAGE_VALUE;
    }

    public function buildFromReadStore(): MetadataDateTime
    {
        $store = $this->getReadStore();
        if (!($store instanceof MetadataDokuWikiStore)) {
            return parent::buildFromReadStore();
        }

        try {
            $modificationTime = FileSystems::getModifiedTime($this->getResource()->getPath());
            $this->setValue($modificationTime);
            return $this;
        } catch (ExceptionNotFound $e) {

            /**
             * Dokuwiki
             * Why do they store the date of the file while it's in the file system ?
             */
            $createdMeta = $store->getCurrentFromName('date')['modified'];
            if (empty($createdMeta)) {
                $createdMeta = $store->getFromPersistentName('date')['modified'];
                if (empty($createdMeta)) {
                    return $this;
                }
            }
            // the data in dokuwiki is saved as timestamp
            $datetime = new DateTime();
            if (!is_int($createdMeta)) {
                LogUtility::msg("The modification time in the dokuwiki meta is not an integer");
                return $this;
            }
            $datetime->setTimestamp($createdMeta);
            $this->setValue($datetime);
            return $this;

        }


    }


    public function getDescription(): string
    {
        return "The last modification date of the page"; // resource
    }

    public function getLabel(): string
    {
        return "Modification Date";
    }

    static public function getName(): string
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

    public function getDefaultValue(): ?DateTime
    {

        try {
            return FileSystems::getModifiedTime($this->getResource()->getPath());
        } catch (ExceptionNotFound $e) {
            return PageCreationDate::createForPage($this->getResource())->getValue();
        }

    }

    public function getCanonical(): string
    {
        return Metadata::CANONICAL;
    }


}
