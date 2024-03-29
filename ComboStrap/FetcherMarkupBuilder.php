<?php

namespace ComboStrap;

use dokuwiki\Cache\CacheInstructions;
use dokuwiki\Cache\CacheParser;
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
class FetcherMarkupBuilder
{

    /**
     * Private are they may be null
     */
    private ?string $builderMarkupString = null;
    private ?Path $builderMarkupSourcePath = null;
    private ?array $builderRequestedInstructions = null;

    protected WikiPath $requestedContextPath;
    protected Mime $mime;
    protected bool $deleteRootBlockElement = false;
    protected string $rendererName;


    protected bool $isDoc;
    protected array $builderContextData;
    private bool $isCodeStandAloneExecution = false;
    /**
     * @var FetcherMarkup - a parent if any
     */
    private FetcherMarkup $parentMarkupHandler;


    public function __construct()
    {
    }

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
    public static function getWikiIdAndLocalFileDokuwikiCompliant(Path $sourcePath): array
    {

        try {
            $markuSourceWikiPath = $sourcePath->toWikiPath();

            if ($markuSourceWikiPath->getDrive() === WikiPath::MARKUP_DRIVE) {
                /**
                 * Dokuwiki special function
                 * that should be the same to conform to the cache key
                 */
                $wikiId = $markuSourceWikiPath->getWikiId();
                $localFile = wikiFN($wikiId);
            } else {
                $localFile = $markuSourceWikiPath->toLocalPath();
                $wikiId = $markuSourceWikiPath->toUriString();
            }
        } catch (ExceptionCast $e) {
            $wikiId = $sourcePath->toAbsoluteId();
            try {
                $localFile = $sourcePath->toLocalPath();
            } catch (ExceptionCast $e) {
                throw new ExceptionRuntimeInternal("The source path ({$sourcePath}) is not supported as markup source path.", $e);
            }
        }
        return [$wikiId, $localFile];
    }

