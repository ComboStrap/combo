<?php


namespace ComboStrap;


use ComboStrap\Meta\Store\MetadataDokuWikiStore;
use ComboStrap\Web\Url;
use ComboStrap\Web\UrlEndpoint;
use ComboStrap\Xml\XmlDocument;
use Doku_Renderer_metadata;
use dokuwiki\Cache\CacheInstructions;
use dokuwiki\Cache\CacheParser;
use dokuwiki\Cache\CacheRenderer;
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
 *
 * Not all properties are public to support
 * the {@link FetcherMarkupBuilder} pattern.
 * Php does not support internal class and protected does not
 * work for class on the same namespace.
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
     * @var array - toc in a dokuwiki format
     */
    public array $toc;

    /**
     * @var CacheParser cache file (may be not set if this is not a {@link self::isPathExecution() execution}
     */
    public CacheParser $contentCache;

    public string $rendererName;

    public array $requestedInstructions;
    public array $contextData;

    public CacheInstructions $instructionsCache;

    /**
     * @var CacheRenderer This cache file stores the last render timestamp (see {@link p_get_metadata()}
     */
    public CacheRenderer $metaCache;
    public LocalPath $metaPath;

    /**
     * @var CacheParser
     */
    public CacheParser $snippetCache;

    /**
     * @var FetcherMarkup - the parent (a instructions run may run inside a path run, ie {@link \syntax_plugin_combo_iterator)
     */
    public FetcherMarkup $parentMarkupHandler;

    /**
     * @var bool threat the markup as a document (not as a fragment)
     */
    public bool $isDoc;


    public Mime $mime;
    private bool $cacheAfterRendering = true;
    public MarkupCacheDependencies $outputCacheDependencies;


    /**
     * @var Snippet[]
     */
    private array $localSnippets = [];

    public bool $deleteRootBlockElement = false;

    /**
     * @var WikiPath the context path, it's important to resolve relative link and to create cache for each context namespace for instance
     */
    public WikiPath $requestedContextPath;

    /**
     * @var Path the source path of the markup (may be not set if we render a markup string for instance)
     */
    public Path $markupSourcePath;


    public string $markupString;

    /**
     * @var bool true if this fetcher has already run
     * (
     * Fighting file modified time, even if we cache has been stored,
     * the modified time is not always good, this indicator will
     * make the processing not run twice)
     */
    private bool $hasExecuted = false;

    /**
     * The result
     * @var string
     */
    private string $fetchString;


    /**
     * @var array
     */
    private array $meta;
    /**
     * @var array - the instructions processed
     */
    private array $processedInstructions;

    /**
     * @var bool - when a execution is not a {@link self::isPathExecution()}, the snippet will not be stored automatically.
     * To avoid this problem, a warning is send if the calling code does not set explicitly that this is specifically a
     * standalone execution
     */
    public bool $isNonPathStandaloneExecution = false;


    /**
     * @param Path $executingPath - the path where we can find the markup
     * @param ?WikiPath $contextPath - the context path, the requested path in the browser url (from where relative component are resolved (ie links, ...))
     * @return FetcherMarkup
     * @throws ExceptionNotExists
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
        return FetcherMarkup::confRoot()
            ->setRequestedExecutingPath($executingPath)
            ->setRequestedContextPath($contextPath)
            ->setRequestedMimeToXhtml()
            ->build();
    }


    public static function confRoot(): FetcherMarkupBuilder
    {
        return new FetcherMarkupBuilder();
    }

    /**
     * Use mostly in test
     * The coutnerpart of {@link \TestUtility::renderText2XhtmlWithoutP()}
     * @throws ExceptionNotExists
     */
    public static function createStandaloneExecutionFromStringMarkupToXhtml(string $markup): FetcherMarkup
    {
        return self::confRoot()
            ->setRequestedMarkupString($markup)
            ->setDeleteRootBlockElement(true)
            ->setRequestedContextPathWithDefault()
            ->setRequestedMimeToXhtml()
            ->setIsCodeStandAloneExecution(true)
            ->build();
    }

    public static function confChild(): FetcherMarkupBuilder
    {
        $executionContext = ExecutionContext::getActualOrCreateFromEnv();
        try {
            $executing = $executionContext->getExecutingMarkupHandler();
        } catch (ExceptionNotFound $e) {
            if (PluginUtility::isDevOrTest() && $executionContext->getExecutingAction() !== ExecutionContext::PREVIEW_ACTION) {
                LogUtility::warning("A markup handler is not running, we couldn't create a child.");
            }
            return self::confRoot();
        }
        return self::confRoot()
            ->setParentMarkupHandler($executing);
    }

    /**
     * Dokuwiki will wrap the markup in a p element
     * if the first element is not a block
     * This option permits to delete it. This is used mostly in test to get
     * the generated html
     */
    public function deleteRootPElementsIfRequested(array &$instructions): void
    {

        if (!$this->deleteRootBlockElement) {
            return;
        }

        /**
         * Delete the p added by {@link Block::process()}
         * if the plugin of the {@link SyntaxPlugin::getPType() normal} and not in a block
         *
         * p_open = document_start in renderer
         */
        if ($instructions[1][0] !== 'p_open') {
            return;
        }
        unset($instructions[1]);

        /**
         * The last p position is not fix
         * We may have other calls due for instance
         * of {@link \action_plugin_combo_syntaxanalytics}
         */
        $n = 1;
        while (($lastPBlockPosition = (sizeof($instructions) - $n)) >= 0) {

            /**
             * p_open = document_end in renderer
             */
            if ($instructions[$lastPBlockPosition][0] == 'p_close') {
                unset($instructions[$lastPBlockPosition]);
                break;
            } else {
                $n = $n + 1;
            }
        }

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

    public function shouldInstructionProcess(): bool
    {

        if (!$this->isPathExecution()) {
            return true;
        }

        if (isset($this->processedInstructions)) {
            return false;
        }

        /**
         * Edge Case
         * (as dokuwiki starts the rendering process here
         * we need to set the execution id)
         */
        $executionContext = ExecutionContext::getActualOrCreateFromEnv()->setExecutingMarkupHandler($this);
        try {
            $useCache = $this->instructionsCache->useCache();
        } finally {
            $executionContext->closeExecutingMarkupHandler();
        }
        return ($useCache === false);
    }

    public function shouldProcess(): bool
    {

        if (!$this->isPathExecution()) {
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
        if ($this->isFragment()) {
            /**
             * Fragment may use variables of the requested page
             * We have dependency on {@link MarkupCacheDependencies::PAGE_PRIMARY_META_DEPENDENCY}
             * but as they may be derived such as the {@link PageTitle}
             * comes from the H1 or the feature image comes from the first image in the section 1
             * We can't really use this event.
             */
            try {
                $depends['files'][] = FetcherMarkup::confRoot()
                    ->setRequestedContextPath($this->getRequestedContextPath())
                    ->setRequestedExecutingPath($this->getRequestedContextPath())
                    ->setRequestedMimeToMetadata()
                    ->build()
                    ->getMetadataPath()
                    ->toAbsoluteString();
            } catch (ExceptionNotExists|ExceptionNotFound $e) {
                LogUtility::error("The metadata path should be known", self::CANONICAL, $e);
            }
        }
        /**
         * Edge Case
         * (as dokuwiki starts the rendering process here
         * we need to set the execution id)
         */
        $executionContext = ExecutionContext::getActualOrCreateFromEnv()
            ->setExecutingMarkupHandler($this);
        try {
            $useCache = $this->contentCache->useCache($depends);
        } finally {
            $executionContext->closeExecutingMarkupHandler();
        }
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
        $this->outputCacheDependencies->rerouteCacheDestination($snippetCache);

        if (count($jsonDecodeSnippets) > 0) {
            $data1 = json_encode($jsonDecodeSnippets);
            $snippetCache->storeCache($data1);
        } else {
            $snippetCache->removeCache();
        }

    }

    /**
     * This functon loads the snippets in the global array
     * by creating them. Not ideal but works for now.
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
                    LogUtility::error("The snippet json array cannot be build into a snippet object. " . $e->getMessage() . "\n" . ArrayUtility::formatAsString($snippet), LogUtility::SUPPORT_CANONICAL,);
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
        if ($this->isPathExecution()) {
            throw new ExceptionRuntimeInternal("A source path should be available as this is a path execution");
        }
        throw new ExceptionRuntime("There is no snippet cache store for a non-path execution");

    }


    public
    function getDependenciesCacheStore(): CacheParser
    {
        return $this->outputCacheDependencies->getDependenciesCacheStore();
    }

    public
    function getDependenciesCachePath(): LocalPath
    {
        $cachePath = $this->outputCacheDependencies->getDependenciesCacheStore()->cache;
        return LocalPath::createFromPathString($cachePath);
    }

    /**
     * @return LocalPath the fetch path - start the process and returns a path. If the cache is on, return the {@link FetcherMarkup::getContentCachePath()}
     * @throws ExceptionCompile
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
     * @throws ExceptionCompile
     */
    public function process(): FetcherMarkup
    {

        $this->hasExecuted = true;

        /**
         * Rendering
         */
        $executionContext = (ExecutionContext::getActualOrCreateFromEnv());

        $extension = $this->getMime()->getExtension();
        switch ($extension) {

            case MarkupRenderer::METADATA_EXTENSION:
                /**
                 * The user may ask just for the metadata
                 * and should then use the {@link self::getMetadata()}
                 * function instead
                 */
                break;
            case MarkupRenderer::INSTRUCTION_EXTENSION:
                /**
                 * The user may ask just for the instuctions
                 * and should then use the {@link self::getInstructions()}
                 * function to get the instructions
                 */
                return $this;
            default:

                $instructions = $this->getInstructions();

                /**
                 * Edge case: We delete here
                 * because the instructions may have been created by dokuwiki
                 * when we test for the cache with {@link CacheParser::useCache()}
                 */
                if ($this->deleteRootBlockElement) {
                    self::deleteRootPElementsIfRequested($instructions);
                }

                if (!isset($this->rendererName)) {
                    $this->rendererName = $this->getMime()->getExtension();
                }

                $executionContext->setExecutingMarkupHandler($this);
                try {
                    if ($this->isDocument()) {
                        $markupRenderer = MarkupRenderer::createFromMarkupInstructions($instructions, $this)
                            ->setRequestedMime($this->getMime())
                            ->setRendererName($this->rendererName);

                        $output = $markupRenderer->getOutput();
                        if ($output === null && !empty($instructions)) {
                            LogUtility::error("The renderer ({$this->rendererName}) seems to have been not found");
                        }
                        $this->cacheAfterRendering = $markupRenderer->getCacheAfterRendering();
                    } else {
                        $output = MarkupDynamicRender::create($this->rendererName)->processInstructions($instructions);
                    }
                } catch (\Exception $e) {
                    /**
                     * Example of errors;
                     * method_exists() expects parameter 2 to be string, array given
                     * inc\parserutils.php:672
                     */
                    throw new ExceptionCompile("An error has occurred while getting the output. Error: {$e->getMessage()}", self::CANONICAL, 1, $e);

                } finally {
                    $executionContext->closeExecutingMarkupHandler();
                }
                if (is_array($output)) {
                    LogUtility::internalError("The output was an array", self::CANONICAL);
                    $this->fetchString = serialize($output);
                } else {
                    $this->fetchString = $output;
                }

                break;
        }

        /**
         * Storage of snippets or dependencies
         * none if this is not a path execution
         * and for now, metadata storage is done by dokuwiki
         */
        if (!$this->isPathExecution() || $this->mime->getExtension() === MarkupRenderer::METADATA_EXTENSION) {
            return $this;
        }


        /**
         * Cache output dependencies
         */
        $this->outputCacheDependencies->storeDependencies();

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
        $this->outputCacheDependencies->rerouteCacheDestination($this->contentCache);
        io_saveFile($this->contentCache->cache, $this->fetchString);

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
        return "markup-fetcher";
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
                // indefinitely
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
        if (!$this->isPathExecution()) {
            if (isset($this->markupString)) {
                $name = "Markup String Execution";
            } elseif (isset($this->requestedInstructions)) {
                $name = "Markup Instructions Execution";
            } else {
                $name = "Markup Unknown Execution";
                LogUtility::internalError("The name of the marku handler is unknown");
            }
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


    public function getOutputCacheDependencies(): MarkupCacheDependencies
    {
        return $this->outputCacheDependencies;
    }


    /**
     * @return string - with replacement if any
     * TODO: edit button replacement could be a script tag with a json, permits to do DOM manipulation
     * @throws ExceptionCompile - if any processing error occurs
     */
    public function getFetchString(): string
    {
        $this->processIfNeeded();

        if (!$this->isPathExecution()) {
            return $this->fetchString;
        }

        /**
         * Source path execution
         * The cache path may have change due to the cache key rerouting
         * We should there always use the {@link FetcherMarkup::getContentCachePath()}
         * as fetch path
         */
        $path = $this->getContentCachePath();
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
    public function getSnippets(): array
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
        /**
         * Snippet should be added only when they can be store
         * (ie when this is path execution)
         * If this is not a path execution, the snippet cannot be
         * stored in a cache and are therefore lost if not used
         */

        /**
         * If there is a parent markup handler
         * Store the snippets there
         */
        if (isset($this->parentMarkupHandler)) {
            $this->parentMarkupHandler->addSnippet($snippet);
            return $this;
        }

        if (!$this->isPathExecution()
            && !$this->isNonPathStandaloneExecution
            // In preview, there is no parent handler because we didn't take over
            && ExecutionContext::getActualOrCreateFromEnv()->getExecutingAction() !== ExecutionContext::PREVIEW_ACTION
        ) {
            LogUtility::warning("The execution ($this) is not a path execution. The snippet $snippet will not be preserved after initial rendering. Set the execution as standalone or set a parent markup handler.");
        }

        $snippetGuid = $snippet->getPath()->toUriString();
        $this->localSnippets[$snippetGuid] = $snippet;
        return $this;


    }

    /**
     * @return bool true if the markup string comes from a path
     * This is motsly important for cache as we use the path as the cache key
     * (Cache:
     * * of the {@link self::getInstructions() instructions},
     * * of the {@link self::getOutputCacheDependencies() output dependencies}
     * * of the {@link self::getSnippets() snippets}
     * * of the {@link self::processMetadataIfNotYetDone() metadata}
     *
     * The rule is this is a path execution of the {@link self::$markupSourcePath executing source path} is set.
     *
     * Ie this is not a path execution, if the input is:
     * * {@link self::$requestedInstructions} (used for templating)
     * * a {@link self::$markupString} (used for test or webcode)
     *
     */
    public function isPathExecution(): bool
    {
        if (isset($this->markupSourcePath)) {
            return true;
        }
        return false;
    }

    /**
     * @throws ExceptionCompile - if any processing errors occurs
     */
    public function processIfNeeded(): FetcherMarkup
    {

        if (!$this->shouldProcess()) {
            return $this;
        }

        $this->process();
        return $this;

    }


    /**
     * @return array - the markup instructions
     */
    public function getInstructions(): array
    {

        if (isset($this->requestedInstructions)) {

            return $this->requestedInstructions;

        }

        if (isset($this->processedInstructions)) {
            return $this->processedInstructions;
        }

        if (!$this->shouldInstructionProcess()) {

            $this->processedInstructions = $this->instructionsCache->retrieveCache();

        } else {

            $this->processInstructions();

        }
        return $this->processedInstructions;


    }


    /**
     * @return bool - a document
     *
     * A document will get an {@link Outline} processing
     * while a {@link self::isFragment() fragment} will not.
     */
    public function isDocument(): bool
    {

        return $this->isDoc;

    }

    public function getSnippetManager(): SnippetSystem
    {
        return PluginUtility::getSnippetManager();
    }

    /**
     * @throws ExceptionBadSyntax
     * @throws ExceptionCompile
     */
    public function getFetchStringAsDom(): XmlDocument
    {
        return XmlDocument::createXmlDocFromMarkup($this->getFetchString());
    }

    public function getSnippetsAsHtmlString(): string
    {

        try {
            $globalSnippets = SnippetSystem::getFromContext()->getSnippetsForSlot($this->getRequestedExecutingPath()->toAbsoluteString());
        } catch (ExceptionNotFound $e) {
            // string execution
            $globalSnippets = [];
        }
        $allSnippets = array_merge($globalSnippets, $this->localSnippets);
        return SnippetSystem::toHtmlFromSnippetArray($allSnippets);

    }

    public function isFragment(): bool
    {
        return $this->isDocument() === false;
    }

    private function getMarkupStringToExecute(): string
    {
        if (isset($this->markupString)) {
            return $this->markupString;
        } else {
            try {
                $sourcePath = $this->getSourcePath();
            } catch (ExceptionNotFound $e) {
                throw new ExceptionRuntimeInternal("A markup or a source markup path should be specified.");
            }
            try {
                return FileSystems::getContent($sourcePath);
            } catch (ExceptionNotFound $e) {
                LogUtility::error("The path ($sourcePath) does not exist, we have set the markup to the empty string during rendering. If you want to delete the cache path, ask it via the cache path function", self::CANONICAL, $e);
                return "";
            }
        }
    }

    public function getContextData(): array
    {
        if (isset($this->contextData)) {
            return $this->contextData;
        }
        $this->contextData = MarkupPath::createPageFromPathObject($this->getRequestedContextPath())->getMetadataForRendering();
        return $this->contextData;
    }


    public function getToc(): array
    {

        if (isset($this->toc)) {
            return $this->toc;
        }
        try {
            return TOC::createForPage($this->getRequestedExecutingPath())->getValue();
        } catch (ExceptionNotFound $e) {
            // no executing page or no value
        }
        /**
         * Derived TOC from instructions
         */
        $toc = Outline::createFromCallStack(CallStack::createFromInstructions($this->getInstructions()))->toTocDokuwikiFormat();
        try {
            TOC::createEmpty()
                ->setValue($toc)
                ->sendToWriteStore();
            return $toc;
        } catch (ExceptionBadArgument $e) {
            throw new ExceptionRuntimeInternal("should not happen");
        }

    }

    public function getInstructionsPath(): LocalPath
    {
        $path = $this->instructionsCache->cache;
        return LocalPath::createFromPathString($path);
    }

    public function getOutline(): Outline
    {
        $instructions = $this->getInstructions();
        $callStack = CallStack::createFromInstructions($instructions);
        try {
            $markupPath = MarkupPath::createPageFromPathObject($this->getRequestedExecutingPath());
        } catch (ExceptionNotFound $e) {
            $markupPath = null;
        }
        return Outline::createFromCallStack($callStack, $markupPath);
    }


    public function getMetadata(): array
    {

        $this->processMetadataIfNotYetDone();
        return $this->meta;

    }


    /**
     * Adaptation of {@link p_get_metadata()}
     * to take into account {@link self::getInstructions()}
     * where we can just pass our own instructions.
     *
     * And yes, adaptation of {@link p_get_metadata()}
     * that process the metadata. Yeah, it calls {@link p_render_metadata()}
     * and save them
     *
     */
    public function processMetadataIfNotYetDone(): FetcherMarkup
    {

        /**
         * Already set ?
         */
        if (isset($this->meta)) {
            return $this;
        }

        $actualMeta = [];

        /**
         * We wrap the whole block
         * because {@link CacheRenderer::useCache()}
         * and the renderer needs it
         */
        $executionContext = ExecutionContext::getActualOrCreateFromEnv()->setExecutingMarkupHandler($this);
        try {

            /**
             * Can we read from the meta file
             */


            if ($this->isPathExecution()) {

                /**
                 * If the meta file exists
                 */
                if (FileSystems::exists($this->getMetaPathOrFail())) {

                    $executingPath = $this->getExecutingPathOrFail();
                    $actualMeta = MetadataDokuWikiStore::getOrCreateFromResource(MarkupPath::createPageFromPathObject($executingPath))
                        ->getDataCurrentAndPersistent();

                    /**
                     * The metadata useCache function has side effect
                     * and triggers a render that fails if the wiki file does not exists
                     */
                    $depends['files'][] = $this->instructionsCache->cache;
                    $depends['files'][] = $executingPath->toAbsolutePath()->toAbsoluteString();
                    $useCache = $this->metaCache->useCache($depends);
                    if ($useCache) {
                        $this->meta = $actualMeta;
                        return $this;
                    }
                }
            }

            /**
             * Process and derived meta
             */
            try {
                $wikiId = $this->getRequestedExecutingPath()->toWikiPath()->getWikiId();
            } catch (ExceptionCast|ExceptionNotFound $e) {
                // not a wiki path execution
                $wikiId = null;
            }

            /**
             * Dokuwiki global variable used to see if the process is in rendering mode
             * See {@link p_get_metadata()}
             * Store the original metadata in the global $METADATA_RENDERERS
             * ({@link p_set_metadata()} use it)
             */
            global $METADATA_RENDERERS;
            $METADATA_RENDERERS[$wikiId] =& $actualMeta;

            // add an extra key for the event - to tell event handlers the page whose metadata this is
            $actualMeta['page'] = $wikiId;
            $evt = new \dokuwiki\Extension\Event('PARSER_METADATA_RENDER', $actualMeta);
            if ($evt->advise_before()) {

                // get instructions (from string or file)
                $instructions = $this->getInstructions();

                // set up the renderer
                $renderer = new Doku_Renderer_metadata();


                /**
                 * Runtime/ Derived metadata
                 * The runtime meta are not even deleted
                 * (See {@link p_render_metadata()}
                 */
                $renderer->meta =& $actualMeta['current'];

                /**
                 * The {@link Doku_Renderer_metadata}
                 * will fail if the file and the date modified property does not exist
                 */
                try {
                    $path = $this->getRequestedExecutingPath();
                    if (!FileSystems::exists($path)) {
                        $renderer->meta['date']['modified'] = null;
                    }
                } catch (ExceptionNotFound $e) {
                    // ok
                }

                /**
                 * The persistent data are now available
                 */
                $renderer->persistent =& $actualMeta['persistent'];

                // Loop through the instructions
                foreach ($instructions as $instruction) {
                    // execute the callback against the renderer
                    call_user_func_array(array(&$renderer, $instruction[0]), (array)$instruction[1]);
                }

                $evt->result = array('current' => &$renderer->meta, 'persistent' => &$renderer->persistent);

            }
            $evt->advise_after();

            $this->meta = $evt->result;

            /**
             * Dokuwiki global variable
             * See {@link p_get_metadata()}
             */
            unset($METADATA_RENDERERS[$wikiId]);

            /**
             * Storage
             */
            if ($wikiId !== null) {
                p_save_metadata($wikiId, $this->meta);
                $this->metaCache->storeCache(time());
            }

        } finally {
            $executionContext->closeExecutingMarkupHandler();
        }
        return $this;

    }

    /**
     * @throws ExceptionNotFound
     */
    public function getMetadataPath(): LocalPath
    {
        if (isset($this->metaPath)) {
            return $this->metaPath;
        }
        throw new ExceptionNotFound("No meta path for this markup");
    }

    /**
     * A wrapper from when we are in a code block
     * were we expect to be a {@link self::isPathExecution()}
     * All path should then be available
     * @return Path
     */
    private
    function getExecutingPathOrFail(): Path
    {
        try {
            return $this->getRequestedExecutingPath();
        } catch (ExceptionNotFound $e) {
            throw new ExceptionRuntime($e);
        }
    }

    /**
     * A wrapper from when we are in a code block
     * were we expect to be a {@link self::isPathExecution()}
     * All path should then be available
     * @return Path
     */
    private
    function getMetaPathOrFail()
    {
        try {
            return $this->getMetadataPath();
        } catch (ExceptionNotFound $e) {
            throw new ExceptionRuntime($e);
        }
    }

    public function processInstructions(): FetcherMarkup
    {
        if (isset($this->processedInstructions)) {
            return $this;
        }

        $markup = $this->getMarkupStringToExecute();
        $executionContext = ExecutionContext::getActualOrCreateFromEnv()
            ->setExecutingMarkupHandler($this);
        try {
            $markupRenderer = MarkupRenderer::createFromMarkup($markup, $this->getExecutingPathOrNull(), $this->getRequestedContextPath())
                ->setRequestedMimeToInstruction();
            $instructions = $markupRenderer->getOutput();
            if (isset($this->instructionsCache)) {
                /**
                 * Not a string execution, ie {@link self::isPathExecution()}
                 * a path execution
                 */
                $this->instructionsCache->storeCache($instructions);
            }
            $this->processedInstructions = $instructions;
            return $this;
        } catch (\Exception $e) {
            throw new ExceptionRuntimeInternal("An error has occurred while getting the output. Error: {$e->getMessage()}", self::CANONICAL, 1, $e);
        } finally {
            $executionContext->closeExecutingMarkupHandler();
        }
    }

    public function getSnippetCachePath(): LocalPath
    {
        $cache = $this->getSnippetCacheStore()->cache;
        return LocalPath::createFromPathString($cache);

    }


}
