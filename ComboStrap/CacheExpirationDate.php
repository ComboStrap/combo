<?php


namespace ComboStrap;


use ComboStrap\Meta\Api\MetadataDateTime;
use ComboStrap\Meta\Store\MetadataDokuWikiStore;
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

    /**
     *
     * @throws ExceptionNotFound - if their is no default value, the HTML document does not exists, the cache is disabled
     *
     */
    public function getDefaultValue(): DateTime
    {
        $resourceCombo = $this->getResource();
        if (!($resourceCombo instanceof MarkupPath)) {
            throw new ExceptionNotFound("Cache expiration is only available for page fragment");
        }

        /**
         * We use {@link FetcherMarkup::getContentCachePath()}
         * and not {@link FetcherMarkup::processIfNeededAndGetFetchPath()}
         * to not create the HTML
         */
        try {
            $fetcherMarkup = $resourceCombo->createHtmlFetcherWithItselfAsContextPath();
        } catch (ExceptionNotExists $e) {
            throw new ExceptionNotFound("The executing path does not exist.");
        }
        $path = $fetcherMarkup->getContentCachePath();
        if (!FileSystems::exists($path)) {
            throw new ExceptionNotFound("There is no HTML document created to expire.");
        }


        $cacheIntervalInSecond = Site::getXhtmlCacheTime();
        if ($cacheIntervalInSecond === -1) {
            throw new ExceptionNotFound("Cache has been disabled globally on the site");
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


    public static function getPersistenceType(): string
    {
        return MetadataDokuWikiStore::CURRENT_METADATA;
    }


    public static function getCanonical(): string
    {
        return CacheExpirationFrequency::PROPERTY_NAME;
    }

    public static function getTab(): string
    {
        return MetaManagerForm::TAB_CACHE_VALUE;
    }

    public static function getDescription(): string
    {
        return "The next cache expiration date (calculated from the cache frequency expression)";
    }

    public static function getLabel(): string
    {
        return "Cache Expiration Date";
    }

    public static function isMutable(): bool
    {
        return false;
    }

    public static function isOnForm(): bool
    {
        return true;
    }
}
