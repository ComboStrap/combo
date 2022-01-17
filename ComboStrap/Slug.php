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


    const SEPARATORS_CHARACTERS = [".", "(", ")", ","];

    public static function createForPage(ResourceCombo $resource)
    {
        return (new Slug())
            ->setResource($resource);
    }

    public function getCanonical(): string
    {
        return self::PROPERTY_NAME;
    }


    /**
     * The goal is to get only words that can be interpreted
     * We could also encode it
     * @param $string
     * @return string|null
     */
    public static function toSlugPath($string): ?string
    {
        if (empty($string)) return null;
        // Reserved word to space
        $slugWithoutReservedWord = str_replace(DokuPath::getReservedWords(), " ", $string);
        // Delete points, comma, parenthesis
        $slugWithoutSeparator = str_replace(self::SEPARATORS_CHARACTERS, " ", $slugWithoutReservedWord);
        // Doubles spaces to space
        $slugWithoutDoubleSpace = preg_replace("/\s{2,}/", " ", $slugWithoutSeparator);
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

    public function setFromStoreValue($value): Metadata
    {
        return $this->buildFromStoreValue($value);
    }

    public function setValue($value): Metadata
    {
        return $this->buildFromStoreValue($value);
    }

    public function buildFromStoreValue($value): Metadata
    {
        return parent::buildFromStoreValue(self::toSlugPath($value));
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
