<?php


namespace ComboStrap;


use dokuwiki\Cache\CacheInstructions;
use dokuwiki\Cache\CacheParser;
use dokuwiki\Cache\CacheRenderer;
use Exception;


/**
 * A class that renders a markup fragment
 * This is the context object
 * during parsing and rendering is determined by {@link FetcherMarkup}
 *
 * You can get it in any place via {@link ExecutionContext::getExecutingFetcherMarkup()}
 *
 * It:
 * * does not output any full page (HTML document) but only fragment.
 * * manage the dependencies (snippets, cache)
 *
 * This is not really a {@link IFetcher function} because it should not be called
 * from the outside but to be able to use the {@link FetcherCache} we need to.
 * (as fetcher cache uses the url as unique identifier)
 *
 *
 * TODO: {@link MarkupRenderer} could be one with {@link FetcherMarkup} ?
 */
class FetcherMarkup extends IFetcherAbs implements IFetcherSource, IFetcherString
{


    const XHTML_MODE = "xhtml";
    const MAX_CACHE_AGE = 999999;

    const CANONICAL = "markup-fragment-fetcher";

    /**
     * @var CacheParser cache file (may be not set if this is a {@link self::isMarkupStringExecution() string execution}
     */
    protected CacheParser $cache;

    /**
     * @var CacheParser
     */
    private CacheParser $snippetCache;


    private Mime $mime;
    private bool $cacheAfterRendering = true;
    private string $renderer;
    private MarkupCacheDependencies $cacheDependencies;
    private bool $objectHasBeenBuild = false;
    private ExecutionContext $executionContext;
    private bool $closed = false;


    /**
     * @var Snippet[]
     */
    private array $snippets = [];

    private bool $removeRootBlockElement = false;
    private string $requestedRendererName = MarkupRenderer::DEFAULT_RENDERER;

    /**
     * @var WikiPath the context path, it's important to resolve relative link and to create cache for each context namespace for instance
     */
    private WikiPath $requestedContextPath;

    /**
     * @var ?Path the source path of the markup (may be not set if we render a markup string for instance)
     */
    private ?Path $markupSourcePath = null;


    private ?string $markupString = null;

    /**
     * @var array the data fetch as array
     * (ie instructions or metadata) for now
     */
    private array $fetchArray;


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
     * @param ?Path $executingPath - the path where we can find the markup
     * @param WikiPath $contextPath - the context path (from where relative component are resolved (ie links, ...))
     * @return FetcherMarkup
     */
    public static function createPageFragmentFetcherFromPath(?Path $executingPath, WikiPath $contextPath): FetcherMarkup
    {
        return (new FetcherMarkup())
            ->setRequestedExecutingPath($executingPath)
            ->setRequestedContextPath($contextPath);
    }

    public static function createPageFragmentFetcherFromMarkupString(string $markupString, WikiPath $contextPath): FetcherMarkup
    {
        return (new FetcherMarkup())
            ->setMarkupString($markupString)
            ->setRequestedContextPath($contextPath);
    }

    private static function createFromMarkupString(string $markup, ?Path $executingPath, WikiPath $requestedContextPath): FetcherMarkup
    {
        return (new FetcherMarkup())
            ->setMarkupString($markup)
            ->setRequestedExecutingPath($executingPath)
            ->setRequestedContextPath($requestedContextPath);
    }


    /**
     *
     * @param Url|null $url
     * @return Url
     *
     * Note: The fetch url is the {@link FetcherCache keyCache}
     */
    function getFetchUrl(Url $url = null): Url
    {
        /**
         * Overwrite default fetcher endpoint
         * that is {@link UrlEndpoint::createFetchUrl()}
         */
        $url = UrlEndpoint::createDokuUrl();
        $url = parent::getFetchUrl($url);
        try {
            $wikiPath = $this->getSourcePath()->toWikiPath();
            $url->addQueryParameter(DokuwikiId::DOKUWIKI_ID_ATTRIBUTE, $wikiPath->getWikiId());
            $url->addQueryParameter(WikiPath::DRIVE_ATTRIBUTE, $wikiPath->getDrive());
        } catch (ExceptionCast|ExceptionNotFound $e) {
            // not an accessible source path
        }
        $url->addQueryParameter("context-id", $this->getRequestedContextPath()->getWikiId());
        return $url;

    }


