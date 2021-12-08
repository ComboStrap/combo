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


    public const DATE_CREATED_PROPERTY = 'date_created';
    const DOKUWIKI_MAIN_KEY = 'date';
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
     * @throws ExceptionCombo
     */
    public function buildFromStore(): MetadataDateTime
    {

        $store = $this->getStore();

        if (!($store instanceof MetadataDokuWikiStore)) {
            return parent::buildFromStore();
        }

        $createdMeta = $store->getFromResourceAndName($this->getResource(), self::DOKUWIKI_MAIN_KEY)[self::DOKUWIKI_SUB_KEY];
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
        $store = $this->getStore();
        if (!($store instanceof MetadataDokuWikiStore)) {
            return parent::toStoreValue();
        }
        $value = $this->getValue();
        if ($value === null) {
            return null;
        }
        return array(
            self::DOKUWIKI_MAIN_KEY => [self::DOKUWIKI_SUB_KEY => $value->getTimestamp()]
        );
    }


    public function getName(): string
    {
        return PageCreationDate::DATE_CREATED_PROPERTY;
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
        return \action_plugin_combo_metamanager::TAB_PAGE_VALUE;
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
}
