<?php

namespace ComboStrap;

class RouterRedirection
{


    /**
     * The combostrap canonical where the doc is
     */
    public const PERMANENT_REDIRECT_CANONICAL = "permanent:redirect";
    /**
     * For permanent, see https://developers.google.com/search/docs/advanced/crawling/301-redirects
     * was `Http` (301)
     */
    public const REDIRECT_PERMANENT_METHOD = 'permanent';
    /**
     * 404 (See other) (when best page name is calculated)
     */
    public const REDIRECT_NOTFOUND_METHOD = "notfound";

    /**
     * Transparent (Just setting another id without HTTP 3xx)
     */
    const REDIRECT_TRANSPARENT_METHOD = 'transparent';

    /**
     * Extended Permalink (abbreviated page id at the end)
     */
    public const TARGET_ORIGIN_PERMALINK_EXTENDED = "extendedPermalink";
    /**
     * Named Permalink (canonical)
     */
    public const TARGET_ORIGIN_CANONICAL = 'canonical';
    public const TARGET_ORIGIN_START_PAGE = 'startPage';
    /**
     * Identifier Permalink (full page id)
     */
    public const TARGET_ORIGIN_PERMALINK = "permalink";
    public const TARGET_ORIGIN_SEARCH_ENGINE = 'searchEngine';
    public const TARGET_ORIGIN_PAGE_RULES = 'pageRules';
    public const TARGET_ORIGIN_ALIAS = 'alias';
    public const TARGET_ORIGIN_BEST_END_PAGE_NAME = 'bestEndPageName';
    public const TARGET_ORIGIN_BEST_PAGE_NAME = 'bestPageName';
    public const TARGET_ORIGIN_BEST_NAMESPACE = 'bestNamespace';
    public const TARGET_ORIGIN_WELL_KNOWN = 'well-known';
    public const TARGET_ORIGIN_SHADOW_BANNED = "shadowBanned";


    private RouterRedirectionBuilder $routerBuilder;

    public function __construct(RouterRedirectionBuilder $routerRedirectionBuilder)
    {
        $this->routerBuilder=$routerRedirectionBuilder;
    }

    public function getOrigin(): string
    {
        return $this->routerBuilder->getOrigin();
    }

    public function getType(): string
    {
        return $this->routerBuilder->getType();
    }

    public function getTargetAsString(): string
    {
        $markupPath = $this->getTargetMarkupPath();
        if ($markupPath !== null){
            return $markupPath->toAbsoluteId();
        }

        $targetUrl = $this->getTargetUrl();
        if($targetUrl!==null){
            return $targetUrl->toAbsoluteUrlString();
        }
        return "";

    }

    public function getTargetUrl(): ?Web\Url
    {
        return $this->routerBuilder->getTargetUrl();
    }

    public function getTargetMarkupPath(): ?MarkupPath
    {
        return $this->routerBuilder->getTargetMarkupPath();
    }


}
