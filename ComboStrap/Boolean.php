<?php


namespace ComboStrap;


class Boolean
{

    public static function toBoolean($value)
    {
        if($value===null) return null;
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
