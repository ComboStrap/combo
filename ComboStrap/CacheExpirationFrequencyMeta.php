<?php


namespace ComboStrap;


use DateTime;

/**
 * Class CacheExpirationFrequencyMeta
 * @package ComboStrap
 * Represents the cache expiration date metadata
 */
class CacheExpirationFrequencyMeta extends Metadata
{


    /**
     * The meta key that has the expiration date
     */
    public const META_CACHE_EXPIRATION_DATE_NAME = "date_cache_expiration";
    private $cacheExpirationDate;
    /**
     * @var bool
     */
    private $wasBuildOrSet = false;


    public static function createForPage(Page $page): CacheExpirationFrequencyMeta
    {
        return new CacheExpirationFrequencyMeta($page);
    }

    public function getDefaultValue(): DateTime
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
            $expirationTime = new DateTime();
        }
        return $expirationTime;

    }

    public function toPersistentDefaultValue(): string
    {

        return $this->toPersistentDateTime($this->getDefaultValue());

    }

    public function getValue(): ?DateTime
    {

        if (!$this->wasBuildOrSet) {
            $this->wasBuildOrSet = true;
            $this->cacheExpirationDate = $this->toDateTime($this->getFileSystemValue());
            if ($this->cacheExpirationDate === null) {
                $cronExpression = $this->getPage()->getCacheExpirationFrequency();
                if ($cronExpression !== null) {
                    try {
                        $this->cacheExpirationDate = Cron::getDate($cronExpression);
                    } catch (ExceptionCombo $e) {
                        // nothing, the cron expression is tested when set
                    }
                }
            }
        }
        return $this->cacheExpirationDate;

    }

    public function setValue(DateTime $cacheExpirationDate): CacheExpirationFrequencyMeta
    {
        $this->wasBuildOrSet = true;
        $this->cacheExpirationDate = $cacheExpirationDate;
        $this->persistToFileSystem();
        return $this;
    }

    public function getName(): string
    {
        return self::META_CACHE_EXPIRATION_DATE_NAME;
    }


    public function toPersistentValue()
    {
        return $this->toPersistentDateTime($this->getValue());
    }


    public function getPersistenceType()
    {
        return Metadata::CURRENT_METADATA;
    }


    public function loadFromFileSystem()
    {
        $value = $this->getFileSystemValue();
        try {
            $this->cacheExpirationDate = $this->fromPersistentDateTime($value);
        } catch (ExceptionCombo $e) {
            LogUtility::msg($e->getMessage(), $this->getCanonical());
        }
    }

    /**
     * @throws ExceptionCombo
     */
    public function setFromPersistentFormat($value): CacheExpirationFrequencyMeta
    {
        $this->fromPersistentDateTime($value);
        return $this;
    }

    public function getCanonical(): string
    {
        return ":page-cache-expiration-frequency";
    }
}
