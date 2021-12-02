<?php


namespace ComboStrap;


class CacheExpirationFrequency extends MetadataText
{

    /**
     * The meta that has the cron expression
     */
    public const META_CACHE_EXPIRATION_FREQUENCY_NAME = "cache_expiration_frequency";
    public const CANONICAL_PROPERTY = "page-cache-expiration-frequency";

    public static function createForPageWithDefaultStore(Page $page): CacheExpirationFrequency
    {
        return (new CacheExpirationFrequency())
            ->setResource($page)
            ->useDefaultStore();
    }

    public function getTab(): string
    {
        return \action_plugin_combo_metamanager::TAB_CACHE_VALUE;
    }

    /** @noinspection PhpParameterNameChangedDuringInheritanceInspection */
    public function setValue(?string $cronExpression): MetadataText
    {

        if ($cronExpression === "" || $cronExpression === null) {
            // html form send an empty string
            parent::setValue(null);
            return $this;
        }

        try {
            $cacheExpirationCalculatedDate = Cron::getDate($cronExpression);
            $cacheExpirationDate = CacheExpirationDate::createForPageWithDefaultStore($this->getResource());
            $cacheExpirationDate->setValue($cacheExpirationCalculatedDate);
            parent::setValue($cronExpression);
            return $this;
        } catch (ExceptionCombo $e) {
            throw new ExceptionCombo("The cache frequency expression ($cronExpression) is not a valid cron expression. <a href=\"https://crontab.guru/\">Validate it on this website</a>", CacheExpirationFrequency::CANONICAL_PROPERTY);
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

    public function getName(): string
    {
        return self::META_CACHE_EXPIRATION_FREQUENCY_NAME;
    }

    public function getPersistenceType(): string
    {
        return Metadata::PERSISTENT_METADATA;
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
        return self::CANONICAL_PROPERTY;
    }


}
