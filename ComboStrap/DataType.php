<?php


namespace ComboStrap;


class DataType
{

    /**
     * The property name when the type value is persisted
     */
    public const PROPERTY_NAME = "type";


    /**
     * An object with several children metadata
     * An entity
     * A group of metadata
     */
    public const TABULAR_TYPE_VALUE = "tabular";
    /**
     * Text with carriage return
     */
    public const PARAGRAPH_TYPE_VALUE = "paragraph";
    /**
     * True/False
     */
    public const BOOLEAN_TYPE_VALUE = "boolean";

    /**
     * A couple of words without any carriage return
     */
    public const TEXT_TYPE_VALUE = "text";
    /**
     * Date Time
     */
    public const DATETIME_TYPE_VALUE = "datetime";
    /**
     * A string but in Json
     */
    public const JSON_TYPE_VALUE = "json";

    /**
     * Integer
     */
    public const INTEGER_TYPE_VALUE = "integer";

    /**
     * Array of array of array
     */
    const ARRAY_VALUE = "array";

    /**
     * The constant value
     */
    public const TYPES = [
        DataType::TEXT_TYPE_VALUE,
        DataType::TABULAR_TYPE_VALUE,
        DataType::DATETIME_TYPE_VALUE,
        DataType::PARAGRAPH_TYPE_VALUE,
        DataType::JSON_TYPE_VALUE,
        DataType::BOOLEAN_TYPE_VALUE,
        DataType::INTEGER_TYPE_VALUE,
    ];
    const FLOOR = "floor";
    const CEIL = "ceil";


    /**
     * @throws ExceptionBadArgument
     */
    public static function toIntegerOrDefaultIfNull($targetValue, $default): int
    {
        if ($targetValue === null) {
            return $default;
        }
        return self::toInteger($targetValue);
    }

    /**
     *
     * @var string $roundDirection - ceil or floor (by default floor)
     * @throws ExceptionBadArgument
     */
    public static function toInteger($targetValue,string $roundDirection = self::FLOOR): int
    {


        if (is_int($targetValue)) {
            return $targetValue;
        }
        if (!is_string($targetValue) && !is_float($targetValue)) {
            $varExport = var_export($targetValue, true);
            throw new ExceptionBadArgument("The value passed is not a numeric/nor a string. We can not translate it to an integer. Value: $varExport");
        }
        /**
         * Float 12.845 will return 12
         */
        $float = self::toFloat($targetValue);
        if($roundDirection===self::FLOOR) {
            $int = floor($float);
        } else {
            $int = ceil($float);
        }
        if (
            $int === 0 &&
            "$targetValue" !== "0"
        ) {
            throw new ExceptionBadArgument("The value ($targetValue) can not be cast to an integer.");
        }
        return $int;
    }

    /**
     * @throws ExceptionBadArgument
     */
    public static function toIntegerCeil($targetValue): int
    {

        return self::toInteger($targetValue, self::CEIL);

    }

    public static function toBoolean($value, $ifNull = null)
    {
        if ($value === null) return $ifNull;
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @throws ExceptionBadArgument - if the value is not a numeric
     */
    public static function toFloat($value): float
    {
        if (is_float($value)) {
            return $value;
        }

        if (!is_numeric($value)) {
            throw new ExceptionBadArgument("The value ($value) is not a numeric");
        }

        return floatval($value);
    }

    public static function toBooleanString(?bool $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if ($value) {
            return "true";
        } else {
            return "false";
        }
    }

    /**
     * @param mixed|null $value
     * @return bool - true if the value is built-in boolean or null
     */
    public static function isBoolean($value): bool
    {
        return is_bool($value);
    }

    public static function toString($value)
    {
        if (is_string($value)) {
            return $value;
        }
        if (is_array($value)) {
            return ArrayUtility::formatAsString($value);
        }
        if (is_object($value)) {
            return $value->__toString();
        }
        if (is_bool($value)) {
            return var_export($value, true);
        }
        return strval($value);
    }

    public static function getType($value): string
    {
        if (is_string($value)) {
            return "string";
        }
        if (is_array($value)) {
            return "array";
        }
        if (is_object($value)) {
            return "object (" . get_class($value) . ")";
        }
        return gettype($value);
    }

    public static function toMilliSeconds(\DateTime $dateTime)
    {

        $secs = $dateTime->getTimestamp(); // Gets the seconds
        $millisecs = $secs * 1000; // Converted to milliseconds
        $millisecs += $dateTime->format("u") / 1000; // Microseconds converted to seconds
        return $millisecs;

    }


}
