<?php


namespace ComboStrap;


class References extends MetadataTabular
{


    public static function createFromResource(Page $page)
    {
        return (new References())
            ->setResource($page);
    }

    public function getDescription(): string
    {
        return "The link of the page that references another resources";
    }

    public function getLabel(): string
    {
        return "References";
    }

    public static function getName(): string
    {
        return "references";
    }

    public function getPersistenceType(): string
    {
        return Metadata::DERIVED_METADATA;
    }

    public function getMutable(): bool
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

    public function getChildrenClass(): ?array
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
        if ($metadataStore instanceof MetadataDokuWikiStore) {

            $relation = $metadataStore->getCurrentFromName("relation");
            if ($relation !== null) {

                $this->wasBuild = true;
                $referencesArray = $relation["references"];
                if($referencesArray!==null) {
                    $references = array_keys($referencesArray);
                }
                $this->buildFromStoreValue($references);
                return $this;

            }

        }

        return parent::buildFromReadStore();
    }


}
