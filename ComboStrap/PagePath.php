<?php


namespace ComboStrap;


use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Api\MetadataWikiPath;
use DateTime;

/**

 * @package ComboStrap
 * Represents the wiki path of the page resource
 */
class PagePath extends MetadataWikiPath
{



    public const PROPERTY_NAME = "path";



    public static function createForPage(ResourceCombo $page): CacheExpirationDate
    {
        return (new CacheExpirationDate())
            ->setResource($page);
    }


    /**
     * We build to be able to send the value elsewhere
     * @param $value
     * @return Metadata
     */
    public function buildFromStoreValue($value): Metadata
    {
        try {
            $value = $this->getResource()->getPathObject()->toWikiPath();
        } catch (ExceptionCast $e) {
            $message = "This error should not happen as this is a wiki path";
            LogUtility::internalError($message);
            $value = null;
        }
        return parent::buildFromStoreValue($value);
    }


    static public function getName(): string
    {
        return self::PROPERTY_NAME;
    }


    public static function getPersistenceType(): string
    {
        return Metadata::DERIVED_METADATA;
    }


    public static function getTab(): string
    {
        return MetaManagerForm::TAB_REDIRECTION_VALUE;
    }

    public static function getDescription(): string
    {
        return "The path of the page on the file system (in wiki format with the colon `:` as path separator)";
    }

    public static function getLabel(): string
    {
        return "Page Path";
    }

    public static function isMutable(): bool
    {
        return false;
    }

    public static function getCanonical(): string
    {
        return self::PROPERTY_NAME;
    }


    public static function getDrive(): string
    {
        return WikiPath::MARKUP_DRIVE;
    }

    public static function isOnForm(): bool
    {
        return true;
    }
}
