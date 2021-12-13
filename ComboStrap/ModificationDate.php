<?php


use ComboStrap\ExceptionCombo;
use ComboStrap\FileSystems;
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
        return action_plugin_combo_metamanager::TAB_PAGE_VALUE;
    }

    public function buildFromStore(): MetadataDateTime
    {
        $store = $this->getStore();
        if (!($store instanceof MetadataDokuWikiStore)) {
            return parent::buildFromStore();
        }
        $createdMeta = $store->getFromName($this->getResource(), 'date')['modified'];
        if (empty($createdMeta)) {
            return $this;
        }
        // the data in dokuwiki is saved as timestamp
        $datetime = new DateTime();
        $datetime->setTimestamp($createdMeta);
        try {
            $this->setValue($datetime);
        } catch (ExceptionCombo $e) {
            LogUtility::msg("Error when setting the time from the store. Message".$e->getMessage(),LogUtility::LVL_MSG_ERROR,$e->getCanonical());
        }
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

        $modificationTime = FileSystems::getModifiedTime($this->getResource()->getPath());
        if ($modificationTime !== null) {
            return $modificationTime;
        }
        return PageCreationDate::createForPage($this->getResource())->getValue();

    }
}
