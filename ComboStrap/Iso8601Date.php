<?php


namespace ComboStrap;


use DateTime;
use IntlDateFormatter;


/**
 * Class Is8601Date
 * @package ComboStrap
 * Format used by Google, Sqlite and others
 *
 * This is the date class of Combostrap
 * that takes a valid input string
 * and output an iso string
 */
class Iso8601Date
{
    public const CANONICAL = "date";
    public const TIME_FORMATTER_TYPE = IntlDateFormatter::NONE;
    public const DATE_FORMATTER_TYPE = IntlDateFormatter::TRADITIONAL;
    /**
     * @var DateTime|false
     */
    private $dateTime;

    /**
     * ATOM = IS08601
     * See {@link Iso8601Date::getFormat()} for more information
     */
    private const VALID_FORMATS = [
        \DateTimeInterface::ATOM,
        'Y-m-d H:i:sP',
        'Y-m-d H:i:s',
        'Y-m-d H:i',
        'Y-m-d H',
        'Y-m-d',
    ];


    /**
     * Date constructor.
     */
    public function __construct($dateTime = null)
    {

        if ($dateTime == null) {

            $this->dateTime = new DateTime();

        } else {

            $this->dateTime = $dateTime;

        }

    }

    /**
     * @param $dateString
     * @return Iso8601Date
     * @throws ExceptionBadSyntax if the format is not supported
     */
    public static function createFromString(string $dateString): Iso8601Date
    {

        $original = $dateString;

        $dateString = trim($dateString);

        /**
         * Time ?
         * (ie only YYYY-MM-DD)
         */
        if (strlen($dateString) <= 10) {
            /**
             * We had the time to 00:00:00
             * because {@link DateTime::createFromFormat} with a format of
             * Y-m-d will be using the actual time otherwise
             *
             */
            $dateString .= "T00:00:00";
        }

        /**
         * Space as T
         */
        $dateString = str_replace(" ", "T", $dateString);


        if (strlen($dateString) <= 13) {
            /**
             * We had the time to 00:00:00
             * because {@link DateTime::createFromFormat} with a format of
             * Y-m-d will be using the actual time otherwise
             *
             */
            $dateString .= ":00:00";
        }

        if (strlen($dateString) <= 16) {
            /**
             * We had the time to 00:00:00
             * because {@link DateTime::createFromFormat} with a format of
             * Y-m-d will be using the actual time otherwise
             *
             */
            $dateString .= ":00";
        }

        /**
         * Timezone
         */
        if (strlen($dateString) <= 19) {
            /**
             * Because this text metadata may be used in other part of the application
             * We add the timezone to make it whole
             * And to have a consistent value
             */
            $dateString .= date('P');
        }


        $dateTime = DateTime::createFromFormat(self::getFormat(), $dateString);
        if ($dateTime === false) {
            $message = "The date string ($original) is not in a valid date format. (" . join(", ", self::VALID_FORMATS) . ")";
            throw new ExceptionBadSyntax($message, self::CANONICAL);
        }
        return new Iso8601Date($dateTime);

    }

    public static function createFromTimestamp($timestamp): Iso8601Date
    {
        $dateTime = new DateTime();
        $dateTime->setTimestamp($timestamp);
        return new Iso8601Date($dateTime);
    }

    /**
     * And note {@link DATE_ISO8601}
     * because it's not the compliant IS0-8601 format
     * as explained here
     * https://www.php.net/manual/en/class.datetimeinterface.php#datetime.constants.iso8601
     * ATOM is
     *
     * This format is used by Sqlite, Google and is pretty the standard everywhere
     * https://www.w3.org/TR/NOTE-datetime
     */
    public static function getFormat(): string
    {
        return DATE_ATOM;
    }

    /**
     *
     */
    public static function isValid($value): bool
    {
        try {
            $dateObject = Iso8601Date::createFromString($value);
            return $dateObject->isValidDateEntry(); // ??? Validation seems to be at construction
        } catch (ExceptionBadSyntax $e) {
            return false;
        }
    }

    public function isValidDateEntry(): bool
    {
        if ($this->dateTime !== false) {
            return true;
        } else {
            return false;
        }
    }

    public static function createFromDateTime(DateTime $dateTime): Iso8601Date
    {
        return new Iso8601Date($dateTime);
    }

