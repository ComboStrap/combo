<?php


namespace ComboStrap;


class PageTitle extends MetadataText
{

    public const PROPERTY_NAME = 'title';
    public const TITLE = 'title';

    public static function createForPage($page): PageTitle
    {
        return (new PageTitle())
            ->setResource($page);
    }

    public function getTab(): string
    {
        return MetaManagerForm::TAB_PAGE_VALUE;
    }

    public function getDescription(): string
    {
        return "The page title is a description advertised to external application such as search engine and browser.";
    }

    public function getLabel(): string
    {
        return "Title";
    }

    static public function getName(): string
    {
        return self::PROPERTY_NAME;
    }

    public function getPersistenceType(): string
    {
        return MetadataDokuWikiStore::PERSISTENT_METADATA;
    }

    public function getMutable(): bool
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
        if (!($resource instanceof Page)) {
            LogUtility::internalError("Resource that are not page have no title");
            return ResourceName::getFromPath($resource->getPath());
        }
        if ($resource->isRootHomePage() && !empty(Site::getTagLine())) {
            return Site::getTagLine();
        }
        try {
            return $resource->getH1OrDefault();
        } catch (ExceptionNotFound $e) {
            // ok
        }
        return $resource->getNameOrDefault();

    }

    public function getCanonical(): string
    {
        return self::TITLE;
    }


}
