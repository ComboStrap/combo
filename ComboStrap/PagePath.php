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


    public function getPersistenceType(): string
    {
        return Metadata::DERIVED_METADATA;
    }


    public function getTab(): string
    {
        return MetaManagerForm::TAB_REDIRECTION_VALUE;
    }

    public function getDescription(): string
    {
        return "The path of the page on the file system (in wiki format with the colon `:` as path separator)";
    }

    public function getLabel(): string
    {
        return "Page Path";
    }

    public function getMutable(): bool
    {
        return false;
    }

    public function getCanonical(): string
    {
        return self::PROPERTY_NAME;
    }



}
