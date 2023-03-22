<?php


namespace ComboStrap;


use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Api\MetadataTabular;

class References extends MetadataTabular
{


    const PROPERTY_NAME = "references";

    public static function createFromResource(MarkupPath $page)
    {
        return (new References())
            ->setResource($page);
    }

    static public function getDescription(): string
    {
        return "The link of the page that references another resources";
    }

    static public function getLabel(): string
    {
        return "References";
    }

    public static function getName(): string
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

    public function getDefaultValue()
    {
        return null;
    }

    public function getUidClass(): ?string
    {
        return Reference::class;
    }

    static public function getChildrenClass(): array
    {
        return [Reference::class];
    }

    public function buildFromReadStore(): References
    {
        $metadataStore = $this->getReadStore();
        if ($metadataStore === null) {
            LogUtility::msg("The metadata store is unknown. You need to define a resource or a store to build from it");
            return $this;
        }
        if ($metadataStore->isDokuWikiStore()) {

            $relation = $metadataStore->getFromPersistentName("relation");
            if (is_array($relation)) {

                $this->wasBuild = true;
                $referencesArray = $relation["references"];
                if ($referencesArray !== null) {
                    $referencesArray = array_keys($referencesArray);
                }
                $this->buildFromStoreValue($referencesArray);

                return $this;

            }

        }

        return parent::buildFromReadStore();
    }


}
