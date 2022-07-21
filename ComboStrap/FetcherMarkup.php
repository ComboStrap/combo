<?php


namespace ComboStrap;


use dokuwiki\Cache\CacheInstructions;
use dokuwiki\Cache\CacheParser;
use dokuwiki\Cache\CacheRenderer;
use Exception;
use http\Exception\RuntimeException;

/**
 * A class that renders markup files.
 * It does not output any full page (HTML document) but only fragment.
 *
 * This is not really a {@link IFetcher function} because it should not be called
 * from the outside but to be able to use the {@link FetcherCache} we need to
 * (Url dependent)
 *
 */
class FetcherMarkup extends IFetcherAbs implements IFetcherSource, IFetcherString
{

    use FetcherTraitWikiPath;

    const XHTML_MODE = "xhtml";
    const MAX_CACHE_AGE = 999999;

    const CANONICAL = "markup-fragment-fetcher";

    /**
     * @var CacheRenderer cache file
     */
    protected CacheParser $cache;

    /**
     * @var CacheParser
     */
    private $snippetCache;


    private Mime $mime;
    private bool $cacheAfterRendering = true;
    private string $renderer;
    private MarkupCacheDependencies $cacheDependencies;
    private bool $objectHasBeenBuild = false;
    private WikiRequest $wikiRequest;
    private bool $closed = false;


    private bool $removeRootBlockElement = false;
    private string $requestedRendererName = MarkupRenderer::DEFAULT_RENDERER;


    public static function createPageFragmentFetcherFromId(string $mainId): FetcherMarkup
    {
        $page = WikiPath::createPagePathFromId($mainId);
        return FetcherMarkup::createPageFragmentFetcherFromPath($page);
    }

    /**
     * @throws ExceptionBadArgument
     */
    public static function createPageFragmentFetcherFromUrl(Url $fetchUrl): FetcherMarkup
    {
        $pageFragment = new FetcherMarkup();
        $pageFragment->buildFromUrl($fetchUrl);
        return $pageFragment;
    }

    /**
     *
     */
    public static function createPageFragmentFetcherFromPath(Path $path): FetcherMarkup
    {
        return (new FetcherMarkup())
            ->setRequestedPath($path);
    }


    /**
     *
     * @param Url|null $url
     * @return Url
     *
     * Note: The fetch url is the {@link FetcherCache keyCache}
     * @throws ExceptionNotFound
     */
    function getFetchUrl(Url $url = null): Url
    {
        /**
         * Overwrite default fetcher endpoint
         * that is {@link UrlEndpoint::createFetchUrl()}
         */
        $url = UrlEndpoint::createDokuUrl();
        $url = parent::getFetchUrl($url);
        $this->addLocalPathParametersToFetchUrl($url, DokuwikiId::DOKUWIKI_ID_ATTRIBUTE);;
        return $url;

    }


    /**
     *
     * It's a duplicate of {@link FetcherMarkup::setSourcePath()}
     * @param Path $path
     * @return $this
     */
    public function setRequestedPath(Path $path): FetcherMarkup
    {

        $this->checkNoSetAfterBuild();
        try {
            $dokuPath = WikiPath::createFromPathObject($path);
        } catch (ExceptionBadArgument $e) {
            throw new ExceptionRuntimeInternal("It should be a wiki path", self::CANONICAL, 1, $e);
        }
        $this->setSourcePath($dokuPath);
        return $this;

    }

    /**
     * @return Mime
     */
    public function getMime(): Mime
    {
        if (isset($this->mime)) {
            return $this->mime;
        }

        // XHTML default
        try {
            return Mime::createFromExtension(self::XHTML_MODE);
        } catch (ExceptionNotFound $e) {
            // should not happen
            throw new RuntimeException("Internal error: The XHTML mime was not found.", self::CANONICAL, 1, $e);
        }
    }


    public function shouldProcess(): bool
    {

        /**
         * The cache is stored by requested page scope
         *
         * We set the environment because
         * {@link CacheParser::useCache()} may call a parsing of the markup fragment
         * And the global environment are not always passed
         * in all actions and is needed to log the {@link  CacheResult cache
         * result}
         */
        $this->buildObjectAndEnvironmentIfNeeded();


        /**
         * Use cache should be always called because it trigger
         * the event coupled to the cache (ie PARSER_CACHE_USE)
         */
        $depends['age'] = $this->getCacheAge();
        $useCache = $this->cache->useCache($depends);
        return ($useCache === false);

    }


