<?php


namespace ComboStrap;


class Html
{


    /**
     * @param string $name
     * @throws ExceptionRuntime
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
            throw new ExceptionRuntime("The name ($name) is not a valid name");
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

    /**
     * Encode special HTML characters to entity
     * (ie escaping)
     *
     * This is used to transform text that may be interpreted as HTML
     * into a text
     *   * that will not be interpreted as HTML
     *   * that may be added in html attribute
     *
     * For instance:
     *  * text that should go in attribute with special HTML characters (such as title)
     *  * text that we don't create (to prevent HTML injection)
     *
     * Example:
     *
     * <script>...</script>
     * to
     * "&lt;script&gt;...&lt;/hello&gt;"
     *
     *
     * @param $text
     * @return string
     */
    public static function encode($text): string
    {
        /**
         * See https://stackoverflow.com/questions/46483/htmlentities-vs-htmlspecialchars/3614344
         *
         * Not {@link htmlentities } htmlentities($text, ENT_QUOTES);
         * Otherwise we get `Error while loading HTMLError: Entity 'hellip' not defined`
         * when loading HTML with {@link XmlDocument}
         *
         * See also {@link self::htmlDecode()}
         *
         * Without ENT_QUOTES
         * <h4 class="heading-combo">
         * is encoded as
         * &gt;h4 class="heading-combo"&lt;
         * and cannot be added in a attribute because of the quote
         * This is used for {@link Tooltip}
         */
        return htmlspecialchars($text, ENT_XHTML | ENT_QUOTES | ENT_DISALLOWED);

    }

    public static function decode($int): string
    {
        return htmlspecialchars_decode($int, ENT_XHTML | ENT_QUOTES);
    }

    public static function getDiffBetweenClass(string $expected, string $actual, string $expectedId = "expected", string $actualId = "actual"): string
    {
        $leftClasses = preg_split("/\s/", $expected);
        $rightClasses = preg_split("/\s/", $actual);
        $error = "";
        foreach ($leftClasses as $leftClass) {
            if (!in_array($leftClass, $rightClasses)) {
                $error .= "The class ($expectedId) has the value (" . $leftClass . ") that is not present in the class ($actualId))\n";
            } else {
                // Delete the value
                $key = array_search($leftClass, $rightClasses);
                unset($rightClasses[$key]);
            }
        }
        foreach ($rightClasses as $rightClass) {
            $error .= "The actual ($actualId) has the value (" . $rightClass . ") that is not present in the class ($expectedId))\n";
        }
        return $error;
    }
}
