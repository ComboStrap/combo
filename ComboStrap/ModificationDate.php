<?php


use ComboStrap\FileSystems;
use ComboStrap\Metadata;
use ComboStrap\MetadataDateTime;
use ComboStrap\MetadataDokuWikiStore;
use ComboStrap\Page;
use ComboStrap\PageCreationDate;

class ModificationDate extends MetadataDateTime
{

    public const DATE_MODIFIED_PROPERTY = 'date_modified';

    public static function createForPage(Page $page)
    {
        return (new ModificationDate())
            ->setResource($page);
    }

    public function getTab(): string
    {
        return action_plugin_combo_metamanager::TAB_PAGE_VALUE;
    }

    public function buildFromStore(): MetadataDateTime
    {
        $store = $this->getStore();
        if (!($store instanceof MetadataDokuWikiStore)) {
            return parent::buildFromStore();
        }
        $createdMeta = $store->getFromResourceAndName($this->getResource(), 'date')['modified'];
        if (empty($createdMeta)) {
            return $this;
        }
        // the data in dokuwiki is saved as timestamp
        $datetime = new DateTime();
        $datetime->setTimestamp($createdMeta);
        $this->setValue($datetime);
        return $this;
    }


    public function getDescription(): string
    {
        return "The last modification date of the page"; // resource
    }

    public function getLabel(): string
    {
        return "Modification Date";
    }

    public function getName(): string
    {
        return self::DATE_MODIFIED_PROPERTY;
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

        $modificationTime = FileSystems::getModifiedTime($this->getResource()->getPath());
        if ($modificationTime !== null) {
            return $modificationTime;
        }
        return PageCreationDate::createForPage($this->getResource())->getValue();

    }
}
