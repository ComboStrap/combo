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
    public function setFromStoreValue($value)
    {

        if ($value === null || $value === "") {
            $this->setValue(null);
            return;
        }

        if (is_array($value)) {
            $this->setValue($value);
        }

        if (is_string($value)) {
            $this->setValue(explode(self::SEPARATOR, $value));
        }

        throw new ExceptionCombo("The keywords value is not an array or a string (value: $value)");


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


    public function getDefaultValues()
    {

        $resource = $this->getResource();
        if (!($resource instanceof Page)) {
            return null;
        }
        $keyWords = explode(" ", $resource->getPageNameNotEmpty());
        $actualPage = $resource;
        while (($parentPage = $actualPage->getParentPage()) !== null) {
            if (!$parentPage->isRootHomePage()) {
                $parentKeyWords = explode(" ", $parentPage->getPageNameNotEmpty());
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
}
