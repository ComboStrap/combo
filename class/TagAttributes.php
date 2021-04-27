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
 *   * output the final HTML attributes at the end of the process with the function {@link TagAttributes::toHTMLAttributeString()}
 *
 * Component attributes have precedence on HTML attributes.
 *
 * @package ComboStrap
 */
class TagAttributes
{
    const ALIGN_KEY = 'align';
    /**
     * @var string the alt attribute value (known as the title for dokuwiki)
     */
    const TITLE_KEY = 'title';
    const CACHE_KEY = 'cache';
    const TYPE_KEY = "type";
    const HEIGHT_KEY = 'height';
    /**
     * Link value:
     *   * 'nolink'
     *   * 'direct': directly to the image
     *   * 'linkonly': show only a url
     *   * 'details': go to the details media viewer
     *
     * @var
     */
    const LINKING_KEY = 'linking';
    const WIDTH_KEY = 'width';
    const ID_KEY = "id";

    /**
     * buster got the same value
     * that the `rev` attribute (ie mtime)
     * We don't use rev as cache buster because Dokuwiki still thinks
     * that this is an old file and search in the attic
     * as seen in the function {@link mediaFN()}
     */
    const BUSTER_KEY = "buster";

    /**
     * The element that have an width and height
     */
    const NATURAL_SIZING_ELEMENT = [SvgImageLink::CANONICAL, RasterImageLink::CANONICAL];

    /**
     * The logical attributes that are not becoming HTML attributes
     */
    const HTML_EXCLUDED_ATTRIBUTES = [
        self::SCRIPT_KEY,
        TagAttributes::TYPE_KEY,
        TagAttributes::LINKING_KEY,
        TagAttributes::CACHE_KEY
    ];

