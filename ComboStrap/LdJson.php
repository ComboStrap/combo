<?php


namespace ComboStrap;


use action_plugin_combo_metagoogle;

class LdJson extends MetadataJson
{

    public const JSON_LD_META_PROPERTY = "json-ld";

    public static function createForPageWithDefaultStore(Page $page): LdJson
    {
        return (new LdJson())
            ->setResource($page)
            ->useDefaultStore();
    }

    public function getName(): string
    {
        return self::JSON_LD_META_PROPERTY;
    }

    public function getPersistenceType(): string
    {
        return Metadata::PERSISTENT_METADATA;
    }

    public function getCanonical(): string
    {
        return action_plugin_combo_metagoogle::CANONICAL;
    }


    public function getDescription(): string
    {
        return "Advanced Page metadata definition with the json-ld format";
    }

    public function getLabel(): string
    {
        return "Json-ld";
    }

    public function getTab(): string
    {
        return \action_plugin_combo_metamanager::TAB_TYPE_VALUE;
    }


    public function getMutable(): bool
    {
        return true;
    }

    public function getDefaultValue()
    {
        return null;
    }

    public function getValue(): ?array
    {
        $value = parent::getValue();

        if ($value === null && $this->getResource()->getTypeNotEmpty() === "organization") {
            // deprecated, old syntax
            $metadata = $this->getResource()->getMetadata("organization");
            if (!empty($metadata)) {
                return ["organization" => $metadata];
            }
        }
        return $value;

    }


}
