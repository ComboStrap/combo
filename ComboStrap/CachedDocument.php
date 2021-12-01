<?php


namespace ComboStrap;

/**
 * Interface CompiledDocument
 * @package ComboStrap
 * A document that is generated and where
 * the output may be stored in the cache
 */
interface CachedDocument
{


    /**
     * Execute the transformation process
     *   * from one format to another
     *   * optimization
     * And stores the output in the {@link PageCompilerDocument::getCacheFile() cache file}
     * if the cache is enabled
     * @return mixed
     */
    public function process();

    /**
     * @return File - the file where the generated content is stored
     */
    public function getCacheFile(): File;

    /**
     * @return bool true if the {@link CachedDocument::process() compilation} should occurs
     *
     * For instance:
     *   * if the output cache file is stale: The most obvious reason would be that the source file has changed but change in configuration may also stale output file
     *   * True if the cache page does not exist
     *   * True if the cache is not allowed
     *
     */
    public function shouldProcess(): bool;

    /**
     * @return mixed - a simple method to get the cache content
     * or generate it
     */
    public function getOrProcessContent();

    /**
     * @return string - the file extension / format
     * For instance:
     *   * "xhtml" for an html document
     *   * "svg" for an svg document
     */
    function getExtension(): string;

}
