<?php


namespace ComboStrap;


class Sanitizer
{

    public static function sanitize($content, $suffixMessage = "", $canonical = "security")
    {
        /**
         * Nodes
         */
        $forbiddenNodes = ["script", "style", "iframe"];
        foreach ($forbiddenNodes as $forbiddenNode) {
            $pattern = "<$forbiddenNode";
            $result = preg_match_all("/$pattern/im", $content, $matches);
            if ($result) {
                return self::logAndReturnTheEmptyString("You can't used a $forbiddenNode node$suffixMessage.", $canonical);
            }
        }

        /**
         * Attribute
         */
        $pattern = "style=";
        $result = preg_match_all("/$pattern/im", $content, $matches);
        if ($result) {
            return self::logAndReturnTheEmptyString("You can't used a style attribute $suffixMessage", $canonical);
        }

        $pattern = "on[a-zA-Z]*=";
        $result = preg_match_all("/$pattern/im", $content, $matches);
        if ($result) {
            return self::logAndReturnTheEmptyString("You can't used an callback handler on attribute $suffixMessage", $canonical);
        }

        return $content;

    }

    /**
     * Created to be sure that the content returned is empty
     * @param string $string
     * @param $canonical
     * @return string
     */
    private static function logAndReturnTheEmptyString(string $string, $canonical): string
    {
        LogUtility::msg($string, LogUtility::LVL_MSG_ERROR, $canonical);
        return "";
    }

}
