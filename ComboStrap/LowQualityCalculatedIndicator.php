<?php


namespace ComboStrap;


class LowQualityCalculatedIndicator extends MetadataBoolean
{

    public const LOW_QUALITY_INDICATOR_CALCULATED = "low_quality_indicator_calculated";

    public static function createFromPage(Page $page)
    {
        return (new LowQualityCalculatedIndicator())
            ->setResource($page);
    }

    public function getTab(): ?string
    {
        // not in a form
        return null;
    }

    public function getDescription(): string
    {
        return "The indicator calculated by the analytics process that tells if a page is of a low quality";
    }

    public function getValue(): ?bool
    {
        $value = parent::getValue();
        if ($value !== null) {
            return $value;
        }

        /**
         * Migration code
         * The indicator {@link LowQualityCalculatedIndicator::LOW_QUALITY_INDICATOR_CALCULATED} is new
         * but if the analytics was done, we can get it
         */
        $resource = $this->getResource();
        if (!($resource instanceof Page)) {
            return null;
        }
        $analyticsDocument = $resource->getAnalyticsDocument();
        if (!FileSystems::exists($analyticsDocument->getCachePath())) {
            return null;
        }
        try {
            return $analyticsDocument->getJson()->toArray()[AnalyticsDocument::QUALITY][AnalyticsDocument::LOW];
        } catch (ExceptionCombo $e) {
            LogUtility::msg("Error while reading the json analytics. {$e->getMessage()}");
            return null;
        }

    }


    public function getLabel(): string
    {
        return "Low Quality Indicator";
    }

    static public function getName(): string
    {
        return self::LOW_QUALITY_INDICATOR_CALCULATED;
    }

    public function getPersistenceType(): string
    {
        return Metadata::DERIVED_METADATA;
    }

    public function getMutable(): bool
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
