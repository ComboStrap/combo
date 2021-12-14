<?php


namespace ComboStrap;


use DateTime;

/**
 * Class CacheExpirationFrequencyMeta
 * @package ComboStrap
 * Represents the cache expiration date metadata
 */
class CacheExpirationDate extends MetadataDateTime
{


    /**
     * The meta key that has the expiration date
     */
    public const PROPERTY_NAME = "date_cache_expiration";


    public static function createForPage(ResourceCombo $page): CacheExpirationDate
    {
        return (new CacheExpirationDate())
            ->setResource($page);
    }

    public function getDefaultValue(): ?DateTime
    {
        $resourceCombo = $this->getResource();
        if (!($resourceCombo instanceof Page)) {
            return null;
        }
        $path = $resourceCombo->getHtmlDocument()->getCachePath();
        if (!FileSystems::exists($path)) {
            return null;
        }

        $cacheIntervalInSecond = Site::getCacheTime();
        if($cacheIntervalInSecond===-1){
            return null;
        }

        /**
         * Not the modified time (it's modified by a process when the cache is read
         * for whatever reason)
         */
        $expirationTime = FileSystems::getCreationTime($path);
        if ($cacheIntervalInSecond !== null) {
            $expirationTime->modify('+' . $cacheIntervalInSecond . ' seconds');
        }

        return $expirationTime;

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


    public static function getName(): string
    {
        return self::PROPERTY_NAME;
    }


    public function getPersistenceType(): string
    {
        return MetadataDokuWikiStore::CURRENT_METADATA;
    }


    public function getCanonical(): string
    {
        return CacheExpirationFrequency::PROPERTY_NAME;
    }

    public function getTab(): string
    {
        return MetaManagerForm::TAB_CACHE_VALUE;
    }

    public function getDescription(): string
    {
        return "The next cache expiration date (calculated from the cache frequency expression)";
    }

    public function getLabel(): string
    {
        return "Cache Expiration Date";
    }

    public function getMutable(): bool
    {
        return false;
    }
}
