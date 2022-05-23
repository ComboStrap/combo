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

use IntlDateFormatter;

/**
 * Class PipelineUtility
 * @package ComboStrap
 * A pipeline to perform filter transformation
 *
 * See also
 * https://getbootstrap.com/docs/5.0/helpers/text-truncation/
 */
class PipelineUtility
{
    const QUOTES_CHARACTERS = "\"'";

    /**
     * @param $input
     * @return string
     * @throws ExceptionBadSyntax - if there is any syntax error
     */
    static public function execute($input): string
    {

        /**
         * Get the value
         */
        $firstQuoteChar = strpos($input, '"');
        $input = substr($input, $firstQuoteChar + 1);
        $secondQuoteChar = strpos($input, '"');
        $value = substr($input, 0, $secondQuoteChar);
        $input = substr($input, $secondQuoteChar + 1);

        /**
         * Go to the first | and delete it from the input
         */
        $pipeChar = strpos($input, '|');
        $input = substr($input, $pipeChar + 1);

        /**
         * Get the command and applies them
         */
        $commands = preg_split("/\|/", $input);
        foreach ($commands as $command) {
            $command = trim($command, " )");
            $leftParenthesis = strpos($command, "(");
            $commandName = substr($command, 0, $leftParenthesis);
            $signature = substr($command, $leftParenthesis + 1);
            $commandArgs = preg_split("/\s*,\s*/", $signature);
            $commandArgs = array_map(
                'trim',
                $commandArgs,
                array_fill(0, sizeof($commandArgs), self::QUOTES_CHARACTERS)
            );
            $commandName = trim($commandName);
            if (!empty($commandName)) {
                switch ($commandName) {
                    case "replace":
                        $value = self::replace($commandArgs, $value);
                        break;
                    case "head":
                        $value = self::head($commandArgs, $value);
                        break;
                    case "tail":
                        $value = self::tail($commandArgs, $value);
                        break;
                    case "rconcat":
                        $value = self::concat($commandArgs, $value, "right");
                        break;
                    case "lconcat":
                        $value = self::concat($commandArgs, $value, "left");
                        break;
                    case "cut":
                        $value = self::cut($commandArgs, $value);
                        break;
                    case "trim":
                        $value = trim($value);
                        break;
                    case "capitalize":
                        $value = ucwords($value);
                        break;
                    case "format":
                        $value = self::format($commandArgs, $value);
                        break;
                    default:
                        LogUtility::msg("command ($commandName) is unknown", LogUtility::LVL_MSG_ERROR, "pipeline");
                }
            }
        }
        return trim($value);
    }

    private static function replace(array $commandArgs, $value)
    {
        $search = $commandArgs[0];
        $replace = $commandArgs[1];
        return str_replace($search, $replace, $value);
    }

    /**
     * @param array $commandArgs
     * @param $value
     * @return false|string
     * See also: https://getbootstrap.com/docs/5.0/helpers/text-truncation/
     */
    private static function head(array $commandArgs, $value)
    {
        $length = $commandArgs[0];
        if (strlen($value) < $length) {
            return $value;
        }
        $headValue = substr($value, 0, $length);
        $tail = $commandArgs[1];
        if ($tail !== null) {
            $headValue .= $tail;
        }
        return $headValue;
    }

    private static function concat(array $commandArgs, $value, $side): string
    {
        $string = $commandArgs[0];
        switch ($side) {
            case "left":
                return $string . $value;
            case "right":
                return $value . $string;
            default:
                LogUtility::msg("The side value ($side) is unknown", LogUtility::LVL_MSG_ERROR, "pipeline");
                return $value . $string;
        }


    }

    private static function tail(array $commandArgs, $value)
    {
        $length = $commandArgs[0];
        return substr($value, strlen($value) - $length);
    }

