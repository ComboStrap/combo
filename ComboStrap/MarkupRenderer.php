<?php

namespace ComboStrap;


class MarkupRenderer
{

    public const INSTRUCTION_EXTENSION = "i";
    const CANONICAL = "markup:renderer";
    const DEFAULT_RENDERER = "xhtml";
    /**
     * @var string source of the renderer is a markup (and not instructions)
     */
    private string $markupSource;
    /**
     * @var array source of the rendere is instructions
     */
    private array $instructionsSource;

    private bool $deleteRootElement = false;
    private Mime $requestedMime;

    /**
     * @var mixed
     */
    private $cacheAfterRendering = true;
    private string $renderer;

    /**
     * @var WikiPath the context path
     * May be null (ie markup rendering without any context such as webcode)
     */
    private WikiPath $requestedContextPath;

    /**
     * @var Path the path from where the instructions/markup where created
     * This is mandatory to add cache dependency informations
     * and set the path that is executing
     */
    private Path $sourcePath;


    /**
     * @param string $markup
     * @param Path|null $markupSourcePath - the source of the markup - may be null (case of webcode)
     * @param WikiPath|null $contextPath - the requested markup path - may be null (case of webcode)
     * @return MarkupRenderer
     */
    public static function createFromMarkup(string $markup, ?Path $markupSourcePath, ?WikiPath $contextPath): MarkupRenderer
    {
        $markupRenderer = (new MarkupRenderer())
            ->setMarkup($markup);
        if ($markupSourcePath != null) {
            $markupRenderer->setSourcePath($markupSourcePath);
        }
        if ($contextPath != null) {
            $markupRenderer->setRequestedContextPath($contextPath);
        }
        return $markupRenderer;

    }

    private function setMarkup(string $markup): MarkupRenderer
    {
        $this->markupSource = $markup;
        return $this;
    }

    public static function createFromInstructions($instructions, Path $instructionsPath, WikiPath $contextPath): MarkupRenderer
    {
        return (new MarkupRenderer())
            ->setInstructions($instructions)
            ->setRequestedContextPath($contextPath)
            ->setSourcePath($instructionsPath);
    }

    /**
     * Dokuwiki will wrap the markup in a p element
     * if the first element is not a block
     * This option permits to delete it. This is used mostly in test to get
     * the generated html
     * @param bool $b
     * @return $this
     */
    public function setDeleteRootBlockElement(bool $b): MarkupRenderer
    {
        $this->deleteRootElement = $b;
        return $this;
    }


    public function setRequestedMimeToInstruction(): MarkupRenderer
    {
        try {
            $this->setRequestedMime(Mime::createFromExtension(self::INSTRUCTION_EXTENSION));
        } catch (ExceptionNotFound $e) {
            throw new ExceptionRuntime("Internal error: the mime is internal and should be good");
        }
        return $this;

    }

    public function setRequestedMime(Mime $requestedMime): MarkupRenderer
    {
        $this->requestedMime = $requestedMime;
        return $this;
    }

