<?php

require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');

use ComboStrap\Index;
use ComboStrap\LogUtility;
use ComboStrap\Message;
use ComboStrap\PagesIndex;
use dokuwiki\Extension\ActionPlugin;


/**
 *
 * To show a message after redirection or rewriting
 *
 *
 *
 */
class action_plugin_combo_routermessage extends ActionPlugin
{

    // a class can not start with a number then webcomponent is not a valid class name
    const REDIRECT_MANAGER_BOX_CLASS = "redirect-manager";

    // Property key
    const ORIGIN_PAGE = 'redirectId';
    const ORIGIN_TYPE = 'redirectOrigin';
    const CONF_SHOW_PAGE_NAME_IS_NOT_UNIQUE = 'ShowPageNameIsNotUnique';

    function __construct()
    {
        // enable direct access to language strings
        // ie $this->lang
        $this->setupLocale();
    }

    /**
     *
     * Return the message properties from a query string
     *
     * An internal HTTP redirect pass them via query string
     */
    private static function getMessageQueryStringProperties(): array
    {

        $returnValues = array();

        global $INPUT;
        $origin = $INPUT->str(self::ORIGIN_PAGE, null);
        if ($origin != null) {
            $returnValues = array(
                $origin,
                $INPUT->str(self::ORIGIN_TYPE, null)
            );
        }
        return $returnValues;

    }


    function register(Doku_Event_Handler $controller)
    {

        /* This will call the function _displayRedirectMessage */
        $controller->register_hook(
            'TPL_ACT_RENDER',
            'BEFORE',
            $this,
            '_displayRedirectMessage',
            array()
        );


    }


    /**
     * Main function; dispatches the visual comment actions
     * @param   $event Doku_Event
     */
    function _displayRedirectMessage(&$event, $param)
    {


        $pageIdOrigin = null;
        $redirectSource = null;


        $messageQueryStringProperties = self::getMessageQueryStringProperties();
        if (!empty($messageQueryStringProperties)) {
            list($pageIdOrigin, $redirectSource) = $messageQueryStringProperties;
        }

        if ($pageIdOrigin) {

            switch ($redirectSource) {

                case action_plugin_combo_router::TARGET_ORIGIN_PAGE_RULES:
                    $message = Message::createInfoMessage()
                        ->addHtmlContent("<p>" . sprintf($this->getLang('message_redirected_by_redirect'), hsc($pageIdOrigin)) . "</p>");
                    break;

                case action_plugin_combo_router::TARGET_ORIGIN_START_PAGE:
                    $message = Message::createWarningMessage()
                        ->addHtmlContent("<p>" . sprintf($this->lang['message_redirected_to_startpage'], hsc($pageIdOrigin)) . "</p>");
                    break;

                case  action_plugin_combo_router::TARGET_ORIGIN_BEST_PAGE_NAME:
                    $message = Message::createWarningMessage()
                        ->addHtmlContent("<p>" . sprintf($this->lang['message_redirected_to_bestpagename'], hsc($pageIdOrigin)) . "</p>");
                    break;

                case action_plugin_combo_router::TARGET_ORIGIN_BEST_NAMESPACE:
                    $message = Message::createWarningMessage()
                        ->addHtmlContent("<p>" . sprintf($this->lang['message_redirected_to_bestnamespace'], hsc($pageIdOrigin)) . "</p>");
                    break;

                case action_plugin_combo_router::TARGET_ORIGIN_SEARCH_ENGINE:
                    $message = Message::createWarningMessage()
                        ->addHtmlContent("<p>" . sprintf($this->lang['message_redirected_to_searchengine'], hsc($pageIdOrigin)) . "</p>");
                    break;

                case action_plugin_combo_router::GO_TO_EDIT_MODE:
                    $message = Message::createInfoMessage()
                        ->addHtmlContent("<p>" . $this->lang['message_redirected_to_edit_mode'] . "</p>");
                    break;
                case action_plugin_combo_router::TARGET_ORIGIN_PERMALINK_EXTENDED:
                case action_plugin_combo_router::TARGET_ORIGIN_PERMALINK:
                    $message = Message::createInfoMessage()
                        ->addHtmlContent("<p>" . $this->lang['message_redirected_from_permalink'] . "</p>");
                    break;
                case action_plugin_combo_router::TARGET_ORIGIN_CANONICAL:
                    $message = Message::createInfoMessage()
                        ->addHtmlContent("<p>" . $this->lang['message_redirected_from_canonical'] . "</p>");
                    break;
                default:
                    LogUtility::msg("The redirection source ($redirectSource) is unknown. It was not expected", LogUtility::LVL_MSG_ERROR, action_plugin_combo_router::CANONICAL);
                    return;

            }


            // Add a list of page with the same name to the message
            // if the redirections is not planned
            if ($redirectSource != action_plugin_combo_router::TARGET_ORIGIN_PAGE_RULES) {
                $this->addToMessagePagesWithSameName($message, $pageIdOrigin);
            }

            if ($event->data === 'show' || $event->data === 'edit' || $event->data === 'search') {
                $html = $message
                    ->setPlugin($this)
                    ->setClass(action_plugin_combo_routermessage::REDIRECT_MANAGER_BOX_CLASS)
                    ->setCanonical(action_plugin_combo_router::CANONICAL)
                    ->setSignatureName(action_plugin_combo_router::URL_MANAGER_NAME)
                    ->toHtmlBox();
                ptln($html);
            }


        }


    }


