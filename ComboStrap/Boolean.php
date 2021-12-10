<?php


namespace ComboStrap;


class Boolean
{

    public static function toBoolean($value)
    {
        if ($value === null) return null;
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public static function toString(?bool $value): ?string
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
}
