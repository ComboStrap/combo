<?php

namespace ComboStrap;

use ComboStrap\Web\Url;

/**
 * Class StringUtility
 * @package ComboStrap
 * A class with string utility
 */
class StringUtility
{

    public const SEPARATORS_CHARACTERS = [".", "(", ")", ",", "-"];


    /**
     * Generate a text with a max length of $length
     * and add ... if above
     * @param $myString
     * @param $length
     * @return string
     */
    static function truncateString($myString, $length): string
    {

        if (strlen($myString) > $length) {
            $suffix = ' ...';
            $myString = substr($myString, 0, ($length - 1) - strlen($suffix)) . $suffix;
        }
        return $myString;
    }

    /**
     * @param $string
     * @return string - the string without any carriage return
     * Used to compare string without worrying about carriage return
     */
    public static function normalized($string)
    {
        return str_replace("\n", "", $string);
    }

    /**
     * @param $needle
     * @param $haystack
     * @return bool
     */
    public static function contain($needle, $haystack)
    {
        $pos = strpos($haystack, $needle);
        if ($pos === FALSE) {
            return false;
        } else {
            return true;
        }
    }

    public static function toString($value)
    {
        /**
         * No transformation if it's a string
         * var_export below is not idempotent
         * ie \ would become \\
         */
        if (is_string($value)) {
            return $value;
        }

        if (is_array($value)) {
            $string = var_export($value, true);

            // An array value gets command in var_export
            $lastCharacterIndex = strlen($string) - 1;
            if ($string[0] === "'" && $string[$lastCharacterIndex] === "'") {
                $string = substr($string, 1, strlen($string) - 2);
            }
            return $string;
        }

        if (is_object($value)) {
            if (method_exists($value, "__toString")) {
                return strval($value);
            } else {
                return get_class($value);
            }
        }

        if (is_numeric($value)) {
            return strval($value);
        }

        if (is_bool($value)) {
            return var_export($value, true);
        }

        $string = var_export($value, true);
        LogUtility::msg("The type of the value ($string) is unknown and could not be properly cast to string", LogUtility::LVL_MSG_WARNING);
        return $string;

    }

    /**
     * Add an EOL if not present at the end of the string
     * @param $doc
     */
    public static function addEolCharacterIfNotPresent(&$doc)
    {
        $strlen = strlen($doc);
        if ($strlen < 1) {
            return;
        }
        if ($doc[$strlen - 1] != DOKU_LF) {
            $doc .= DOKU_LF;
        }
    }

    /**
     * Delete the string from the end
     * This is used generally to delete the previous opening tag of an header or a blockquote
     * @param $doc
     * @param $string
     */
    public static function rtrim(&$doc, $string)
    {

        /**
         * We trim because in the process, we may get extra {@link DOKU_LF} at the end
         */
        $doc = trim($doc);
        $string = trim($string);
        $length = strlen($doc) - strlen($string);
        if (substr($doc, $length) === $string) {
            $doc = substr($doc, 0, $length);
        }

    }

    /**
     * Delete the string from the beginning
     * This is used to delete a tag for instance
     * @param $doc
     * @param $string
     */
    public static function ltrim(&$doc, $string)
    {

        $doc = trim($doc);
        $string = trim($string);
        $length = strlen($string);
        if (substr($doc, 0, $length) === $string) {
            $doc = substr($doc, $length);
        }

    }

    /**
     * The word count does not take into account
     * words with non-words characters such as < =
     * Therefore the node <node> and attribute name=value are not taken in the count
     * @param $text
     * @return int the number of words
     */
    public static function getWordCount($text)
    {
        /**
         * Delete the frontmatter
         */
        $text = preg_replace("/^---(json)?$.*^---$/Ums", "", $text);
        /**
         * New line for node
         */
        $text = str_replace("<", "\n<", $text);
        $text = str_replace(">", ">\n", $text);
        // \s shorthand for whitespace
        // | the table and links are separated with a |
        // / to take into account expression such as and/or
        // /u for unicode support (https://www.php.net/manual/en/reference.pcre.pattern.modifiers.php)
        $wordSeparator = '/[\s|\/]/u';
        $preg_split = preg_split($wordSeparator, $text);
        $wordsWithoutEmpty = array_filter($preg_split, self::class . '::isWord');
        return count($wordsWithoutEmpty);
    }

    public static function normalize($expected)
    {
        $expected = preg_replace("/[\s]/", " ", $expected);
        $expected = str_replace("  ", " ", $expected);
        $expected = str_replace("  ", " ", $expected);
        $expected = str_replace("  ", " ", $expected);
        $expected = str_replace("  ", " ", $expected);
        return trim($expected);

    }

    /**
     * @param $text
     * @return bool
     */
    public static function isWord($text)
    {
        if (empty($text)) {
            return false;
        }
        /**
         * We also allow `-` minus
         *
         * And because otherwise the words are not counted:
         *   * `'` (used to highlight words)
         *   * `[]` used in links
         *   * `,` used at the end of a sentenct
         */
        $preg_match = preg_match("/^[\w\-'\]\[,]*$/u", $text);
        return $preg_match == 1;
    }

    public static function match($subject, $pattern)
    {
        return preg_match("/$pattern/", $subject) === 1;
    }

    public static function endWiths($string, $suffix)
    {
        $suffixStartPosition = strlen($string) - strlen($suffix);
        return strrpos($string, $suffix) === $suffixStartPosition;
    }

    public static function explodeAndTrim($string, $delimiter = ",")
    {
        return array_map('trim', explode($delimiter, $string));
    }

    public static function lastIndexOf($haystack, $needle)
    {
        /**
         * strRpos
         * and not strpos
         */
        return strrpos($haystack, $needle);
    }

    public static function startWiths($string, $prefix)
    {
        return strrpos($string, $prefix) === 0;
    }

    /**
     * @param $string
     * @param null $separatorsCharacters - characters that will separate the words
     * @return array a words
     */
    public static function getWords($string, $separatorsCharacters = null): array
    {
        // Reserved characters to space
        if ($separatorsCharacters === null) {
            $separatorsCharacters = StringUtility::getAllSeparators();
        }
        if (!is_array($separatorsCharacters)) {
            LogUtility::msg("The separators characters are not an array, default characters used");
            $separatorsCharacters = StringUtility::getAllSeparators();
        }

        $string = str_replace($separatorsCharacters, " ", $string);
        // Doubles spaces to space
        $string = preg_replace("/\s{2,}/", " ", $string);
        // Trim space
        $string = trim($string);

        return explode(" ", $string);
    }

    private static function getAllSeparators(): array
    {
        return array_merge(
            Url::RESERVED_WORDS,
            LocalPath::RESERVED_WINDOWS_CHARACTERS,
            StringUtility::SEPARATORS_CHARACTERS
        );
    }

}
