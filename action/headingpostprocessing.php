<?php


use ComboStrap\CallStack;
use ComboStrap\ExceptionBadArgument;
use ComboStrap\FetcherPage;
use ComboStrap\LogUtility;
use ComboStrap\MarkupDynamicRender;
use ComboStrap\Outline;
use ComboStrap\MarkupPath;
use ComboStrap\PageLayoutName;
use ComboStrap\Site;
use ComboStrap\Toc;
use ComboStrap\WikiPath;
use ComboStrap\WikiRequest;

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

        /**
         * @var Doku_Handler $handler
         */
        $handler = $event->data;

        $act = WikiRequest::get()->getActualAct();
        switch ($act) {
            case MarkupDynamicRender::DYNAMIC_RENDERING:
                $callStack = CallStack::createFromHandler($handler);
                // no outline or edit button for dynamic rendering
                // but closing of atx heading
                $handler->calls = Outline::createFromCallStack($callStack)
                    ->toDynamicInstructionCalls();
                return;
            case "show":
                $runningMarkup = WikiPath::createRunningMarkupWikiPath();
                $requestedPath = WikiPath::createRequestedPagePathFromRequest();
                if ($requestedPath->toPathString() !== $runningMarkup->toPathString()) {
                    return;
                }
                $callStack = CallStack::createFromHandler($handler);
                $requestedMarkup = MarkupPath::createPageFromPathObject($requestedPath);
                $outline = Outline::createFromCallStack($callStack, $requestedMarkup);
                if (Site::getTemplate() !== Site::STRAP_TEMPLATE_NAME) {
                    $handler->calls = $outline->toDefaultTemplateInstructionCalls();
                } else {
                    $handler->calls = $outline->toHtmlSectionOutlineCalls();
                }
                return;
            default:
                // No outline if not show (ie admin, edit, ...)
        }



    }

}
