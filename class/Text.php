<?php
/**
 * Copyright (c) 2021. ComboStrap, Inc. and its affiliates. All Rights Reserved.
 *
 * This source code is licensed under the GPL license found in the
 * COPYING  file in the root directory of this source tree.
 *
 * @license  GPL 3 (https://www.gnu.org/licenses/gpl-3.0.en.html)
 * @author   ComboStrap <support@combostrap.com>
 *
 */

namespace ComboStrap;


class Text
{

    /**
     * The word count does not take into account
     * words with non-words characters such as < =
     * Therefore the node <node> and attribute name=value are not taken in the count
     * @param $text
     * @return int the number of words
     */
    public static function getWordCount($text)
    {
        $text = str_replace("<", "\n<", $text);
        $text = str_replace(">", ">\n", $text);
        // \s shorthand for whitespace
        // | the table and links are separated with a |
        // /u for unicode support (https://www.php.net/manual/en/reference.pcre.pattern.modifiers.php)
        $wordSeparator = '/[\s|]/u';
        $preg_split = preg_split($wordSeparator, $text);
        $wordsWithoutEmpty = array_filter($preg_split, 'self::isWord');
        return count($wordsWithoutEmpty);
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

    public static function normalize($expected)
    {
        $expected = preg_replace("/[\s]/", " ", $expected);
        $expected = str_replace("  ", " ", $expected);
        $expected = str_replace("  ", " ", $expected);
        $expected = str_replace("  ", " ", $expected);
        $expected = str_replace("  ", " ", $expected);
        return trim($expected);

    }

}
