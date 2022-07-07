<?php


use ComboStrap\CallStack;
use ComboStrap\MarkupDynamicRender;
use ComboStrap\Outline;
use ComboStrap\Site;
use ComboStrap\TocUtility;
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
        syntax_plugin_combo_heading::TAG,
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


        $runningMarkup = WikiPath::createRunningPageFragmentPathFromGlobalId();
        $requestedPath = WikiPath::createRequestedPagePathFromRequest();
        if ($requestedPath->toPathString()!==$runningMarkup->toPathString()) {
            return;
        }

        /**
         * @var Doku_Handler $handler
         */
        $handler = $event->data;
        $callStack = CallStack::createFromHandler($handler);
        $outline = Outline::createFromCallStack($callStack);

        global $ACT;
        switch ($ACT) {
            case MarkupDynamicRender::DYNAMIC_RENDERING:
                // no outline or edit button for dynamic rendering
                // but closing of atx heading
                $handler->calls = $outline->toDynamicInstructionCalls();
                return;
            case "show":
                break;
            default:
                // No outline if not show (ie admin, edit, ...)
                return;
        }

        if (Site::getTemplate() !== Site::STRAP_TEMPLATE_NAME) {
            $handler->calls = $outline->toDefaultTemplateInstructionCalls();
        } else {
            $handler->calls = $outline->toHtmlSectionOutlineCalls();
        }

        /**
         * TOC
         */
        $toc = $outline->getTocDokuwikiFormat();
        if (TocUtility::shouldTocBePrinted($toc)) {
            global $TOC;
            $TOC = $toc;
        }


    }

}
