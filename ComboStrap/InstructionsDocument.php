<?php


namespace ComboStrap;


use dokuwiki\Cache\CacheInstructions;

class InstructionsDocument extends Document
{

    private $file;

    /**
     * @var CacheInstructionsByLogicalKey|CacheInstructions
     */
    private $cache;


    /**
     * InstructionsDocument constructor.
     */
    public function __construct($page)
    {
        parent::__construct($page);

        if ($this->getPage()->isStrapSideSlot()) {

            /**
             * @noinspection PhpIncompatibleReturnTypeInspection
             * No inspection because this is not the same object interface
             * because we can't overide the constructor of {@link CacheInstructions}
             * but they should used the same interface (ie manipulate array data)
             */
            $this->cache = new CacheInstructionsByLogicalKey($page);

        } else {

            $this->cache = new CacheInstructions($page->getDokuwikiId(), $page->getAbsoluteFileSystemPath());

        }
        $this->file = File::createFromPath($this->cache->cache);
    }

    function getExtension(): string
    {
        return "i";
    }

    function compile()
    {

        if (!$this->shouldCompile()) {
            return $this->getFileContent();
        }

        /**
         * The id is not passed while on handler
         * Therefore the global id should be set
         */
        global $ID;
        $oldId = $ID;
        $ID = $this->getPage()->getDokuwikiId();

        /**
         * Get the instructions
         * Adapted from {@link p_cached_instructions()}
         */
        $instructions = p_get_instructions($this->getPage()->getTextContent());

        // close restore ID
        $ID = $oldId;

        if (!$this->cache->storeCache($instructions)) {
            $message = 'Unable to save the parsed instructions cache file. Hint: disk full; file permissions; safe_mode setting ?';
            LogUtility::msg($message, LogUtility::LVL_MSG_ERROR);
            return [];
        }

        // the parsing may have set new metadata values
        $this->getPage()->rebuild();

        return $instructions;

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

    public function getFile(): File
    {
        return $this->file;
    }

    public function shouldCompile(): bool
    {
        return $this->cache->useCache() === false;
    }


}
