<?php


namespace ComboStrap;


class CacheExpirationFrequency extends MetadataText
{

    /**
     * The meta that has the cron expression
     */
    public const PROPERTY_NAME = "cache_expiration_frequency";
    public const CANONICAL = "page-cache-expiration-frequency";

    public static function createForPage(ResourceCombo $page): CacheExpirationFrequency
    {
        return (new CacheExpirationFrequency())
            ->setResource($page);
    }

    public function getTab(): string
    {
        return MetaManagerForm::TAB_CACHE_VALUE;
    }

    /**
     * @param string|null $value
     * @return Metadata
     * @throws ExceptionCombo
     */
    public function setValue($value): Metadata
    {

        if ($value === null) {
            parent::setValue($value);
            return $this;
        }

        $value = trim($value);
        if ($value === "") {
            // html form send an empty string
            return $this;
        }

        try {
            $cacheExpirationCalculatedDate = Cron::getDate($value);
            $cacheExpirationDate = CacheExpirationDate::createForPage($this->getResource());
            $cacheExpirationDate
                ->setValue($cacheExpirationCalculatedDate)
                ->persist();
            parent::setValue($value);
            return $this;
        } catch (ExceptionCombo $e) {
            throw new ExceptionCombo("The cache frequency expression ($value) is not a valid cron expression. <a href=\"https://crontab.guru/\">Validate it on this website</a>", CacheExpirationFrequency::PROPERTY_NAME, 0, $e);
        }

    }


    public function getDescription(): string
    {
        return "A page expiration frequency expressed as a cron expression";
    }

    public function getLabel(): string
    {
        return "Cache Expiration Frequency";
    }

    public static function getName(): string
    {
        return self::PROPERTY_NAME;
    }

    public function getPersistenceType(): string
    {
        return MetadataDokuWikiStore::PERSISTENT_METADATA;
    }

    public function getMutable(): bool
    {
        return true;
    }

    public function getDefaultValue()
    {
        return null;
    }

    public function getCanonical(): string
    {
        return self::CANONICAL;
    }


}
