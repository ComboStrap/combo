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

    public function getOrProcessContent()
    {

        $debug = "";
        $logicalId = $this->getPage()->getLogicalId();
        $scope = $this->getPage()->getScope();

        if ($this->shouldProcess()) {
            $this->process();

            if (
                (Site::debugIsOn() || PluginUtility::isDevOrTest())
            ) {
                /**
                 * Due to the instructions parsing, they may have been changed
                 * by a component
                 */
                $debug = "<div id=\"{$this->getPage()->getCacheHtmlId()}\" style=\"display:none;\" data-logical-Id=\"$logicalId\" data-scope=\"$scope\" data-cache-op=\"created\" data-cache-file=\"{$this->getCachePath()->toAbsolutePath()->toString()}\"></div>";
            }

        } else {


            if (
                (Site::debugIsOn() || PluginUtility::isDevOrTest())
            ) {
                $debug = "<div id=\"{$this->getPage()->getCacheHtmlId()}\" style=\"display:none;\" data-logical-Id=\"$logicalId\" data-scope=\"$scope\" data-cache-op=\"forbidden\"></div>" ;
            }

        }
        return $debug . $this->getContent();


    }


}
