<?php
/**
 * Copyright (c) 2020. ComboStrap, Inc. and its affiliates. All Rights Reserved.
 *
 * This source code is licensed under the GPL license found in the
 * COPYING  file in the root directory of this source tree.
 *
 * @license  GPL 3 (https://www.gnu.org/licenses/gpl-3.0.en.html)
 * @author   ComboStrap <support@combostrap.com>
 *
 */

namespace ComboStrap\TagAttribute;


use ComboStrap\ExceptionNotEquals;
use ComboStrap\StringUtility;
use ComboStrap\TagAttributes;

/**
 * This class gots static function about the HTML style attribute
 *
 * The style attribute is not allowed due to security concern
 * in Combostrap (Click Hijacking, ...)
 */
class StyleAttribute
{

    const COMBOSTRAP_FIX = "cs";
    public const STYLE_ATTRIBUTE = "style";

    public static function getRule(array $styles, $selector): string
    {
        $rule = $selector . " {" . DOKU_LF;
        foreach ($styles as $key => $value) {
            $rule .= "    $key:$value;" . DOKU_LF;
        }
        StringUtility::rtrim($rule, ";");
        return $rule . DOKU_LF . "}" . DOKU_LF;

    }

    /**
     * @param array $array of property as key and value
     * @return string a html inline style property
     */
    public static function createInlineValue(array $array)
    {
        $inline = "";
        foreach ($array as $property => $value) {
            if ($inline != "") {
                $inline .= ";$property:$value";
            } else {
                $inline = "$property:$value";
            }
        }
        return $inline;

    }

    /**
     * Add class for user styling
     * See
     * https://combostrap.com/styling/userstyle#class
     * @param TagAttributes $tagAttributes
     */
    public static function addStylingClass(TagAttributes &$tagAttributes)
    {
        $logicalTag = $tagAttributes->getLogicalTag();
        if ($logicalTag !== null && $tagAttributes->getDefaultStyleClassShouldBeAdded() === true) {

            $tagAttributes->addClassName(self::addComboStrapSuffix($logicalTag));
            if (!empty($tagAttributes->getType())) {
                $tagAttributes->addClassName($logicalTag . "-" . $tagAttributes->getType() . "-" . self::COMBOSTRAP_FIX);
            }
        }
    }

    public static function addComboStrapSuffix($name): string
    {
        return $name . "-" . self::COMBOSTRAP_FIX;
    }

    public static function HtmlStyleValueToArray(string $htmlStyleValue): array
    {
        $stylingDeclarationsAsString = explode(";", $htmlStyleValue);
        $stylingDeclarationAsArray = [];
        foreach ($stylingDeclarationsAsString as $stylingDeclaration) {
            if (empty($stylingDeclaration)) {
                // case with a trailing comma. ie `width:18rem;`
                continue;
            }
            [$key, $value] = preg_split("/:/", $stylingDeclaration, 2);
            $stylingDeclarationAsArray[$key] = $value;
        }
        return $stylingDeclarationAsArray;

    }

    /**
     * @throws ExceptionNotEquals
     */
    public static function arrayEquals(array $expectedQuery, array $actualQuery)
    {
        foreach ($actualQuery as $key => $value) {
            $expectedValue = $expectedQuery[$key];
            if ($expectedValue === null) {
                throw new ExceptionNotEquals("The expected style does not have the $key property");
            }
            if ($expectedValue !== $value) {
                throw new ExceptionNotEquals("The style $key property does not have the same value ($value vs $expectedValue)");
            }
            unset($expectedQuery[$key]);
        }
        foreach ($expectedQuery as $key => $value) {
            throw new ExceptionNotEquals("The expected styles has an extra property ($key=$value)");
        }
    }

    /**
     * @throws ExceptionNotEquals
     */
    public static function stringEquals($leftStyles, $rightStyles)
    {
        $leftStylesArray = StyleAttribute::HtmlStyleValueToArray($leftStyles);
        $rightStylesArray = StyleAttribute::HtmlStyleValueToArray($rightStyles);
        self::arrayEquals($leftStylesArray,$rightStylesArray);
    }
}
