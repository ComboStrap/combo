<?php

namespace ComboStrap;


class MarkupRenderer
{

    public const INSTRUCTION_EXTENSION = "i";
    const CANONICAL = "markup:renderer";
    const XHTML_RENDERER = "xhtml";
    const DEFAULT_RENDERER = self::XHTML_RENDERER;
    const METADATA_EXTENSION = "meta";
    /**
     * @var string source of the renderer is a markup (and not instructions)
     */
    private string $markupSource;
    /**
     * @var array source of the rendere is instructions
     */
    private array $instructionsSource;

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
     * @var ?Path the path from where the instructions/markup where created
     * This is mandatory to add cache dependency informations
     * and set the path that is executing
     */
    private ?Path $executingPath;


    /**
     * @param string $markup
     * @param Path|null $executingPath - the source of the markup - may be null (case of webcode)
     * @param WikiPath|null $contextPath - the requested markup path - may be null (case of webcode)
     * @return MarkupRenderer
     */
    public static function createFromMarkup(string $markup, ?Path $executingPath, ?WikiPath $contextPath): MarkupRenderer
    {
        $markupRenderer = (new MarkupRenderer())
            ->setMarkup($markup);
        if ($executingPath != null) {
            $markupRenderer->setRequestedExecutingPath($executingPath);
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

    public static function createFromInstructions($instructions, FetcherMarkup $fetcherMarkup): MarkupRenderer
    {
        return (new MarkupRenderer())
            ->setInstructions($instructions)
            ->setRequestedContextPath($fetcherMarkup->getRequestedContextPath())
            ->setRequestedExecutingPath($fetcherMarkup->getExecutingPathOrNull());
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
                return p_get_instructions($this->markupSource);

            default:
                /**
                 * The code below is adapted from {@link p_cached_output()}
                 * $ret = p_cached_output($file, 'xhtml', $pageid);
                 */
                if (!isset($this->instructionsSource)) {
                    $executingPath = null;
                    if (isset($this->executingPath)) {
                        $executingPath = $this->executingPath;
                    }

                    $contextPath = null;
                    if (isset($this->requestedContextPath)) {
                        $contextPath = $this->requestedContextPath;
                    }

                    $this->instructionsSource = MarkupRenderer::createFromMarkup($this->markupSource, $executingPath, $contextPath)
                        ->setRequestedMimeToInstruction()
                        ->getOutput();
                }

                /**
                 * Render
                 */
                $result = p_render($this->getRendererNameOrDefault(), $this->instructionsSource, $info);
                $this->cacheAfterRendering = $info['cache'] !== null ? $info['cache'] : false;
                return $result;

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

    private function setRequestedExecutingPath(?Path $executingPath): MarkupRenderer
    {
        $this->executingPath = $executingPath;
        return $this;
    }


}
