<?php
/**
 * Copyright (c) 2020. ComboStrap, Inc. and its affiliates. All Rights Reserved.
 *
 * This source code is licensed under the GPL license found in the
 * COPYING  file in the root directory of this source tree.
 *
 * @license  GPL 3 (https://www.gnu.org/licenses/gpl-3.0.en.html)
 * @author   ComboStrap <support@combostrap.com>
 *
 */

namespace ComboStrap;

use dokuwiki\Logger;
use Throwable;

require_once(__DIR__ . '/PluginUtility.php');

class LogUtility
{

    /**
     * Constant for the function {@link msg()}
     * -1 = error, 0 = info, 1 = success, 2 = notify
     * (Not even in order of importance)
     */
    const LVL_MSG_ABOVE_ERROR = 5; // a level to disable the error to thrown in test
    const LVL_MSG_ERROR = 4; //-1;
    const LVL_MSG_WARNING = 3; //2;
    const LVL_MSG_SUCCESS = 2; //1;
    const LVL_MSG_INFO = 1; //0;
    const LVL_MSG_DEBUG = 0; //3;


    /**
     * Id level to name
     */
    const LVL_NAME = array(
        0 => "debug",
        1 => "info",
        3 => "warning",
        2 => "success",
        4 => "error"
    );

    /**
     * Id level to name
     * {@link msg()} constant
     */
    const LVL_TO_MSG_LEVEL = array(
        0 => 3,
        1 => 0,
        2 => 1,
        3 => 2,
        4 => -1
    );


    const LOGLEVEL_URI_QUERY_PROPERTY = "loglevel";
    const SUPPORT_CANONICAL = "support";

    /**
     *
     * @var bool
     */
    private static bool $throwExceptionOnDevTest = true;
    /**
     * @var int
     */
    const DEFAULT_THROW_LEVEL = self::LVL_MSG_WARNING;

    /**
     * Send a message to a manager and log it
     * Fail if in test
     * @param string $message
     * @param int $level - the level see LVL constant
     * @param string $canonical - the canonical
     * @param \Exception|null $e
     */
    public static function msg(string $message, int $level = self::LVL_MSG_ERROR, string $canonical = self::SUPPORT_CANONICAL, \Exception $e = null)
    {

        try {
            self::messageNotEmpty($message);
        } catch (ExceptionCompile $e) {
            self::log2file($e->getMessage(), LogUtility::LVL_MSG_ERROR, $canonical);
        }

        /**
         * Log to frontend
         */
        self::log2FrontEnd($message, $level, $canonical);

        /**
         * Log level passed for a page (only for file used)
         * to not allow an attacker to see all errors in frontend
         */
        global $INPUT;
        $loglevelProp = $INPUT->str(self::LOGLEVEL_URI_QUERY_PROPERTY, null);
        if (!empty($loglevelProp)) {
            $level = $loglevelProp;
        }
        /**
         * TODO: Make it a configuration ?
         */
        if ($level >= self::LVL_MSG_WARNING) {
            self::log2file($message, $level, $canonical, $e);
        }

        /**
         * If test, we throw an error
         */
        self::throwErrorIfTest($level, $message, $e);
    }

    /**
     * Print log to a  file
     *
     * Adapted from {@link dbglog}
     * Note: {@link dbg()} dbg print to the web page
     *
     * @param null|string $msg - may be null always this is the default if a variable is not initialized.
     * @param int $logLevel
     * @param string|null $canonical
     * @param \Exception|null $e
     */
    static function log2file(?string $msg, int $logLevel = self::LVL_MSG_ERROR, ?string $canonical = self::SUPPORT_CANONICAL, \Exception $e = null)
    {

        try {
            self::messageNotEmpty($msg);
        } catch (ExceptionCompile $e) {
            $msg = $e->getMessage();
            $logLevel = self::LVL_MSG_ERROR;
        }

        if (PluginUtility::isTest() || $logLevel >= self::LVL_MSG_WARNING) {

            $prefix = PluginUtility::$PLUGIN_NAME;
            if (!empty($canonical)) {
                $prefix .= ' - ' . $canonical;
            }
            $msg = $prefix . ' - ' . $msg;

            global $INPUT;

            /**
             * Adding page - context information
             * We are not using {@link MarkupPath::createFromRequestedPage()}
             * because it throws an error message when the environment
             * is not good, creating a recursive call.
             */
            $id = $INPUT->str("id");
            $messageWritten = self::LVL_NAME[$logLevel] . " - $msg - (Page: $id, IP: {$INPUT->server->str('REMOTE_ADDR')})\n";
            // dokuwiki does not have the warning level
            Logger::error($messageWritten);
            self::throwErrorIfTest($logLevel, $msg, $e);

        }

    }

