<?php


namespace ComboStrap;


use action_plugin_combo_qualitymessage;

class QualityDynamicMonitoringOverwrite extends MetadataBoolean
{

    /**
     * Key in the frontmatter that disable the message
     */
    public const PROPERTY_NAME = "dynamic_quality_monitoring";
    public const EXECUTE_DYNAMIC_QUALITY_MONITORING_DEFAULT = true;

    public static function createFromPage(Page $page)
    {
        return (new QualityDynamicMonitoringOverwrite())
            ->setResource($page);
    }

    public function getTab(): ?string
    {
        return MetaManagerForm::TAB_QUALITY_VALUE;
    }

    public function getDescription(): string
    {
        return "If checked, the quality message will not be shown for the page.";
    }

    public function getLabel(): string
    {
        return "Disable the quality control of this page";
    }

    public function getCanonical(): string
    {
        return action_plugin_combo_qualitymessage::CANONICAL;
    }


    static public function getName(): string
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
        return self::EXECUTE_DYNAMIC_QUALITY_MONITORING_DEFAULT;
    }
}
