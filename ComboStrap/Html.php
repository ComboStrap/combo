<?php


namespace ComboStrap;


class Html
{


    /**
     * @param string $name
     * @throws ExceptionComboRuntime
     * Garbage In / Garbage Out design
     */
    public static function validNameGuard(string $name)
    {
        /**
         * If the name is not in lowercase,
         * the shorthand css selector does not work
         */
        $validName = strtolower($name);
        if ($validName != $name) {
            throw new ExceptionComboRuntime("The name ($name) is not a valid name");
        }
    }
}