    private static function cut(array $commandArgs, $value)
    {
        $pattern = $commandArgs[0];
        $words = preg_split("/$pattern/i", $value);
        if ($words !== false) {
            $selector = $commandArgs[1];
            $startEndSelector = preg_split("/-/i", $selector);
            $start = $startEndSelector[0] - 1;
            $end = null;
            if (isset($startEndSelector[1])) {
                $end = $startEndSelector[1];
                if (empty($end)) {
                    $end = sizeof($words);
                }
                $end = $end - 1;
            }
            if ($end == null) {
                if (isset($words[$start])) {
                    return $words[$start];
                } else {
                    return $value;
                }
            } else {
                $result = "";
                for ($i = $start; $i <= $end; $i++) {
                    if (isset($words[$i])) {
                        if (!empty($result)) {
                            $result .= $pattern;
                        }
                        $result .= $words[$i];
                    }
                }
                return $result;
            }

        } else {
            return "An error occurred: could not split with the pattern `$pattern`, the value `$value`.";
        }
    }

    /**
     * @throws ExceptionBadSyntax
     */
    public static function format(array $commandArgs, $value): string
    {

        /**
         * For now only date time are
         */
        try {
            $dateTime = Iso8601Date::createFromString($value)->getDateTime();
        } catch (ExceptionBadSyntax $e) {
            throw new ExceptionBadSyntax("The format method allows for now only date. The value ($value) is not a date.", \syntax_plugin_combo_pipeline::CANONICAL);
        }

        $size = sizeof($commandArgs);
        $pattern = null;
        $locale = null;
        switch ($size) {
            case 0:
                break;
            case 1:
                $pattern = $commandArgs[0];
                break;
            case 2:
            default:
                $pattern = $commandArgs[0];
                $locale = $commandArgs[1];
                break;
        }
        $localeSeparator = '_';
        if ($locale === null) {
            $path = ContextManager::getOrCreate()->getAttribute(PagePath::PROPERTY_NAME);
            if ($path === null) {
                // should never happen but yeah
                LogUtility::error("Internal Error: The page content was not set. We were unable to get the page locale. Defaulting to the site locale");
                $locale = Site::getLocale();
            } else {
                $page = Page::createPageFromQualifiedPath($path);
                $locale = Locale::createForPage($page)->getValueOrDefault();
            }
        }

        if ($locale === null) {
            // should never happen but yeah
            $locale = 'en_US';
            LogUtility::error("Internal Error: No default locale could be determined. The locale was set to $locale", \syntax_plugin_combo_date::CANONICAL);
        }

        /**
         * If the user has set a lang
         * Transform it as locale
         */
        if (strlen(trim($locale)) === 2) {
            $derivedLocale = strtolower($locale) . $localeSeparator . strtoupper($locale);
        } else {
            $derivedLocale = $locale;
        }

        /**
         * https://www.php.net/manual/en/function.strftime.php
         * As been deprecated
         * The only alternative with local is
         * https://www.php.net/manual/en/intldateformatter.format.php
         *
         * Based on ISO date
         * ICU Date formatter: https://unicode-org.github.io/icu-docs/#/icu4c/udat_8h.html
         * ICU Date formats: https://unicode-org.github.io/icu/userguide/format_parse/datetime/#datetime-format-syntax
         * ICU User Guide: https://unicode-org.github.io/icu/userguide/
         * ICU Formatting Dates and Times: https://unicode-org.github.io/icu/userguide/format_parse/datetime/
         */

        /**
         * This parameters
         * are used to format date with the locale
         * when the pattern is null
         * Doc: https://unicode-org.github.io/icu/userguide/format_parse/datetime/#producing-normal-date-formats-for-a-locale
         *
         * They may be null by the way.
         *
         */
        $dateType = IntlDateFormatter::TRADITIONAL;
        $timeType = IntlDateFormatter::SHORT;

        /**
         * Formatter instantiation
         */
        $formatter = datefmt_create(
            $derivedLocale,
            $dateType,
            $timeType,
            $dateTime->getTimezone(),
            IntlDateFormatter::GREGORIAN,
            $pattern
        );
        $formatted = datefmt_format($formatter, $dateTime);
        if ($formatted === false) {
            if ($locale === null) {
                $locale = "";
            }
            throw new ExceptionBadSyntax("Unable to format the date ($value) with the pattern ($pattern) and locale ($locale)");
        }

        return $formatted;
    }

}
