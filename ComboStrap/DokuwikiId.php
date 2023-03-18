<?php


namespace ComboStrap;


use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Api\MetadataText;
use DateTime;

/**

 * @package ComboStrap
 * Represents the wiki id of a resource
 */
class DokuwikiId extends MetadataText
{


    public const DOKUWIKI_ID_ATTRIBUTE = "id";

    public static function createForPage(ResourceCombo $page): DokuwikiId
    {
        return (new DokuwikiId())
            ->setResource($page);
    }

    public function getDefaultValue(): ?DateTime
    {
        return null;
    }

    public function getValue(): string
    {
        $path = $this->getResource()->getPathObject();
        if($path instanceof WikiPath){
            return $path->getWikiId();
        }
        if($path instanceof LocalPath){
            try {
                return $path->toWikiPath()->getWikiId();
            } catch (ExceptionBadArgument $e) {
                throw new ExceptionNotFound($e->getMessage());
            }
        }
        throw new ExceptionNotFound("Unknown path, the dokuwiki id cannot be determined");

    }


    public static function getName(): string
    {
        return self::DOKUWIKI_ID_ATTRIBUTE;
    }


    public function getPersistenceType(): string
    {
        return Metadata::DERIVED_METADATA;
    }


    public function getTab(): ?string
    {
        return null;
    }

    public function getDescription(): string
    {
        return "The id of a resource represents the path of a resource from its root directory";
    }

    public function getLabel(): string
    {
        return "Wiki Id";
    }

    public function getMutable(): bool
    {
        return false;
    }
}
