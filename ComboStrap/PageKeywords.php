<?php


namespace ComboStrap;


class PageKeywords extends MetadataArray
{

    public const PROPERTY_NAME = "keywords";
    const SEPARATOR = ",";

    public static function createForPage(Page $page)
    {
        return (new PageKeywords())
            ->setResource($page);
    }

    public function getTab(): string
    {
        return MetaManagerForm::TAB_PAGE_VALUE;
    }

    /**
     * @throws ExceptionCombo
     */
    public function setFromStoreValue($value): PageKeywords
    {
        $this->setValue($this->toArray($value));
        return $this;
    }

    public function getDataType(): string
    {
        // in a form, we send a list of words
        return DataType::TEXT_TYPE_VALUE;
    }


    public function getDescription(): string
    {
        return "The keywords added to your page (separated by a comma)";
    }

    public function getLabel(): string
    {
        return "Keywords";
    }

    static public function getName(): string
    {
        return self::PROPERTY_NAME;
    }

    public function toStoreValue()
    {
        if ($this->getValue() === null) {
            return null;
        }
        return implode(self::SEPARATOR, $this->getValue());

    }


    public function toStoreDefaultValue()
    {
        if ($this->getDefaultValue() === null) {
            return null;
        }
        return implode(self::SEPARATOR, $this->getDefaultValue());
    }


    /**
     * The default of dokuwiki is the recursive parts of all {@link ResourceName page name}
     * in the hierarchy.
     * @return string[]|null
     */
    public function getDefaultValue(): ?array
    {

        $resource = $this->getResource();
        if (!($resource instanceof Page)) {
            return null;
        }
        $keyWords = explode(" ", $resource->getNameOrDefault());
        $actualPage = $resource;
        while (($parentPage = $actualPage->getParentPage()) !== null) {
            if (!$parentPage->isRootHomePage()) {
                $parentKeyWords = explode(" ", $parentPage->getNameOrDefault());
                $keyWords = array_merge($keyWords, $parentKeyWords);
            }
            $actualPage = $parentPage;
        }
        $keyWords = array_map(function ($element) {
            return strtolower($element);
        }, $keyWords);
        return array_unique($keyWords);
    }

    public function getPersistenceType(): string
    {
        return Metadata::PERSISTENT_METADATA;
    }

    public function getMutable(): bool
    {
        return true;
    }


    public function buildFromStoreValue($value): Metadata
    {
        try {
            $this->array = $this->toArray($value);
        } catch (ExceptionCombo $e) {
            LogUtility::msg($e->getMessage(), LogUtility::LVL_MSG_ERROR, $e->getMessage());
        }
        return $this;
    }

    /**
     * @throws ExceptionCombo
     */
    private function toArray($value)
    {
        if ($value === null || $value === "") {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            return explode(self::SEPARATOR, $value);
        }

        throw new ExceptionCombo("The keywords value is not an array or a string (value: $value)");
    }

    public function getCanonical(): string
    {
        return self::PROPERTY_NAME;
    }


}