    public
    function storeSnippets()
    {

        $slotId = $this->getSourcePath()->getWikiId();

        /**
         * Snippet
         */
        $snippetManager = PluginUtility::getSnippetManager();
        $jsonDecodeSnippets = $snippetManager->getJsonArrayFromSlotSnippets($slotId);

        /**
         * Cache file
         * Using a cache parser, set the page id and will trigger
         * the parser cache use event in order to log/report the cache usage
         * At {@link action_plugin_combo_cache::createCacheReport()}
         */
        $snippetCache = $this->getSnippetCacheStore();


        if ($jsonDecodeSnippets !== null) {
            $data1 = json_encode($jsonDecodeSnippets);
            $snippetCache->storeCache($data1);
        } else {
            $snippetCache->removeCache();
        }

    }

    /**
     * @return Snippet[]
     */
    public
    function loadSnippets(): array
    {
        $data = $this->getSnippetCacheStore()->retrieveCache();
        $nativeSnippets = [];
        if (!empty($data)) {
            $jsonDecodeSnippets = json_decode($data, true);
            foreach ($jsonDecodeSnippets as $snippet) {
                try {
                    $nativeSnippets[] = Snippet::createFromJson($snippet);
                } catch (ExceptionCompile $e) {
                    LogUtility::msg("The snippet json array cannot be build into a snippet object. " . $e->getMessage());
                }

            }
        }
        return $nativeSnippets;

    }

    private
    function removeSnippets()
    {
        $snippetCacheFile = $this->getSnippetCacheStore()->cache;
        if ($snippetCacheFile !== null) {
            if (file_exists($snippetCacheFile)) {
                unlink($snippetCacheFile);
            }
        }
    }

    /**
     * Cache file
     * Using a cache parser, set the page id and will trigger
     * the parser cache use event in order to log/report the cache usage
     * At {@link action_plugin_combo_cache::createCacheReport()}
     */
    public
    function getSnippetCacheStore(): CacheParser
    {
        if ($this->snippetCache !== null) {
            return $this->snippetCache;
        }

        $this->buildObjectAndEnvironmentIfNeeded();

        $id = $this->getRequestedPath()->getWikiId();
        $slotLocalFilePath = $this->getRequestedPath()
            ->toLocalPath()
            ->toAbsolutePath()
            ->toPathString();
        $this->snippetCache = new CacheParser($id, $slotLocalFilePath, "snippet.json");

        /**
         * Snippet.json is data dependent
         *
         * For instance, the carrousel may add glide or grid as snippet. It depends on the the number of backlinks.
         *
         * Therefore the output should be unique by rendered slot
         * Therefore we reroute (recalculate the cache key to the same than the html file)
         */
        $this->cacheDependencies->rerouteCacheDestination($this->snippetCache);
        return $this->snippetCache;
    }

    public
    function getDependencies(): MarkupCacheDependencies
    {
        return $this->cacheDependencies;
    }


    public
    function getDependenciesCacheStore(): CacheParser
    {
        return $this->cacheDependencies->getDependenciesCacheStore();
    }