    /**
     * The inline element
     */
    const INLINE_LOGICAL_ELEMENTS = [SvgImageLink::CANONICAL,RasterImageLink::CANONICAL];
    const SCRIPT_KEY = "script";
    const TRANSFORM = "transform";
    const FLOAT_KEY = "float";


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
     * @var string the functional tag to which the attributes applies
     * It's not an HTML tag (a div can have a flex display or a block and they don't carry this information)
     * The tag gives also context for the attributes (ie an div has no natural width while an img has)
     */
    private $tag;

    /**
     * An html that should be added after the enter tag
     * (used for instance to add metadata  such as backgrounds, illustration image for cards ,...
     * @var string
     */
    private $htmlAfterEnterTag;

    /**
     * ComponentAttributes constructor.
     * Use the static create function to instantiate this object
     * @param $tag - tag (the tag gives context for the attributes (ie an div has no natural width while an img has)
     * @param array $componentAttributes
     */
    private function __construct($componentAttributes = array(), $tag = null)
    {
        $this->tag = $tag;
        $this->componentAttributes = $componentAttributes;
    }

    /**
     * @param $match - the {@link SyntaxPlugin::handle()} match
     * @return TagAttributes
     */
    public static function createFromTagMatch($match)
    {
        $htmlAttributes = PluginUtility::getTagAttributes($match);
        $tag = PluginUtility::getTag($match);
        return self::createFromCallStackArray($htmlAttributes, $tag);
    }


    public static function createEmpty()
    {
        return new TagAttributes();
    }

    /**
     * @param $renderArray - an array of key value pair
     * @param string $logicalTag - the logical tag for which this attribute will apply
     * @return TagAttributes
     */
    public static function createFromCallStackArray($renderArray, $logicalTag = null)
    {
        $attributes = self::CallStackArrayToInternalArray($renderArray);
        return new TagAttributes($attributes, $logicalTag);
    }

    /**
     * Utility function to go from an html array to a tag array
     * Used during refactoring
     * @param array $callStackAttributes
     * @return array
     */
    private static function CallStackArrayToInternalArray(array $callStackAttributes)
    {

        $attributes = array();
        foreach ($callStackAttributes as $key => $attribute) {

            /**
             * Key are always lower
             */
            $lowerKey = strtolower($key);

            /**
             * null is not a string or a boolean
             */
            if ($attribute === null) {
                continue;
            }

            /**
             *
             * Boolean and numeric to string
             */
            if (is_bool($attribute) || is_numeric($attribute)) {
                $attributes[$lowerKey] = [$attribute => true];
                continue;
            }

            /**
             * Life is harder
             */
            if (is_string($attribute)) {

                /**
                 * false is considered as empty, that's why this code is
                 * in the is_string block
                 */
                if (empty($attribute)) {
                    continue;
                }

                $explodeArray = explode(" ", $attribute);
                $arrayValues = array();
                foreach ($explodeArray as $explodeValue) {

                    $arrayValues[$explodeValue] = true;
                }
                $attributes[$lowerKey] = $arrayValues;
                continue;
            }

            /**
             * Array
             */
            if (is_array($attribute)) {
                $attributes[$lowerKey] = $attribute;
                continue;
            }

            /**
             * Not processed
             */
            LogUtility::msg("The variable value ($attribute) of the key ($key) is not a string, a boolean, a numeric or an array and was ignored", LogUtility::LVL_MSG_ERROR, "support");


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
        /**
         * A length value may be also `fit-content`
         * we just check that if there is only number,
         * we add the pixel
         * Same as {@link is_numeric()} ?
         */
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
             * Float
             */
            Float::processFloat($this);

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
             * Background
             */
            Background::processBackgroundAttributes($this);

            /**
             * Transform
             */
            if ($this->hasComponentAttribute(self::TRANSFORM)){
                $transformValue = $this->getValueAndRemove(self::TRANSFORM);
                $this->addStyleDeclaration("transform",$transformValue);
            }


            /**
             * Create a non-sorted temporary html attributes array
             */
            $tempHtmlArray = $this->htmlAttributes;

            /**
             * copy the unknown component attributes
             */
            foreach ($this->componentAttributes as $key => $arrayValue) {
                if (!in_array($key, self::HTML_EXCLUDED_ATTRIBUTES)) {
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
        if (empty($value)) {
            LogUtility::msg("The value of the HTML attribute is empty, use the if empty function instead", LogUtility::LVL_MSG_ERROR, "support");
        }
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
     * This array is saved on the disk
     */
    public function toCallStackArray()
    {
        $array = array();
        foreach ($this->componentAttributes as $key => $value) {
            $array[$key] = StringUtility::toString($this->getValue($key));
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


    public function toHTMLAttributeString()
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

    public function toHtmlEnterTag($htmlTag)
    {

        $enterTag = "<" . $htmlTag;
        $attributeString = $this->toHTMLAttributeString();
        if (!empty($attributeString)) {
            $enterTag .= " " . $attributeString;
        }
        $enterTag .= ">";

        if (!empty($this->htmlAfterEnterTag)) {
            $enterTag .= $this->htmlAfterEnterTag;
        }
        return $enterTag;

    }

    public function getLogicalTag()
    {
        return $this->tag;
    }

    public function setTag($tag)
    {
        $this->tag = $tag;
    }

    public function removeComponentAttribute($attribute)
    {
        $lowerAtt = strtolower($attribute);
        if (isset($this->componentAttributes[$lowerAtt])) {
            unset($this->componentAttributes[$lowerAtt]);
        } else {
            LogUtility::msg("Internal Error: The component attribute ($attribute) is not present. Use the ifPresent function, if you don't want this message", LogUtility::LVL_MSG_ERROR, "support");
        }

    }

    /**
     * @param $html - an html that should be closed and added after the enter tag
     */
    public function addHtmlAfterEnterTag($html)
    {
        $this->htmlAfterEnterTag .= $html;
    }

    public function getValueAsArrayAndRemove($attributeName, $default = array())
    {
        $value = $default;
        if ($this->hasComponentAttribute($attributeName)) {
            $value = $this->componentAttributes[$attributeName];
            unset($this->componentAttributes[$attributeName]);
        }
        return $value;

    }


}
