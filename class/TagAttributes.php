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

use dokuwiki\Extension\SyntaxPlugin;

/**
 * An helper to create manipulate component and html attributes
 *
 * You can:
 *   * declare component attribute after parsing
 *   * declare Html attribute during parsing
 *   * output the final HTML attributes at the end of the process with the function {@link TagAttributes::toHTMLString()}
 *
 * Component attributes have precedence on HTML attributes.
 *
 * @package ComboStrap
 */
class TagAttributes
{
    const ALIGN_KEY = 'align';

    /**
     * @var array attribute that were set on a component
     */
    private $componentAttributes;

    /**
     * @var array the style declaration array
     */
    private $styleDeclaration = array();

    /**
     * @var bool - set when the transformation from component attribute to html attribute
     * was done to avoid circular problem
     */
    private $componentToHtmlAttributeProcessingWasDone = false;

    /**
     * @var array - html attributes set in the code. This is needed to make a difference
     * on attribute name that are the same such as the component attribute `width` that is
     * transformed as a style `max-width` but exists also as attribute of an image for instance
     */
    private $htmlAttributes = array();

    /**
     * @var array - the final html array
     */
    private $finalHtmlArray = array();

    /**
     * ComponentAttributes constructor.
     * Use the static create function to instantiate this object
     * @param array $componentAttributes
     */
    private function __construct($componentAttributes = array())
    {
        $this->componentAttributes = $componentAttributes;
    }

    /**
     * @param $match - the {@link SyntaxPlugin::handle()} match
     * @return TagAttributes
     */
    public static function createFromTagMatch($match)
    {
        $htmlAttributes = PluginUtility::getTagAttributes($match);
        return self::createFromCallStackArray($htmlAttributes);
    }

    /**
     * @param $array - the array got from the {@link TagAttributes::toInternalArray()} that is passed between the {@link SyntaxPlugin::handle()} and {@link SyntaxPlugin::render()}  method
     * @return TagAttributes
     */
    public static function createFromInternalArray($array)
    {
        return new TagAttributes($array);
    }

    public static function createEmpty()
    {
        return new TagAttributes(array());
    }

    /**
     * @param $renderArray - an array of key value pair
     * @return TagAttributes
     */
    public static function createFromCallStackArray($renderArray)
    {
        $attributes = self::CallStackArrayToInternalArray($renderArray);
        return new TagAttributes($attributes);
    }

    /**
     * Utility function to go from an html array to a tag array
     * Used during refactoring
     * @param array $htmlAttributes
     * @return array
     */
    private static function CallStackArrayToInternalArray(array $htmlAttributes)
    {
        $attributes = array();
        foreach ($htmlAttributes as $key => $attribute) {

            if (empty($attribute)) {
                continue;
            }

            /**
             * Life is hard
             */
            if (is_bool($attribute)) {
                $attribute = var_export($attribute, true);
            }
            /**
             * Life is harder
             */
            if (is_string($attribute)) {
                $explodeArray = explode(" ", $attribute);
                $arrayValues = array();
                foreach ($explodeArray as $explodeValue) {
                    $arrayValues[$explodeValue] = true;
                }
                $attributes[$key] = $arrayValues;
            } else {
                LogUtility::msg("The variable value ($attribute) of the key ($key) is not a string and was ignored", LogUtility::LVL_MSG_ERROR, "support");
            }

        }
        return $attributes;
    }

    /**
     *
     * @param $value
     * @return string return a CSS property with pixel as unit if the unit is not specified
     */
    public static function toPixelLengthIfNoSpecified($value)
    {
        if (preg_match("/^[0-9]*$/", $value)) {
            return $value . "px";
        } else {
            return $value;
        }

    }

    public function addClassName($className)
    {

        $this->addComponentAttributeValue('class', $className);

    }

    public function getClass()
    {
        return $this->getValue('class');
    }

