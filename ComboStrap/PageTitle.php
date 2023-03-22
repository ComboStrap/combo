<?php


namespace ComboStrap;


use ComboStrap\Meta\Api\MetadataText;
use ComboStrap\Meta\Field\PageH1;
use ComboStrap\Meta\Store\MetadataDokuWikiStore;

class PageTitle extends MetadataText
{

    public const PROPERTY_NAME = 'title';
    public const TITLE = 'title';

    public static function createForMarkup($page): PageTitle
    {
        return (new PageTitle())
            ->setResource($page);
    }

    public static function getTab(): string
    {
        return MetaManagerForm::TAB_PAGE_VALUE;
    }

    public static function getDescription(): string
    {
        return "The page title is a description advertised to external application such as search engine and browser.";
    }

    public static function getLabel(): string
    {
        return "Title";
    }

    static public function getName(): string
    {
        return self::PROPERTY_NAME;
    }

    public static function getPersistenceType(): string
    {
        return MetadataDokuWikiStore::PERSISTENT_DOKUWIKI_KEY;
    }

    public static function isMutable(): bool
    {
        return true;
    }

    /**
     * `title` is created by DokuWiki
     * in current but not persistent
     * and hold the heading 1, see {@link p_get_first_heading}
     */
    public function getDefaultValue(): string
    {

        $resource = $this->getResource();
        if (!($resource instanceof MarkupPath)) {
            LogUtility::internalError("Resource that are not page have no title");
            return ResourceName::getFromPath($resource->getPathObject());
        }
        if ($resource->isRootHomePage() && !empty(Site::getTagLine())) {
            return Site::getTagLine();
        }
        return PageH1::createForPage($this->getResource())
            ->getValueOrDefault();

    }

    /**
     * @return string
     */
    public function getValueOrDefault(): string
    {
        try {
            return $this->getValue();
        } catch (ExceptionNotFound $e) {
            return $this->getDefaultValue();
        }
    }


    public static function getCanonical(): string
    {
        return self::TITLE;
    }


    public static function isOnForm(): bool
    {
        return true;
    }

}
