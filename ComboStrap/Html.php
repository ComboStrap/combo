<?php


namespace ComboStrap;


use ComboStrap\Web\Url;
use ComboStrap\Xml\XmlDocument;

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
     *
     * See also: https://github.com/Flet/github-slugger
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
     * Encode special HTML characters to entity (ie escaping)
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
     *
     * Note that if the `meta[charset]` matches the text encoding   , it should not be encoded
     *
     * True ? Beware that this still allows users to insert unsafe scripting vectors, such as markdown links like [xss](javascript:alert%281%29).
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

    public static function getDiffBetweenValuesSeparatedByBlank(string $expected, string $actual, string $expectedName = "expected class", string $actualName = "actual class"): string
    {
        $leftClasses = preg_split("/\s/", $expected);
        $rightClasses = preg_split("/\s/", $actual);
        $error = "";
        foreach ($leftClasses as $leftClass) {
            if (!in_array($leftClass, $rightClasses)) {
                $error .= "The $expectedName has the value (" . $leftClass . ") that is not present in the $actualName)\n";
            } else {
                // Delete the value
                $key = array_search($leftClass, $rightClasses);
                unset($rightClasses[$key]);
            }
        }
        foreach ($rightClasses as $rightClass) {
            $error .= "The $actualName has the value (" . $rightClass . ") that is not present in the $expectedName)\n";
        }
        return $error;
    }

    /**
     * @throws ExceptionBadSyntax - bad url
     * @throws ExceptionNotEquals - not equals
     */
    public static function getDiffBetweenSrcSet(string $expected, string $actual)
    {
        $expectedSrcSets = explode(",", $expected);
        $actualSrcSets = explode(",", $actual);
        $countExpected = count($expectedSrcSets);
        $countActual = count($actualSrcSets);
        if ($countExpected !== $countActual) {
            throw new ExceptionNotEquals("The expected srcSet count ($countExpected) is not the same than the actual ($countActual).");
        }
        for ($i = 0; $i < $countExpected; $i++) {
            $expectedSrcSet = trim($expectedSrcSets[$i]);
            $expectedSrcSetExploded = explode(" ", $expectedSrcSet, 2);
            $expectedSrc = $expectedSrcSetExploded[0];
            if (count($expectedSrcSetExploded) == 2) {
                $expectedWidth = $expectedSrcSetExploded[1];
            } else {
                $expectedWidth = null;
            }
            $actualSrcSet = trim($actualSrcSets[$i]);
            $actualSrcSetExploded = explode(" ", $actualSrcSet, 2);
            $actualSrc = $actualSrcSetExploded[0];
            if (count($actualSrcSetExploded) == 2) {
                $actualWidth = $actualSrcSetExploded[1];
            } else {
                $actualWidth = null;
            }
            if ($expectedWidth !== $actualWidth) {
                throw new ExceptionNotEquals("The expected width ($expectedWidth) of the srcSet ($i) is not the same than the actual ($actualWidth).");
            }
            try {
                Html::getDiffBetweenUrlStrings($expectedSrc, $actualSrc);
            } catch (ExceptionBadSyntax $e) {
                throw new ExceptionBadSyntax("Bad Syntax on Src Set ($i). Error: {$e->getMessage()}. Expected: $expectedSrc vs Actual: $actualSrc. ");
            } catch (ExceptionNotEquals $e) {
                throw ExceptionNotEquals::create("Not Equals on Src Set ($i). Error: {$e->getMessage()}.", $expectedSrc, $actualSrc);
            }
        }
    }


    /**
     * @throws ExceptionBadSyntax
     * @throws ExceptionNotEquals
     */
    public static function getDiffBetweenUrlStrings(string $expected, string $actual)
    {
        try {
            $url = Url::createFromString($expected);
        } catch (ExceptionBadSyntax $e) {
            throw new ExceptionBadSyntax("The expected URL string ($expected) is not valid. Error: {$e->getMessage()}");
        }
        try {
            $urlActual = Url::createFromString($actual);
        } catch (ExceptionBadSyntax $e) {
            throw new ExceptionBadSyntax("The $actual URL string ($actual) is not valid. Error: {$e->getMessage()}");
        }
        $url->equals($urlActual);

    }

    /**
     * Merge class name
     * @param string $newNames - the name that we want to add
     * @param ?string $actualNames - the actual names
     * @return string - the class name list
     *
     * for instance:
     *   * newNames = foo blue
     *   * actual Name = foo bar
     * return
     *   * foo bar blue
     */
    public static function mergeClassNames(string $newNames, ?string $actualNames): string
    {
        /**
         * It may be in the form "value1 value2"
         */
        $newValues = StringUtility::explodeAndTrim($newNames, " ");
        if (!empty($actualNames)) {
            $actualValues = StringUtility::explodeAndTrim(trim($actualNames), " ");
        } else {
            $actualValues = [];
        }
        $newValues = array_merge($actualValues, $newValues);
        $newValues = array_unique($newValues);
        return implode(" ", $newValues);
    }

    /**
     * @param array $styleProperties - an array of CSS properties with key, value
     * @return string - the value for the style attribute (ie all rules where joined with the comma)
     */
    public static function array2InlineStyle(array $styleProperties)
    {
        $inlineCss = "";
        foreach ($styleProperties as $key => $value) {
            $inlineCss .= "$key:$value;";
        }
        // Suppress the last ;
        if ($inlineCss[strlen($inlineCss) - 1] == ";") {
            $inlineCss = substr($inlineCss, 0, -1);
        }
        return $inlineCss;
    }
}
