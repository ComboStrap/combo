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
 * An helper to create manipulate component attributes
 * It checks the uniqueness of the value for an attribute
 * @package ComboStrap
 */
class TagAttributes
{

    /**
     * @var array of attribute name that contains an array of unique value
     */
    private $attributes;
    private $styleDeclaration = array();

    /**
     * ComponentAttributes constructor.
     * Use the static create function to instantiate this object
     * @param array $attributes
     */
    private function __construct($attributes = array())
    {
        $this->attributes = $attributes;
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
    public static function createFromArray($array)
    {
        return new TagAttributes($array);
    }

    public static function createEmpty()
    {
        return new TagAttributes(array());
    }

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
            $explodeArray = explode(" ", $attribute);
            $arrayValues = array();
            foreach ($explodeArray as $explodeValue){
                $arrayValues[$explodeValue]=true;
            }
            $attributes[$key] = $arrayValues;
        }
        return $attributes;
    }

    public function addClassName($className)
    {

        $this->addAttributeValue('class', $className);

    }

    public function getClass()
    {
        return $this->getXmlAttributeValue('class');
    }

    public function addAttributeValue($attributeName, $attributeValue)
    {
        $attLower = strtolower($attributeName);
        if (!$this->hasAttribute($attLower)) {
            $this->attributes[$attLower] = array();
        }

        /**
         * It may be in the form "value1 value2"
         */
        $values = StringUtility::explodeAndTrim($attributeValue," ");
        foreach ($values as $value) {
            $this->attributes[$attLower][$value] = true;
        }
    }

    public function hasAttribute($attributeName)
    {
        $lowerAtt = strtolower($attributeName);
        return isset($this->attributes[$lowerAtt]);
    }

    /**
     * To an HTML array in the form
     *   class => 'value1 value2',
     *   att => 'value1 value 2'
     * For historic reason, data passed between the handle and the render
     * can still be in this format
     */
    public function toHtmlArrayWithProcessing()
    {

        $htmlArray = $this->toCallStackArray();
        PluginUtility::array2HTMLAttributesAsArray($htmlArray);

        return $htmlArray;

    }

    /**
     * @param $attributeName
     * @return string|null a HTML value in the form 'value1 value2...'
     */
    public function getXmlAttributeValue($attributeName)
    {
        if ($this->hasAttribute($attributeName)) {
            $keys = array_keys($this->attributes[$attributeName]);
            return implode(" ", $keys);
        } else {
            return null;
        }
    }

    /**
     * @return array - the storage format returned from the {@link SyntaxPlugin::handle()}  method
     */
    public function toInternalArray()
    {
        return $this->attributes;
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
        if ($this->hasAttribute($attributeName)) {
            $value = $this->getXmlAttributeValue($attributeName);
            unset($this->attributes[$attributeName]);
        }
        return $value;
    }



    /**
     * @return array
     */
    public function toCallStackArray()
    {
        $array = array();
        foreach ($this->attributes as $key => $value) {
            $array[$key] = $this->getXmlAttributeValue($key);
        }
        return $array;
    }

    public function getValue($attributeName, $default = null)
    {
        $lowerAttribute = strtolower($attributeName);
        $value = $default;
        if ($this->hasAttribute($lowerAttribute)) {
            $value = $this->getXmlAttributeValue($lowerAttribute);
        }
        return $value;
    }

    public function addStyleDeclaration($property, $value)
    {
        $this->styleDeclaration[$property]=$value;
    }

    public function process()
    {
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

    }


}
