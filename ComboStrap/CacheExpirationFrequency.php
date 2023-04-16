<?php


namespace ComboStrap;


use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Api\MetadataText;
use ComboStrap\Meta\Store\MetadataDokuWikiStore;

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

    public static function getTab(): string
    {
        return MetaManagerForm::TAB_CACHE_VALUE;
    }

    /**
     * @param string|null $value
     * @return Metadata
     * @throws ExceptionBadArgument - if the value cannot be persisted
     * @throws ExceptionBadSyntax - if the frequency has not the good syntax
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
        } catch (ExceptionBadSyntax $e) {
            throw new ExceptionBadSyntax("The cache frequency expression ($value) is not a valid cron expression. <a href=\"https://crontab.guru/\">Validate it on this website</a>", CacheExpirationFrequency::PROPERTY_NAME, 0, $e);
        }
        $cacheExpirationDate = CacheExpirationDate::createForPage($this->getResource());
        $cacheExpirationDate
            ->setValue($cacheExpirationCalculatedDate)
            ->persist();
        parent::setValue($value);
        return $this;


    }


    public static function getDescription(): string
    {
        return "A page expiration frequency expressed as a cron expression";
    }

    public static function getLabel(): string
    {
        return "Cache Expiration Frequency";
    }

    public static function getName(): string
    {
        return self::PROPERTY_NAME;
    }

    public static function getPersistenceType(): string
    {
        return MetadataDokuWikiStore::PERSISTENT_DOKUWIKI_KEY;
    }

    public static function isMutable(): bool
    {
        return true;
    }

    public function getDefaultValue()
    {
        return null;
    }

    public static function getCanonical(): string
    {
        return self::CANONICAL;
    }


    public static function isOnForm(): bool
    {
        return true;
    }
}
