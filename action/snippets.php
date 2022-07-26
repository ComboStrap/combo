<?php

use ComboStrap\CacheManager;
use ComboStrap\ExceptionNotFound;
use ComboStrap\ExecutionContext;
use ComboStrap\MarkupDynamicRender;
use ComboStrap\FetcherMarkup;
use ComboStrap\MarkupPath;
use ComboStrap\PluginUtility;
use ComboStrap\SnippetSystem;

if (!defined('DOKU_INC')) die();

/**
 *
 *
 * Add the snippet needed by the components
 *
 */
class action_plugin_combo_snippets extends DokuWiki_Action_Plugin
{

    const CLASS_SNIPPET_IN_CONTENT = "snippet-content-combo";
    const SNIPPETS_WERE_ADDED = "snippet_added";


    function __construct()
    {
        // enable direct access to language strings
        // ie $this->lang
        $this->setupLocale();
    }

    public function register(Doku_Event_Handler $controller)
    {

        /**
         * To add the snippets in the header
         */
        $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'componentSnippetHead', array());

        /**
         * To add the snippets in the content
         * if they have not been added to the header
         *
         * Not https://www.dokuwiki.org/devel:event:tpl_content_display TPL_ACT_RENDER
         * or https://www.dokuwiki.org/devel:event:tpl_act_render
         * because it works only for the main content
         * in {@link tpl_content()}
         *
         * We use
         * https://www.dokuwiki.org/devel:event:renderer_content_postprocess
         * that is in {@link p_render()} and takes into account also the slot page.
         */
        $controller->register_hook('RENDERER_CONTENT_POSTPROCESS', 'AFTER', $this, 'componentSnippetContent', array());


    }


    /**
     *
     * Add the snippets in the head
     *
     * @param $event
     */
    function componentSnippetHead($event)
    {

        /**
         * Advertise that this event has occurred
         * In a strap template, this event is last because head are added after content rendering
         * In another template, this event is first
         * The function {@link action_plugin_combo_snippets::componentSnippetContent()} used it to determine if
         * the snippets should be added into the content
         */
        $executionContext = ExecutionContext::getRootOrCreateFromEnv();

        try {
            $snippetAlreadyAdded = $executionContext->getRuntimeBoolean(self::SNIPPETS_WERE_ADDED);
            if ($snippetAlreadyAdded === true) {
                return;
            }
        } catch (ExceptionNotFound $e) {
            $executionContext->setRuntimeBoolean(self::SNIPPETS_WERE_ADDED, true);
        }


        try {
            $executionContext->getWikiId();
        } catch (ExceptionNotFound $e) {
            global $_SERVER;
            $scriptName = $_SERVER['SCRIPT_NAME'];
            /**
             * If this is an ajax call, return
             * only if this not from webcode
             */
            if (strpos($scriptName, "/lib/exe/ajax.php") !== false) {
                global $_REQUEST;
                $call = $_REQUEST['call'];
                if ($call !== action_plugin_combo_ajax::COMBO_CALL_NAME) {
                    return;
                }
            } else if (!(strpos($scriptName, "/lib/exe/detail.php") !== false)) {
                /**
                 * Image page has an header and footer that may needs snippet
                 * We return only if this is not a image/detail page
                 */
                return;
            }
        }

        /**
         * For each processed slot in the page, retrieve the snippets
         */
        $cacheReporters = CacheManager::getFromContextExecution()->getCacheResults();
        if ($cacheReporters !== null) {
            foreach ($cacheReporters as $cacheReporter) {

                foreach ($cacheReporter->getResults() as $report) {

                    if ($report->getMode() !== FetcherMarkup::XHTML_MODE) {
                        continue;
                    }

                    $pageFragment = $report->getPageFragment()->getHtmlFetcher();
                    try {
                        $pageFragment->loadSnippets();
                    } finally {
                        $pageFragment->close();
                    }

                }

            }
        }

        $snippetSystem = SnippetSystem::getFromContext();
        $allSnippets = $snippetSystem->snippetsToDokuwikiArray($snippetSystem->getAllSnippets());
        foreach ($allSnippets as $tagType => $tags) {

            foreach ($tags as $tag) {
                $event->data[$tagType][] = $tag;
            }

        }


    }

    /**
     *
     * This function store the snippets in the HTML content when needed
     * (mostly admin page or any other template than strap ...)
     *
     * This event/function is called first because {@link \ComboStrap\FetcherPage} parse the main markup first (It's the driver)
     *
     * In any other template, they follows the creation of the page, the
     * header are called first, then the content
     *
     *
     * @param $event
     */
    function componentSnippetContent($event)
    {

        /**
         * Add snippet in the content
         *  - if the header output was already called
         *  - if this is not a page rendering (ie an admin rendering)
         * for instance, the upgrade plugin call {@link p_cached_output()} on local file
         */

        /**
         * Dynamic rendering call this event
         * We don't add any component at this moment
         */
        global $ACT;
        if ($ACT === MarkupDynamicRender::DYNAMIC_RENDERING) {
            return;
        }


        $format = $event->data[0];
        if ($format !== "xhtml") {
            return;
        }

        $executionContext = ExecutionContext::getRootOrCreateFromEnv();

        try {
            $snippetsWereAlreadyAdded = $executionContext->getRuntimeBoolean(self::SNIPPETS_WERE_ADDED);
        } catch (ExceptionNotFound $e) {
            $snippetsWereAlreadyAdded = false;
        }

        /**
         * Put snippet in the content
         * if this is not a show (ie Admin page rendering)
         *
         * And if the header output was already called
         * (case that the template is not strap)
         */
        $putSnippetInContent =
            $snippetsWereAlreadyAdded === true
            ||
            ($ACT !== "show" && $ACT !== null);
        if (!$putSnippetInContent) {
            return;
        }

        $snippetManager = PluginUtility::getSnippetManager();
        $xhtmlContent = &$event->data[1];
        /**
         * What fucked up is fucked up
         *
         * In admin page, as we don't know the source of the processing text
         * (It may be a partial (ie markup) to create the admin page
         * We may have several times the same global request slot
         *
         * We can't make the difference.
         *
         * For now, we add therefore only the snippet for the slots.
         * The snippet for the request should have been already added with the
         * DOKUWIKI_STARTED hook
         */

        if (sizeof($snippetManager->getSnippets()) > 0) {
            $executionContext->setRuntimeBoolean(self::SNIPPETS_WERE_ADDED, true);
            $class = self::CLASS_SNIPPET_IN_CONTENT;
            $xhtmlContent .= <<<EOF
<div class="$class">
    {$snippetManager->toHtmlForSlotSnippets()}
</div>
EOF;

        }

    }


}
