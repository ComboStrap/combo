<?php

namespace ComboStrap;

use Doku_Renderer;
use PHPUnit\Exception;

/**
 *
 * Adaptation of the function {@link p_render()}
 * for dynamic rendering because the {@link \Doku_Renderer_xhtml Renderer} has also state
 * such as the `counter_row` for the function {@link \Doku_Renderer_xhtml::table_open()}
 *
 * If {@link p_render()} is used multiple time, the renderer is recreated and the counter is reset to zero and the
 * row of each table is lost.
 *
 */
class MarkupDynamicRender
{
    /**
     * @var MarkupDynamicRender[]
     */
    static array $DYNAMIC_RENDERERS_CACHE = array();


    /**
     * @var string the format (xhtml, ...)
     */
    private string $format;


    /**
     * @var Doku_Renderer the renderer that calls the render function
     */
    private Doku_Renderer $renderer;

    /**
     * @throws ExceptionNotFound
     */
    public function __construct($format)
    {
        $this->format = $format;
        $renderer = p_get_renderer($format);
        if (is_null($renderer)) {
            throw new ExceptionNotFound("No renderer was found for the format $format");
        }

        $this->renderer = $renderer;
        $this->renderer->reset();
        $this->renderer->smileys = getSmileys();
        $this->renderer->entities = getEntities();
        $this->renderer->acronyms = getAcronyms();
        $this->renderer->interwiki = getInterwiki();
    }

    /**
     * @throws ExceptionNotFound
     */
    public static function create($format): MarkupDynamicRender
    {
        /**
         * Don't create a static object
         * to preserve the build because
         * the instructions may also recursively render.
         *
         * Therefore, a small instructions rendering such as a tooltip
         * would take the actual rendering and close the previous.
         */
        return new MarkupDynamicRender($format);
    }

    public static function createXhtml(): MarkupDynamicRender
    {
        try {
            return self::create("xhtml");
        } catch (ExceptionNotFound $e) {
            throw new ExceptionRuntimeInternal("xhtml should be available");
        }
    }

    public function setDateAt($date_at)
    {
        if ($this->renderer instanceof \Doku_Renderer_xhtml) {
            $this->renderer->date_at = $date_at;
        }

    }

    /**
     * @throws ExceptionCompile
     * @throws ExceptionBadState
     */
    public function processInstructions($callStackHeaderInstructions): string
    {

        try {

            // Loop through the instructions
            foreach ($callStackHeaderInstructions as $instruction) {
                // Execute the callback against the Renderer
                if (method_exists($this->renderer, $instruction[0])) {
                    call_user_func_array(array(&$this->renderer, $instruction[0]), $instruction[1] ?: array());
                }
            }

            // Post process
            // $data = array($this->format, & $this->renderer->doc);
            // \dokuwiki\Extension\Event::createAndTrigger('RENDERER_CONTENT_POSTPROCESS', $data);

            return $this->renderer->doc;


        } /** @noinspection PhpRedundantCatchClauseInspection */ catch (Exception $e) {
            /**
             * Example of errors;
             * method_exists() expects parameter 2 to be string, array given
             * inc\parserutils.php:672
             */
            throw new ExceptionCompile("Error while rendering instructions. Error was: {$e->getMessage()}", "dynamic renderer", 1, $e);
        } finally {
            $this->renderer->reset();
        }
    }

    public function __toString()
    {
        return "Dynamic $this->format renderer";
    }


}
