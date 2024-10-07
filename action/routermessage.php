<?php

require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');

use ComboStrap\ExceptionBadSyntax;
use ComboStrap\Identity;
use ComboStrap\Index;
use ComboStrap\LinkMarkup;
use ComboStrap\LogUtility;
use ComboStrap\MarkupPath;
use ComboStrap\Message;
use ComboStrap\Router;
use ComboStrap\RouterRedirection;
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
    const CONF_SHOW_MESSAGE_CLASSIC = 'ShowMessageClassic';
    const CONF_SHOW_MESSAGE_CLASSIC_DEFAULT = 1;

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

                case RouterRedirection::TARGET_ORIGIN_PAGE_RULES:
                    if (!$this->showMessageIfPublicAndPlanned()) {
                        return;
                    }
                    $message = Message::createInfoMessage()
                        ->addHtmlContent("<p>" . sprintf($this->getLang('message_redirected_by_redirect'), hsc($pageIdOrigin)) . "</p>");
                    break;

                case RouterRedirection::TARGET_ORIGIN_START_PAGE:
                    $message = Message::createWarningMessage()
                        ->addHtmlContent("<p>" . sprintf($this->lang['message_redirected_to_startpage'], hsc($pageIdOrigin)) . "</p>");
                    break;
                case  RouterRedirection::TARGET_ORIGIN_BEST_PAGE_NAME:
                    $message = Message::createWarningMessage()
                        ->addHtmlContent("<p>" . sprintf($this->lang['message_redirected_to_bestpagename'], hsc($pageIdOrigin)) . "</p>");
                    break;
                case  RouterRedirection::TARGET_ORIGIN_BEST_END_PAGE_NAME:
                    $message = Message::createWarningMessage()
                        ->addHtmlContent("<p>" . sprintf($this->lang['message_redirected_to_bestendpagename'], hsc($pageIdOrigin)) . "</p>");
                    break;
                case RouterRedirection::TARGET_ORIGIN_BEST_NAMESPACE:
                    $message = Message::createWarningMessage()
                        ->addHtmlContent("<p>" . sprintf($this->lang['message_redirected_to_bestnamespace'], hsc($pageIdOrigin)) . "</p>");
                    break;

                case RouterRedirection::TARGET_ORIGIN_SEARCH_ENGINE:
                    $message = Message::createWarningMessage()
                        ->addHtmlContent("<p>" . sprintf($this->lang['message_redirected_to_searchengine'], hsc($pageIdOrigin)) . "</p>");
                    break;

                case Router::GO_TO_EDIT_MODE:
                    $message = Message::createInfoMessage()
                        ->addHtmlContent("<p>" . $this->lang['message_redirected_to_edit_mode'] . "</p>");
                    break;
                case RouterRedirection::TARGET_ORIGIN_PERMALINK_EXTENDED:
                case RouterRedirection::TARGET_ORIGIN_PERMALINK:
                    $message = Message::createInfoMessage()
                        ->addHtmlContent("<p>" . $this->lang['message_redirected_from_permalink'] . "</p>");
                    break;
                case RouterRedirection::TARGET_ORIGIN_CANONICAL:
                    if (!$this->showMessageIfPublicAndPlanned()) {
                        return;
                    }
                    $message = Message::createInfoMessage()
                        ->addHtmlContent("<p>" . $this->lang['message_redirected_from_canonical'] . "</p>");
                    break;
                default:
                    LogUtility::msg("The redirection source ($redirectSource) is unknown. It was not expected", LogUtility::LVL_MSG_ERROR, action_plugin_combo_router::CANONICAL);
                    return;

            }


            // Add a list of page with the same name to the message
            // if the redirections is not planned
            if ($redirectSource != RouterRedirection::TARGET_ORIGIN_PAGE_RULES) {
                $pageOrigin = MarkupPath::createMarkupFromId($pageIdOrigin);
                $this->addToMessagePagesWithSameName($message, $pageOrigin);
            }

            if ($event->data === 'show' || $event->data === 'edit' || $event->data === 'search') {
                $html = $message
                    ->setPlugin($this)
                    ->setClass(action_plugin_combo_routermessage::REDIRECT_MANAGER_BOX_CLASS)
                    ->setCanonical(action_plugin_combo_router::CANONICAL)
                    ->setSignatureName(action_plugin_combo_router::URL_MANAGER_NAME)
                    ->toHtmlBox();
                LogUtility::infoToPublic($html, action_plugin_combo_router::CANONICAL);
            }


        }


    }


    /**
     * Add the page with the same page name but in an other location
     * @param Message $message
     * @param MarkupPath $pageOrigin
     */
    function addToMessagePagesWithSameName(Message $message, MarkupPath $pageOrigin)
    {

        if (!$this->getConf(self::CONF_SHOW_PAGE_NAME_IS_NOT_UNIQUE) == 1) {
            return;
        }

        global $ID;
        // The page name
        $pageName = $pageOrigin->getNameOrDefault();
        $pagesWithSameName = Index::getOrCreate()->getPagesWithSameLastName($pageOrigin);

        if (count($pagesWithSameName) === 1) {
            $page = $pagesWithSameName[0];
            if ($page->getWikiId() === $ID) {
                // the page itself
                return;
            }
        }

        if (count($pagesWithSameName) > 0) {

            $message->setType(Message::TYPE_WARNING);

            // Assign the value to a variable to be able to use the construct .=
            if ($message->getPlainTextContent() <> '') {
                $message->addHtmlContent('<br/><br/>');
            }
            $message->addHtmlContent($this->lang['message_pagename_exist_one']);
            $message->addHtmlContent('<ul>');

            $i = 0;
            $listPagesHtml = "";
            foreach ($pagesWithSameName as $page) {

                if ($page->getWikiId() === $ID) {
                    continue;
                }
                $i++;
                if ($i > 10) {
                    $listPagesHtml .= '<li>' .
                        tpl_link(
                            "?do=search&q=" . rawurldecode($pageName),
                            "More ...",
                            'class="" rel="nofollow" title="More..."',
                            $return = true
                        ) . '</li>';
                    break;
                }

                try {
                    $markupRef = LinkMarkup::createFromPageIdOrPath($page->getWikiId());
                    $tagAttributes = $markupRef
                        ->toAttributes()
                        ->addOutputAttributeValue("rel", "nofollow");
                    $listPagesHtml .= "<li>{$tagAttributes->toHtmlEnterTag("a")}{$markupRef->getDefaultLabel()}</a></li>";
                } catch (ExceptionBadSyntax $e) {
                    LogUtility::internalError("Internal Error: Unable to get a markup ref for the page ($page). Error: {$e->getMessage()}");
                }

            }
            $message->addHtmlContent($listPagesHtml);
            $message->addHtmlContent('</ul>');

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

    /**
     * We don't saw the message if it was planned and
     * it's a reader
     * @return bool
     */
    private function showMessageIfPublicAndPlanned(): bool
    {
        if (Identity::isWriter()) {
            return true;
        }
        return $this->getConf(self::CONF_SHOW_MESSAGE_CLASSIC, self::CONF_SHOW_MESSAGE_CLASSIC_DEFAULT) == 1;
    }

}
