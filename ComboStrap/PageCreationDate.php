<?php


namespace ComboStrap;


use DateTime;

/**
 * Class CacheExpirationFrequencyMeta
 * @package ComboStrap
 * Represents the creation date of a resource
 */
class PageCreationDate extends MetadataDateTime
{


    public const PROPERTY_NAME = 'date_created';
    const DATE_DOKUWIKI_PROPERTY_NAME = 'date';
    const DOKUWIKI_SUB_KEY = 'created';


    public static function createForPage(ResourceCombo $page): PageCreationDate
    {
        return (new PageCreationDate())
            ->setResource($page);
    }

    public static function create(): PageCreationDate
    {
        return new PageCreationDate();
    }

    public function getDefaultValue(): ?DateTime
    {
        $path = $this->getResource()->getPath();
        return FileSystems::getCreationTime($path);
    }

    /**
     */
    public function buildFromReadStore(): MetadataDateTime
    {

        $store = $this->getReadStore();

        if (!($store instanceof MetadataDokuWikiStore)) {
            return parent::buildFromReadStore();
        }

        $fromName = $store->getFromPersistentName(self::DATE_DOKUWIKI_PROPERTY_NAME);
        $createdMeta = $fromName[self::DOKUWIKI_SUB_KEY];
        if (empty($createdMeta)) {
            return $this;
        }
        // the data in dokuwiki is saved as timestamp
        $datetime = new DateTime();
        $datetime->setTimestamp($createdMeta);
        $this->setValue($datetime);
        return $this;
    }

    public function toStoreValue()
    {
        $store = $this->getWriteStore();
        if (!($store instanceof MetadataDokuWikiStore)) {
            return parent::toStoreValue();
        }
        $value = $this->getValue();
        if ($value === null) {
            return null;
        }
        return array(
            self::DATE_DOKUWIKI_PROPERTY_NAME => [self::DOKUWIKI_SUB_KEY => $value->getTimestamp()]
        );
    }


    static public function getName(): string
    {
        return PageCreationDate::PROPERTY_NAME;
    }


    public function getPersistenceType(): string
    {
        /**
         * On windows, the creation time is not preserved when you copy
         * a file
         *
         * If you copy a file from C:\fat16 to D:\NTFS,
         * it keeps the same modified date and time but changes the created date
         * and time to the current date and time.
         * If you move a file from C:\fat16 to D:\NTFS,
         * it keeps the same modified date and time
         * and keeps the same created date and time
         */
        return Metadata::PERSISTENT_METADATA;
    }


    public function getTab(): string
    {
        return MetaManagerForm::TAB_PAGE_VALUE;
    }

    public function getDescription(): string
    {
        return "The creation date of the page";
    }

    public function getLabel(): string
    {
        return "Creation Date";
    }

    public function getMutable(): bool
    {
        /**
         * Not sure, It should not be really mutable by the user
         * but the date should be found in the frontmatter for instance
         */
        return false;
    }

    public function getCanonical(): string
    {
        return Metadata::CANONICAL;
    }
}
