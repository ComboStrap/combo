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

    public function shouldProcess(): bool
    {
        if (!Site::isHtmlRenderCacheOn()) {
            return true;
        }
        return parent::shouldProcess();
    }

    public function getOrProcessContent(): string
    {

        $debug = "";


        if ($this->shouldProcess()) {
            $this->process();

            /**
             * Scope may change during processing
             * And therefore logical id also
             */
            $scope = $this->getPage()->getScope();
            $logicalId = $this->getPage()->getLogicalId();

            if (
            (Site::debugIsOn() || PluginUtility::isDevOrTest())
            ) {
                $cachePath = $this->getCachePath()->toAbsolutePath()->toString();
                $debug = "<div id=\"{$this->getPage()->getCacheHtmlId()}\" style=\"display:none;\" data-logical-Id=\"$logicalId\" data-scope=\"$scope\" data-cache-op=\"created\" data-cache-file=\"{$cachePath}\"></div>";
            }

        } else {

            $scope = $this->getPage()->getScope();
            $logicalId = $this->getPage()->getLogicalId();
            if (
            (Site::debugIsOn() || PluginUtility::isDevOrTest())
            ) {
                $debug = "<div id=\"{$this->getPage()->getCacheHtmlId()}\" style=\"display:none;\" data-logical-Id=\"$logicalId\" data-scope=\"$scope\" data-cache-op=\"forbidden\"></div>";
            }

        }
        return $debug . $this->getContent();


    }


}