    /**
     *
     */
    function getFetchPath(): Path
    {

        $this->buildObjectAndEnvironmentIfNeeded();

        if (!$this->shouldProcess()) {
            return $this->getCachePath();
        }

        /**
         * Process
         */
        $path = $this->getRequestedPath();
        try {
            $markup = FileSystems::getContent($path);
        } catch (ExceptionNotFound $e) {
            $markup = "";
            LogUtility::error("The path ($path) does not exist, we have set the markup to the empty string during rendering", self::CANONICAL);
        }

        $extension = $this->getMime()->getExtension();
        switch ($extension) {
            case MarkupRenderer::INSTRUCTION_EXTENSION:
                $markupRenderer = MarkupRenderer::createFromMarkup($markup)
                    ->setRequestedMimeToInstruction()
                    ->setDeleteRootBlockElement($this->removeRootBlockElement);
                try {
                    $content = $markupRenderer->getOutput();
                } finally {
                    $markupRenderer->close();
                }
                break;
            default:
                $instructionsFetcher = FetcherMarkup::createPageFragmentFetcherFromPath($this->getRequestedPath())
                    ->setRequestedMimeToInstructions();
                try {
                    $instructions = $instructionsFetcher->getFetchPathAsInstructionsArray();
                } finally {
                    $instructionsFetcher->close();
                }
                $markupRenderer = MarkupRenderer::createFromInstructions($instructions)
                    ->setRendererName($this->getRequestedRendererNameOrDefault())
                    ->setRequestedMime($this->getMime());
                try {
                    $content = $markupRenderer->getOutput();
                } finally {
                    $markupRenderer->close();
                }
                $this->cacheAfterRendering = $markupRenderer->getCacheAfterRendering();
        }


        /**
         * Snippets and dependencies if XHTML
         * (after processing as they can be added at runtime)
         */
        if ($this->getMime()->getExtension() === self::XHTML_MODE) {

            /**
             * Reroute the cache output by runtime dependencies
             * set during processing
             */
            $this->cacheDependencies->rerouteCacheDestination($this->cache);

            /**
             * We make the Snippet store to Html store an atomic operation
             *
             * Why ? Because if the rendering of the page is stopped,
             * the cache of the HTML page may be stored but not the cache of the snippets
             * leading to a bad page because the next rendering will see then no snippets.
             */
            try {
                $this->storeSnippets();
            } catch (Exception $e) {
                // if any write os exception
                LogUtility::msg("Error while storing the xhtml content: {$e->getMessage()}");
                $this->removeSnippets();
            }

            /**
             * Cache output dependencies
             */
            $this->cacheDependencies->storeDependencies();

        }

        /**
         * We store always the output in the cache
         * if the cache is not on, the file is just overwritten
         */
        $this->cache->storeCache($content);

        /**
         * The cache path may have change due to the cache key rerouting
         * We should there always use the {@link FetcherMarkup::getCachePath()}
         * as fetch path
         */
        return $this->getCachePath();

    }

    function getBuster(): string
    {
        // no buster
        return "";
    }

    public
    function getFetcherName(): string
    {
        return "page-fragment";
    }


    /**
     */
    public function setRequestedMime(Mime $mime): FetcherMarkup
    {
        $this->checkNoSetAfterBuild();
        $this->mime = $mime;
        return $this;
    }

    public function setRequestedMimeToXhtml(): FetcherMarkup
    {
        try {
            return $this->setRequestedMime(Mime::createFromExtension("xhtml"));
        } catch (ExceptionNotFound $e) {
            throw new RuntimeException("Internal error", 0, $e);
        }

    }

    private function getCacheAge(): int
    {

        $extension = $this->getMime()->getExtension();
        switch ($extension) {
            case self::XHTML_MODE:
                if (!Site::isHtmlRenderCacheOn()) {
                    return 0;
                }
                break;
            case MarkupRenderer::INSTRUCTION_EXTENSION:
                return self::MAX_CACHE_AGE;
        }
        try {
            $requestedCache = $this->getRequestedCache();
        } catch (ExceptionNotFound $e) {
            $requestedCache = IFetcherAbs::RECACHE_VALUE;
        }
        $cacheAge = $this->getCacheMaxAgeInSec($requestedCache);
        return $this->cacheAfterRendering ? $cacheAge : 0;

    }


    /**
     * Build object (mostly the cache)
     * Setter should not be used after this
     * function has been called
     */
    private function buildObjectAndEnvironmentIfNeeded(): void
    {

        if ($this->objectHasBeenBuild === true) {
            if ($this->closed) {
                /**
                 * Just a check
                 */
                throw new ExceptionRuntimeInternal("The fetcher page fragment has already been closed and cannnot be used anymore");
            }
            return;
        }
        $this->objectHasBeenBuild = true;

        /**
         * The cache object depends on the running request
         * We build it then just
         *
         * A request is also send by dokuwiki to check the cache validity
         * We build it only once
         *
         * You need to close it with the {@link FetcherMarkup::close()}
         */
        $wikiId = $this->getRequestedPath()->getWikiId();
        $this->wikiRequest = WikiRequest::createRequestOrSubRequest($wikiId);



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
        $localFile = wikiFN($wikiId);

        /**
         * Cache by extension (ie type)
         */
        $extension = $this->getMime()->getExtension();
        switch ($extension) {
            case MarkupRenderer::INSTRUCTION_EXTENSION:
                $this->cache = new CacheInstructions($wikiId, $localFile);
                break;
            default:
                $this->cache = new CacheRenderer($wikiId, $localFile, $extension);

                $this->cacheDependencies = CacheManager::getOrCreateFromRequestedPath()
                    ->getCacheDependenciesForPath($this->getRequestedPath());
                $this->cacheDependencies->rerouteCacheDestination($this->cache);
                break;
        }


    }

