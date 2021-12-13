<?php


use ComboStrap\DokuPath;
use ComboStrap\MetaManagerForm;
use ComboStrap\Metadata;
use ComboStrap\MetadataWikiPath;
use ComboStrap\PageTitle;
use ComboStrap\ResourceCombo;

class Slug extends MetadataWikiPath
{

    public const PROPERTY_NAME = "slug";

    public static function createForPage(ResourceCombo $resource)
    {
        return (new Slug())
            ->setResource($resource);
    }

    public static function toSlugPath($string): ?string
    {
        if (empty($string)) return null;
        // Reserved word to space
        $slugWithoutReservedWord = str_replace(DokuPath::getReservedWords(), " ", $string);
        // Doubles spaces to space
        $slugWithoutDoubleSpace = preg_replace("/\s{2,}/", " ", $slugWithoutReservedWord);
        // Trim space
        $slugTrimmed = trim($slugWithoutDoubleSpace);
        // No Space around the path part
        $slugParts = explode(DokuPath::PATH_SEPARATOR, $slugTrimmed);
        $slugParts = array_map(function ($e) {
            return trim($e);
        }, $slugParts);
        $slugWithoutSpaceAroundParts = implode(DokuPath::PATH_SEPARATOR, $slugParts);
        // Space to separator
        $slugWithoutSpace = str_replace(" ", DokuPath::SLUG_SEPARATOR, $slugWithoutSpaceAroundParts);
        // No double separator
        $slugWithoutDoubleSeparator = preg_replace("/" . DokuPath::SLUG_SEPARATOR . "{2,}/", DokuPath::SLUG_SEPARATOR, $slugWithoutSpace);
        // Root
        DokuPath::addRootSeparatorIfNotPresent($slugWithoutDoubleSeparator);
        // Lower case
        return strtolower($slugWithoutDoubleSeparator);
    }

    public function getTab(): string
    {
        return MetaManagerForm::TAB_REDIRECTION_VALUE;
    }

    public function getDescription(): string
    {
        return "The slug is used in the url of the page (if chosen)";
    }

    public function getLabel(): string
    {
        return "Slug Path";
    }

    public function toStoreValue()
    {
        return self::toSlugPath(parent::toStoreValue());
    }


    public function getName(): string
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

    public function getDefaultValue(): ?string
    {
        $title = PageTitle::createForPage($this->getResource())
            ->getValueOrDefault();
        if ($title === null) {
            return null;
        }
        return self::toSlugPath($title);
    }
}