    /**
     * @param $message
     * @param $level
     * @param string $canonical
     * @param bool $publicMessage
     */
    public static function log2FrontEnd($message, $level, string $canonical = self::SUPPORT_CANONICAL, bool $publicMessage = false)
    {

        try {
            self::messageNotEmpty($message);
        } catch (ExceptionCompile $e) {
            $message = $e->getMessage();
            $level = self::LVL_MSG_ERROR;
        }

        /**
         * If we are not in the console
         * and not in test
         * we test that the message comes in the front end
         * (example {@link \plugin_combo_frontmatter_test}
         */
        $isTerminal = Console::isConsoleRun();
        if ($isTerminal) {
            if (!defined('DOKU_UNITTEST')) {
                /**
                 * such as {@link cli_plugin_combo}
                 */
                $userAgent = "cli";
            } else {
                $userAgent = "phpunit";
            }
        } else {
            $userAgent = "browser";
        }

        switch ($userAgent) {
            case "cli":
                echo "$message\n";
                break;
            case "phpunit":
            case "browser":
            default:
                if ($canonical !== null) {
                    $label = ucfirst(str_replace(":", " ", $canonical));
                    $htmlMsg = PluginUtility::getDocumentationHyperLink($canonical, $label, false);
                } else {
                    $htmlMsg = PluginUtility::getDocumentationHyperLink("", PluginUtility::$PLUGIN_NAME, false);
                }


                /**
                 * Adding page - context information
                 * We are not creating the page
                 * direction from {@link MarkupPath::createFromRequestedPage()}
                 * because it throws an error message when the environment
                 * is not good, creating a recursive call.
                 */
                global $INPUT;
                $id = $INPUT->str("id");
                if ($id != null) {

                    /**
                     * We don't use any Page object to not
                     * create a cycle while building it
                     */
                    $url = wl($id, [], true);
                    $htmlMsg .= " - <a href=\"$url\">$id</a>";

                }

                /**
                 *
                 */
                $htmlMsg .= " - " . $message;
                if ($level > self::LVL_MSG_DEBUG) {
                    $dokuWikiLevel = self::LVL_TO_MSG_LEVEL[$level];
                    if ($publicMessage) {
                        $allow = MSG_PUBLIC;
                    } else {
                        $allow = MSG_USERS_ONLY;
                    }
                    msg($htmlMsg, $dokuWikiLevel, '', '', $allow);
                }
        }
    }

    /**
     * Log a message to the browser console
     * @param $message
     */
    public static function log2BrowserConsole($message)
    {
        // TODO
    }


    /**
     * @param $level
     * @param $message
     * @param $e - the original exception for chaining
     * @return void
     */
    private static function throwErrorIfTest($level, $message, \Exception $e = null)
    {
        if (PluginUtility::isTest() && self::$throwExceptionOnDevTest) {
            try {
                $actualLevel = ExecutionContext::getExecutionContext()->getConfig()->getLogExceptionLevel();
            } catch (ExceptionNotFound $e) {
                // In context creation
                return;
            }
            if ($level >= $actualLevel) {
                throw new LogException($message, $level, $e);
            }
        }
    }

    /**
     * @param string|null $message
     * @throws ExceptionCompile
     */
    private static function messageNotEmpty(?string $message)
    {
        $message = trim($message);
        if ($message === null || $message === "") {
            $newMessage = "The passed message to the log was empty or null. BackTrace: \n";
            $newMessage .= LogUtility::getCallStack();
            throw new ExceptionCompile($newMessage);
        }
    }

