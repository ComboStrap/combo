<?php


namespace ComboStrap;

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

    public static function createForPage(Page $page)
    {
        return (new LowQualityPageOverwrite())
            ->setResource($page);
    }

    public function getTab(): string
    {
        return MetaManagerForm::TAB_QUALITY_VALUE;
    }

    public function getDescription(): string
    {
        return "If checked, this page will never be a low quality page";
    }

    public function getLabel(): string
    {
        return "Prevent this page to become a low quality page";
    }

    public static function getName(): string
    {
        return self::PROPERTY_NAME;
    }

    public function getPersistenceType(): string
    {
        return Metadata::PERSISTENT_METADATA;
    }

    public function getMutable(): bool
    {
        return true;
    }

    public function getDefaultValue(): bool
    {
        /**
         * A page can be of low quality by default
         */
        return self::CAN_BE_LOW_QUALITY_PAGE_DEFAULT;
    }

    public function getCanonical(): string
    {
        return "low_quality_page";
    }


}