    /**
     * The source where the markup is stored (null if dynamic)
     * It's a duplicate of {@link FetcherMarkup::setSourcePath()}
     * @param ?Path $executingPath
     * @return $this
     */
    private function setRequestedExecutingPath(?Path $executingPath): FetcherMarkup
    {

        $this->checkNoSetAfterBuild();
        $this->markupSourcePath = $executingPath;
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
            throw new ExceptionRuntime("Internal error: The XHTML mime was not found.", self::CANONICAL, 1, $e);
        }
    }


    public function shouldProcess(): bool
    {

        if ($this->isMarkupStringExecution()) {
            return true;
        }

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


        /**
         * Snippet
         */

        $snippets = $this->getSnippets();
        $jsonDecodeSnippets = SnippetSystem::toJsonArrayFromSlotSnippets($snippets);

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
     * @return CacheParser - the cache where the snippets are stored
     * Cache file
     * Using a cache parser, set the page id and will trigger
     * the parser cache use event in order to log/report the cache usage
     * At {@link action_plugin_combo_cache::createCacheReport()}
     */
    public
    function getSnippetCacheStore(): CacheParser
    {
        if (isset($this->snippetCache)) {
            return $this->snippetCache;
        }

        $this->buildObjectAndEnvironmentIfNeeded();

        $id = $this->getSourcePath()->getWikiId();
        $slotLocalFilePath = $this->getSourcePath()
            ->toLocalPath()
            ->toAbsolutePath()
            ->toQualifiedId();
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
    function getDependenciesCacheStore(): CacheParser
    {
        return $this->cacheDependencies->getDependenciesCacheStore();
    }

    /**
     * @return LocalPath the fetch path - start the process and returns a path. If the cache is on, return the {@link FetcherMarkup::getCachePath()}
     */
    function processIfNeededAndGetFetchPath(): LocalPath
    {
        $this->processIfNeeded();

        /**
         * The cache path may have change due to the cache key rerouting
         * We should there always use the {@link FetcherMarkup::getCachePath()}
         * as fetch path
         */
        return $this->getCachePath();

    }


    /**
     * @return $this
     */
    public function feedCache(): FetcherMarkup
    {

        $this->buildObjectAndEnvironmentIfNeeded();

        if (!$this->shouldProcess()) {
            return $this;
        }

        /**
         * Process
         */
        if (isset($this->markupString)) {
            $markup = $this->markupString;
        } else {
            try {
                $sourcePath = $this->getSourcePath();
            } catch (ExceptionNotFound $e) {
                throw new ExceptionRuntimeInternal("A markup or a source markup path should be specified.");
            }
            try {
                $markup = FileSystems::getContent($sourcePath);
            } catch (ExceptionNotFound $e) {
                $markup = "";
                LogUtility::error("The path ($sourcePath) does not exist, we have set the markup to the empty string during rendering. If you want to delete the cache path, ask it via the cache path function", self::CANONICAL, $e);
            }
        }

        /**
         * Rendering
         */
        $executionContext = (ExecutionContext::getActualOrCreateFromEnv());


        $extension = $this->getMime()->getExtension();
        switch ($extension) {
            case MarkupRenderer::INSTRUCTION_EXTENSION:
                $markupRenderer = MarkupRenderer::createFromMarkup($markup, $this->getSourcePathOrNull(), $this->getRequestedContextPath())
                    ->setRequestedMimeToInstruction()
                    ->setDeleteRootBlockElement($this->removeRootBlockElement);
                $executionContext->setExecutingFetcherMarkup($this);
                try {
                    $instructions = $markupRenderer->getOutput();
                    $this->fetchArray = $instructions;
                    $contentToStore = serialize($instructions);
                } catch (\Exception $e) {
                    throw new ExceptionRuntimeInternal("An error has occurred while getting the output. Error: {$e->getMessage()}", self::CANONICAL, 1, $e);
                } finally {
                    $executionContext->closeRunningFetcherMarkup();
                }
                break;
            case MarkupRenderer::METADATA_EXTENSION:
                /**
                 * We don't manage/take over the storage
                 * for now. We use the dokwuiki standard function
                 */
                $contentToStore = null;
                $executionContext->setExecutingFetcherMarkup($this);
                try {
                    /**
                     * Trigger a:
                     *  a {@link p_render_metadata() metadata render}
                     *  a {@link p_save_metadata() metadata save}
                     *
                     * Note that {@link p_get_metadata()} uses a strange recursion
                     * There is a metadata recursion logic to avoid rendering
                     * that is not easy to grasp
                     * and therefore you may get no metadata and no backlinks
                     */
                    $wikiId = $this->getSourcePath()->toWikiPath()->getWikiId();
                    $actualMeta = p_read_metadata($wikiId);
                    $newMetadata = p_render_metadata($wikiId, $actualMeta);
                    p_save_metadata($wikiId, $newMetadata);
                    $this->fetchArray = $newMetadata;
                } catch (ExceptionNotFound $e) {
                    // no source path for this markup, no meta then
                } catch (ExceptionCast $e) {
                    // a source path that is not a wiki path (ie layout fragement, ...)
                } catch (\Exception $e) {
                    throw new ExceptionRuntimeInternal("An error has occurred while processing the metadata. Error: {$e->getMessage()}", self::CANONICAL, 1, $e);
                } finally {
                    $executionContext->closeRunningFetcherMarkup();
                }
                break;
            default:
                $instructionsFetcher = FetcherMarkup::createFromMarkupString(
                    $markup,
                    $this->getSourcePathOrNull(),
                    $this->getRequestedContextPath()
                )
                    ->setRequestedMimeToInstructions();
                try {
                    $instructions = $instructionsFetcher
                        ->processIfNeeded()
                        ->getInstructionsArray();
                } finally {
                    $instructionsFetcher->close();
                }

                $markupRenderer = MarkupRenderer::createFromInstructions(
                    $instructions,
                    $this
                )
                    ->setRendererName($this->getRequestedRendererNameOrDefault())
                    ->setRequestedMime($this->getMime());
                $executionContext->setExecutingFetcherMarkup($this);
                try {
                    $contentToStore = $markupRenderer->getOutput();
                } catch (\Exception $e) {
                    throw new ExceptionRuntimeInternal("An error has occurred while getting the output. Error: {$e->getMessage()}", self::CANONICAL, 1, $e);
                } finally {
                    $executionContext->closeRunningFetcherMarkup();
                }
                $this->cacheAfterRendering = $markupRenderer->getCacheAfterRendering();
        }
        /**
         * We store always the output in the cache
         * if the cache is not on, the file is just overwritten
         *
         * We don't use
         * {{@link CacheParser::storeCache()}
         * because it uses the protected parameter `__nocache`
         * that will disallow the storage
         */
        if ($contentToStore != null && isset($this->cache->cache)) {
            io_saveFile($this->cache->cache, $contentToStore);
        }

        /**
         * Snippets and dependencies if XHTML
         * (after processing as they can be added at runtime)
         */
        if (
            $this->getMime()->getExtension() === self::XHTML_MODE &&
            !$this->isMarkupStringExecution()
        ) {

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


        return $this;
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
            throw new ExceptionRuntime("Internal error", 0, $e);
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
         */


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
            $wikiId = $this->getSourcePath()->toWikiPath()->getWikiId();
        } catch (ExceptionCast|ExceptionNotFound $e) {
            if ($this->getSourcePathOrNull() != null) {
                LogUtility::errorIfDevOrTest("There is a source path / executing, we should be able to cache");
            }
            return;
        }
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
                $this->getCacheDependencies()->rerouteCacheDestination($this->cache);
                break;
        }


    }

    public function __toString()
    {
        if($this->isMarkupStringExecution()){
            $name = "Markup String Execution";
        } else {
            try {
                $name = $this->getSourcePath();
            } catch (ExceptionNotFound $e) {
                throw new ExceptionRuntimeInternal("A source path should be defined if it's not a markup string execution");
            }
        }
        return parent::__toString() . " (" . $name .", ". $this->getMime()->toString() . ")";
    }


    /**
     * @throws ExceptionBadArgument
     */
    public function buildFromTagAttributes(TagAttributes $tagAttributes): FetcherMarkup
    {
        parent::buildFromTagAttributes($tagAttributes);
        return $this;
    }


    public function setRequestedMimeToInstructions(): FetcherMarkup
    {
        try {
            $this->setRequestedMime(Mime::createFromExtension(MarkupRenderer::INSTRUCTION_EXTENSION));
        } catch (ExceptionNotFound $e) {
            throw new ExceptionRuntime("Internal error: the mime is internal and should be good");
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
            $path = $this->processIfNeededAndGetFetchPath();
            $contents = FileSystems::getContent($path);
        } catch (ExceptionNotFound $e) {
            throw new ExceptionRuntimeInternal("The fetch path was not found but should be present", self::CANONICAL, 1, $e);
        }
        return !empty($contents) ? unserialize($contents) : array();
    }


    /**
     * @return LocalPath - the cache path is where the result is stored if the cache is on
     * The cache path may have change due to the cache key rerouting
     * We should there always use the {@link FetcherMarkup::getCachePath()}
     * as fetch path
     */
    public function getCachePath(): LocalPath
    {
        if (!$this->objectHasBeenBuild) {
            $this->buildObjectAndEnvironmentIfNeeded();
        }
        $path = $this->cache->cache;
        return LocalPath::createFromPathString($path);
    }


    public function getCacheDependencies(): MarkupCacheDependencies
    {
        if (!isset($this->cacheDependencies)) {
            $this->cacheDependencies = MarkupCacheDependencies::create($this->getSourcePathOrNull(), $this->getRequestedContextPath());
        }
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
        $path = $this->processIfNeededAndGetFetchPath();
        try {
            $text = FileSystems::getContent($path);
        } catch (ExceptionNotFound $e) {
            throw new ExceptionRuntime("Internal error: The fetch path should exists.", self::CANONICAL, 1, $e);
        }

        if (!in_array($this->getMime()->getExtension(), ["html", "xhtml"])) {
            return $text;
        }
        if ($this->getSourcePath()->getDrive() !== WikiPath::MARKUP_DRIVE) {
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
     * @param WikiPath $contextPath
     * @return $this
     */
    private function setRequestedContextPath(WikiPath $contextPath): FetcherMarkup
    {
        $this->requestedContextPath = $contextPath;
        return $this;

    }

    /**
     * Restore the environment variable
     * @return $this
     */
    public function close(): FetcherMarkup
    {
        $this->closed = true;
        return $this;
    }

    public function isClosed(): bool
    {
        return $this->closed = true;
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
        if (!isset($this->requestedRendererName)) {
            return MarkupRenderer::DEFAULT_RENDERER;
        }
        return $this->requestedRendererName;
    }


    public function getLabel(): string
    {
        $sourcePath = $this->getSourcePath();
        return ResourceName::getFromPath($sourcePath);
    }

    private function getRequestedContextPath(): WikiPath
    {
        if (!isset($this->requestedContextPath)) {
            LogUtility::errorIfDevOrTest("The requested context path should be set");
            try {
                return WikiPath::createRequestedPagePathFromRequest();
            } catch (ExceptionNotFound $e) {
                throw new ExceptionRuntimeInternal("A requested context path could not be found", $e);
            }
        }
        return $this->requestedContextPath;
    }

    public function getRequestedtContextPath(): WikiPath
    {
        return $this->requestedContextPath;
    }

    public function setRequestedMimeToMetadata(): FetcherMarkup
    {
        try {
            return $this->setRequestedMime(Mime::createFromExtension(MarkupRenderer::METADATA_EXTENSION));
        } catch (ExceptionNotFound $e) {
            throw new ExceptionRuntime("Internal error", 0, $e);
        }
    }

    /**
     * @throws ExceptionNotFound
     */
    public function getSourcePath(): Path
    {
        if (isset($this->markupSourcePath)) {
            return $this->markupSourcePath;
        }
        throw new ExceptionNotFound("No source path for this markup");
    }

    /**
     * @return Path|null - utility class to get the source markup path or null (if this is a markup snippet/string rendering)
     */
    public function getSourcePathOrNull(): ?Path
    {
        try {
            return $this->getSourcePath();
        } catch (ExceptionNotFound $e) {
            return null;
        }
    }

    /**
     * @return array - the data fetched in a array format
     */
    public function getFetchArray(): array
    {
        return $this->fetchArray;
    }

    /**
     * @param string $markupString - the markup is a string format
     * @return FetcherMarkup
     */
    private function setMarkupString(string $markupString): FetcherMarkup
    {
        $this->markupString = $markupString;
        return $this;
    }

    /**
     * @param string $componentId
     * @return Snippet[]
     */
    public function getSnippetsForComponent(string $componentId): array
    {
        $snippets = [];
        foreach ($this->getSnippets() as $snippet) {
            try {
                if ($snippet->getComponentId() === $componentId) {
                    $snippets[] = $snippet;
                }
            } catch (ExceptionNotFound $e) {
                //
            }
        }
        return $snippets;

    }

    /**
     * @return Snippet[]
     */
    private function getSnippets(): array
    {

        $snippets = $this->snippets;

        /**
         * Old ways where snippets were added to the global scope
         * and not to the fetcher markup via {@link self::addSnippet()}
         *
         * During the transition, we support the two
         *
         * Note that with the new system where render code
         * can access this object via {@link ExecutionContext::getExecutingFetcherMarkup()}
         * the code may had snippets without any id
         * (For the time being, not yet)
         */
        try {
            $slotId = $this->getSourcePath()->toWikiPath()->getWikiId();
        } catch (ExceptionNotFound $e) {
            // a markup string run
            return $snippets;
        } catch (ExceptionCast $e) {
            // not a wiki path
            return $snippets;
        }

        $snippetManager = PluginUtility::getSnippetManager();
        $oldWaySnippets = $snippetManager->getSnippetsForSlot($slotId);
        return array_merge($oldWaySnippets, $snippets);

    }

    /**
     * @param Snippet $snippet
     * @return FetcherMarkup
     */
    public function addSnippet(Snippet $snippet): FetcherMarkup
    {
        $snippetGuid = $snippet->getPath()->toUriString();
        $this->snippets[$snippetGuid] = $snippet;
        return $this;

    }

    private function isMarkupStringExecution(): bool
    {
        if ($this->markupSourcePath === null) {
            if ($this->markupString !== null) {
                return true;
            }
            throw new ExceptionRuntimeInternal("A markup source path or a markup string should be set");
        }
        return false;
    }

    public function processIfNeeded(): FetcherMarkup
    {
        $this->buildObjectAndEnvironmentIfNeeded();

        if (!$this->shouldProcess()) {
            return $this;
        }

        $this->feedCache();
        return $this;
    }

    public function getInstructionsArray(): array
    {
        if($this->getMime()->getExtension()!==MarkupRenderer::INSTRUCTION_EXTENSION){
            throw new ExceptionRuntimeInternal("This is not an instruction run, you can't ask the instruction array");
        }
        if (isset($this->fetchArray)) {
            /**
             * In a {@link self::isMarkupStringExecution()}, there is only an array
             * (no storage)
             */
            return $this->fetchArray;
        }
        try {
            $contents = FileSystems::getContent($this->getCachePath());
        } catch (ExceptionNotFound $e) {
            throw new ExceptionRuntimeInternal("No cache path and no instructions arrays, did you process the fetch");
        }
        return !empty($contents) ? unserialize($contents) : array();

    }


}
