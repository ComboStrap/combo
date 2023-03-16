<?php


use ComboStrap\CallStack;
use ComboStrap\ExceptionNotFound;
use ComboStrap\ExecutionContext;
use ComboStrap\HeadingTag;
use ComboStrap\MarkupPath;
use ComboStrap\Outline;
use ComboStrap\WikiPath;

class action_plugin_combo_headingpostprocessing extends DokuWiki_Action_Plugin
{


    /**
     * This section are not HTML
     * section, they are edit section
     * that delimits the edit area
     */
    const EDIT_SECTION_OPEN = 'section_open';
    const EDIT_SECTION_CLOSE = 'section_close';
    const HEADING_TAGS = [
        HeadingTag::HEADING_TAG,
        syntax_plugin_combo_headingatx::TAG,
        syntax_plugin_combo_headingwiki::TAG
    ];

    const CANONICAL = Outline::CANONICAL;


    public
    function register(\Doku_Event_Handler $controller)
    {
        /**
         * Found in {@link Doku_Handler::finalize()}
         *
         * Doc: https://www.dokuwiki.org/devel:event:parser_handler_done
         */
        $controller->register_hook(
            'PARSER_HANDLER_DONE',
            'AFTER',
            $this,
            '_post_process_heading',
            array()
        );

    }


    /**
     * Transform the special heading atx call
     * in an enter and exit heading atx calls
     *
     * Add the section close / open
     *
     * Build the toc
     * And create the main are
     *
     * Code extracted and adapted from the end of {@link Doku_Handler::header()}
     *
     * @param   $event Doku_Event
     */
    function _post_process_heading(&$event, $param)
    {

        /**
         * @var Doku_Handler $handler
         */
        $handler = $event->data;

        $executionContext = ExecutionContext::getActualOrCreateFromEnv();
        try {
            $fetcherMarkup = $executionContext->getExecutingMarkupHandler();
            $isFragment = $fetcherMarkup->isFragment() === true;
            try {
                $executingPath = $fetcherMarkup->getRequestedExecutingPath();
            } catch (ExceptionNotFound $e) {
                $executingPath = null;
            }
        } catch (ExceptionNotFound $e) {
            /**
             * What fucked up is fucked up !
             * {@link pageinfo()} in common may starts before the {@link action_plugin_combo_docustom handler } is called
             * {@link action_plugin_combo_docustom}
             */
            $requestedPath = $executionContext->getRequestedPath();
            $isFragment = true;
            $executingPath = null;
            try {
                $executingId = $executionContext->getExecutingWikiId();
                $isFragment = MarkupPath::createPageFromPathObject($requestedPath)->isKnownFragmentMarkup();
                if ($isFragment === false) {
                    if ($executingId !== $requestedPath->getWikiId()) {
                        $isFragment = true;
                    }
                }
                $executingPath = WikiPath::createMarkupPathFromId($executingId);
            } catch (ExceptionNotFound $e) {
                //
            }
        }

        /**
         * Fragment execution
         */
        if ($isFragment) {
            $callStack = CallStack::createFromHandler($handler);
            // no outline or edit button for dynamic rendering
            // but closing of atx heading
            $handler->calls = Outline::createFromCallStack($callStack)
                ->toFragmentInstructionCalls();
            return;
        }

        /**
         * Document execution
         * (add outline section, ...)
         */
        $callStack = CallStack::createFromHandler($handler);
        if ($executingPath !== null) {
            $executingMarkupPath = MarkupPath::createPageFromPathObject($executingPath);
        } else {
            $executingMarkupPath = null;
        }
        $outline = Outline::createFromCallStack($callStack, $executingMarkupPath);
        if (!$executionContext->getConfig()->isThemeSystemEnabled()) {
            $handler->calls = $outline->toDokuWikiTemplateInstructionCalls();
        } else {
            $handler->calls = $outline->toHtmlSectionOutlineCalls();
        }


    }

}