    public function __toString()
    {
        return $this->getRequestedPath() . $this->getMime()->toString();
    }


    /**
     * @throws ExceptionBadArgument
     * @throws ExceptionBadSyntax
     * @throws ExceptionNotExists
     * @throws ExceptionNotFound
     */
    public function buildFromTagAttributes(TagAttributes $tagAttributes): FetcherMarkup
    {
        parent::buildFromTagAttributes($tagAttributes);
        $this->buildOriginalPathFromTagAttributes($tagAttributes);
        return $this;
    }


    public function setRequestedMimeToInstructions(): FetcherMarkup
    {
        try {
            $this->setRequestedMime(Mime::createFromExtension(MarkupRenderer::INSTRUCTION_EXTENSION));
        } catch (ExceptionNotFound $e) {
            throw new RuntimeException("Internal error: the mime is internal and should be good");
        }
        return $this;

    }

    /**
     * Utility function that returns the fetch path as instructions array
     * (ie un-serialized)
     *
     */
    public function getFetchPathAsInstructionsArray()
    {
        try {
            $contents = FileSystems::getContent($this->getFetchPath());
        } catch (ExceptionNotFound $e) {
            throw new ExceptionRuntimeInternal("The fetch path was not found but should be present", self::CANONICAL, 1, $e);
        }
        return !empty($contents) ? unserialize($contents) : array();
    }


    public function getCachePath(): LocalPath
    {
        $this->buildObjectAndEnvironmentIfNeeded();
        $path = $this->cache->cache;
        return LocalPath::createFromPathString($path);
    }


    public function getCacheDependencies(): MarkupCacheDependencies
    {
        return $this->cacheDependencies;
    }





    private function checkNoSetAfterBuild()
    {
        if ($this->objectHasBeenBuild) {
            LogUtility::internalError("You can't set when the object has been build");
        }
    }


    /**
     * @return string - with replacement if any
     * TODO: edit button replacement could be a script tag with a json, permits to do DOM manipulation
     */
    public function getFetchString(): string
    {
        $path = $this->getFetchPath();
        try {
            $text = FileSystems::getContent($path);
        } catch (ExceptionNotFound $e) {
            throw new RuntimeException("Internal error: The fetch path should exists.", self::CANONICAL, 1, $e);
        }
        if (!in_array($this->getMime()->getExtension(), ["html", "xhtml"])) {
            return $text;
        }
        if ($this->getSourcePath()->getDrive() !== WikiPath::PAGE_DRIVE) {
            // case when this is a default page in the resource directory
            return EditButton::deleteAll($text);
        } else {
            return EditButton::replaceOrDeleteAll($text);
        }

    }

    /**
     * @throws ExceptionBadSyntax
     */
    public function getFetchPathAsHtmlDom(): XmlDocument
    {
        return XmlDocument::createHtmlDocFromMarkup($this->getFetchString());
    }

    /**
     * @throws ExceptionBadSyntax
     */
    public function getFetchPathAsXHtmlDom(): XmlDocument
    {
        return XmlDocument::createXmlDocFromMarkup($this->getFetchString());
    }

    /**
     * The page context in which this fragment was requested
     * @param Path $path
     * @return $this
     * @throws ExceptionBadArgument - if the path cannot be transformed as wiki path
     */
    public function setRequestedPagePath(Path $path): FetcherMarkup
    {
        $this->requestedPagePath = WikiPath::createFromPathObject($path);
        return $this;
    }

    /**
     * Restore the environment variable
     * @return $this
     */
    public function close(): FetcherMarkup
    {
        $this->wikiRequest->close($this->getRequestedPath()->getWikiId());
        $this->closed = true;
        return $this;
    }

    public function isClosed(): bool
    {
        return $this->closed = true;
    }



    private function getRequestedPath(): WikiPath
    {
        return $this->getSourcePath();
    }

    /**
     * The renderer will add a p block element
     * if the first one is not one
     * @param bool $b
     * @return $this
     */
    public function setRemoveRootBlockElement(bool $b): FetcherMarkup
    {
        $this->removeRootBlockElement = $b;
        return $this;
    }

    public function setRequestedRendererName(string $rendererName): FetcherMarkup
    {
        $this->requestedRendererName = $rendererName;
        return $this;
    }

    private function getRequestedRendererNameOrDefault(): string
    {
        if(!isset($this->requestedRendererName)){
            return MarkupRenderer::DEFAULT_RENDERER;
        }
        return $this->requestedRendererName;
    }


}
