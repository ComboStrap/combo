<?php


namespace ComboStrap;


class PageKeywords extends MetadataArray
{

    public const KEYWORDS_ATTRIBUTE = "keywords";
    const SEPARATOR = ",";

    public static function createForPage(Page $page)
    {
        return (new PageKeywords())
            ->setResource($page);
    }

    public function getTab(): string
    {
        return \action_plugin_combo_metamanager::TAB_PAGE_VALUE;
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

    public function getName(): string
    {
        return self::KEYWORDS_ATTRIBUTE;
    }

    public function toStoreValue()
    {
        if ($this->getValue() === null) {
            return null;
        }
        return implode(self::SEPARATOR, $this->getValue());

    }

    /**
     * @throws ExceptionCombo
     */
    public function setFromFormData($formData)
    {
        $this->setFromStoreValue($formData);
        return $this;
    }

    public function toStoreDefaultValue()
    {
        if ($this->getDefaultValues() === null) {
            return null;
        }
        return implode(self::SEPARATOR, $this->getDefaultValues());
    }


    /**
     * The default of dokuwiki is the recursive parts of all {@link ResourceName page name}
     * in the hierarchy.
     * @return string[]|null
     */
    public function getDefaultValues(): ?array
    {

        $resource = $this->getResource();
        if (!($resource instanceof Page)) {
            return null;
        }
        $keyWords = explode(" ", $resource->getPageNameOrDefault());
        $actualPage = $resource;
        while (($parentPage = $actualPage->getParentPage()) !== null) {
            if (!$parentPage->isRootHomePage()) {
                $parentKeyWords = explode(" ", $parentPage->getPageNameOrDefault());
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
}