    public function getStyle()
    {
        return PluginUtility::array2InlineStyle($this->styleDeclaration);
    }

    /**
     * Add an attribute with its value if the value is not empty
     * @param $attributeName
     * @param $attributeValue
     */
    public function addComponentAttributeValue($attributeName, $attributeValue)
    {

        if (empty($attributeValue)) {
            LogUtility::msg("The value of the attribute ($attributeName) is empty. Use the nonEmpty function instead", LogUtility::LVL_MSG_WARNING, "support");
        }

        $attLower = strtolower($attributeName);
        if (!$this->hasComponentAttribute($attLower)) {
            $this->componentAttributes[$attLower] = array();
        }

        /**
         * It may be in the form "value1 value2"
         */
        $values = StringUtility::explodeAndTrim($attributeValue, " ");
        foreach ($values as $value) {
            $this->componentAttributes[$attLower][trim($value)] = true;
        }

    }


    public function addComponentAttributeValueIfNotEmpty($attributeName, $attributeValue)
    {
        if (!empty($attributeValue)) {
            $this->addComponentAttributeValue($attributeName, $attributeValue);
        }
    }

    public function hasComponentAttribute($attributeName)
    {
        $lowerAtt = strtolower($attributeName);
        return isset($this->componentAttributes[$lowerAtt]);
    }

    /**
     * To an HTML array in the form
     *   class => 'value1 value2',
     *   att => 'value1 value 2'
     * For historic reason, data passed between the handle and the render
     * can still be in this format
     */
    public function toHtmlArray()
    {
        if (!$this->componentToHtmlAttributeProcessingWasDone) {

            $this->componentToHtmlAttributeProcessingWasDone = true;


            /**
             * Process animation (onHover, onView)
             */
            Animation::processOnHover($this);
            Animation::processOnView($this);


            /**
             * Position and Stickiness
             */
            Position::processStickiness($this);
            Position::processPosition($this);

            /**
             * Process the attributes that have an effect on the class
             */
            PluginUtility::processSpacingAttributes($this);
            PluginUtility::processAlignAttributes($this);

            /**
             * Process the style attributes if any
             */
            PluginUtility::processStyle($this);
            PluginUtility::processCollapse($this);


            /**
             * Create a non-sorted temporary html attributes array
             */
            $tempHtmlArray = $this->htmlAttributes;

            // copy the unknown component attributes
            $excludedAttribute = ["script", "type"];
            foreach ($this->componentAttributes as $key => $arrayValue) {
                if (!in_array($key, $excludedAttribute)) {
                    $value = implode(array_keys($arrayValue), " ");
                    $tempHtmlArray[$key] = $value;
                }
            }
            // Copy the style
            $tempHtmlArray["style"] = $this->getStyle();

            /**
             * Sort by attribute
             * https://datacadamia.com/web/html/attribute#order
             */
            $sortedArray = array();
            $once = "once";
            $multiple = "multiple";
            $orderPatterns = [
                "class" => $once,
                "id" => $once,
                "name" => $once,
                "data-.*" => $multiple,
                "src.*" => $multiple,
                "for" => $once,
                "type" => $once,
                "href" => $once,
                "value" => $once,
                "title" => $once,
                "alt" => $once,
                "role" => $once,
                "aria-*" => $multiple];
            foreach ($orderPatterns as $pattern => $type) {
                foreach ($tempHtmlArray as $name => $value) {
                    if (empty($value)) {
                        break;
                    }
                    $searchPattern = "^$pattern$";
                    if (preg_match("/$searchPattern/", $name)) {
                        $sortedArray[$name] = $value;
                        unset($tempHtmlArray[$name]);
                        if ($type == $once) {
                            break;
                        }
                    }
                }
            }
            foreach ($tempHtmlArray as $name => $value) {
                if (!empty($value)) {
                    $sortedArray[$name] = $value;
                }
            }
            $this->finalHtmlArray = $sortedArray;

        }


        return $this->finalHtmlArray;

    }

