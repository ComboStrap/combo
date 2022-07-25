<?php

namespace ComboStrap;

use Doku_Renderer;
use PHPUnit\Exception;

/**
 *
 * Adaptation of the function {@link p_render()}
 * for dynamic rendering because the Renderer has also state
 * such as the `counter_row` for the function {@link \Doku_Renderer_xhtml::table_open()}
 *
 * If {@link p_render()} is used multiple time, the renderer is recreated and the counter is reset to zero and the
 * row of each table is lost.
 *
 */
class MarkupDynamicRender
{

    /**
     * When the rendering is a snippet or an instructions
     */
    public const DYNAMIC_RENDERING = "dynamic";


    /**
     * @var string the format (xhtml, ...)
     */
    private $format;

    /**
     * @var string the output
     */
    private $output;

    /**
     * @var Doku_Renderer the renderer that calls the render function
     */
    private $renderer;

    /**
     * @throws ExceptionNotFound
     */
    public function __construct($format)
    {
        $this->format = $format;
        $this->renderer = p_get_renderer($format);
        if (is_null($this->renderer)) {
            throw new ExceptionNotFound("No renderer was found for the format $format");
        }

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
        return new MarkupDynamicRender($format);
    }

    public static function createXhtml(): MarkupDynamicRender
    {
        return new MarkupDynamicRender("xhtml");
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
    public function processInstructions($callStackHeaderInstructions, $contextData = null)
    {
        global $ID;
        $keepID = $ID;
        global $ACT;
        $keepACT = $ACT;
        global $ID;
        $contextManager = ContextManager::getOrCreate();
        if ($contextData !== null) {
            $contextManager->setContextArrayData($contextData);
        }
        try {

            if ($ID === null && PluginUtility::isTest()) {
                $ID = ExecutionContext::DEFAULT_SLOT_ID_FOR_TEST;
            }
            $ACT = self::DYNAMIC_RENDERING;

            // Loop through the instructions
            foreach ($callStackHeaderInstructions as $instruction) {
                // Execute the callback against the Renderer
                if (method_exists($this->renderer, $instruction[0])) {
                    call_user_func_array(array(&$this->renderer, $instruction[0]), $instruction[1] ? $instruction[1] : array());
                }
            }

            // Post process
            $data = array($this->format, & $this->renderer->doc);
            \dokuwiki\Extension\Event::createAndTrigger('RENDERER_CONTENT_POSTPROCESS', $data);


        } catch (Exception $e) {
            /**
             * Example of errors;
             * method_exists() expects parameter 2 to be string, array given
             * inc\parserutils.php:672
             */
            throw new ExceptionCompile("Error while rendering instructions. Error was: {$e->getMessage()}");
        } finally {
            $ACT = $keepACT;
            $ID = $keepID;
            $contextManager->reset();
        }
    }

    public function getOutput(): string
    {
        return $this->renderer->doc;
    }


}
