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
use syntax_plugin_combo_follow;

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
    /**
     * @var string the alt attribute value (known as the title for dokuwiki)
     */
    const TITLE_KEY = 'title';


    const TYPE_KEY = "type";
    const ID_KEY = "id";

    /**
     * If not strict, no error is reported
     */
    const STRICT = "strict";

    /**
     * The logical attributes that are not becoming HTML attributes
     * (ie internal reserved words)
     *
     * TODO: They should be advertised by the syntax component
     */
    const RESERVED_ATTRIBUTES = [
        self::SCRIPT_KEY, // no script attribute for security reason
        TagAttributes::TYPE_KEY, // type is the component class
        MediaMarkup::LINKING_KEY, // internal to image
        FetcherAbs::CACHE_KEY, // internal also
        \syntax_plugin_combo_webcode::RENDERING_MODE_ATTRIBUTE,
        Vertical::VERTICAL_ATTRIBUTE,
        self::OPEN_TAG,
        self::HTML_BEFORE,
        self::HTML_AFTER,
        Dimension::RATIO_ATTRIBUTE,
        self::STRICT,
        FetcherSvg::REQUESTED_PRESERVE_ATTRIBUTE,
        \syntax_plugin_combo_link::CLICKABLE_ATTRIBUTE,
        LinkMarkup::PREVIEW_ATTRIBUTE,
        Skin::SKIN_ATTRIBUTE,
        ColorRgb::PRIMARY_VALUE,
        ColorRgb::SECONDARY_VALUE,
        Dimension::ZOOM_ATTRIBUTE,
        syntax_plugin_combo_follow::HANDLE_ATTRIBUTE,
        \syntax_plugin_combo_menubar::BREAKPOINT_ATTRIBUTE,
        \syntax_plugin_combo_container::CONTAINER_ATTRIBUTE,
        \syntax_plugin_combo_heading::HEADING_TEXT_ATTRIBUTE,
        self::GENERATED_ID_KEY
    ];

    /**
     * The inline element
     * We could pass the plugin object into tag attribute in place of the logical tag
     * and check if the {@link SyntaxPlugin::getPType()} is normal
     */
    const INLINE_LOGICAL_ELEMENTS = [
        FetcherSvg::CANONICAL,
        FetcherRaster::CANONICAL,
        \syntax_plugin_combo_media::TAG,
        \syntax_plugin_combo_link::TAG, // link button for instance
        \syntax_plugin_combo_button::TAG
    ];

    /**
     * Container
     * Heading is a block but not a container
     */
    const CONTAINER_LOGICAL_ELEMENTS = [
        \syntax_plugin_combo_box::TAG,
        \syntax_plugin_combo_card::TAG,
        \syntax_plugin_combo_blockquote::TAG,
    ];

    const SCRIPT_KEY = "script";
    const TRANSFORM = "transform";

    const CANONICAL = "tag";

    const CLASS_KEY = "class";
    const WIKI_ID = "wiki-id";

    /**
     * The open tag attributes
     * permit to not close the tag in {@link TagAttributes::toHtmlEnterTag()}
     *
     * It's used for instance by the {@link \syntax_plugin_combo_tooltip}
     * to advertise that it will add attribute and close it
     */
    const OPEN_TAG = "open-tag";

    /**
     * If an attribute has this value,
     * it will not be added to the output (ie {@link TagAttributes::toHtmlEnterTag()})
     * Child element can unset attribute this way
     * in order to write their own
     *
     * This is used by the {@link \syntax_plugin_combo_tooltip}
     * to advertise that the title attribute should not be set
     */
    const UN_SET = "unset";

    /**
     * When wrapping an element
     * A tag may get HTML before and after
     * Uses for instance to wrap a svg in span
     * when adding a {@link \syntax_plugin_combo_tooltip}
     */
    const HTML_BEFORE = "htmlBefore";
    const HTML_AFTER = "htmlAfter";

    /**
     * Attribute with multiple values
     */
    const MULTIPLE_VALUES_ATTRIBUTES = [self::CLASS_KEY, self::REL, Align::ALIGN_ATTRIBUTE];

    /**
     * Link relation attributes
     * https://html.spec.whatwg.org/multipage/links.html#linkTypes
     */
    const REL = "rel";

    /**
     * The default id if no one is specified
     */
    const GENERATED_ID_KEY = "generated_id";

    /**
     * The attributes that may flow into an HTML output
     * TODO: href comes from {@link \syntax_plugin_combo_brand}, it should be corrected to use {@link LinkMarkup}
     */
    const HTML_ATTRIBUTES = [
        TagAttributes::CLASS_KEY,
        StyleUtility::STYLE_ATTRIBUTE,
        TagAttributes::ID_KEY,
        TagAttributes::TITLE_KEY,
        "href"
    ];

    /**
     * Attribute that cannot be deleted
     * TODO: This is because the request object and the response object are the same. We should add the request attribute in the {@link \TagAttributes}
     */
    const PROTECTED_ATTRIBUTES = [
        TagAttributes::TYPE_KEY
    ];


    /**
     * A global static counter
     * to {@link TagAttributes::generateAndSetId()}
     */
    private static $counter = 0;


    /**
     * @var ArrayCaseInsensitive attribute that were set on a component
     */
    private ArrayCaseInsensitive $componentAttributesCaseInsensitive;

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
     * @var array - output attribute are not the parsed attributes known as componentAttribute)
     * They are created by the {@link TagAttributes::toHtmlArray()} processing mainly
     */
    private $outputAttributes = array();

    /**
     * @var array - the final html array
     */
    private $finalHtmlArray = array();

    /**
     * @var string the functional tag to which the attributes applies
     * It's not an HTML tag (a div can have a flex display or a block and they don't carry this information)
     * The tag gives also context for the attributes (ie an div has no natural width while an img has)
     */
    private $logicalTag;

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
     * @var bool - adding  the default class for the logical tag
     */
    private $defaultStyleClassShouldBeAdded = true;
    private $knownTypes;


    /**
     * ComponentAttributes constructor.
     * Use the static create function to instantiate this object
     * @param $tag - tag (the tag gives context for the attributes
     *     * an div has no natural width while an img has
     *     * this is not always the component name / syntax name (for instance the {@link \syntax_plugin_combo_codemarkdown} is another syntax
     * for a {@link \syntax_plugin_combo_code} and have therefore the same logical name)
     * @param array $componentAttributes
     */
    private function __construct(array $componentAttributes = array(), $tag = null)
    {
        $this->logicalTag = $tag;
        $this->componentAttributesCaseInsensitive = new ArrayCaseInsensitive($componentAttributes);

        /**
         * Delete null values
         * Empty string, 0 may exist
         */
        foreach ($componentAttributes as $key => $value) {
            if (is_null($value)) {
                unset($this->componentAttributesCaseInsensitive[$key]);
                continue;
            }
            if ($key === StyleUtility::STYLE_ATTRIBUTE) {
                unset($this->componentAttributesCaseInsensitive[$key]);
                $stylingProperties = StyleUtility::HtmlStyleValueToArray($value);
                foreach ($stylingProperties as $styleKey => $styleValue) {
                    $this->addStyleDeclarationIfNotSet($styleKey, $styleValue);
                }
            }
        }

    }

    /**
     * @param $match - the {@link SyntaxPlugin::handle()} match
     * @param array $defaultAttributes
     * @param array|null $knownTypes
     * @return TagAttributes
     */
    public static function createFromTagMatch($match, array $defaultAttributes = [], array $knownTypes = null): TagAttributes
    {
        $inlineHtmlAttributes = PluginUtility::getTagAttributes($match, $knownTypes);
        $tag = PluginUtility::getTag($match);
        $mergedAttributes = PluginUtility::mergeAttributes($inlineHtmlAttributes, $defaultAttributes);
        return self::createFromCallStackArray($mergedAttributes, $tag)
            ->setKnownTypes($knownTypes);
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
     * @param array|null $callStackArray - an array of key value pair
     * @param string|null $logicalTag - the logical tag for which this attribute will apply
     * @return TagAttributes
     */
    public static function createFromCallStackArray(?array $callStackArray, string $logicalTag = null): TagAttributes
    {
        if ($callStackArray === null) {
            $callStackArray = [];
        }
        if (!is_array($callStackArray)) {
            LogUtility::msg("The renderArray variable passed is not an array ($callStackArray)");
            $callStackArray = [];
        }
        return new TagAttributes($callStackArray, $logicalTag);
    }


    /**
     * For CSS a unit is mandatory (not for HTML or SVG attributes)
     * @param $value
     * @return string return a CSS property with pixel as unit if the unit is not specified
     * @throws ExceptionBadArgument
     */
    public static function toQualifiedCssValue($value): string
    {
        return ConditionalLength::createFromString($value)->toCssLength();

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
                return Dimension::WIDTH_KEY;
            case "h":
                return Dimension::HEIGHT_KEY;
            default:
                return $name;
        }
    }

    /**
     * Clone a tag attributes
     * Tag Attributes are used for request and for response
     * To avoid conflict, a function should clone it before
     * calling the final method {@link TagAttributes::toHtmlArray()}
     * or {@link TagAttributes::toHtmlEnterTag()}
     * @param TagAttributes $tagAttributes
     * @return TagAttributes
     */
    public static function createFromTagAttributes(TagAttributes $tagAttributes): TagAttributes
    {
        $newTagAttributes = new TagAttributes($tagAttributes->getComponentAttributes(), $tagAttributes->getLogicalTag());
        foreach ($tagAttributes->getStyleDeclarations() as $property => $value) {
            $newTagAttributes->addStyleDeclarationIfNotSet($property, $value);
        }
        return $newTagAttributes;
    }

    public static function isEmptyValue($attributeValue): bool
    {
        return empty($attributeValue) && !is_bool($attributeValue);
    }

    public function addClassName($className): TagAttributes
    {

        $this->addComponentAttributeValue(self::CLASS_KEY, $className);
        return $this;

    }

    public function getClass()
    {
        return $this->getValue(self::CLASS_KEY);
    }

    public function getStyle(): ?string
    {
        if (sizeof($this->styleDeclaration) != 0) {
            return PluginUtility::array2InlineStyle($this->styleDeclaration);
        } else {
            /**
             * null is needed to see if the attribute was set or not
             * because an attribute may have the empty string
             * Example: the wiki id of the root namespace
             */
            return null;
        }

    }

    public function getStyleDeclarations(): array
    {
        return $this->styleDeclaration;

    }

    /**
     * Add an attribute with its value if the value is not empty
     * @param $attributeName
     * @param $attributeValue
     * @return TagAttributes
     */
    public function addComponentAttributeValue($attributeName, $attributeValue): TagAttributes
    {

        if (TagAttributes::isEmptyValue($attributeValue)) {
            LogUtility::msg("The value of the attribute ($attributeName) is empty. Use the nonEmpty function instead if it's the wanted behavior", LogUtility::LVL_MSG_WARNING, "support");
        }

        $attLower = strtolower($attributeName);
        $actual = null;
        if ($this->hasComponentAttribute($attLower)) {
            $actual = $this->componentAttributesCaseInsensitive[$attLower];
        }

        /**
         * Type of data: list (class) or atomic (id)
         */
        if (in_array($attributeName, self::MULTIPLE_VALUES_ATTRIBUTES)) {
            $this->componentAttributesCaseInsensitive[$attLower] = Html::mergeClassNames($attributeValue, $actual);
        } else {
            if (!empty($actual)) {
                LogUtility::msg("The attribute ($attLower) stores an unique value and has already a value ($actual). to set another value ($attributeValue), use the `set` operation instead", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
            }
            $this->componentAttributesCaseInsensitive[$attLower] = $attributeValue;
        }

        return $this;

    }

    public function setComponentAttributeValue($attributeName, $attributeValue)
    {
        $attLower = strtolower($attributeName);
        $actualValue = $this->getValue($attributeName);
        if ($actualValue === null || $actualValue !== TagAttributes::UN_SET) {
            $this->componentAttributesCaseInsensitive[$attLower] = $attributeValue;
        }
    }

    public function addComponentAttributeValueIfNotEmpty($attributeName, $attributeValue)
    {
        if (!empty($attributeValue)) {
            $this->addComponentAttributeValue($attributeName, $attributeValue);
        }
    }

    public function hasComponentAttribute($attributeName): bool
    {
        $isset = isset($this->componentAttributesCaseInsensitive[$attributeName]);
        if ($isset === false && $this->knownTypes === null) {
            /**
             * Edge effect
             * if this is a boolean value and the first value, it may be stored in the type
             */
            if (isset($this->componentAttributesCaseInsensitive[TagAttributes::TYPE_KEY])) {
                if ($attributeName == $this->componentAttributesCaseInsensitive[TagAttributes::TYPE_KEY]) {
                    LogUtility::warning("Internal Error: The tag ({$this->getLogicalTag()}) has a the boolean attribute ($attributeName) defined as a type. The possible types should be defined for this tag as it's deprecated.");
                    return true;
                }
            }
        }
        return $isset;
    }

    /**
     * To an HTML array in the form
     *   class => 'value1 value2',
     *   att => 'value1 value 2'
     * For historic reason, data passed between the handle and the render
     * can still be in this format
     */
    public function toHtmlArray(): array
    {
        if ($this->componentToHtmlAttributeProcessingWasDone) {
            LogUtility::msg("This tag attribute ($this) was already finalized. You cannot finalized it twice", LogUtility::LVL_MSG_ERROR);
            return $this->finalHtmlArray;
        }

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
        Display::processDisplay($this);
        Vertical::processVertical($this);
        Horizontal::processHorizontal($this);

        /**
         * Block processing
         *
         * Float, align, spacing
         */
        FloatAttribute::processFloat($this);
        Align::processAlignAttributes($this);
        Spacing::processSpacingAttributes($this);
        Hero::processHero($this);
        Opacity::processOpacityAttribute($this);
        Background::processBackgroundAttributes($this);
        Shadow::process($this);

        /**
         * Process text attributes
         */
        LineSpacing::processLineSpacingAttributes($this);
        TextAlign::processTextAlign($this);
        Boldness::processBoldnessAttribute($this);
        FontSize::processFontSizeAttribute($this);
        TextColor::processTextColorAttribute($this);
        Underline::processUnderlineAttribute($this);

        /**
         * Process the style attributes if any
         */
        PluginUtility::processStyle($this);
        Toggle::processToggle($this);


        /**
         * Skin Attribute
         */
        Skin::processSkinAttribute($this);

        /**
         * Lang
         */
        Lang::processLangAttribute($this);

        /**
         * Transform
         */
        if ($this->hasComponentAttribute(self::TRANSFORM)) {
            $transformValue = $this->getValueAndRemove(self::TRANSFORM);
            $this->addStyleDeclarationIfNotSet("transform", $transformValue);
        }

        /**
         * Tooltip
         */
        Tooltip::processTooltip($this);

        /**
         * Add the type class used for CSS styling
         */
        StyleUtility::addStylingClass($this);

        /**
         * Add the style has html attribute
         * before processing
         */
        $this->addOutputAttributeValueIfNotEmpty("style", $this->getStyle());

        /**
         * Create a non-sorted temporary html attributes array
         */
        $tempHtmlArray = $this->outputAttributes;

        /**
         * copy the unknown component attributes
         */
        $originalArray = $this->componentAttributesCaseInsensitive->getOriginalArray();
        foreach ($originalArray as $key => $value) {

            // Null Value, not needed
            if (is_null($value)) {
                continue;
            }

            // No overwrite
            if (isset($tempHtmlArray[$key])) {
                continue;
            }

            // We only add the common HTML attribute
            if (in_array($key, self::HTML_ATTRIBUTES)) {
                $tempHtmlArray[$key] = $value;
            } else {

                if (!in_array($key, [
                    TagAttributes::TYPE_KEY,
                    TagAttributes::GENERATED_ID_KEY,
                    TagAttributes::OPEN_TAG
                ])) {

                    /**
                     * Note for developers:
                     *    * If it must be in the HTML output, you should add it via the output attribute methods during processing.
                     *    * Otherwise you need for now to get and delete it
                     */
                    $message = "The component attribute ($key) is unknown or does not apply ";
                    if (isset($this->logicalTag)) {
                        $message = "$message for the component ({$this->logicalTag}).";
                    }
                    LogUtility::warning($message);

                }
            }

        }


        /**
         * Sort by attribute
         * https://datacadamia.com/web/html/attribute#order
         * https://codeguide.co/#html-attribute-order
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
                $searchPattern = "^$pattern$";
                if (preg_match("/$searchPattern/", $name)) {
                    unset($tempHtmlArray[$name]);
                    if ($type === $once) {
                        $sortedArray[$name] = $value;
                        continue 2;
                    } else {
                        $multipleValues[$name] = $value;
                    }
                }
            }
            if (!empty($multipleValues)) {
                ksort($multipleValues);
                $sortedArray = array_merge($sortedArray, $multipleValues);
                $multipleValues = [];
            }
        }
        foreach ($tempHtmlArray as $name => $value) {

            if (!is_null($value)) {
                /**
                 *
                 * Don't add a filter on the empty values
                 *
                 * The value of an HTML attribute may be empty
                 * Example the wiki id of the root namespace
                 *
                 * By default, {@link TagAttributes::addOutputAttributeValue()}
                 * will not accept any value, it must be implicitly said with the
                 * {@link TagAttributes::addOutputAttributeValue()}
                 *
                 */
                $sortedArray[$name] = $value;
            }

        }
        $this->finalHtmlArray = $sortedArray;

        /**
         * To Html attribute encoding
         */
        $this->finalHtmlArray = $this->encodeToHtmlValue($this->finalHtmlArray);

        return $this->finalHtmlArray;

    }

    /**
     *
     *
     * @param $key
     * @param $value
     * @return TagAttributes
     */
    public function addOutputAttributeValue($key, $value): TagAttributes
    {
        if (blank($value)) {
            LogUtility::error("The value of the output attribute is blank for the key ($key) - Tag ($this->logicalTag). Use the empty function if the value can be empty");
        }
        $this->outputAttributes[$key] = $value;
        return $this;
    }


    public function addOutputAttributeValueIfNotEmpty($key, $value)
    {
        if (!empty($value)) {
            $this->addOutputAttributeValue($key, $value);
        }
    }

    /**
     * @param $attributeName
     * @param null $default
     * @return string|array|null a HTML value in the form 'value1 value2...'
     */
    public function getValue($attributeName, $default = null)
    {
        $attributeName = strtolower($attributeName);
        if ($this->hasComponentAttribute($attributeName)) {
            return $this->componentAttributesCaseInsensitive[$attributeName];
        } else {
            return $default;
        }
    }


    /**
     * Get the value and remove it from the attributes
     * @param $attributeName
     * @param $default
     * @return string|array|null
     *
     * TODO: we should create a new response object and not deleting data from the request
     */
    public function getValueAndRemove($attributeName, $default = null)
    {
        $attributeName = strtolower($attributeName);
        $value = $default;
        if ($this->hasComponentAttribute($attributeName)) {
            $value = $this->getValue($attributeName);

            if (!in_array($attributeName, self::PROTECTED_ATTRIBUTES)) {
                /**
                 * Don't remove for instance the `type`
                 * because it may be used elsewhere
                 */
                unset($this->componentAttributesCaseInsensitive[$attributeName]);
            }

        }
        return $value;
    }


    /**
     * @return array - an array of key string and value of the component attributes
     * This array is saved on the disk
     */
    public function toCallStackArray(): array
    {

        $generatedId = $this->getValue(TagAttributes::GENERATED_ID_KEY);
        if ($generatedId === null) {

            $componentName = $this->logicalTag;
            if ($componentName === null) {
                $componentName = "unknown-component";
            }
            $id = IdManager::getOrCreate()->generateNewHtmlIdForComponent($componentName);
            $this->addComponentAttributeValue(TagAttributes::GENERATED_ID_KEY, $id);

        }

        $array = array();
        $originalArray = $this->componentAttributesCaseInsensitive->getOriginalArray();
        foreach ($originalArray as $key => $value) {
            /**
             * Only null value are not passed
             * width can be zero, wiki-id can be the empty string (ie root namespace)
             *
             * Value can be array, number, string
             */
            if (!is_null($value)) {
                $array[$key] = $value;
            }
        }
        /**
         * html attribute may also be in the callstack
         */
        foreach ($this->outputAttributes as $key => $value) {
            $array[$key] = $value;
        }
        $style = $this->getStyle();
        if ($style != null) {
            $array["style"] = $style;
        }

        return $array;
    }

    public
    function getComponentAttributeValue($attributeName, $default = null)
    {
        $lowerAttribute = strtolower($attributeName);
        $value = $default;
        if ($this->hasComponentAttribute($lowerAttribute)) {
            $value = $this->getValue($lowerAttribute);
        }
        return $value;
    }

    public
    function addStyleDeclarationIfNotSet($property, $value)
    {
        ArrayUtility::addIfNotSet($this->styleDeclaration, $property, $value);
    }


    public
    function hasStyleDeclaration($styleDeclaration): bool
    {
        return isset($this->styleDeclaration[$styleDeclaration]);
    }

    public
    function getAndRemoveStyleDeclaration($styleDeclaration)
    {
        $styleValue = $this->styleDeclaration[$styleDeclaration];
        unset($this->styleDeclaration[$styleDeclaration]);
        return $styleValue;
    }


    public
    function toHTMLAttributeString(): string
    {

        $tagAttributeString = "";

        $htmlArray = $this->toHtmlArray();
        foreach ($htmlArray as $name => $value) {

            /**
             * Empty value are authorized
             * null are just not set
             */
            if (!is_null($value)) {

                /**
                 * Unset attribute should not be added
                 */
                if ($value === TagAttributes::UN_SET) {
                    continue;
                }

                /**
                 * The condition is important
                 * because we may pass the javascript character `\n` in a `srcdoc` for javascript
                 * and the {@link StringUtility::toString()} will transform it as `\\n`
                 * making it unusable
                 */
                if (!is_string($value)) {
                    $stringValue = StringUtility::toString($value);
                } else {
                    $stringValue = $value;
                }


                $tagAttributeString .= $name . '="' . $stringValue . '" ';
            }

        }
        return trim($tagAttributeString);


    }

    public
    function getComponentAttributes(): array
    {
        return $this->toCallStackArray();
    }

    public
    function removeComponentAttributeIfPresent($attributeName)
    {
        if ($this->hasComponentAttribute($attributeName)) {
            unset($this->componentAttributesCaseInsensitive[$attributeName]);
        }

    }

    public
    function toHtmlEnterTag($htmlTag): string
    {

        $enterTag = "<" . trim($htmlTag);
        $attributeString = $this->toHTMLAttributeString();
        if (!empty($attributeString)) {
            $enterTag .= " " . $attributeString;
        }
        /**
         * Is it an open tag ?
         */
        if (!$this->getValue(self::OPEN_TAG, false)) {

            $enterTag .= ">";

            /**
             * Do we have html after the tag is closed
             */
            if (!empty($this->htmlAfterEnterTag)) {
                $enterTag .= DOKU_LF . $this->htmlAfterEnterTag;
            }

        }


        return $enterTag;

    }

    public
    function toHtmlEmptyTag($htmlTag): string
    {

        $enterTag = "<" . $htmlTag;
        $attributeString = $this->toHTMLAttributeString();
        if (!empty($attributeString)) {
            $enterTag .= " " . $attributeString;
        }
        return $enterTag . "/>";

    }

    public function getLogicalTag()
    {
        return $this->logicalTag;
    }

    public
    function setLogicalTag($tag): TagAttributes
    {
        $this->logicalTag = $tag;
        return $this;
    }

    /**
     * @param $attribute
     * @return mixed|null - the value deleted / null if it does not exist
     */
    public function removeComponentAttribute($attribute)
    {
        $lowerAtt = strtolower($attribute);
        if (isset($this->componentAttributesCaseInsensitive[$lowerAtt])) {
            $value = $this->componentAttributesCaseInsensitive[$lowerAtt];
            unset($this->componentAttributesCaseInsensitive[$lowerAtt]);
            return $value;
        } else {
            /**
             * Edge case, this is the first boolean attribute
             * and may has been categorized as the type
             */
            if (!$this->getType() == $lowerAtt) {
                LogUtility::msg("Internal Error: The component attribute ($attribute) is not present. Use the ifPresent function, if you don't want this message", LogUtility::LVL_MSG_ERROR);
            }
            return null;


        }

    }

    /**
     * @param $html - an html that should be closed and added after the enter tag
     */
    public
    function addHtmlAfterEnterTag($html)
    {
        $this->htmlAfterEnterTag = $html . $this->htmlAfterEnterTag;
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
    public
    function setMime($mime)
    {
        $this->mime = $mime;
    }

    /**
     * @return string - the mime of the request
     */
    public
    function getMime()
    {
        return $this->mime;
    }

    public
    function getType()
    {
        return $this->getValue(self::TYPE_KEY);
    }

    /**
     * @param $attributeName
     * @return ConditionalValue
     */
    public
    function getConditionalValueAndRemove($attributeName)
    {
        $value = $this->getConditionalValueAndRemove($attributeName);
        return new ConditionalValue($value);

    }

    /**
     * @param $attributeName
     * @param null $default
     * @return null|string[] - an array of values
     * @throws ExceptionBadArgument
     */
    public
    function getValuesAndRemove($attributeName, $default = null): array
    {

        $trim = $this->getValues($attributeName, $default);
        $this->removeAttributeIfPresent($attributeName);
        return $trim;


    }

    public
    function setType($type): TagAttributes
    {
        $this->setComponentAttributeValue(TagAttributes::TYPE_KEY, $type);
        return $this;
    }

    /**
     * Merging will add the values, no replace or overwrite
     * @param $callStackArray
     */
    public
    function mergeWithCallStackArray($callStackArray)
    {
        foreach ($callStackArray as $key => $value) {

            if ($this->hasComponentAttribute($key)) {
                $isMultipleAttributeValue = in_array($key, self::MULTIPLE_VALUES_ATTRIBUTES);
                if ($isMultipleAttributeValue) {
                    $this->addComponentAttributeValue($key, $value);
                }
            } else {
                $this->setComponentAttributeValue($key, $value);
            }
        }

    }

    /**
     * @param $string
     * @return TagAttributes
     */
    public
    function removeAttributeIfPresent($string): TagAttributes
    {
        $this->removeComponentAttributeIfPresent($string);
        $this->removeHTMLAttributeIfPresent($string);
        return $this;

    }

    public function removeHTMLAttributeIfPresent($string)
    {
        $lowerAtt = strtolower($string);
        if (isset($this->outputAttributes[$lowerAtt])) {
            unset($this->outputAttributes[$lowerAtt]);
        }
    }

    public
    function getValueAndRemoveIfPresent($attribute, $default = null)
    {
        $value = $this->getValue($attribute, $default);
        $this->removeAttributeIfPresent($attribute);
        return $value;
    }

    public
    function generateAndSetId()
    {
        self::$counter += 1;
        $id = self::$counter;
        $logicalTag = $this->getLogicalTag();
        if (!empty($logicalTag)) {
            $id = $this->logicalTag . $id;
        }
        $this->setComponentAttributeValue("id", $id);
        return $id;
    }

    /**
     *
     * @param $markiTag
     * @return string - the marki tag made of logical attribute
     * There is no processing to transform it to an HTML tag
     */
    public
    function toMarkiEnterTag($markiTag)
    {
        $enterTag = "<" . $markiTag;

        $attributeString = "";
        foreach ($this->getComponentAttributes() as $key => $value) {
            $attributeString .= "$key=\"$value\" ";
        }
        $attributeString = trim($attributeString);

        if (!empty($attributeString)) {
            $enterTag .= " " . $attributeString;
        }
        $enterTag .= ">";
        return $enterTag;

    }

    /**
     * @param string $key add an html attribute with the empty string
     */
    public
    function addEmptyOutputAttributeValue($key)
    {

        $this->outputAttributes[$key] = '';
        return $this;

    }

    public
    function addEmptyComponentAttributeValue($attribute)
    {
        $this->componentAttributesCaseInsensitive[$attribute] = "";
    }

    /**
     * @param $attribute
     * @param null $default
     * @return mixed
     */
    public
    function getBooleanValueAndRemoveIfPresent($attribute, $default = null)
    {
        $value = $this->getBooleanValue($attribute, $default);
        $this->removeAttributeIfPresent($attribute);
        return $value;
    }

    public
    function getBooleanValue($attribute, $default = null)
    {
        $value = $this->getValue($attribute);
        if ($value !== null) {
            return DataType::toBoolean($value);
        }
        return $default;
    }

    public function hasAttribute($attribute): bool
    {
        $hasAttribute = $this->hasComponentAttribute($attribute);
        if ($hasAttribute === true) {
            return true;
        } else {
            return $this->hasHtmlAttribute($attribute);
        }
    }

    function hasHtmlAttribute($attribute): bool
    {
        return isset($this->outputAttributes[$attribute]);
    }

    /**
     * Encoding should happen always to the target format output.
     * ie HTML
     *
     * If it's user or not data.
     *
     * Sanitizing is completely useless. We follow the same principal than SQL parameters
     *
     * We  follows the rule 2 to encode the unknown value
     * We encode the component attribute to the target output (ie HTML)
     *
     * @param array $arrayToEscape
     * @param null $subKey
     *
     *
     *
     *
     * https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html#rule-2-attribute-encode-before-inserting-untrusted-data-into-html-common-attributes
     *
     * @return array
     */
    private
    function encodeToHtmlValue(array $arrayToEscape, $subKey = null): array
    {

        $returnedArray = [];
        foreach ($arrayToEscape as $name => $value) {

            $encodedName = Html::encode($name);

            /**
             * Boolean does not need to be encoded
             */
            if (is_bool($value)) {
                if ($subKey == null) {
                    $returnedArray[$encodedName] = $value;
                } else {
                    $returnedArray[$subKey][$encodedName] = $value;
                }
                continue;
            }

            /**
             *
             * Browser bug in a srcset
             *
             * In the HTML attribute srcset (not in the img src), if we set,
             * ```
             * http://nico.lan/_media/docs/metadata/metadata_manager.png?w=355&amp;h=176&amp;tseed=1636624852&amp;tok=af396a 355w
             * ```
             * the request is encoded ***by the browser**** one more time and the server gets:
             *   * `&amp;&amp;h  =   176`
             *   * php create therefore the property
             *      * `&amp;h  =   176`
             *      * and note `h = 176`
             */
            $encodeValue = true;
            if ($encodedName === "srcset" && !PluginUtility::isTest()) {
                /**
                 * Our test xhtml processor does not support non ampersand encoded character
                 */
                $encodeValue = false;
            }
            if ($encodeValue) {
                $value = Html::encode($value);
            }
            if ($subKey == null) {
                $returnedArray[$encodedName] = $value;
            } else {
                $returnedArray[$subKey][$encodedName] = $value;
            }

        }
        return $returnedArray;

    }

    public function __toString()
    {
        return "TagAttributes";
    }

    /**
     * @throws ExceptionCompile
     */
    public function getValueAsInteger(string $WIDTH_KEY, ?int $default = null): ?int
    {
        $value = $this->getValue($WIDTH_KEY, $default);
        if ($value === null) {
            return null;
        }
        return DataType::toInteger($value);
    }

    public function hasClass(string $string): bool
    {
        return strpos($this->getClass(), $string) !== false;
    }

    public function getDefaultStyleClassShouldBeAdded(): bool
    {
        return $this->defaultStyleClassShouldBeAdded;
    }

    public function setDefaultStyleClassShouldBeAdded(bool $bool): TagAttributes
    {
        $this->defaultStyleClassShouldBeAdded = $bool;
        return $this;
    }

    public function getIdOrDefault()
    {
        $id = $this->getValue(TagAttributes::ID_KEY);
        if ($id !== null) {
            return $id;
        }
        return $this->getValue(TagAttributes::GENERATED_ID_KEY);
    }

    public function setKnownTypes(?array $knownTypes): TagAttributes
    {
        $this->knownTypes = $knownTypes;
        return $this;
    }

    public function removeType(): TagAttributes
    {
        $this->removeAttributeIfPresent(self::TYPE_KEY);
        return $this;
    }

    /**
     * @param $attributeName
     * @param array|null $default
     * @return string[]
     * @throws ExceptionBadArgument
     */
    public function getValues($attributeName, ?array $default = null): ?array
    {
        /**
         * Replace all suite of space that have more than 2 characters
         */
        $value = $this->getValue($attributeName);
        if ($value === null) {
            return $default;
        }
        if (!is_string($value)) {
            throw new ExceptionBadArgument("The attribute ($attributeName) does not contain a string, we can't return multiple values");
        }
        $value = preg_replace("/\s{2,}/", " ", trim($value));
        return explode(" ", $value);

    }

    public function getComponentAttributeValueAndRemoveIfPresent(string $attribute, $default = null)
    {
        $value = $this->getComponentAttributeValue($attribute, $default);
        $this->removeComponentAttributeIfPresent($attribute);
        return $value;
    }

    public function toUrl(): Url
    {
        $url = Url::createEmpty();
        foreach ($this->componentAttributesCaseInsensitive as $key => $value) {
            $url->addQueryParameter($key, $value);
        }
        return $url;
    }

    public function hasComponentAttributeAndRemove(string $key): bool
    {
        $hasAttribute = $this->hasComponentAttribute($key);
        if ($hasAttribute) {
            $this->removeComponentAttribute($key);
        }
        return $hasAttribute;
    }


}
