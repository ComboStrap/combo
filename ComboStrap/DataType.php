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
     * The constant value
     */
    public const TYPES = [
        DataType::TEXT_TYPE_VALUE,
        DataType::TABULAR_TYPE_VALUE,
        DataType::DATETIME_TYPE_VALUE,
        DataType::PARAGRAPH_TYPE_VALUE,
        DataType::JSON_TYPE_VALUE,
        DataType::BOOLEAN_TYPE_VALUE,
    ];

    /**
     * @throws ExceptionCombo
     */
    public static function toInteger($targetValue): int
    {
        if (is_int($targetValue)) {
            return $targetValue;
        }
        if (!is_string($targetValue) && !is_float($targetValue)) {
            $varExport = var_export($targetValue, true);
            throw new ExceptionCombo("The value passed is not a numeric/nor a string. We can not translate it to an integer. Value: $varExport");
        }
        /**
         * Float 12.845 will return 12
         */
        $int = intval($targetValue);
        if (
            $int === 0 &&
            "$targetValue" !== "0"
        ) {
            throw new ExceptionCombo("The value ($targetValue) can not be cast to an integer.");
        }
        return $int;
    }

    public static function toBoolean($value)
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public static function toFloat($value): float
    {
        return floatval($value);
    }


}
