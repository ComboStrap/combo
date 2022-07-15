<?php

use ComboStrap\ExceptionBadArgument;
use ComboStrap\FetcherSystem;
use ComboStrap\IFetcher;
use ComboStrap\LogUtility;
use ComboStrap\Url;

/**
 * Implementation of custom do (ie ACT) to output {@link \ComboStrap\IFetcherString}
 * https://www.dokuwiki.org/devel:event:tpl_act_unknown
 *
 * The output is going in the admin area.
 * Not really useful if you want your own layout or do an export
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
         * Allow the combo action
         */
        $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'allowComboDoAction');

        $controller->register_hook('TPL_ACT_UNKNOWN', 'BEFORE', $this, 'executeComboDoAction');
    }

    /**
     * @param Doku_Event $event
     * @param $param
     * @return void
     *
     */
    public function allowComboDoAction(Doku_Event $event, $param)
    {
        $action = $event->data;
        if (!$this->isComboDoAction($action)) return;

        /**
         * Otherwise the act_clean function sanitize the action
         * and the handler for TPL_ACT_UNKNOWN is never be called.
         * More https://www.dokuwiki.org/devel:event:tpl_act_unknown#note_for_implementors
         */
        $event->preventDefault();
    }

    public function executeComboDoAction(Doku_Event $event, $param)
    {

        $action = $event->data;
        if (!$this->isComboDoAction($action)) return;

        $event->preventDefault();


        try {
            $url = Url::createFromGetOrPostGlobalVariable()
                ->addQueryParameter(IFetcher::FETCHER_KEY,$this->getFetcherNameFromAction($action));
            echo FetcherSystem::createFetcherStringFromUrl($url)
                ->getFetchString();
        } catch (ExceptionBadArgument|\ComboStrap\ExceptionNotFound|\ComboStrap\ExceptionInternal $e) {
            LogUtility::error("An error has occurred during the execution of the action. Error: {$e->getMessage()} ");
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
