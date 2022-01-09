<?php


namespace ComboStrap;


use DateTime;


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
     * @param null $dateString
     * @return Iso8601Date
     * @throws ExceptionCombo if the format is not supported
     */
    public static function createFromString($dateString = null): Iso8601Date
    {

        $original = $dateString;

        if ($dateString === null) {
            return new Iso8601Date();
        }

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
            throw new ExceptionCombo($message, self::CANONICAL);
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

    public static function isValid($value): bool
    {
        $dateObject = Iso8601Date::createFromString($value);
        return $dateObject->isValidDateEntry();
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

    public function getDateTime()
    {
        return $this->dateTime;
    }

    public function __toString()
    {
        return $this->getDateTime()->format(self::getFormat());
    }

    /**
     * Shortcut to {@link DateTime::format()}
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




}
