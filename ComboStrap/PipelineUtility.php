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
    const QUOTES_CHARACTERS = ['"', '\''];
    const SPACE_CHARACTER = " ";


    /**
     * @param $expression
     * @param array|null $contextData
     * @return string
     * @throws ExceptionBadSyntax - if there is any syntax error
     */
    static public function execute($expression, array $contextData = null): string
    {

        /**
         * Get the value (called the message in a pipeline)
         */
        $processedExpression = $expression;
        $firstQuoteChar = strpos($processedExpression, '"');
        $firstPipeChar = strpos($processedExpression, '|');
        if ($firstQuoteChar < $firstPipeChar || $firstPipeChar === false) {

            /**
             * Example:
             * a literal: "$title"
             * a literal with a pipe: "World | Do" | replace ("world,"you")
             */
            $message = null;
            if ($firstQuoteChar !== false) {
                $processedExpression = substr($processedExpression, $firstQuoteChar + 1);
                $secondQuoteChar = strpos($processedExpression, '"');
                if ($secondQuoteChar !== false) {
                    $message = substr($processedExpression, 0, $secondQuoteChar);
                }
            }

            $pipeCharPosition = strpos($processedExpression, '|');
            if ($pipeCharPosition !== false) {
                $commandChain = substr($processedExpression, $pipeCharPosition + 1);
                if ($message == null) {
                    // not quoted expression
                    // do we support that ?
                    $message = substr($processedExpression, 0, $pipeCharPosition);
                }
            } else {
                if ($message == null) {
                    // not quoted expression
                    // do we support that ?
                    $message = $processedExpression;
                }
                $commandChain = "";
            }

        } else {

            /**
             * Example: a variable with an expression
             * $title | replace ("world,"you")
             */
            $message = trim(substr($processedExpression, 0, $firstPipeChar));
            $commandChain = trim(substr($processedExpression, $firstPipeChar + 1));

        }


        /**
         * Command chain splits
         */
        $commands = preg_split("/\|/", $commandChain);


        /**
         * We replace after the split to be sure that there is not a | separator in the variable value
         * that would fuck up the process
         */
        $message = \syntax_plugin_combo_variable::replaceVariablesWithValuesFromContext($message, $contextData);

        $charactersToTrimFromCommand = implode("", self::QUOTES_CHARACTERS);
        foreach ($commands as $command) {
            $command = trim($command, " )");
            $leftParenthesis = strpos($command, "(");
            $commandName = substr($command, 0, $leftParenthesis);
            $signature = substr($command, $leftParenthesis + 1);
            $commandArgs = preg_split("/\s*,\s*/", $signature);
            /**
             * Delete space characters
             */
            $commandArgs = array_map(
                'trim',
                $commandArgs,
                array_fill(0, sizeof($commandArgs), self::SPACE_CHARACTER)
            );
            /**
             * Delete quote characters
             */
            $commandArgs = array_map(
                'trim',
                $commandArgs,
                array_fill(0, sizeof($commandArgs), $charactersToTrimFromCommand)
            );
            $commandName = trim($commandName);
            if (!empty($commandName)) {
                switch ($commandName) {
                    case "replace":
                        $message = self::replace($commandArgs, $message);
                        break;
                    case "head":
                        $message = self::head($commandArgs, $message);
                        break;
                    case "tail":
                        $message = self::tail($commandArgs, $message);
                        break;
                    case "rconcat":
                        $message = self::concat($commandArgs, $message, "right");
                        break;
                    case "lconcat":
                        $message = self::concat($commandArgs, $message, "left");
                        break;
                    case "cut":
                        $message = self::cut($commandArgs, $message);
                        break;
                    case "trim":
                        $message = trim($message);
                        break;
                    case "capitalize":
                        $message = ucwords($message);
                        break;
                    case "format":
                        $message = self::format($commandArgs, $message);
                        break;
                    default:
                        LogUtility::msg("command ($commandName) is unknown", LogUtility::LVL_MSG_ERROR, "pipeline");
                }
            }
        }
        return $message;
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
    public static function head(array $commandArgs, $value)
    {
        $length = $commandArgs[0];
        if (strlen($value) < $length) {
            return $value;
        }
        $words = explode(" ", $value);
        $headValue = "";
        for ($i = 0; $i < sizeof($words); $i++) {
            if ($i != 0) {
                $headValue .= " ";
            }
            $headValue .= $words[$i];
            if (strlen($headValue) >= $length) {
                break;
            }
        }

        $tail = $commandArgs[1];
        if ($tail !== null) {
            $headValue .= $tail;
        }

        return $headValue;
    }

    private
    static function concat(array $commandArgs, $value, $side): string
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

    private
    static function tail(array $commandArgs, $value)
    {
        $length = $commandArgs[0];
        return substr($value, strlen($value) - $length);
    }

    private
    static function cut(array $commandArgs, $value)
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
    public
    static function format(array $commandArgs, $value): string
    {

        /**
         * For now only date time are
         */
        try {
            $dateTime = Iso8601Date::createFromString($value);
        } catch (ExceptionBadSyntax $e) {
            throw new ExceptionBadSyntax("The format method allows for now only date. The value ($value) is not a date.", PipelineTag::CANONICAL);
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
            $path = ExecutionContext::getActualOrCreateFromEnv()->getContextPath();
            $page = MarkupPath::createPageFromPathObject($path);
            $locale = Locale::createForPage($page)->getValueOrDefault();
        }

        if ($locale === null) {
            // should never happen but yeah
            $locale = 'en_US';
            LogUtility::error("Internal Error: No default locale could be determined. The locale was set to $locale", DateTag::CANONICAL);
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

        return $dateTime->formatLocale($pattern, $derivedLocale);

    }

}