    public static function disableThrowExceptionOnDevTest()
    {
        self::$throwExceptionOnDevTest = false;
    }

    public static function enableThrowExceptionOnDevTest()
    {
        self::$throwExceptionOnDevTest = true;
    }

    public static function wrapInRedForHtml(string $message): string
    {
        return "<span class=\"text-danger\">$message</span>";
    }

    /**
     * @return false|string - the actual php call stack (known as backtrace)
     */
    public static function getCallStack()
    {
        ob_start();
        $limit = 10;
        /**
         * DEBUG_BACKTRACE_IGNORE_ARGS options to avoid
         * PHP Fatal error:  Allowed memory size of 2147483648 bytes exhausted (tried to allocate 1876967424 bytes)
         */
        debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $limit); // It prints also the data passed :)
        $trace = ob_get_contents();
        ob_end_clean();
        return $trace;
    }

    /**
     * @param string $message the message
     * @param string $canonical the page
     * @param \Exception|null $e the original exception for trace chaining
     * @return void
     */
    public static function error(string $message, string $canonical = self::SUPPORT_CANONICAL, \Exception $e = null)
    {
        self::msg($message, LogUtility::LVL_MSG_ERROR, $canonical, $e);
    }

    public static function warning(string $message, string $canonical = self::SUPPORT_CANONICAL, \Exception $e = null)
    {
        self::msg($message, LogUtility::LVL_MSG_WARNING, $canonical, $e);
    }

    public static function info(string $message, string $canonical = self::SUPPORT_CANONICAL, \Exception $e = null)
    {
        self::msg($message, LogUtility::LVL_MSG_INFO, $canonical, $e);
    }

    /**
     * @param int $level
     * @return void
     * @deprecated use {@link SiteConfig::setLogExceptionLevel()}
     */
    public static function setTestExceptionLevel(int $level)
    {
        ExecutionContext::getActualOrCreateFromEnv()->getConfig()->setLogExceptionLevel($level);
    }

    public static function setTestExceptionLevelToDefault()
    {
        ExecutionContext::getActualOrCreateFromEnv()->getConfig()->setLogExceptionLevel(self::LVL_MSG_WARNING);
    }

    public static function errorIfDevOrTest($message, $canonical = "support")
    {
        if (PluginUtility::isDevOrTest()) {
            LogUtility::error($message, $canonical);
        }
    }

    /**
     * @return void
     * @deprecated use the config object instead
     */
    public static function setTestExceptionLevelToError()
    {
        ExecutionContext::getActualOrCreateFromEnv()->getConfig()->setLogExceptionToError();
    }

    /**
     * Advertise an error that should not take place if the code was
     * written properly
     * @param string $message
     * @param string $canonical
     * @param Throwable|null $previous
     * @return void
     */
    public static function internalError(string $message, string $canonical = "support", Throwable $previous = null)
    {
        $internalErrorMessage = "Sorry. An internal error has occurred";
        if (PluginUtility::isDevOrTest()) {
            throw new ExceptionRuntimeInternal("$internalErrorMessage - $message", $canonical, 1, $previous);
        } else {
            $errorPreviousMessage = "";
            if ($previous !== null) {
                $errorPreviousMessage = " Error: {$previous->getMessage()}";
            }
            self::error("{$internalErrorMessage}: $message.$errorPreviousMessage", $canonical);
        }
    }

    /**
     * @param string $message
     * @param string $canonical
     * @param $e
     * @return void
     * Debug, trace
     */
    public static function debug(string $message, string $canonical = self::SUPPORT_CANONICAL, $e = null)
    {
        self::msg($message, LogUtility::LVL_MSG_DEBUG, $canonical, $e);
    }

    public static function infoToPublic(string $html, string $canonical)
    {
        self::log2FrontEnd($html, LogUtility::LVL_MSG_INFO, $canonical, true);
    }

}
