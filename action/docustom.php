<?php

use ComboStrap\ExceptionBadArgument;
use ComboStrap\ExceptionInternal;
use ComboStrap\ExceptionNotFound;
use ComboStrap\ExceptionReporter;
use ComboStrap\ExecutionContext;
use ComboStrap\FetcherIdentityForms;
use ComboStrap\FetcherPage;
use ComboStrap\FetcherSystem;
use ComboStrap\FileSystems;
use ComboStrap\HttpResponseStatus;
use ComboStrap\Identity;
use ComboStrap\IFetcher;
use ComboStrap\LocalPath;
use ComboStrap\LogUtility;
use ComboStrap\Mime;
use ComboStrap\PluginUtility;
use ComboStrap\TplUtility;
use ComboStrap\Url;

/**
 * Implementation of custom do (ie ACT) to output {@link \ComboStrap\IFetcherString}
 *
 *
 *
 */
class action_plugin_combo_docustom extends DokuWiki_Action_Plugin
{

    const DO_PREFIX = "combo_";

    /**
     * @var bool to avoid recursion that may happen using {@link tpl_content()}
     */
    private bool $doCustomExecuting = false;

    public static function getDoParameterValue(string $fetcherName): string
    {
        return self::DO_PREFIX . $fetcherName;
    }

    /**
     *
     * @param Doku_Event_Handler $controller
     * @return void
     */

    public function register(\Doku_Event_Handler $controller)
    {
        /**
         * Execute the combo action via an ACTION_ACT_PREPROCESS
         *
         * Not via the [TPL_ACT_UNKNOWN](https://www.dokuwiki.org/devel:event:tpl_act_unknown)
         * because it would otherwise put the content in the middle of the page
         * as an admin page. Not really useful if you want your own layout or do an export
         */
        $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'executeComboDoAction');

    }

    /**
     * @param Doku_Event $event
     * @param $param
     * @return void
     *
     */
    public function executeComboDoAction(Doku_Event $event, $param)
    {

        if ($this->doCustomExecuting) {
            return;
        }

        /**
         * The router may have done a redirection
         * (The Dokuwiki testRequest does not stop unfortunately)
         */
        $executionContext = ExecutionContext::getActualOrCreateFromEnv();
        $hasEnded = $executionContext
            ->response()
            ->hasEnded();
        if ($hasEnded) {
            if ($executionContext->isTestRun()) {
                /**
                 * This info helps the developer to see
                 * why nothing happens when it sends two dokuwiki {@link TestRequest}
                 *
                 * And not two {@link \ComboStrap\HttpResponse}
                 * that reinitialize the global scope
                 */
                LogUtility::info("ExecuteDoAction: The response has already be send (ended).");
            }
            return;
        }

        $action = $event->data;

        if (FetcherPage::isEnabledAsShowAction()) {
            switch ($action) {
                case "show":
                    $action = self::getDoParameterValue(FetcherPage::NAME);
                    break;
                case ExecutionContext::LOGIN_ACTION:
                case ExecutionContext::REGISTER_ACTION:
                case ExecutionContext::RESEND_PWD_ACTION:
                case ExecutionContext::PROFILE_ACTION:
                    $action = self::getDoParameterValue(FetcherIdentityForms::NAME);
                    break;
            }
        }

        if (!$this->isComboDoAction($action)) return;

        /**
         * To avoid recursion
         */
        $this->doCustomExecuting = true;

        /**
         * Otherwise the act_clean function sanitize the action
         * and the handler for TPL_ACT_UNKNOWN is never be called.
         * More https://www.dokuwiki.org/devel:event:tpl_act_unknown#note_for_implementors
         */
        $event->preventDefault();


        try {
            $fetcherName = $this->getFetcherNameFromAction($action);
            $url = Url::createFromGetOrPostGlobalVariable()
                ->addQueryParameter(IFetcher::FETCHER_KEY, $fetcherName);
            $fetcher = FetcherSystem::createFetcherStringFromUrl($url);
            $body = $fetcher->getFetchString();
            $mime = $fetcher->getMime();
            $executionContext->response()
                ->setStatus(HttpResponseStatus::ALL_GOOD)
                ->setBody($body, $mime)
                ->end();
        } catch (\Exception $e) {

            $html = ExceptionReporter::createForException($e)
                ->getHtmlPage("An error has occurred during the execution of the action ($action)");
            \ComboStrap\HttpResponse::createFromException($e)
                ->setBody($html, Mime::getHtml())
                ->end();
        }

    }


    private function isComboDoAction($actionName): bool
    {
        return strpos($actionName, self::DO_PREFIX) === 0;
    }

    private function getFetcherNameFromAction($actionName)
    {
        return substr($actionName, strlen(self::DO_PREFIX));
    }


}
