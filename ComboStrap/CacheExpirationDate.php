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
        if ($cacheIntervalInSecond === -1) {
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


    /**
     * @throws ExceptionNotFound
     */
    public function getValue(): DateTime
    {

        try {
            return parent::getValue();
        } catch (ExceptionNotFound $e) {
            $cronExpression = CacheExpirationFrequency::createForPage($this->getResource())->getValue();
            try {
                $value = Cron::getDate($cronExpression);
                parent::setValue($value);
                return $value;
            } catch (ExceptionBadArgument $badArgument) {
                throw $e;
            }
        }

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
