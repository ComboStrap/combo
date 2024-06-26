<?php


namespace ComboStrap;


use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Api\MetadataText;
use ComboStrap\Web\UrlRewrite;

/**
 * Class PageUrlPath
 * @package ComboStrap
 *
 *
 * The path (ie id attribute in the url) in a absolute format (ie with root)
 *
 * This is used in the {@link UrlRewrite} module where the path is rewritten
 *
 * url path: name for ns + slug (title) + page id
 * or
 * url path: canonical path + page id
 * or
 * url path: page path + page id
 *
 *
 *   - slug
 *   - hierarchical slug
 *   - permanent canonical path (page id)
 *   - canonical path
 *   - permanent page path (page id)
 *   - page path
 *
 * This is not the URL of the page but of the generated HTML web page (Ie {@link MarkupPath}) with all pages (slots)
 */
class PageUrlPath extends MetadataText
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
    public const CANONICAL = "page:url";
    const PROPERTY_NAME = "page-url-path";

    public static function createForPage(MarkupPath $page): PageUrlPath
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

    static public function getTab(): string
    {
        return MetaManagerForm::TAB_REDIRECTION_VALUE;
    }

    public function getValue(): string
    {

        $page = $this->getResource();
        if (!($page instanceof MarkupPath)) {
            throw new ExceptionNotFound("The Url Path is not implemented for the resource type (" . $page->getType() . ")");
        }

        /**
         * Type of Url
         */
        $pageUrlType = PageUrlType::createFromPage($page);
        $urlType = $pageUrlType->getValue();
        $urlTypeDefault = $pageUrlType->getDefaultValue();
        if ($urlType === $urlTypeDefault) {
            // not sure why ? may be to not store the value if it has the same default
            throw new ExceptionNotFound("Same value as default");
        }
        return $this->getUrlPathFromType($urlType);

    }

    /**
     * @return string
     *
     */
    public function getValueOrDefault(): string
    {
        try {
            return $this->getValue();
        } catch (ExceptionNotFound $e) {
            return $this->getDefaultValue();
        }

    }


    static public function getDescription(): string
    {
        return "The path used in the page url";
    }

    static public function getLabel(): string
    {
        return "Url Path";
    }

    static public function getName(): string
    {
        return self::PROPERTY_NAME;
    }

    static public function getPersistenceType(): string
    {
        return Metadata::DERIVED_METADATA;
    }

    static public function isMutable(): bool
    {
        return false;
    }

    /**
     * @return string
     */
    public function getDefaultValue(): string
    {

        $urlTypeDefault = PageUrlType::createFromPage($this->getResource())->getDefaultValue();
        return $this->getUrlPathFromType($urlTypeDefault);

    }

    static public function getCanonical(): string
    {
        return self::CANONICAL;
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
     * @return MarkupPath|null
     */
    private function getPage(): ?MarkupPath
    {
        $resource = $this->getResource();
        if ($resource instanceof MarkupPath) {
            return $resource;
        }
        return null;
    }

    /**
     * In case of internal error, the path is returned
     */
    public function getUrlPathFromType(string $urlType): string
    {

        $page = $this->getResource();
        $pagePath = $page->getPathObject()->toAbsoluteId();
        if ((!$page instanceof MarkupPath)) {
            $message = "The url path is only for page resources";
            LogUtility::internalError($message, $this->getCanonical());
            return $pagePath;
        }


        switch ($urlType) {
            case PageUrlType::CONF_VALUE_PAGE_PATH:
                // the default
                return $pagePath;
            case PageUrlType::CONF_VALUE_PERMANENT_PAGE_PATH:
                return $this->toPermanentUrlPath($pagePath);
            case PageUrlType::CONF_VALUE_CANONICAL_PATH:
                try {
                    return Canonical::createForPage($page)->getValueOrDefault()->toAbsoluteId();
                } catch (ExceptionNotFound $e) {
                    // no canonical, path as default
                    return $pagePath;
                }
            case PageUrlType::CONF_VALUE_PERMANENT_CANONICAL_PATH:
                return $this->toPermanentUrlPath($page->getCanonicalOrDefault());
            case PageUrlType::CONF_VALUE_SLUG:
                return $this->toPermanentUrlPath($page->getSlugOrDefault());
            case PageUrlType::CONF_VALUE_HIERARCHICAL_SLUG:
                $urlPath = $page->getSlugOrDefault();
                $parentPage = $page;
                while (true) {
                    try {
                        $parentPage = $parentPage->getParent();
                    } catch (ExceptionNotFound $e) {
                        break;
                    }
                    if (!$parentPage->isRootHomePage()) {
                        try {
                            $urlPath = Slug::toSlugPath($parentPage->getNameOrDefault()) . $urlPath;
                        } catch (ExceptionNull $e) {
                            throw new \RuntimeException("The default name of the page (" . $parentPage . ") should not be empty.");
                        }
                    }
                }
                return $this->toPermanentUrlPath($urlPath);
            case PageUrlType::CONF_VALUE_HOMED_SLUG:
                $urlPath = $page->getSlugOrDefault();
                try {
                    $parentPage = $page->getParent();
                    if (!$parentPage->isRootHomePage()) {
                        try {
                            $urlPath = Slug::toSlugPath($parentPage->getNameOrDefault()) . $urlPath;
                        } catch (ExceptionNull $e) {
                            throw new \RuntimeException("The default name of the page (" . $parentPage . ") should not be empty.");
                        }
                    }
                } catch (ExceptionNotFound $e) {
                    // no parent page
                }
                return $this->toPermanentUrlPath($urlPath);
            default:
                $message = "The url type ($urlType) is unknown and was unexpected";
                LogUtility::internalError($message, self::PROPERTY_NAME);
                return $pagePath;

        }
    }

    static public function isOnForm(): bool
    {
        return true;
    }

    public function getValueOrDefaultAsWikiId(): string
    {
        return WikiPath::removeRootSepIfPresent($this->getValueOrDefault());
    }

}