    public static function createFromNow(): Iso8601Date
    {
        return new Iso8601Date();
    }

    /**
     * @throws ExceptionNotFound
     */
    public static function getInternationalFormatter($constant): int
    {
        $constantNormalized = trim(strtolower($constant));
        switch ($constantNormalized) {
            case "none":
                return IntlDateFormatter::NONE;
            case "full":
                return IntlDateFormatter::FULL;
            case "relativefull":
                return IntlDateFormatter::RELATIVE_FULL;
            case "long":
                return IntlDateFormatter::LONG;
            case "relativelong":
                return IntlDateFormatter::RELATIVE_LONG;
            case "medium":
                return IntlDateFormatter::MEDIUM;
            case "relativemedium":
                return IntlDateFormatter::RELATIVE_MEDIUM;
            case "short":
                return IntlDateFormatter::SHORT;
            case "relativeshort":
                return IntlDateFormatter::RELATIVE_SHORT;
            case "traditional":
                return IntlDateFormatter::TRADITIONAL;
            default:
                throw new ExceptionNotFound("The constant ($constant) is not a valid constant", self::CANONICAL);
        }
    }

    public function getDateTime()
    {
        return $this->dateTime;
    }

    public function __toString()
    {
        return $this->getDateTime()->format(self::getFormat());
    }

    public function toIsoStringMs()
    {
        return $this->getDateTime()->format("Y-m-d\TH:i:s.u");
    }

    /**
     * Shortcut to {@link DateTime::format()}
     * Format only in English
     * @param $string
     * @return string
     * @link https://php.net/manual/en/datetime.format.php
     */
    public function format($string): string
    {
        return $this->getDateTime()->format($string);
    }

    public function toString()
    {
        return $this->__toString();
    }

    /**
     * @throws ExceptionBadSyntax
     */
    public function formatLocale($pattern = null, $locale = null)
    {

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
        if (strpos($pattern, "%") !== false) {
            LogUtility::warning("The date format ($pattern) is no more supported. Why ? Because Php has deprecated <a href=\"https://www.php.net/manual/en/function.strftime.php\">strftime</a>. You need to use the <a href=\"https://unicode-org.github.io/icu/userguide/format_parse/datetime/#datetime-format-syntax\">Unicode Date Time format</a>", self::CANONICAL);
            return strftime($pattern, $this->dateTime->getTimestamp());
        }

        /**
         * This parameters
         * are used to format date with the locale
         * when the pattern is null
         * Doc: https://unicode-org.github.io/icu/userguide/format_parse/datetime/#producing-normal-date-formats-for-a-locale
         *
         * They may be null by the way.
         *
         */
        $dateType = self::DATE_FORMATTER_TYPE;
        $timeType = self::TIME_FORMATTER_TYPE;
        if ($pattern !== null) {
            $normalFormat = explode(" ", $pattern);
            if (sizeof($normalFormat) === 2) {
                try {
                    $dateType = self::getInternationalFormatter($normalFormat[0]);
                    $timeType = self::getInternationalFormatter($normalFormat[1]);
                    $pattern = null;
                } catch (ExceptionNotFound $e) {
                    // ok
                }
            }
        }

        /**
         * Formatter instantiation
         */
        $formatter = datefmt_create(
            $locale,
            $dateType,
            $timeType,
            $this->dateTime->getTimezone(),
            IntlDateFormatter::GREGORIAN,
            $pattern
        );
        $formatted = datefmt_format($formatter, $this->dateTime);
        if ($formatted === false) {
            if ($locale === null) {
                $locale = "";
            }
            if ($pattern === null) {
                $pattern = "";
            }
            throw new ExceptionBadSyntax("Unable to format the date with the pattern ($pattern) and locale ($locale)");
        }
        return $formatted;
    }

    public function olderThan(DateTime $rightTime): bool
    {

        $internalMs = DataType::toMilliSeconds($this->dateTime);
        $externalMilliSeconds = DataType::toMilliSeconds($rightTime);
        if ($externalMilliSeconds > $internalMs) {
            return true;
        }
        return false;

    }

    public function diff(DateTime $rightTime): \DateInterval
    {
        // example get the s part of the diff (even if there is day of diff)
        // $seconds = $diff->format('%s');
        return $this->dateTime->diff($rightTime, true);
    }


}