    /**
     * HTML attribute are attributes
     * that are not transformed to HTML
     * (We make a difference between a high level attribute
     * that we have in the written document set on a component
     * @param $key
     * @param $value
     */
    public function addHtmlAttributeValue($key, $value)
    {
        $this->htmlAttributes[$key] = $value;
    }

    public function addHtmlAttributeValueIfNotEmpty($key, $value)
    {
        if (!empty($value)) {
            $this->addHtmlAttributeValue($key, $value);
        }
    }

    /**
     * @param $attributeName
     * @param null $default
     * @return string|null a HTML value in the form 'value1 value2...'
     */
    public function getValue($attributeName, $default = null)
    {
        if ($this->hasComponentAttribute($attributeName)) {
            $value = $this->componentAttributes[$attributeName];
            if (!is_array($value)) {
                LogUtility::msg("Internal Error: The value ($value) is not an array", LogUtility::LVL_MSG_ERROR, "support");
            }
            $keys = array_keys($value);
            return implode(" ", $keys);
        } else {
            return $default;
        }
    }

    /**
     * @return array - the storage format returned from the {@link SyntaxPlugin::handle()}  method
     */
    public function toInternalArray()
    {
        return $this->componentAttributes;
    }

    /**
     * Get the value and remove it from the attributes
     * @param $attributeName
     * @param $default
     * @return string|null
     */
    public function getValueAndRemove($attributeName, $default = null)
    {
        $value = $default;
        if ($this->hasComponentAttribute($attributeName)) {
            $value = $this->getValue($attributeName);
            unset($this->componentAttributes[$attributeName]);
        }
        return $value;
    }


    /**
     * @return array - an array of key string and value of the component attributes
     */
    public function toCallStackArray()
    {
        $array = array();
        foreach ($this->componentAttributes as $key => $value) {
            $array[$key] = $this->getValue($key);
        }
        $style = $this->getStyle();
        if (!empty($style)) {
            $array["style"] = $style;
        }
        return $array;
    }

    public function getComponentAttributeValue($attributeName, $default = null)
    {
        $lowerAttribute = strtolower($attributeName);
        $value = $default;
        if ($this->hasComponentAttribute($lowerAttribute)) {
            $value = $this->getValue($lowerAttribute);
        }
        return $value;
    }

    public function addStyleDeclaration($property, $value)
    {
        ArrayUtility::addIfNotSet($this->styleDeclaration, $property, $value);
    }


    public function hasStyleDeclaration($styleDeclaration)
    {
        return isset($this->styleDeclaration[$styleDeclaration]);
    }

    public function getAndRemoveStyleDeclaration($styleDeclaration)
    {
        $styleValue = $this->styleDeclaration[$styleDeclaration];
        unset($this->styleDeclaration[$styleDeclaration]);
        return $styleValue;
    }


    public function toHTMLString()
    {

        $tagAttributeString = "";

        $urlEncoding = ["href", "src", "data-src", "data-srcset"];
        foreach ($this->toHtmlArray() as $name => $value) {

            if (!empty($value)) {
                /**
                 * Following the rule 2 to encode the value
                 * https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html#rule-2-attribute-encode-before-inserting-untrusted-data-into-html-common-attributes
                 */
                $stringValue = StringUtility::toString($value);
                if (!in_array($name, $urlEncoding)) {
                    $stringValue = PluginUtility::htmlEncode($stringValue);
                }
                $tagAttributeString .= PluginUtility::htmlEncode($name) . '="' . $stringValue . '" ';
            }

        }
        return trim($tagAttributeString);


    }

    public function getComponentAttributes()
    {
        return $this->toCallStackArray();
    }

    public function removeComponentAttributeIfPresent($attributeName)
    {
        if ($this->hasComponentAttribute($attributeName)) {
            unset($this->componentAttributes[$attributeName]);
        }

    }


}
