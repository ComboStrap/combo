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

namespace ComboStrap;

require_once(__DIR__ . '/StringUtility.php');

class StyleUtility
{

    const COMBOSTRAP_FIX = "cs";
    public const STYLE_ATTRIBUTE = "style";

    public static function getRule(array $styles, $selector)
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

            $tagAttributes->addClassName(self::getStylingClassForTag($logicalTag));
            if (!empty($tagAttributes->getType())) {
                $tagAttributes->addClassName($logicalTag . "-" . $tagAttributes->getType() . "-" . self::COMBOSTRAP_FIX);
            }
        }
    }

    public static function getStylingClassForTag($logicalTag): string
    {
        return $logicalTag . "-" . self::COMBOSTRAP_FIX;
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
}
