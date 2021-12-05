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


    public const DATE_CREATED = 'date_created';


    public static function createForPage(Page $page): CacheExpirationDate
    {
        return (new CacheExpirationDate())
            ->setResource($page);
    }

    public static function create(): PageCreationDate
    {
        return new PageCreationDate();
    }

    public function getDefaultValue(): ?DateTime
    {
        return null;
    }

    public function buildFromStore()
    {

        $store = $this->getStore();

        if (!($store instanceof MetadataDokuWikiStore)) {
            return parent::buildFromStore();
        }

        $createdMeta = $store->getFromResourceAndName($this->getResource(), 'date')['created'];
        if (empty($createdMeta)) {
            return $this;
        }
        // the data in dokuwiki is saved as timestamp
        $datetime = new DateTime();
        $datetime->setTimestamp($createdMeta);
        $this->setValue($datetime);
        return $this;
    }


    public function getValue(): ?DateTime
    {

        $value = parent::getValue();
        if ($value === null) {
            $cronExpression = $this->getResource()->getCacheExpirationFrequency();
            if ($cronExpression !== null) {
                try {
                    $value = Cron::getDate($cronExpression);
                    parent::setValue($value);
                } catch (ExceptionCombo $e) {
                    // nothing, the cron expression is tested when set
                }
            }
        }
        return $value;

    }


    public function getName(): string
    {
        return PageCreationDate::DATE_CREATED;
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
        return false;
    }
}
