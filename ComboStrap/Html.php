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

    /**
     * Transform a text into a valid HTML id
     * @param $string
     * @return string
     */
    public static function toHtmlId($string): string
    {
        /**
         * sectionId calls cleanID
         * cleanID delete all things before a ':'
         * we do then the replace before to not
         * lost a minus '-' separator
         */
        $string = str_replace(array(':', '.'), '', $string);
        return sectionID($string, $check);
    }
}
