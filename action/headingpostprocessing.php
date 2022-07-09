<?php


use ComboStrap\CallStack;
use ComboStrap\ExceptionBadArgument;
use ComboStrap\FetcherPage;
use ComboStrap\LogUtility;
use ComboStrap\MarkupDynamicRender;
use ComboStrap\Outline;
use ComboStrap\PageFragment;
use ComboStrap\PageLayout;
use ComboStrap\Site;
use ComboStrap\Toc;
use ComboStrap\WikiPath;
use ComboStrap\WikiRequestEnvironment;

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

        $act = WikiRequestEnvironment::createAndCaptureState()->getActualAct();
        switch ($act) {
            case MarkupDynamicRender::DYNAMIC_RENDERING:
                $callStack = CallStack::createFromHandler($handler);
                // no outline or edit button for dynamic rendering
                // but closing of atx heading
                $handler->calls = Outline::createFromCallStack($callStack)
                    ->toDynamicInstructionCalls();
                return;
            case "show":
                $runningMarkup = WikiPath::createRunningPageFragmentPathFromGlobalId();
                $requestedPath = WikiPath::createRequestedPagePathFromRequest();
                if ($requestedPath->toPathString() !== $runningMarkup->toPathString()) {
                    return;
                }
                $callStack = CallStack::createFromHandler($handler);
                $outline = Outline::createFromCallStack($callStack);
                if (Site::getTemplate() !== Site::STRAP_TEMPLATE_NAME) {
                    $handler->calls = $outline->toDefaultTemplateInstructionCalls();
                } else {
                    $fetcherPage = FetcherPage::createPageFetcherFromRequestedPage();
                    try {
                        if (!$fetcherPage->hasContentHeader()) {
                            $handler->calls = $outline->toHtmlSectionOutlineCalls();
                        } else {
                            $handler->calls = $outline->toHtmlSectionOutlineCallsWithoutHeader();
                        }
                    } finally {
                        $fetcherPage->close();
                    }
                }
                /**
                 * TOC
                 */
                $toc = $outline->getTocDokuwikiFormat();
                try {
                    Toc::createForRequestedPage()
                        ->setValue($toc)
                        ->persist();
                } catch (ExceptionBadArgument $e) {
                    LogUtility::error("The Toc could not be persisted. Error:{$e->getMessage()}");
                }
                return;
            default:
                // No outline if not show (ie admin, edit, ...)
        }



    }

}
