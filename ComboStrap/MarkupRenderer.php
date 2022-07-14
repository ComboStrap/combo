<?php

namespace ComboStrap;

use http\Exception\RuntimeException;

class MarkupRenderer
{

    public const INSTRUCTION_EXTENSION = "i";
    const CANONICAL = "markup:renderer";
    const DEFAULT_RENDERER = "xhtml";
    private string $markup;
    private bool $deleteRootElement = false;
    private Mime $requestedMime;
    private array $instructions;
    /**
     * @var mixed
     */
    private $cacheAfterRendering = true;
    private string $renderer;
    private WikiRequestEnvironment $wikiEnvRequest;
    private bool $closed = false;

    public static function createFromMarkup(string $markup): MarkupRenderer
    {
        return (new MarkupRenderer())
            ->setMarkup($markup);
    }

    private function setMarkup(string $markup): MarkupRenderer
    {
        $this->markup = $markup;
        return $this;
    }

    public static function createFromInstructions($instructions): MarkupRenderer
    {
        return (new MarkupRenderer())
            ->setInstructions($instructions);
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
            throw new RuntimeException("Internal error: the mime is internal and should be good");
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

        $this->build();

        $extension = $this->requestedMime->getExtension();
        switch ($extension) {
            case self::INSTRUCTION_EXTENSION:
                /**
                 * Get the instructions
                 * Adapted from {@link p_cached_instructions()}
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
                $instructions = p_get_instructions($this->markup);
                return $this->deleteRootPElementsIfRequested($instructions);

            default:
                /**
                 * The code below is adapted from {@link p_cached_output()}
                 * $ret = p_cached_output($file, 'xhtml', $pageid);
                 */
                if (!isset($this->instructions)) {
                    $this->instructions = MarkupRenderer::createFromMarkup($this->markup)
                        ->setRequestedMimeToInstruction()
                        ->getOutput();
                }

                /**
                 * Render
                 */
                $result = p_render($this->getRendererNameOrDefault(), $this->instructions, $info);
                $this->cacheAfterRendering = $info['cache'];
                return $result;

        }


    }

    private function setInstructions(array $instructions): MarkupRenderer
    {
        $this->instructions = $instructions;
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

    public function setRequestedMimeToXhtml(): MarkupRenderer
    {
        try {
            return $this->setRequestedMime(Mime::createFromExtension("xhtml"));
        } catch (ExceptionNotFound $e) {
            throw new RuntimeException("Internal error", 0, $e);
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

    public function close(): MarkupRenderer
    {
        if ($this->closed) {
            // to avoid restoring a bad state
            throw new ExceptionRuntimeInternal("You can't close a already closed object", self::CANONICAL);
        }
        $this->wikiEnvRequest->restoreState();
        $this->closed = true;
        return $this;
    }

    private function build()
    {
        $this->wikiEnvRequest = WikiRequestEnvironment::createAndCaptureState();
        if ($this->wikiEnvRequest->getActualGlobalId() === null && PluginUtility::isTest()) {
            $this->wikiEnvRequest->setNewRunningId(WikiRequestEnvironment::DEFAULT_SLOT_ID_FOR_TEST);
        }
        if (
            isset($this->markup)
            && $this->requestedMime->getExtension() !== self::INSTRUCTION_EXTENSION
        ) {
            $this->wikiEnvRequest->setNewAct(MarkupDynamicRender::DYNAMIC_RENDERING);
        }
    }

}
