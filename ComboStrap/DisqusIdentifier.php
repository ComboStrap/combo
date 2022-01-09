<?php


namespace ComboStrap;

/**
 * Class DisqusIdentifier
 * @package ComboStrap
 * @deprecated for the page id
 */
class DisqusIdentifier extends MetadataText
{



    public const PROPERTY_NAME = "disqus_identifier";

    public static function createForResource($page): DisqusIdentifier
    {
        return (new DisqusIdentifier())
            ->setResource($page);
    }

    public function getTab(): ?string
    {
        // Page id should be taken
        return null;
    }

    public function getDescription(): string
    {
        return "The identifier of the disqus forum";
    }

    public function getLabel(): string
    {
        return "Disqus Identifier";
    }

    public static function getName(): string
    {
        return self::PROPERTY_NAME;
    }

    public function getPersistenceType(): string
    {
        return MetadataDokuWikiStore::PERSISTENT_METADATA;
    }

    public function getMutable(): bool
    {
        return true;
    }

    public function getDefaultValue(): ?string
    {

        return $this->getResource()->getUid()->getValueOrDefault();

    }

    public function getCanonical(): string
    {
        return  "disqus";
    }


}
