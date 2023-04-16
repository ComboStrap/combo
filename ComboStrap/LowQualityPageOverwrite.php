<?php


namespace ComboStrap;

use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Api\MetadataBoolean;

/**
 * Class LowQualityPageIndicator
 * @package ComboStrap
 * Tells if the page can be of low quality or not
 * By default, it can
 */
class LowQualityPageOverwrite extends MetadataBoolean
{

    /**
     * An indicator in the meta
     * that set a boolean to true or false
     * to tell if a page may be of low quality
     */
    public const PROPERTY_NAME = 'low_quality_page';
    public const CAN_BE_LOW_QUALITY_PAGE_DEFAULT = true;

    public static function createForPage(MarkupPath $page)
    {
        return (new LowQualityPageOverwrite())
            ->setResource($page);
    }

    static public function getTab(): string
    {
        return MetaManagerForm::TAB_QUALITY_VALUE;
    }

    static public function getDescription(): string
    {
        return "If checked, this page will never be a low quality page";
    }

    static public function getLabel(): string
    {
        return "Prevent this page to become a low quality page";
    }

    public static function getName(): string
    {
        return self::PROPERTY_NAME;
    }

    static public function getPersistenceType(): string
    {
        return Metadata::PERSISTENT_METADATA;
    }

    static public function isMutable(): bool
    {
        return true;
    }

    /**
     * @return bool
     */
    public function getValueOrDefault(): bool
    {
        try {
            return $this->getValue();
        } catch (ExceptionNotFound $e) {
            return $this->getDefaultValue();
        }
    }


    /**
     * @return bool
     */
    public function getDefaultValue(): bool
    {
        /**
         * A page can be of low quality by default
         */
        return self::CAN_BE_LOW_QUALITY_PAGE_DEFAULT;
    }

    static public function getCanonical(): string
    {
        return "low_quality_page";
    }


    static public function isOnForm(): bool
    {
        return true;
    }

}