    /**
     * Add the page with the same page name but in an other location
     * @param Message $message
     * @param $pageIdOrigin
     */
    function addToMessagePagesWithSameName(Message $message, $pageIdOrigin)
    {

        if ($this->getConf(self::CONF_SHOW_PAGE_NAME_IS_NOT_UNIQUE) == 1) {

            global $ID;
            // The page name
            $pageName = noNS($pageIdOrigin);
            $pagesWithSameName = Index::getOrCreate()->getPagesWithSameLastName($pageIdOrigin);

            if (count($pagesWithSameName) > 0) {

                $message->setType(Message::TYPE_WARNING);

                // Assign the value to a variable to be able to use the construct .=
                if ($message->getPlainTextContent() <> '') {
                    $message->addHtmlContent('<br/><br/>');
                }
                $message->addHtmlContent($this->lang['message_pagename_exist_one']);
                $message->addHtmlContent('<ul>');

                $i = 0;
                foreach ($pagesWithSameName as $pageId => $title) {
                    if ($pageId === $ID) {
                        continue;
                    }
                    $i++;
                    if ($i > 10) {
                        $message->addHtmlContent('<li>' .
                            tpl_link(
                                wl($pageIdOrigin) . "?do=search&q=" . rawurldecode($pageName),
                                "More ...",
                                'class="" rel="nofollow" title="More..."',
                                $return = true
                            ) . '</li>');
                        break;
                    }
                    if ($title == null) {
                        $title = $pageId;
                    }
                    $message->addHtmlContent('<li>' .
                        tpl_link(
                            wl($pageId),
                            $title,
                            'class="" rel="nofollow" title="' . $title . '"',
                            $return = true
                        ) . '</li>');
                }
                $message->addHtmlContent('</ul>');
            }
        }
    }


    /**
     * Set the redirect in a session that will be be read after the redirect
     * in order to show a message to the user
     * @param string $id
     * @param string $redirectSource
     */
    static function notify($id, $redirectSource)
    {
        // Msg via session
        if (!defined('NOSESSION')) {
            //reopen session, store data and close session again
            self::sessionStart();
            $_SESSION[DOKU_COOKIE][self::ORIGIN_PAGE] = $id;
            $_SESSION[DOKU_COOKIE][self::ORIGIN_TYPE] = $redirectSource;
            self::sessionClose();

        }
    }


    private static function sessionStart()
    {
        $sessionStatus = session_status();
        switch ($sessionStatus) {
            case PHP_SESSION_DISABLED:
                throw new RuntimeException("Sessions are disabled");

            case PHP_SESSION_NONE:
                $result = @session_start();
                if (!$result) {
                    throw new RuntimeException("The session was not successfully started");
                }
                break;
            case PHP_SESSION_ACTIVE:
                break;
        }
    }

    private static function sessionClose()
    {
        // Close the session
        $phpVersion = phpversion();
        if ($phpVersion > "7.2.0") {
            /** @noinspection PhpVoidFunctionResultUsedInspection */
            $result = session_write_close();
            if (!$result) {
                // Session is really not a well known mechanism
                // Set this error in a info level to not fail the test
                LogUtility::msg("Failure to write the session", LogUtility::LVL_MSG_INFO);
            }
        } else {
            session_write_close();
        }

    }

}