    /**
     * @param string $markupString - the markup is a string format
     * @return FetcherMarkupBuilder
     */
    public function setRequestedMarkupString(string $markupString): FetcherMarkupBuilder
    {
        $this->builderMarkupString = $markupString;
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
        $this->deleteRootBlockElement = $b;
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
            $this->builderMarkupSourcePath = $executingPath->toWikiPath();
        } catch (ExceptionCast $e) {
            $this->builderMarkupSourcePath = $executingPath;
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


    /**
     * Technically, you could set the mime to whatever you want
     * and still get the instructions via {@link FetcherMarkup::getInstructions()}
     * Setting the mime to instructions will just not do any render processing.
     * @return $this
     */
    public function setRequestedMimeToInstructions(): FetcherMarkupBuilder
    {
        try {
            $this->setRequestedMime(Mime::createFromExtension(MarkupRenderer::INSTRUCTION_EXTENSION));
        } catch (ExceptionNotFound $e) {
            throw new ExceptionRuntime("Internal error: the mime is internal and should be good");
        }
        return $this;

    }


    /**
     * @throws ExceptionNotExists
     */
    public function build(): FetcherMarkup
    {

        /**
         * One input should be given
         */
        if ($this->builderMarkupSourcePath === null && $this->builderMarkupString === null && $this->builderRequestedInstructions === null) {
            throw new ExceptionRuntimeInternal("A markup source path, a markup string or instructions should be given");
        }
        /**
         * Only one input should be given
         */
        $foundInput = "";
        if ($this->builderMarkupSourcePath !== null) {
            $foundInput = "markup path";
        }
        if ($this->builderMarkupString !== null) {
            if (!empty($foundInput)) {
                throw new ExceptionRuntimeInternal("Only one input should be given, we have found 2 inputs ($foundInput and markup string)");
            }
            $foundInput = "markup string";

        }
        if ($this->builderRequestedInstructions !== null) {
            if (!empty($foundInput)) {
                throw new ExceptionRuntimeInternal("Only one input should be given, we have found 2 inputs ($foundInput and instructions)");
            }
        }

        /**
         * Other Mandatory
         */
        if (!isset($this->mime)) {
            throw new ExceptionRuntimeInternal("A mime is mandatory");
        }
        if (!isset($this->requestedContextPath)) {
            throw new ExceptionRuntimeInternal("A context path is mandatory");
        }

        /**
         * The object type is mandatory
         */
        if (!isset($this->rendererName)) {
            switch ($this->mime->toString()) {
                case Mime::XHTML:
                    /**
                     * ie last name of {@link \Doku_Renderer_xhtml}
                     */
                    $rendererName = MarkupRenderer::XHTML_RENDERER;
                    break;
                case Mime::META:
                    /**
                     * ie last name of {@link \Doku_Renderer_metadata}
                     */
                    $rendererName = "metadata";
                    break;
                case Mime::INSTRUCTIONS:
                    /**
                     * Does not exist yet bu that the future
                     */
                    $rendererName = FetcherMarkupInstructions::NAME;
                    break;
                default:
                    throw new ExceptionRuntimeInternal("A renderer name (ie builder name/output object type) is mandatory");
            }
        } else {
            $rendererName = $this->rendererName;
        }

        /**
         * Building
         */
        $newFetcherMarkup = new FetcherMarkup();
        $newFetcherMarkup->builderName = $rendererName;

        $newFetcherMarkup->requestedContextPath = $this->requestedContextPath;
        if ($this->builderMarkupString !== null) {
            $newFetcherMarkup->markupString = $this->builderMarkupString;
        }
        if ($this->builderMarkupSourcePath !== null) {
            $newFetcherMarkup->markupSourcePath = $this->builderMarkupSourcePath;
            if (!FileSystems::exists($this->builderMarkupSourcePath)) {
                /**
                 * Too much edge case for now with dokuwiki
                 * The {@link \Doku_Renderer_metadata} for instance throws an error if the file does not exist
                 * ... etc ....
                 */
                throw new ExceptionNotExists("The executing source file ({$this->builderMarkupSourcePath}) does not exist");
            }
        }
        if ($this->builderRequestedInstructions !== null) {
            $newFetcherMarkup->requestedInstructions = $this->builderRequestedInstructions;
        }
        $newFetcherMarkup->mime = $this->mime;
        $newFetcherMarkup->deleteRootBlockElement = $this->deleteRootBlockElement;


        $newFetcherMarkup->isDoc = $this->getIsDocumentExecution();
        if (isset($this->builderContextData)) {
            $newFetcherMarkup->contextData = $this->builderContextData;
        }

        if (isset($this->parentMarkupHandler)) {
            $newFetcherMarkup->parentMarkupHandler = $this->parentMarkupHandler;
        }
        $newFetcherMarkup->isNonPathStandaloneExecution = $this->isCodeStandAloneExecution;


        /**
         * We build the cache dependencies even if there is no source markup path (therefore no cache store)
         * (Why ? for test purpose, where we want to check if the dependencies was applied)
         * !!! Attention, the build of the dependencies should happen after that the markup source path is set !!!
         */
        $newFetcherMarkup->outputCacheDependencies = MarkupCacheDependencies::create($newFetcherMarkup);

        /**
         * The cache object depends on the running request
         * We build it then just
         *
         * A request is also send by dokuwiki to check the cache validity
         *
         */
        if ($this->builderMarkupSourcePath !== null) {


            list($wikiId, $localFile) = self::getWikiIdAndLocalFileDokuwikiCompliant($this->builderMarkupSourcePath);

            /**
             * Instructions cache
             */
            $newFetcherMarkup->instructionsCache = new CacheInstructions($wikiId, $localFile);

            /**
             * Content cache
             */
            $extension = $this->mime->getExtension();
            $newFetcherMarkup->contentCache = new CacheRenderer($wikiId, $localFile, $extension);
            $newFetcherMarkup->outputCacheDependencies->rerouteCacheDestination($newFetcherMarkup->contentCache);

            /**
             * Snippet Cache
             * Snippet.json is data dependent
             *
             * For instance, the carrousel may add glide or grid as snippet. It depends on the the number of backlinks.
             *
             * Therefore the output should be unique by rendered slot
             * Therefore we reroute (recalculate the cache key to the same than the html file)
             */
            $newFetcherMarkup->snippetCache = new CacheParser($wikiId, $localFile, "snippet.json");
            $newFetcherMarkup->outputCacheDependencies->rerouteCacheDestination($newFetcherMarkup->snippetCache);


            /**
             * Runtime Meta cache
             * (Technically, it's derived from the instructions)
             */
            $newFetcherMarkup->metaPath = LocalPath::createFromPathString(metaFN($wikiId, '.meta'));
            $newFetcherMarkup->metaCache = new CacheRenderer($wikiId, $localFile, 'metadata');

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

    /**
     * @param bool $isDoc - if the markup is a document or a fragment
     * @return $this
     * If the markup is a document, an outline is added, a toc is calculated.
     *
     * The default is execution parameters dependent if not set
     * and is calculated at {@link FetcherMarkupBuilder::getIsDocumentExecution()}
     */
    public function setIsDocument(bool $isDoc): FetcherMarkupBuilder
    {
        $this->isDoc = $isDoc;
        return $this;
    }

    /**
     * @param array $instructions
     * @return FetcherMarkupBuilder
     */
    public function setRequestedInstructions(array $instructions): FetcherMarkupBuilder
    {
        $this->builderRequestedInstructions = $instructions;
        return $this;
    }

    /**
     * @param array|null $contextData
     * @return $this
     */
    public function setContextData(?array $contextData): FetcherMarkupBuilder
    {
        if ($contextData == null) {
            return $this;
        }
        $this->builderContextData = $contextData;
        return $this;
    }

    /**
     * @param bool $isStandAlone
     * @return $this
     *
     * when a execution is not a {@link FetcherMarkup::isPathExecution()}, the snippet will not be stored automatically.
     * To avoid this problem, a warning is send if the calling code does not set explicitly that this is specifically a
     * standalone execution
     */
    public function setIsStandAloneCodeExecution(bool $isStandAlone): FetcherMarkupBuilder
    {
        $this->isCodeStandAloneExecution = $isStandAlone;
        return $this;
    }

    /**
     * Determins if the run is a fragment or document execution
     *
     * Note: in dokuwiki term, a {@link ExecutionContext::PREVIEW_ACTION}
     * preview action is a fragment
     *
     * @return bool true if this is a document execution
     */
    private function getIsDocumentExecution(): bool
    {

        if (isset($this->isDoc)) {
            return $this->isDoc;
        }

        /**
         * By default, a string is not a whole doc
         * (in test, this is almost always the case)
         */
        if ($this->builderMarkupString !== null) {
            return false;
        }

        /**
         * By default, a instructions array is not a whole doc
         * (in test and rendering, this is almost always the case)
         */
        if ($this->builderRequestedInstructions !== null) {
            return false;
        }

        try {

            /**
             * What fucked up is fucked up
             * Fragment such as sidebar may run in their own context (ie when editing for instance)
             * but are not a document
             */
            $executingWikiPath = $this->builderMarkupSourcePath->toWikiPath();
            $isFragmentMarkup = MarkupPath::createPageFromPathObject($executingWikiPath)->isSlot();
            if ($isFragmentMarkup) {
                return false;
            }

            /**
             * If the context and executing path are:
             * * the same, this is a document run
             * * not the same, this is a fragment run
             */
            if ($this->requestedContextPath->toUriString() !== $executingWikiPath->toUriString()) {
                return false;
            }

        } catch (ExceptionCast $e) {
            // no executing path, not a wiki path
        }

        return true;

    }

    public function setParentMarkupHandler(FetcherMarkup $parentMarkupHandler): FetcherMarkupBuilder
    {
        $this->parentMarkupHandler = $parentMarkupHandler;
        return $this;
    }


}
