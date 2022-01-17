<?php


namespace ComboStrap;


use dokuwiki\Cache\CacheInstructions;

class InstructionsDocument extends PageCompilerDocument
{

    private $path;

    /**
     * @var CacheInstructionsByLogicalKey|CacheInstructions
     */
    private $cache;



    /**
     * InstructionsDocument constructor.
     * @var Page $page
     */
    public function __construct(Page $page)
    {
        parent::__construct($page);

        if ($this->getPage()->isSlot()) {

            /**
             * @noinspection PhpIncompatibleReturnTypeInspection
             * No inspection because this is not the same object interface
             * because we can't override the constructor of {@link CacheInstructions}
             * but they should used the same interface (ie manipulate array data)
             */
            $this->cache = new CacheInstructionsByLogicalKey($page);

        } else {

            $path = $page->getPath();
            $id = $path->getDokuwikiId();
            /**
             * The local path is part of the key cache and should be the same
             * than dokuwiki
             *
             * For whatever reason, Dokuwiki uses:
             *   * `/` as separator on Windows
             *   * and Windows short path `GERARD~1` not gerardnico
             * See {@link wikiFN()}
             * There is also a cache in the function
             *
             * We can't use our {@link Path} class because the
             * path is on windows format without the short path format
             */
            $localFile = wikiFN($id);
            $this->cache = new CacheInstructions($id, $localFile);

        }
        $this->path = LocalPath::createFromPath($this->cache->cache);
    }

    function getExtension(): string
    {
        return "i";
    }

    function process(): CachedDocument
    {

        if (!$this->shouldProcess()) {
            return $this;
        }

        /**
         * The id is not passed while on handler
         * Therefore the global id should be set
         */
        global $ID;
        $oldId = $ID;
        $ID = $this->getPage()->getPath()->getDokuwikiId();

        /**
         * Get the instructions
         * Adapted from {@link p_cached_instructions()}
         */
        $text = $this->getPage()->getTextContent();
        $instructions = p_get_instructions($text);

        // close restore ID
        $ID = $oldId;

        if (!$this->cache->storeCache($instructions)) {
            $message = 'Unable to save the parsed instructions cache file. Hint: disk full; file permissions; safe_mode setting ?';
            LogUtility::msg($message, LogUtility::LVL_MSG_ERROR);
            $this->setContent([]);
            return $this;
        }

        // the parsing may have set new metadata values
        $this->getPage()->rebuild();

        $this->setContent($instructions);
        return $this;

    }



    public function getFileContent()
    {
        /**
         * The data is {@link serialize serialized} for instructions
         * we can't use the parent method that retrieve text by default
         */
        return $this->cache->retrieveCache();

    }


    function getRendererName(): string
    {
        return "i";
    }

    public function getCachePath(): Path
    {
        return $this->path;
    }

    public function shouldProcess(): bool
    {

        global $ID;
        $keep = $ID;
        try {
            $ID = $this->getPage()->getDokuwikiId();
            return $this->cache->useCache() === false;
        } finally {
            $ID = $keep;
        }
    }


    public function storeContent($content)
    {
        $this->cache->storeCache($content);
        return $this;
    }
}
