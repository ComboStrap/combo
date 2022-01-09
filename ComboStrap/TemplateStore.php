<?php


namespace ComboStrap;

/**
 * Class TemplateStore
 * @package ComboStrap
 * The data goes from and out of a template format
 */
class TemplateStore extends MetadataStoreAbs implements MetadataStore
{

    const CANONICAL = "template";

    public function set(Metadata $metadata)
    {
        LogUtility::msg("You can't set a value with a template store");
    }

    public function get(Metadata $metadata, $default = null)
    {
        LogUtility::msg("You can't get a value with a template store");
    }


    public function getFromPersistentName(string $name, $default = null)
    {
        LogUtility::msg("You can't get a value with a template store");
    }

    public function setFromPersistentName(string $name, $value)
    {
        LogUtility::msg("You can't set a value with a template store");
    }

    public function persist()
    {
        LogUtility::msg("You can't persist with a template store");
    }

    public function isHierarchicalTextBased(): bool
    {
        return true;
    }

    public function reset()
    {
        LogUtility::msg("Reset: The template format is not yet implemented");
    }

    public function getCanonical(): string
    {
        return self::CANONICAL;
    }

    static function getOrCreateFromResource(ResourceCombo $resourceCombo): MetadataStore
    {
        return new TemplateStore($resourceCombo);
    }
}
