<?php

namespace ComboStrap;

use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Api\MetadataText;

class Slug extends MetadataText
{

    public const PROPERTY_NAME = "slug";


    public static function createForPage(ResourceCombo $resource)
    {
        return (new Slug())
            ->setResource($resource);
    }

    static public function getCanonical(): string
    {
        return self::PROPERTY_NAME;
    }


    /**
     * The goal is to get only words that can be interpreted
     * We could also encode it
     * @param $string
     * @return string
     * @throws ExceptionNull
     */
    public static function toSlugPath($string): string
    {
        if (empty($string)) throw new ExceptionNull("The slug value should not be empty");
        $excludedCharacters = array_merge(WikiPath::getReservedWords(), StringUtility::SEPARATORS_CHARACTERS);
        $excludedCharacters[] = WikiPath::SLUG_SEPARATOR;
        $parts = explode(WikiPath::NAMESPACE_SEPARATOR_DOUBLE_POINT, $string);
        $parts = array_map(function ($e) use ($excludedCharacters) {
            $wordsPart = StringUtility::getWords(
                $e,
                $excludedCharacters
            );
            // Implode and Lower case
            return strtolower(implode(WikiPath::SLUG_SEPARATOR, $wordsPart));
        }, $parts);

        $slug = implode(WikiPath::NAMESPACE_SEPARATOR_DOUBLE_POINT, $parts);
        // Space to separator
        //$slugWithoutSpace = str_replace(" ", DokuPath::SLUG_SEPARATOR, $slugWithoutSpaceAroundParts);
        // No double separator
        //$slugWithoutDoubleSeparator = preg_replace("/" . DokuPath::SLUG_SEPARATOR . "{2,}/", DokuPath::SLUG_SEPARATOR, $slugWithoutSpace);
        WikiPath::addRootSeparatorIfNotPresent($slug);
        return $slug;
    }

    static public function getTab(): string
    {
        return MetaManagerForm::TAB_REDIRECTION_VALUE;
    }

    static public function getDescription(): string
    {
        return "The slug is used in the url of the page (if chosen)";
    }

    static public function getLabel(): string
    {
        return "Slug Path";
    }

    public function setFromStoreValue($value): Metadata
    {
        return $this->setFromStoreValueWithoutException($value);
    }

    public function setValue($value): Metadata
    {
        return $this->setFromStoreValueWithoutException($value);
    }

    public function setFromStoreValueWithoutException($value): Metadata
    {
        try {
            $slug = self::toSlugPath($value);
        } catch (ExceptionNull $e) {
            $slug = null;
        }
        return parent::setFromStoreValueWithoutException($slug);
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
     * @return string
     */
    public function getDefaultValue(): string
    {
        $title = PageTitle::createForMarkup($this->getResource())->getValueOrDefault();
        try {
            return self::toSlugPath($title);
        } catch (ExceptionNull $e) {
            throw new \RuntimeException("The default title of the page (" . $this->getResource() . ") should not be empty.");
        }

    }

    static public function isOnForm(): bool
    {
        return true;
    }
}
