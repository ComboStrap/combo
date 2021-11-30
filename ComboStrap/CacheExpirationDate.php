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
    public const META_CACHE_EXPIRATION_DATE_NAME = "date_cache_expiration";


    public static function createForPage(Page $page): CacheExpirationDate
    {
        return new CacheExpirationDate($page);
    }

    public function getDefaultValue(): ?DateTime
    {
        $xhtmlCache = $this->getPage()->getRenderCache("xhtml");
        $file = File::createFromPath($xhtmlCache->cache);
        if ($file->exists()) {
            $cacheIntervalInSecond = Site::getCacheTime();
            /**
             * Not the modified time (it's modified by a process when the cache is read
             * for whatever reason)
             */
            $expirationTime = $file->getCreationTime();
            if ($cacheIntervalInSecond !== null) {
                $expirationTime->modify('+' . $cacheIntervalInSecond . ' seconds');
            }
        } else {
            $expirationTime = null;
        }
        return $expirationTime;

    }


    public function getValue(): ?DateTime
    {

        $value = parent::getValue();
        if ($value === null) {
            $cronExpression = $this->getPage()->getCacheExpirationFrequency();
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
        return self::META_CACHE_EXPIRATION_DATE_NAME;
    }


    public function getPersistenceType(): string
    {
        return Metadata::CURRENT_METADATA;
    }


    public function getCanonical(): string
    {
        return CacheExpirationFrequency::CANONICAL_NAME;
    }

    public function getTab(): string
    {
        return \action_plugin_combo_metamanager::TAB_CACHE_VALUE;
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
