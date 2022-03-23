<?php


namespace ComboStrap;


class Integer
{

    /**
     * @throws ExceptionCombo
     */
    public static function toInt($value): int
    {
        /**
         * Note: `is_int($value)` returns false for a string 2
         */
        if (is_int($value)) {
            return $value;
        }

        if (!is_numeric($value)) {
            throw new ExceptionCombo("The value is not a numeric");
        }

        return intval($value);
    }
}
