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
            $expirationTime = $file->getModifiedTime();
            if ($cacheIntervalInSecond !== null) {
                $expirationTime->modify('+' . $cacheIntervalInSecond . ' seconds');
            }
        } else {
            $expirationTime = new DateTime();
        }
        return $expirationTime;

    }

    public function getPersistentDefaultValue(): string
    {

        return $this->toPersistentDateTime($this->getDefaultValue());

    }

    public function getValue(): ?DateTime
    {

        if (!$this->wasBuildOrSet) {
            $this->wasBuildOrSet = true;
            $this->cacheExpirationDate = $this->toDateTime($this->getMetadataValue());
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
        $this->save();
        return $this;
    }

    public function getName(): string
    {
        return self::META_CACHE_EXPIRATION_DATE_NAME;
    }


    public function getPersistentValue()
    {
        return $this->toPersistentDateTime($this->getValue());
    }


    public function getPersistenceType()
    {
        return Metadata::CURRENT_METADATA;
    }
}
