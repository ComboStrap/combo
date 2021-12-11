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
     * Multiple value can be chosen
     */
    public const ARRAY_TYPE_VALUE = "array";

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
        DataType::ARRAY_TYPE_VALUE,
    ];

}
