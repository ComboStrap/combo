<?php


namespace ComboStrap;

use action_plugin_combo_metaprocessing;
use ComboStrap\Meta\Api\MetadataText;
use ComboStrap\Meta\Store\MetadataDokuWikiStore;

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

    static public function getTab(): ?string
    {
        // Page id should be taken
        return null;
    }

    static public function getDescription(): string
    {
        return "The identifier of the disqus forum";
    }

    static public function getLabel(): string
    {
        return "Disqus Identifier";
    }

    public static function getName(): string
    {
        return self::PROPERTY_NAME;
    }

    static public function getPersistenceType(): string
    {
        return MetadataDokuWikiStore::PERSISTENT_DOKUWIKI_KEY;
    }

    static public function isMutable(): bool
    {
        return true;
    }

    public function getDefaultValue(): ?string
    {

        return $this->getResource()->getUid()->getValueOrDefault();

    }

    static public function getCanonical(): string
    {
        return "disqus";
    }


}
