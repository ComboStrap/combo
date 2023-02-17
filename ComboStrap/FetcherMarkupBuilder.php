<?php

namespace ComboStrap;

use dokuwiki\Cache\CacheInstructions;
use dokuwiki\Cache\CacheRenderer;

/**
 * Builder class for {@link FetcherMarkup}
 * Php does not allow for nested class
 * We therefore need to get the builder class out.
 *
 * We extends just to get access to protected members class
 * and to mimic a builder pattern
 *
 * @internal
 */
class FetcherMarkupBuilder extends FetcherMarkup
{

    protected ?string $markupString;
    protected ?Path $markupSourcePath = null;
    protected WikiPath $requestedContextPath;
    protected Mime $mime;
    protected bool $removeRootBlockElement = false;
    protected string $rendererName = MarkupRenderer::DEFAULT_RENDERER;


    public function __construct()
    {
    }

    /**
     * @param string $markupString - the markup is a string format
     * @return FetcherMarkupBuilder
     */
    public function setRequestedMarkupString(string $markupString): FetcherMarkupBuilder
    {
        $this->markupString = $markupString;
        return $this;
    }

    /**
     * Delete the first P instructions
     * (The parser will add a p block element)
     * @param bool $b
     * @return $this
     */
    public function setDeleteRootBlockElement(bool $b): FetcherMarkupBuilder
    {
        $this->removeRootBlockElement = $b;
        return $this;
    }

    /**
     * The source where the markup is stored (null if dynamic)
     * It's a duplicate of {@link FetcherMarkup::setSourcePath()}
     * @param ?Path $executingPath
     * @return $this
     */
    public function setRequestedExecutingPath(?Path $executingPath): FetcherMarkupBuilder
    {

        if ($executingPath == null) {
            return $this;
        }

        try {
            /**
             * Normalize to wiki path if possible
             * Why ?
             * Because the parent path may be used a {@link MarkupCacheDependencies::getValueForKey()  cache key}
             * and they will have different value if the path type is different
             * * With {@link LocalPath Local Path}: `C:\Users\gerardnico\AppData\Local\Temp\dwtests-1676386702.9751\data\pages\ns_without_scope`
             * * With {@link WikiPath Wiki Path}: `ns_without_scope`
             * It will then make the cache file path different (ie the md5 output key is the file name)
             */
            $this->markupSourcePath = $executingPath->toWikiPath();
        } catch (ExceptionCast $e) {
            $this->markupSourcePath = $executingPath;
        }
        return $this;

    }

    /**
     * The page context in which this fragment was requested
     *
     * Note that it may or may be not the main requested markup page.
     * You can have a markup rendering inside another markup rendering.
     *
     * @param WikiPath $contextPath
     * @return $this
     */
    public function setRequestedContextPath(WikiPath $contextPath): FetcherMarkupBuilder
    {
        $this->requestedContextPath = $contextPath;
        return $this;
    }

    /**
     */
    public function setRequestedMime(Mime $mime): FetcherMarkupBuilder
    {
        $this->mime = $mime;
        return $this;
    }

    public function setRequestedMimeToXhtml(): FetcherMarkupBuilder
    {
        try {
            return $this->setRequestedMime(Mime::createFromExtension("xhtml"));
        } catch (ExceptionNotFound $e) {
            throw new ExceptionRuntime("Internal error", 0, $e);
        }

    }


    public function setRequestedMimeToInstructions(): FetcherMarkupBuilder
    {
        try {
            $this->setRequestedMime(Mime::createFromExtension(MarkupRenderer::INSTRUCTION_EXTENSION));
        } catch (ExceptionNotFound $e) {
            throw new ExceptionRuntime("Internal error: the mime is internal and should be good");
        }
        return $this;

    }


    public function build(): FetcherMarkup
    {

        if ($this->markupSourcePath === null && $this->markupString === null) {
            throw new ExceptionRuntimeInternal("A markup source path or a markup string should be given");
        }
        if (!isset($this->mime)) {
            throw new ExceptionRuntimeInternal("A mime is mandatory");
        }
        if (!isset($this->requestedContextPath)) {
            throw new ExceptionRuntimeInternal("A context path is mandatory");
        }

        $newFetcherMarkup = new FetcherMarkup();
        $newFetcherMarkup->requestedContextPath = $this->requestedContextPath;
        if (isset($this->markupString)) {
            $newFetcherMarkup->markupString = $this->markupString;
        }
        $newFetcherMarkup->markupSourcePath = $this->markupSourcePath;
        $newFetcherMarkup->mime = $this->mime;
        $newFetcherMarkup->removeRootBlockElement = $this->removeRootBlockElement;
        $newFetcherMarkup->rendererName = $this->rendererName;

        /**
         * We build the cache dependencies even if there is no source markup path (therefore no cache store)
         * (Why ? for test purpose, where we want to check if the dependencies was applied)
         * !!! Attention, the build of the dependencies should happen after that the markup source path is set !!!
         */
        $newFetcherMarkup->cacheDependencies = MarkupCacheDependencies::create($newFetcherMarkup);

        /**
         * The cache object depends on the running request
         * We build it then just
         *
         * A request is also send by dokuwiki to check the cache validity
         *
         */
        if ($this->markupSourcePath != null) {


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
             * We can't use our {@link Path} class to be compatible because the
             * path is on windows format without the short path format
             */
            try {
                $wikiId = $this->markupSourcePath->toWikiPath()->getWikiId();
                $localFile = wikiFN($wikiId);
            } catch (ExceptionCast $e) {
                $wikiId = $this->markupSourcePath->toQualifiedId();
                try {
                    $localFile = $this->markupSourcePath->toLocalPath();
                } catch (ExceptionCast $e) {
                    throw new ExceptionRuntimeInternal("The source path ({$this->markupSourcePath}) is not supported as markup source path.", $e);
                }
            }

            /**
             * Cache by extension (ie type)
             */
            $extension = $this->mime->getExtension();
            switch ($extension) {
                case MarkupRenderer::INSTRUCTION_EXTENSION:
                    $newFetcherMarkup->contentCache = new CacheInstructions($wikiId, $localFile);
                    break;
                default:
                    $newFetcherMarkup->contentCache = new CacheRenderer($wikiId, $localFile, $extension);
                    $newFetcherMarkup->cacheDependencies->rerouteCacheDestination($newFetcherMarkup->contentCache);
                    break;
            }
        }

        return $newFetcherMarkup;

    }

    public function setRequestedMimeToMetadata(): FetcherMarkupBuilder
    {
        try {
            return $this->setRequestedMime(Mime::createFromExtension(MarkupRenderer::METADATA_EXTENSION));
        } catch (ExceptionNotFound $e) {
            throw new ExceptionRuntime("Internal error", 0, $e);
        }
    }

    public function setRequestedRenderer(string $rendererName): FetcherMarkupBuilder
    {
        $this->rendererName = $rendererName;
        return $this;
    }

    public function setRequestedContextPathWithDefault(): FetcherMarkupBuilder
    {
        $executionContext = ExecutionContext::getActualOrCreateFromEnv();
        try {
            // do we have an executing handler
            $this->requestedContextPath = $executionContext
                ->getExecutingMarkupHandler()
                ->getRequestedExecutingPath()
                ->toWikiPath();
        } catch (ExceptionCast|ExceptionNotFound $e) {
            $this->requestedContextPath = $executionContext->getConfig()->getDefaultContextPath();
        }
        return $this;
    }


}
