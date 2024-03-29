<?php


namespace ComboStrap;


use ComboStrap\Api\QualityMessageHandler;
use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Api\MetadataBoolean;

class QualityDynamicMonitoringOverwrite extends MetadataBoolean
{

    /**
     * Key in the frontmatter that disable the message
     */
    public const PROPERTY_NAME = "dynamic_quality_monitoring";
    public const EXECUTE_DYNAMIC_QUALITY_MONITORING_DEFAULT = true;

    public static function createFromPage(MarkupPath $page): QualityDynamicMonitoringOverwrite
    {
        return (new QualityDynamicMonitoringOverwrite())
            ->setResource($page);
    }

    static public function getTab(): ?string
    {
        return MetaManagerForm::TAB_QUALITY_VALUE;
    }

    static public function getDescription(): string
    {
        return "If checked, the quality message will not be shown for the page.";
    }

    static public function getLabel(): string
    {
        return "Disable the quality control of this page";
    }

    static public function getCanonical(): string
    {
        return QualityMessageHandler::CANONICAL;
    }


    static public function getName(): string
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
        return self::EXECUTE_DYNAMIC_QUALITY_MONITORING_DEFAULT;
    }

    static public function isOnForm(): bool
    {
        return true;
    }

}