    public function getOutput()
    {

        /**
         * Context Path
         */
        $executionContext = ExecutionContext::getActualOrCreateFromEnv();
        if (isset($this->sourcePath)) {
            if ($this->sourcePath instanceof WikiPath) {
                $runningId = $this->sourcePath->getWikiId();
            } else {
                $runningId = $this->sourcePath->toQualifiedId();
            }
        } else {
            try {
                $runningId = $this->getRequestedContextPath()->getWikiId();
            } catch (ExceptionNotFound $e) {
                try {
                    $runningId = $executionContext->getRequestedWikiId();
                } catch (ExceptionNotFound $e) {
                    $runningId = "markup-renderer-default";
                }
            }
        }

        /**
         * Dynamic rendering ?
         */
        $runningAct = $executionContext->getExecutingAction();
        if (
            isset($this->markupSource)
            && $this->requestedMime->getExtension() !== self::INSTRUCTION_EXTENSION
        ) {
            $runningAct = MarkupDynamicRender::DYNAMIC_RENDERING;
        }
        $executionContext->startSubExecutionEnv(MarkupRenderer::class, $runningId, $runningAct);

        try {
            $extension = $this->requestedMime->getExtension();
            switch ($extension) {
                case self::INSTRUCTION_EXTENSION:
                    /**
                     * Get the instructions adapted from {@link p_cached_instructions()}
                     *
                     * Note that this code may not run at first rendering
                     *
                     * Why ?
                     * Because dokuwiki asks first page information
                     * via the {@link pageinfo()} method.
                     * This function then render the metadata (ie {@link p_render_metadata()} and therefore will trigger
                     * the rendering with this function
                     * ```p_cached_instructions(wikiFN($id),false,$id)```
                     *
                     * The best way to manipulate the instructions is not before but after
                     * the parsing. See {@link \action_plugin_combo_headingpostprocessing}
                     *
                     */
                    $instructions = p_get_instructions($this->markupSource);
                    return $this->deleteRootPElementsIfRequested($instructions);

                default:
                    /**
                     * The code below is adapted from {@link p_cached_output()}
                     * $ret = p_cached_output($file, 'xhtml', $pageid);
                     */
                    if (!isset($this->instructionsSource)) {
                        $this->instructionsSource = MarkupRenderer::createFromMarkup($this->markupSource, $this->sourcePath, $this->requestedContextPath)
                            ->setRequestedMimeToInstruction()
                            ->setDeleteRootBlockElement($this->deleteRootElement)
                            ->getOutput();
                    }

                    /**
                     * Render
                     */
                    $result = p_render($this->getRendererNameOrDefault(), $this->instructionsSource, $info);
                    $this->cacheAfterRendering = $info['cache'];
                    return $result;

            }
        } finally {

            $executionContext->closeSubExecutionEnv();

        }


    }

    private function setInstructions(array $instructions): MarkupRenderer
    {
        $this->instructionsSource = $instructions;
        return $this;
    }


    function getRendererNameOrDefault(): string
    {
        if (isset($this->renderer)) {
            return $this->renderer;
        }
        /**
         * Note: This value is passed to {@link p_get_renderer} to get the renderer class
         */
        return self::DEFAULT_RENDERER;
    }

    public function setRendererName(string $rendererName): MarkupRenderer
    {
        $this->renderer = $rendererName;
        return $this;
    }

    public function getCacheAfterRendering()
    {
        return $this->cacheAfterRendering;
    }

    /**
     * The page context in which this markup was requested
     * @param WikiPath $path
     * @return $this
     */
    private function setRequestedContextPath(WikiPath $path): MarkupRenderer
    {
        $this->requestedContextPath = $path;
        return $this;
    }

    public function setRequestedMimeToXhtml(): MarkupRenderer
    {
        try {
            return $this->setRequestedMime(Mime::createFromExtension("xhtml"));
        } catch (ExceptionNotFound $e) {
            throw new ExceptionRuntime("Internal error", 0, $e);
        }
    }

    private function deleteRootPElementsIfRequested(array $instructions): array
    {
        if ($this->deleteRootElement === false) {
            return $instructions;
        }

        /**
         * Delete the p added by {@link Block::process()}
         * if the plugin of the {@link SyntaxPlugin::getPType() normal} and not in a block
         *
         * p_open = document_start in renderer
         */
        if ($instructions[1][0] !== 'p_open') {
            return $instructions;
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

        return $instructions;
    }

    /**
     * @throws ExceptionNotFound
     */
    private function getRequestedContextPath(): WikiPath
    {

        if (!isset($this->requestedContextPath)) {
            throw new ExceptionNotFound("No requested context path");
        }
        return $this->requestedContextPath;

    }

    private function setSourcePath(Path $sourcePath): MarkupRenderer
    {
        $this->sourcePath = $sourcePath;
        return $this;
    }


}
