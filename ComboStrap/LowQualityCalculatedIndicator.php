<?php


namespace ComboStrap;


use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Api\MetadataBoolean;
use renderer_plugin_combo_analytics;

class LowQualityCalculatedIndicator extends MetadataBoolean
{

    public const PROPERTY_NAME = "low_quality_indicator_calculated";

    public static function createFromPage(MarkupPath $page)
    {
        return (new LowQualityCalculatedIndicator())
            ->setResource($page);
    }

    public static function getTab(): ?string
    {
        // not in a form
        return null;
    }

    public static function getDescription(): string
    {
        return "The indicator calculated by the analytics process that tells if a page is of a low quality";
    }

    public function getValue(): bool
    {

        try {
            return parent::getValue();
        } catch (ExceptionNotFound $e) {

            /**
             * Migration code
             * The indicator {@link LowQualityCalculatedIndicator::PROPERTY_NAME} is new
             * but if the analytics was done, we can get it
             */
            $resource = $this->getResource();
            if (!($resource instanceof MarkupPath)) {
                throw new ExceptionNotFound("Low Quality is only for page resources");
            }
            try {
                $analyticsDocument = $resource->fetchAnalyticsDocument();
            } catch (ExceptionNotExists $e) {
                throw new ExceptionNotFound("No analytics document could be found");
            }

            $analyticsCache = $analyticsDocument->getContentCachePath();
            if (!FileSystems::exists($analyticsCache)) {
                throw new ExceptionNotFound("No analytics document could be found");
            }

            try {
                return Json::createFromPath($analyticsCache)->toArray()[renderer_plugin_combo_analytics::QUALITY][renderer_plugin_combo_analytics::LOW];
            } catch (ExceptionCompile $e) {
                $message = "Error while reading the json analytics. {$e->getMessage()}";
                LogUtility::internalError($message, self::CANONICAL);
                throw new ExceptionNotFound($message);
            }

        }

    }


    static public function getLabel(): string
    {
        return "Low Quality Indicator";
    }

    static public function getName(): string
    {
        return self::PROPERTY_NAME;
    }

    static public function getPersistenceType(): string
    {
        return Metadata::DERIVED_METADATA;
    }

    static public function isMutable(): bool
    {
        return false;
    }

    /**
     * By default, if a file has not been through
     * a {@link \renderer_plugin_combo_analytics}
     * analysis, this is a low page if protection is set
     */
    public function getDefaultValue(): bool
    {

        if (!Site::isLowQualityProtectionEnable()) {
            return false;
        }
        return true;
    }
}
