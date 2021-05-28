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
     * The logical attributes that:
     *   * are not becoming HTML attributes
     *   * are never deleted
     * (ie internal reserved words)
     */
    const RESERVED_ATTRIBUTES = [
        self::SCRIPT_KEY, // no script attribute for security reason
        TagAttributes::TYPE_KEY, // type is the component class
        TagAttributes::LINKING_KEY, // internal to image
        CacheMedia::CACHE_KEY, // internal also
    ];

    /**
     * The inline element
     */
    const INLINE_LOGICAL_ELEMENTS = [SvgImageLink::CANONICAL, RasterImageLink::CANONICAL];
    const SCRIPT_KEY = "script";
    const TRANSFORM = "transform";

    const CANONICAL = "tag";
    const DISPLAY = "display";
    const CLASS_KEY = "class";


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
     * Use to make the difference between
     * an HTTP call for a media (ie SVG) vs an HTTP call for a page (HTML)
     */
    const TEXT_HTML_MIME = "text/html";
    private $mime = TagAttributes::TEXT_HTML_MIME;

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


    public static function createEmpty($logicalTag = "")
    {
        if ($logicalTag !== "") {
            return new TagAttributes([], $logicalTag);
        } else {
            return new TagAttributes();
        }
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
            $attributes[$lowerKey] = $attribute;

        }
        return $attributes;
    }

    /**
     * For CSS a unit is mandatory (not for HTML or SVG attributes)
     * @param $value
     * @return string return a CSS property with pixel as unit if the unit is not specified
     */
    public static function toQualifiedCssValue($value)
    {
        /**
         * A length value may be also `fit-content`
         * we just check that if there is only number,
         * we add the pixel
         * Same as {@link is_numeric()} ?
         */
        if (is_numeric($value)) {
            return $value . "px";
        } else {
            return $value;
        }

    }

    /**
     * Function used to normalize the attribute name to the combostrap attribute name
     * @param $name
     * @return mixed|string
     */
    public static function AttributeNameFromDokuwikiToCombo($name)
    {
        switch ($name) {
            case "w":
                return TagAttributes::WIDTH_KEY;
            case "h":
                return TagAttributes::HEIGHT_KEY;
            default:
                return $name;
        }
    }

    public function addClassName($className)
    {

        $this->addComponentAttributeValue(self::CLASS_KEY, $className);

    }

    public function getClass()
    {
        return $this->getValue(self::CLASS_KEY);
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
        if ($this->hasComponentAttribute($attLower)) {
            $actual = $this->componentAttributes[$attLower];
        }

        if ($attributeName === "class") {
            if (!is_string($attributeValue)) {
                LogUtility::msg("The value ($attributeValue) for the `class` attribute is not a string", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
            }
            /**
             * It may be in the form "value1 value2"
             */
            $newValues = StringUtility::explodeAndTrim($attributeValue, " ");
            if (!empty($actual)) {
                $actualValues = StringUtility::explodeAndTrim($actual, " ");
            } else {
                $actualValues = [];
            }
            $newValues = PluginUtility::mergeAttributes($newValues, $actualValues);
            $this->componentAttributes[$attLower] = implode(" ", $newValues);
        } else {
            if (!empty($actual)) {
                LogUtility::msg("The attribute ($attLower) has already a value ($actual). Adding another value ($attributeValue) is not yet implemented. Use the set operation instead", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
            }
            $this->componentAttributes[$attLower] = $attributeValue;
        }


    }

    public function setComponentAttributeValue($attributeName, $attributeValue)
    {
        $attLower = strtolower($attributeName);
        $this->componentAttributes[$attLower] = $attributeValue;
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
             * Width and height
             */
            Dimension::processWidthAndHeight($this);

            /**
             * Process animation (onHover, onView)
             */
            Hover::processOnHover($this);
            Animation::processOnView($this);


            /**
             * Position and Stickiness
             */
            Position::processStickiness($this);
            Position::processPosition($this);

            /**
             * Float
             */
            FloatAttribute::processFloat($this);

            /**
             * Process the attributes that have an effect on the class
             */
            Spacing::processSpacingAttributes($this);
            Align::processAlignAttributes($this);

            /**
             * Process text attributes
             */
            LineSpacing::processLineSpacingAttributes($this);
            TextAlign::processTextAlign($this);

            /**
             * Process the style attributes if any
             */
            PluginUtility::processStyle($this);
            PluginUtility::processCollapse($this);

            /**
             * Background
             */
            Opacity::processOpacityAttribute($this);
            Background::processBackgroundAttributes($this);


            /**
             * Skin Attribute
             */
            Skin::processSkinAttribute($this);

            /**
             * Transform
             */
            if ($this->hasComponentAttribute(self::TRANSFORM)) {
                $transformValue = $this->getValueAndRemove(self::TRANSFORM);
                $this->addStyleDeclaration("transform", $transformValue);
            }

            /**
             * Add the type class used for CSS styling
             */
            StyleUtility::addStylingClass($this);

            /**
             * Create a non-sorted temporary html attributes array
             */
            $tempHtmlArray = $this->htmlAttributes;

            /**
             * copy the unknown component attributes
             */
            foreach ($this->componentAttributes as $key => $value) {

                // Null Value, not needed
                if ($value == null) {
                    continue;
                }

                // No overwrite
                if (isset($tempHtmlArray[$key])) {
                    continue;
                }

                // Reserved attribute
                if (!in_array($key, self::RESERVED_ATTRIBUTES)) {
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
        $attributeName = strtolower($attributeName);
        if ($this->hasComponentAttribute($attributeName)) {
            return $this->componentAttributes[$attributeName];
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
     * @return string|array|null
     */
    public function getValueAndRemove($attributeName, $default = null)
    {
        $attributeName = strtolower($attributeName);
        $value = $default;
        if ($this->hasComponentAttribute($attributeName)) {
            $value = $this->getValue($attributeName);

            if (!in_array($attributeName, self::RESERVED_ATTRIBUTES)) {
                /**
                 * Don't remove for instance the `type`
                 * because it may be used elsewhere
                 */
                unset($this->componentAttributes[$attributeName]);
            } else {
                LogUtility::msg("Internal: The attribute $attributeName is a reserved word and cannot be removed. Use the get function instead", LogUtility::LVL_MSG_WARNING, "support");
            }
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
            if (!empty($value)) {
                $array[$key] = StringUtility::toString($value);
            }
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
            $enterTag .= DOKU_LF . $this->htmlAfterEnterTag;
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

    /**
     * The mime of the HTTP request
     * This is not the good place but yeah,
     * this class has become the context class
     *
     * Mime make the difference for a svg to know if it's required as external resource (ie SVG)
     * or as included in HTML page
     * @param $mime
     */
    public function setMime($mime)
    {
        $this->mime = $mime;
    }

    /**
     * @return string - the mime of the request
     */
    public function getMime()
    {
        return $this->mime;
    }

    public function getType()
    {
        return $this->getValue(self::TYPE_KEY);
    }

    /**
     * @param $attributeName
     * @return ConditionalValue
     */
    public function getConditionalValueAndRemove($attributeName)
    {
        $value = $this->getConditionalValueAndRemove($attributeName);
        return new ConditionalValue($value);

    }

    public function getValuesAndRemove($attributeName)
    {

        /**
         * Trim
         */
        $trim = trim($this->getValueAndRemove($attributeName));

        /**
         * Replace all suite of space that have more than 2 characters
         */
        $value = preg_replace("/\s{2,}/", " ", $trim);
        return explode(" ", $value);

    }


}
