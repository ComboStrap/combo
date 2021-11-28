<?php


namespace ComboStrap;


class DataType
{

    public const TABULAR_TYPE_VALUE = "tabular";
    public const PARAGRAPH_TYPE_VALUE = "paragraph";
    public const BOOLEAN_TYPE_VALUE = "boolean";
    public const DATA_TYPE_ATTRIBUTE = "type";
    public const TEXT_TYPE_VALUE = "text";
    public const DATETIME_TYPE_VALUE = "datetime";
    public const JSON_TYPE_VALUE = "json";
    /**
     * The constant value
     */
    public const TYPES = [
        DataType::TEXT_TYPE_VALUE,
        DataType::TABULAR_TYPE_VALUE,
        DataType::DATETIME_TYPE_VALUE,
        DataType::PARAGRAPH_TYPE_VALUE,
        DataType::JSON_TYPE_VALUE,
        DataType::BOOLEAN_TYPE_VALUE
    ];
}
