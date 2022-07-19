<?php

use ComboStrap\ExceptionBadArgument;
use ComboStrap\ExceptionInternal;
use ComboStrap\ExceptionNotFound;
use ComboStrap\ExceptionReporter;
use ComboStrap\FetcherPage;
use ComboStrap\FetcherSystem;
use ComboStrap\Identity;
use ComboStrap\IFetcher;
use ComboStrap\LogUtility;
use ComboStrap\Mime;
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
        $action = $event->data;

        if (FetcherPage::isEnabledAsShowAction() && $action === "show") {
            $action = self::DO_PREFIX . FetcherPage::NAME;
        }

        if (!$this->isComboDoAction($action)) return;

        /**
         * Otherwise the act_clean function sanitize the action
         * and the handler for TPL_ACT_UNKNOWN is never be called.
         * More https://www.dokuwiki.org/devel:event:tpl_act_unknown#note_for_implementors
         */
        $event->preventDefault();

        try {
            $url = Url::createFromGetOrPostGlobalVariable()
                ->addQueryParameter(IFetcher::FETCHER_KEY, $this->getFetcherNameFromAction($action));
            $fetcher = FetcherSystem::createFetcherStringFromUrl($url);
            $body = $fetcher->getFetchString();
            $mime = $fetcher->getMime();
            \ComboStrap\HttpResponse::createForStatus(\ComboStrap\HttpResponse::STATUS_ALL_GOOD)
                ->setBody($body, $mime)
                ->send();
        } catch (\Exception $e) {

            $html = ExceptionReporter::createForException($e)
                ->getHtmlPage("An error has occurred during the execution of the action ($action)");
            \ComboStrap\HttpResponse::createFromException($e)
                ->setBody($html, Mime::getHtml())
                ->send();
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
