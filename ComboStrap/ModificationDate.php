<?php

namespace ComboStrap;

use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Api\MetadataDateTime;
use ComboStrap\Meta\Store\MetadataDokuWikiStore;
use DateTime;

class ModificationDate extends MetadataDateTime
{

    public const PROPERTY_NAME = 'date_modified';

    public static function createForPage(MarkupPath $page)
    {
        return (new ModificationDate())
            ->setResource($page);
    }

    static public function getTab(): string
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
            $modificationTime = FileSystems::getModifiedTime($this->getResource()->getPathObject());
            $this->setValue($modificationTime);
            return $this;
        } catch (ExceptionNotFound $e) {

            /**
             * Dokuwiki
             * Why do they store the date of the file while it's in the file system ?
             */
            $currentDateMeta = $store->getCurrentFromName('date');
            $createdMeta = null;
            if ($currentDateMeta !== null) {
                $createdMeta = $currentDateMeta['modified'] ?? null;
            }
            if (empty($createdMeta)) {
                $createdMeta = $currentDateMeta['modified'] ?? null;
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


    static public function getDescription(): string
    {
        return "The last modification date of the page"; // resource
    }

    static public function getLabel(): string
    {
        return "Modification Date";
    }

    static public function getName(): string
    {
        return self::PROPERTY_NAME;
    }

    static public function getPersistenceType(): string
    {
        return Metadata::DERIVED_METADATA;
    }

    static public function isMutable(): bool
    {
        return false;
    }

    /**
     * @throws ExceptionNotFound - if the file does not exists
     */
    public function getDefaultValue(): DateTime
    {

        try {
            return FileSystems::getModifiedTime($this->getResource()->getPathObject());
        } catch (ExceptionNotFound $e) {
            return CreationDate::createForPage($this->getResource())->getValue();
        }

    }

    static public function getCanonical(): string
    {
        return Metadata::CANONICAL;
    }


    static public function isOnForm(): bool
    {
        return true;
    }
}
