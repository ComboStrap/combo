<?php


namespace ComboStrap;


class PageKeywords extends MetadataMultiple
{

    public const PROPERTY_NAME = "keywords";


    public static function createForPage(PageFragment $page)
    {
        return (new PageKeywords())
            ->setResource($page);
    }

    public function getTab(): string
    {
        return MetaManagerForm::TAB_PAGE_VALUE;
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


    /**
     * The default of dokuwiki is the recursive parts of all {@link ResourceName page name}
     * in the hierarchy.
     * @return string[]|null
     */
    public function getDefaultValue(): ?array
    {

        $resource = $this->getResource();
        if (!($resource instanceof PageFragment)) {
            return null;
        }
        $keyWords = explode(" ", $resource->getNameOrDefault());
        $parentPage = $resource;
        while (true) {
            try {
                $parentPage = $parentPage->getParentPage();
            } catch (ExceptionNotFound $e) {
                break;
            }
            if (!$parentPage->isRootHomePage()) {
                $parentKeyWords = explode(" ", $parentPage->getNameOrDefault());
                $keyWords = array_merge($keyWords, $parentKeyWords);
            }
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
            $this->array = $this->toArrayOrNull($value);
        } catch (ExceptionCompile $e) {
            LogUtility::msg($e->getMessage(), LogUtility::LVL_MSG_ERROR, $e->getMessage());
        }
        return $this;
    }


    public function getCanonical(): string
    {
        return self::PROPERTY_NAME;
    }


}
