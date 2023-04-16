<?php

namespace ComboStrap;


use syntax_plugin_combo_variable;

/**
 * Format a date
 * @deprecated use the pipline instead
 */
class DateTag
{
    public const CANONICAL = "variable:date";
    public const FORMAT_ATTRIBUTE = "format";
    public const DATE_ATTRIBUTE = "date";
    public const TAG = "date";
    /**
     * https://www.php.net/manual/en/function.strftime.php
     */
    public const DEFAULT_FORMAT = "%A, %d %B %Y";


    /**
     * @param string $date
     * @param string $format
     * @param string|null $lang
     * @return string
     * @throws ExceptionBadSyntax
     */
    public static function formatDateString(string $date, string $format = DateTag::DEFAULT_FORMAT, string $lang = null): string
    {
        // https://www.php.net/manual/en/function.date.php
        // To format dates in other languages, you should use the setlocale() and strftime() functions instead of date().
        $localeSeparator = '_';
        if ($lang === null) {
            try {
                $lang = Lang::createFromRequestedMarkup()->getValueOrDefault();
            } catch (ExceptionNotFound $e) {
                // should never happen but yeah
                LogUtility::error("Internal Error: The requested page was not found. We were unable to get the page language. Defaulting to the site language");
                $lang = Site::getLang();
            }
        }
        $actualLocale = setlocale(LC_ALL, 0);
        try {
            if ($lang !== null && trim($lang) !== "") {
                // Set local takes several possible locales value
                // The lang just works fine but the second argument can be seen in the doc
                if (strlen(trim($lang)) === 2) {
                    $derivedLocale = strtolower($lang) . $localeSeparator . strtoupper($lang);
                } else {
                    $derivedLocale = $lang;
                }
                $newLocale = setlocale(LC_TIME, $lang, $derivedLocale);
                if ($newLocale === false) {
                    $newLocale = setlocale(LC_TIME, $lang);
                    /** @noinspection PhpStatementHasEmptyBodyInspection */
                    if ($newLocale === false) {
                        /**
                         * Not the good algorithm as we come here
                         * everytime on linux.
                         * strftime is deprecated, we should change this code then
                         *
                         */
                        // throw new ExceptionBadSyntax("The language ($lang) / locale ($derivedLocale) is not available as locale on the server. You can't then format the value ($date) in this language.");
                    }
                }
            }
            $date = syntax_plugin_combo_variable::replaceVariablesWithValuesFromContext($date);
            $timeStamp = Iso8601Date::createFromString($date)->getDateTime()->getTimestamp();
            $formatted = strftime($format, $timeStamp);
            if ($formatted === false) {
                if ($lang === null) {
                    $lang = "";
                }
                throw new ExceptionBadSyntax("Unable to format the date ($date) with the format ($format) and lang ($lang)");
            }
            return $formatted;
        } finally {
            /**
             * Restore the locale
             */
            setlocale(LC_ALL, $actualLocale);
        }

    }

    public static function handleEnterAndSpecial()
    {
        LogUtility::warning("The date component has been deprecated for the date variable", DateTag::CANONICAL);
    }

    public static function renderHtml(TagAttributes $tagAttributes): string
    {
        /**
         * Locale
         */
        $lang = $tagAttributes->getComponentAttributeValue(Lang::PROPERTY_NAME);

        /**
         * The format
         */
        $format = $tagAttributes->getValue(DateTag::FORMAT_ATTRIBUTE, DateTag::DEFAULT_FORMAT);
        /**
         * The date
         */
        $defaultDateTime = Iso8601Date::createFromNow()->toString();
        $date = $tagAttributes->getComponentAttributeValue(DateTag::DATE_ATTRIBUTE, $defaultDateTime);
        try {
            return DateTag::formatDateString($date, $format, $lang);
        } catch (ExceptionBadSyntax $e) {
            $message = "Error while formatting a date. Error: {$e->getMessage()}";
            LogUtility::error($message, DateTag::CANONICAL);
            return LogUtility::wrapInRedForHtml($message);
        }
    }

    public static function handleExit(\Doku_Handler $handler)
    {
        $callStack = CallStack::createFromHandler($handler);
        $openingTag = $callStack->moveToPreviousCorrespondingOpeningCall();
        $call = $callStack->next();
        if ($call !== false) {
            $date = $call->getCapturedContent();
            $openingTag->addAttribute(DateTag::DATE_ATTRIBUTE, $date);
            $callStack->deleteActualCallAndPrevious();
        }
    }
}
