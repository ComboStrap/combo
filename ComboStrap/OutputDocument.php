<?php


namespace ComboStrap;


use dokuwiki\Cache\CacheRenderer;

abstract class OutputDocument extends Document
{


    /**
     * @var CacheRenderer
     */
    protected $cache;

    /**
     * @var File Cache/Output file
     */
    private $file;

    /**
     * OutputDocument constructor.
     */
    public function __construct($page)
    {
        parent::__construct($page);
        $this->cache = new CacheRenderer($this->getPage()->getDokuwikiId(), $this->getPage()->getAbsoluteFileSystemPath(), $this->getRendererName());
        $this->file = File::createFromPath($this->cache->cache);
    }

    function compile()
    {

        if (!$this->getPage()->exists()) {
            return "";
        }

        if (!$this->isStale() && $this->getFile()->exists() && PluginUtility::isDevOrTest()) {
            LogUtility::msg("The file ({$this->getExtension()}) is not stale and exists, compilation is not needed", LogUtility::LVL_MSG_ERROR);
        }

        /**
         * Global ID is the ID of the HTTP request
         * (ie the page id)
         * We change it for the run
         * And restore it at the end
         */
        global $ID;
        $keep = $ID;
        $ID = $this->getPage()->getDokuwikiId();

        /**
         * The code below is adapted from {@link p_cached_output()}
         * $ret = p_cached_output($file, 'xhtml', $pageid);
         */

        $instructions = $this->getPage()->getInstructionsDocument()->getOrGenerateContent();


        /**
         * Due to the instructions parsing, they may have been changed
         * by a component
         */
        $logicalId = $this->getPage()->getLogicalId();
        $scope = $this->getPage()->getScope();

        /**
         * Render
         */
        $result = p_render($this->getRendererName(), $instructions, $info);

        // restore ID
        $ID = $keep;

        // Store
        if ($info['cache'] && $this->cache->storeCache($result)) {
            if (
                (Site::debugIsOn() || PluginUtility::isDevOrTest())
                && $this->getExtension() === HtmlDocument::extension
            ) {
                $result = "<div id=\"{$this->getPage()->getCacheHtmlId()}\" style=\"display:none;\" data-logical-Id=\"$logicalId\" data-scope=\"$scope\" data-cache-op=\"created\" data-cache-file=\"{$this->getFile()->getAbsoluteFileSystemPath()}\"></div>" . $result;
            }
        } else {
            $this->cache->removeCache(); // try to delete cachefile
            if (
                (Site::debugIsOn() || PluginUtility::isDevOrTest())
                && $this->getExtension() === HtmlDocument::extension
            ) {
                $result = "<div id=\"{$this->getPage()->getCacheHtmlId()}\" style=\"display:none;\" data-logical-Id=\"$logicalId\" data-scope=\"$scope\" data-cache-op=\"forbidden\"></div>" . $result;
            }
        }

        return $result;

    }

    public function getFile(): File
    {
        return $this->file;
    }

    public function isStale(): bool
    {
        return $this->cache->useCache() === false;
    }


}
