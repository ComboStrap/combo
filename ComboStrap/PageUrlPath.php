<?php


namespace ComboStrap;


use Slug;

class PageUrlPath extends MetadataWikiPath
{

    /**
     *
     * The page id is separated in the URL with a "-"
     * and not the standard "/"
     * because in the devtool or any other tool, they takes
     * the last part of the path as name.
     *
     * The name would be the short page id `22h2s2j4`
     * and would have therefore no signification
     *
     * Instead the name is `metadata-manager-22h2s2j4`
     * we can see then a page description, even order on it
     */
    public const PAGE_ID_URL_SEPARATOR = "-";
    /**
     * The canonical page for the page url
     */
    public const PROPERTY_NAME = "page:url";
    const PROPERTY_NAME = "page-url-path";

    public static function createForPage(Page $page)
    {
        return (new PageUrlPath())
            ->setResource($page);
    }

    public static function getShortEncodedPageIdFromUrlId($lastPartName)
    {
        $lastPosition = strrpos($lastPartName, PageUrlPath::PAGE_ID_URL_SEPARATOR);
        if ($lastPosition === false) {
            return null;
        }
        return substr($lastPartName, $lastPosition + 1);
    }

    public function getTab(): string
    {
        return \action_plugin_combo_metamanager::TAB_REDIRECTION_VALUE;
    }

    public function getValue(): ?string
    {

        $page = $this->getResource();
        if (!($page instanceof Page)) {
            LogUtility::msg("The Url Path is not implemented for the resource type (" . get_class($page) . ")");
            return null;
        }

        /**
         * Type of Url
         */
        $urlType = PageUrlType::getOrCreateForPage($page)->getValueOrDefault();
        $pagePath = $page->getPath()->toString();
        switch ($urlType) {
            case PageUrlType::CONF_VALUE_PAGE_PATH:
                // the default
                return $pagePath;
            case PageUrlType::CONF_VALUE_PERMANENT_PAGE_PATH:
                return $this->toPermanentUrlPath($pagePath);
            case PageUrlType::CONF_VALUE_CANONICAL_PATH:
                return $page->getCanonicalOrDefault();
            case PageUrlType::CONF_VALUE_PERMANENT_CANONICAL_PATH:
                return $this->toPermanentUrlPath($page->getCanonicalOrDefault());
            case PageUrlType::CONF_VALUE_SLUG:
                return $this->toPermanentUrlPath($page->getSlugOrDefault());
            case PageUrlType::CONF_VALUE_HIERARCHICAL_SLUG:
                $urlPath = $page->getSlugOrDefault();
                while (($parent = $page->getParentPage()) != null) {
                    $urlPath = Slug::toSlugPath($parent->getPageNameOrDefault()) . $urlPath;
                }
                return $this->toPermanentUrlPath($urlPath);
            case PageUrlType::CONF_VALUE_HOMED_SLUG:
                $urlPath = $page->getSlugOrDefault();
                if (($parent = $page->getParentPage()) != null) {
                    $urlPath = Slug::toSlugPath($parent->getPageNameOrDefault()) . $urlPath;
                }
                return $this->toPermanentUrlPath($urlPath);
            default:
                LogUtility::msg("The url type ($urlType) is unknown and was unexpected", LogUtility::LVL_MSG_ERROR, self::PROPERTY_NAME);
                return null;
        }

    }


    public function getDescription(): string
    {
        return "The path used in the page url";
    }

    public function getLabel(): string
    {
        return "Url Path";
    }

    public function getName(): string
    {
        return self::PROPERTY_NAME;
    }

    public function getPersistenceType(): string
    {
        return Metadata::DERIVED_METADATA;
    }

    public function getMutable(): bool
    {
        return false;
    }

    public function getDefaultValue()
    {
        return $this->getResource()->getPath()->toString();
    }

    public function getCanonical(): string
    {
        return self::PROPERTY_NAME;
    }


    private
    function toPermanentUrlPath(string $id): string
    {
        return $id . self::PAGE_ID_URL_SEPARATOR . $this->getPageIdAbbrUrlEncoded();
    }

    /**
     * Add a one letter checksum
     * to verify that this is a page id abbr
     * ( and not to hit the index for nothing )
     * @return string
     */
    public
    function getPageIdAbbrUrlEncoded(): ?string
    {
        $page = $this->getPage();
        if ($page->getPageIdAbbr() == null) return null;
        $abbr = $page->getPageIdAbbr();
        return self::encodePageId($abbr);
    }

    /**
     * Add a checksum character to the page id
     * to check if it's a page id that we get in the url
     * @param string $pageId
     * @return string
     */
    public static function encodePageId(string $pageId): string
    {
        return self::getPageIdChecksumCharacter($pageId) . $pageId;
    }

    /**
     * @param string $encodedPageId
     * @return string|null return the decoded page id or null if it's not an encoded page id
     */
    public static function decodePageId(string $encodedPageId): ?string
    {
        if (empty($encodedPageId)) return null;
        $checkSum = $encodedPageId[0];
        $extractedEncodedPageId = substr($encodedPageId, 1);
        $calculatedCheckSum = self::getPageIdChecksumCharacter($extractedEncodedPageId);
        if ($calculatedCheckSum == null) return null;
        if ($calculatedCheckSum != $checkSum) return null;
        return $extractedEncodedPageId;
    }

    /**
     * @param string $pageId
     * @return string|null - the checksum letter or null if this is not a page id
     */
    public static function getPageIdChecksumCharacter(string $pageId): ?string
    {
        $total = 0;
        for ($i = 0; $i < strlen($pageId); $i++) {
            $letter = $pageId[$i];
            $pos = strpos(PageId::PAGE_ID_ALPHABET, $letter);
            if ($pos === false) {
                return null;
            }
            $total += $pos;
        }
        $checkSum = $total % strlen(PageId::PAGE_ID_ALPHABET);
        return PageId::PAGE_ID_ALPHABET[$checkSum];
    }

    /**
     * Utility to change the type of the resource
     * @return Page|null
     */
    private function getPage(): ?Page
    {
        $resource = $this->getResource();
        if ($resource instanceof Page) {
            return $resource;
        }
        return null;
    }

}
