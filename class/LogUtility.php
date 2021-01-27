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


class LogUtility
{
    const LVL_MSG_INFO = 0;
    const LVL_MSG_WARNING = 2;
    const LVL_MSG_SUCCESS = 1;
    /**
     * Constant for the function {@link msg()}
     * -1 = error, 0 = info, 1 = success, 2 = notify
     */
    const LVL_MSG_ERROR = -1;
    const LVL_MSG_DEBUG = 3;

    /**
     * Send a message to a manager and log it
     * Fail if in test
     * @param string $message
     * @param int $level - the level see LVL constant
     * @param string $canonical - the canonical
     */
    public static function msg($message, $level = self::LVL_MSG_ERROR, $canonical = null)
    {

        self::log2FrontEnd($message, $level, $canonical);
        /**
         * Print to a log file
         * Note: {@link dbg()} dbg print to the web page
         */
        $prefix = PluginUtility::$PLUGIN_NAME;
        if ($canonical != null) {
            $prefix .= ' - ' . $canonical;
        }
        $msg = $prefix . ' - ' . $message;
        self::log2file($msg);


        $loglevel = self::LVL_MSG_INFO;
        global $INPUT;
        $loglevelProp = $INPUT->str("loglevel", null);
        if ($loglevelProp != null) {
            $loglevel = $loglevelProp;
        }
        if (defined('DOKU_UNITTEST')
            && ($level == self::LVL_MSG_WARNING || $level == self::LVL_MSG_ERROR)
            && ($loglevel != self::LVL_MSG_ERROR)
        ) {
            throw new \RuntimeException($msg);
        }
    }

    /**
     * Print log to a  file
     *
     * Adapted from {@link dbglog}
     *
     * @param string $msg
     */
    static function log2file($msg)
    {

        /* @var Input $INPUT */
        global $INPUT;
        global $conf;

        if (is_object($msg) || is_array($msg)) {
            $msg = print_r($msg, true);
        }

        $file = $conf['cachedir'] . '/debug.log';
        $fh = fopen($file, 'a');
        if ($fh) {
            fwrite($fh, date('H:i:s ') . $INPUT->server->str('REMOTE_ADDR') . ': ' . $msg . "\n");
            fclose($fh);
        }

    }

    /**
     * @param $message
     * @param $level
     * @param $canonical
     * @param $withIconURL
     */
    public static function log2FrontEnd($message, $level, $canonical, $withIconURL = true)
    {
        /**
         * If we are not in the console
         * and not in test
         * we test that the message comes in the front end
         * (example {@link \plugin_combo_frontmatter_test}
         */
        $isCLI = (php_sapi_name() == 'cli');
        $print = true;
        if ($isCLI) {
            if (!defined('DOKU_UNITTEST')) {
                $print = false;
            }
        }
        if ($print) {
            $prefix = PluginUtility::getUrl("", PluginUtility::$PLUGIN_NAME, $withIconURL);
            if ($canonical != null) {
                $prefix = PluginUtility::getUrl($canonical, ucfirst(str_replace(":", " ", $canonical)));
            }

            $htmlMsg = $prefix . " - " . $message;
            if ($level != self::LVL_MSG_DEBUG) {
                msg($htmlMsg, $level, '', '', MSG_USERS_ONLY);
            }
        }
    }
}
