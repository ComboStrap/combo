<?php


namespace ComboStrap;


class HtmlDocument extends OutputDocument
{
    const extension = "xhtml";

    public function __construct($page)
    {
        parent::__construct($page);
        if ($page->isStrapSideSlot()) {

            /**
             * Logical cache based on scope (ie logical id) is the scope and part of the key
             *
             * We don't use {@link CacheRenderer}
             * because the cache key is the physical file
             */
            $this->cache = new CacheByLogicalKey($page, $this->getExtension());

        }
    }


    function getExtension(): string
    {
        return self::extension;
    }

    function getRendererName(): string
    {
        return self::extension;
    }

    public function shouldCompile(): bool
    {
        if (!Site::isHtmlRenderCacheOn()) {
            return true;
        }
        return parent::shouldCompile();
    }


}
