<?php


namespace ComboStrap;


use dokuwiki\Cache\CacheParser;
use Exception;


/**
 * A class that renders a markup fragment
 * This is the context object
 * during parsing and rendering is determined by {@link FetcherMarkup}
 *
 * You can get it in any place via {@link ExecutionContext::getExecutingMarkupHandler()}
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
     * When the rendering is done from:
     * * a string
     * * or an instructions (template)
     * but not from a file
     */
    public const MARKUP_DYNAMIC_EXECUTION_NAME = "markup-dynamic-execution";

    /**
     * @var CacheParser cache file (may be not set if this is a {@link self::isStringExecution() string execution}
     */
    protected CacheParser $contentCache;

    protected string $rendererName;

    /**
     * @var CacheParser
     */
    private CacheParser $snippetCache;


    protected Mime $mime;
    private bool $cacheAfterRendering = true;
    protected MarkupCacheDependencies $cacheDependencies;


    /**
     * @var Snippet[]
     */
    private array $localSnippets = [];

    protected bool $removeRootBlockElement = false;

    /**
     * @var WikiPath the context path, it's important to resolve relative link and to create cache for each context namespace for instance
     */
    protected WikiPath $requestedContextPath;

    /**
     * @var ?Path the source path of the markup (may be not set if we render a markup string for instance)
     */
    protected ?Path $markupSourcePath = null;


    protected ?string $markupString = null;

    /**
     * @var array the data fetch as array
     * (ie instructions or metadata) for now
     */
    private array $fetchArray;
    /**
     * @var bool true if this fetcher has already run
     * (
     * Fighting file modified time, even if we cache has been stored,
     * the modified time is not always good, this indicator will
     * make the processing not run twice)
     */
    private bool $hasExecuted = false;

    /**
     * The result when this is a {@link self::isStringExecution() execution}
     * @var string
     */
    private $fetchString;


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
     * @param Path $executingPath - the path where we can find the markup
     * @param ?WikiPath $contextPath - the context path, the requested path in the browser url (from where relative component are resolved (ie links, ...))
     * @return FetcherMarkup
     */
    public static function createXhtmlMarkupFetcherFromPath(Path $executingPath, WikiPath $contextPath = null): FetcherMarkup
    {
        if ($contextPath === null) {
            try {
                $contextPath = $executingPath->toWikiPath();
            } catch (ExceptionCast $e) {
                /**
                 * Not a wiki path, default to the default
                 */
                $contextPath = ExecutionContext::getActualOrCreateFromEnv()->getDefaultContextPath();
            }
        }
        return FetcherMarkup::getBuilder()
            ->setRequestedExecutingPath($executingPath)
            ->setRequestedContextPath($contextPath)
            ->setRequestedMimeToXhtml()
            ->build();
    }


    public static function getBuilder(): FetcherMarkupBuilder
    {
        return new FetcherMarkupBuilder();
    }

    public static function createFromStringMarkupToXhtml(string $markup): FetcherMarkup
    {
        return self::getBuilder()
            ->setRequestedMarkupString($markup)
            ->setDeleteRootBlockElement(true)
            ->setRequestedContextPathWithDefault()
            ->setRequestedMimeToXhtml()
            ->build();
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

        if ($this->isStringExecution()) {
            return true;
        }

        if ($this->hasExecuted) {
            return false;
        }

        /**
         * The cache is stored by requested page scope
         *
         * We set the environment because
         * {@link CacheParser::useCache()} may call a parsing of the markup fragment
         * And the global environment are not always passed
         * in all actions and is needed to log the {@link  CacheResult cache
         * result}
         *
         * Use cache should be always called because it trigger
         * the event coupled to the cache (ie PARSER_CACHE_USE)
         */
        $depends['age'] = $this->getCacheAge();
        $useCache = $this->contentCache->useCache($depends);
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

        $path = null;
        try {
            $path = $this->getSourcePath();
        } catch (ExceptionNotFound $e) {
            if (!$this->isStringExecution()) {
                throw new ExceptionRuntimeInternal("A source path should be available as this is not a markup string execution");
            }
        }
        $id = $path->toQualifiedId();
        try {
            $slotLocalFilePath = $path
                ->toLocalPath()
                ->toAbsolutePath()
                ->toQualifiedId();
        } catch (ExceptionCast $e) {
            throw new ExceptionRuntimeInternal("The path type ($path) is not supported, we couldn't store the snippets.");
        }
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
     * @return LocalPath the fetch path - start the process and returns a path. If the cache is on, return the {@link FetcherMarkup::getContentCachePath()}
     */
    function processIfNeededAndGetFetchPath(): LocalPath
    {
        $this->processIfNeeded();

        /**
         * The cache path may have change due to the cache key rerouting
         * We should there always use the {@link FetcherMarkup::getContentCachePath()}
         * as fetch path
         */
        return $this->getContentCachePath();

    }


    /**
     * @return $this
     */
    public function feedCache(): FetcherMarkup
    {

        $this->hasExecuted = true;

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
                $markupRenderer = MarkupRenderer::createFromMarkup($markup, $this->getExecutingPathOrNull(), $this->getRequestedContextPath())
                    ->setRequestedMimeToInstruction()
                    ->setDeleteRootBlockElement($this->removeRootBlockElement);
                $executionContext->setExecutingMarkupHandler($this);
                try {
                    $instructions = $markupRenderer->getOutput();
                    $this->fetchArray = $instructions;
                    $contentToStore = serialize($instructions);
                } catch (\Exception $e) {
                    throw new ExceptionRuntimeInternal("An error has occurred while getting the output. Error: {$e->getMessage()}", self::CANONICAL, 1, $e);
                } finally {
                    $executionContext->closeExecutingMarkupHandler();
                }
                break;
            case MarkupRenderer::METADATA_EXTENSION:
                /**
                 * We don't manage/take over the storage
                 * for now. We use the dokwuiki standard function
                 */
                $contentToStore = null;
                $executionContext->setExecutingMarkupHandler($this);
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
                    $executionContext->closeExecutingMarkupHandler();
                }
                break;
            case MarkupRenderer::XHTML_RENDERER:
                $instructionsFetcher = FetcherMarkup::getBuilder()
                    ->setRequestedMarkupString($markup)
                    ->setRequestedContextPath($this->getRequestedContextPath())
                    ->setRequestedExecutingPath($this->getExecutingPathOrNull())
                    ->setRequestedMimeToInstructions()
                    ->setDeleteRootBlockElement($this->removeRootBlockElement)
                    ->build();

                $instructions = $instructionsFetcher
                    ->processIfNeeded()
                    ->getInstructionsArray();

                $markupRenderer = MarkupRenderer::createFromInstructions(
                    $instructions,
                    $this
                )
                    ->setRequestedMime($this->getMime());
                $executionContext->setExecutingMarkupHandler($this);
                try {
                    $contentToStore = $markupRenderer->getOutput();
                } catch (\Exception $e) {
                    throw new ExceptionRuntimeInternal("An error has occurred while getting the output. Error: {$e->getMessage()}", self::CANONICAL, 1, $e);
                } finally {
                    $executionContext->closeExecutingMarkupHandler();
                }
                $this->cacheAfterRendering = $markupRenderer->getCacheAfterRendering();


                break;
            default:
                /**
                 * Other such as Analytics
                 */
                $markupRenderer = MarkupRenderer::createFromMarkup($markup, $this->getExecutingPathOrNull(), $this->getRequestedContextPath())
                    ->setRequestedMime($this->getMime())
                    ->setRendererName($this->rendererName);
                $executionContext->setExecutingMarkupHandler($this);
                try {
                    $output = $markupRenderer->getOutput();
                    $contentToStore = $output;
                } catch (\Exception $e) {
                    throw new ExceptionRuntimeInternal("An error has occurred while getting the output. Error: {$e->getMessage()}", self::CANONICAL, 1, $e);
                } finally {
                    $executionContext->closeExecutingMarkupHandler();
                }
                break;
        }

        /**
         * Snippets and dependencies if XHTML
         * (after processing as they can be added at runtime)
         */
        if ($this->isStringExecution()) {
            $this->fetchString = $contentToStore;
            return $this;
        }


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

        /**
         * We store always the output in the cache
         * if the cache is not on, the file is just overwritten
         *
         * We don't use
         * {{@link CacheParser::storeCache()}
         * because it uses the protected parameter `__nocache`
         * that will disallow the storage
         *
         * Reroute the cache output by runtime dependencies
         * set during processing
         */
        $this->cacheDependencies->rerouteCacheDestination($this->contentCache);
        io_saveFile($this->contentCache->cache, $contentToStore);

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


    public function __toString()
    {
        if ($this->isStringExecution()) {
            $name = "Markup String Execution";
        } else {
            try {
                $name = $this->getSourcePath();
            } catch (ExceptionNotFound $e) {
                throw new ExceptionRuntimeInternal("A source path should be defined if it's not a markup string execution");
            }
        }
        return parent::__toString() . " (" . $name . ", " . $this->getMime()->toString() . ")";
    }


    /**
     * @throws ExceptionBadArgument
     */
    public function buildFromTagAttributes(TagAttributes $tagAttributes): FetcherMarkup
    {
        parent::buildFromTagAttributes($tagAttributes);
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
     * We should there always use the {@link FetcherMarkup::getContentCachePath()}
     * as fetch path
     */
    public function getContentCachePath(): LocalPath
    {
        $path = $this->contentCache->cache;
        return LocalPath::createFromPathString($path);
    }


    public function getCacheDependencies(): MarkupCacheDependencies
    {
        return $this->cacheDependencies;
    }


    /**
     * @return string - with replacement if any
     * TODO: edit button replacement could be a script tag with a json, permits to do DOM manipulation
     */
    public function getFetchString(): string
    {
        $this->processIfNeeded();

        if ($this->isStringExecution()) {

            return $this->fetchString;
        }

        /**
         * Source path execution
         */
        $path = $this->processIfNeededAndGetFetchPath();
        try {
            $text = FileSystems::getContent($path);
        } catch (ExceptionNotFound $e) {
            throw new ExceptionRuntime("Internal error: The fetch path should exists.", self::CANONICAL, 1, $e);
        }

        /**
         * Edit button Processing for XHtml
         * (Path is mandatory to create the buttons)
         */
        if (!in_array($this->getMime()->getExtension(), ["html", "xhtml"])) {
            return $text;
        }
        try {
            if ($this->getSourcePath()->toWikiPath()->getDrive() !== WikiPath::MARKUP_DRIVE) {
                // case when this is a default page in the resource/template directory
                return EditButton::deleteAll($text);
            }
        } catch (ExceptionNotFound|ExceptionCast $e) {
            // not a wiki path
        }
        return EditButton::replaceOrDeleteAll($text);

    }

    /**
     * @throws ExceptionBadSyntax
     */
    public function getOutputAsHtmlDom(): XmlDocument
    {
        return XmlDocument::createHtmlDocFromMarkup($this->getFetchString());
    }

    /**
     * @throws ExceptionBadSyntax
     */
    public function getOutputAsXHtmlDom(): XmlDocument
    {
        return XmlDocument::createXmlDocFromMarkup($this->getFetchString());
    }


    public function getLabel(): string
    {
        try {
            $sourcePath = $this->getSourcePath();
        } catch (ExceptionNotFound $e) {
            return self::MARKUP_DYNAMIC_EXECUTION_NAME;
        }
        return ResourceName::getFromPath($sourcePath);
    }


    public function getRequestedContextPath(): WikiPath
    {
        return $this->requestedContextPath;
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
     * Utility class that return the source path
     * @return Path
     * @throws ExceptionNotFound
     */
    public function getRequestedExecutingPath(): Path
    {
        return $this->getSourcePath();
    }

    /**
     * @return Path|null - utility class to get the source markup path or null (if this is a markup snippet/string rendering)
     */
    public function getExecutingPathOrNull(): ?Path
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

        $snippets = $this->localSnippets;

        /**
         * Old ways where snippets were added to the global scope
         * and not to the fetcher markup via {@link self::addSnippet()}
         *
         * During the transition, we support the two
         *
         * Note that with the new system where render code
         * can access this object via {@link ExecutionContext::getExecutingMarkupHandler()}
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
        $this->localSnippets[$snippetGuid] = $snippet;
        return $this;

    }

    public function isStringExecution(): bool
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

        if (!$this->shouldProcess()) {
            return $this;
        }

        $this->feedCache();
        return $this;

    }

    public function getInstructionsArray(): array
    {
        if ($this->getMime()->getExtension() !== MarkupRenderer::INSTRUCTION_EXTENSION) {
            throw new ExceptionRuntimeInternal("This is not an instruction run, you can't ask the instruction array");
        }
        if (isset($this->fetchArray)) {
            /**
             * In a {@link self::isStringExecution()}, there is only an array
             * (no storage)
             */
            return $this->fetchArray;
        }
        try {
            $contents = FileSystems::getContent($this->getContentCachePath());
        } catch (ExceptionNotFound $e) {
            throw new ExceptionRuntimeInternal("No cache path and no instructions arrays, did you process the fetch");
        }
        return !empty($contents) ? unserialize($contents) : array();

    }

    public function isFragmentExecution(): bool
    {
        if ($this->isStringExecution()) {
            return false;
        }
        try {
            /**
             * If the context and executing path are not
             * the same, this is a fragment run
             */
            if ($this->getRequestedContextPath()->getWikiId() !== $this->getRequestedExecutingPath()->toWikiPath()->getWikiId()) {
                return true;
            }
        } catch (ExceptionNotFound|ExceptionCast $e) {
            // no executing path, not a wiki path
        }
        return false;
    }

    public function getSnippetManager(): SnippetSystem
    {
        return PluginUtility::getSnippetManager();
    }

    /**
     * @throws ExceptionBadSyntax
     */
    public function getFetchStringAsDom(): XmlDocument
    {
        return XmlDocument::createXmlDocFromMarkup($this->getFetchString());
    }

    public function getSnippetsAsHtmlString(): string
    {

        try {
            $globalSnippets = SnippetSystem::getFromContext()->getSnippetsForSlot($this->getRequestedExecutingPath()->toQualifiedId());
        } catch (ExceptionNotFound $e) {
            // string execution
            $globalSnippets = [];
        }
        $allSnippets = array_merge($globalSnippets,$this->localSnippets);
        return SnippetSystem::toHtmlFromSnippetArray($allSnippets);

    }


}
