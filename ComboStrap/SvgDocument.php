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


use DOMAttr;
use DOMElement;

require_once(__DIR__ . '/XmlDocument.php');
require_once(__DIR__ . '/Unit.php');

class SvgDocument extends XmlDocument
{


    const CANONICAL = "svg";

    /**
     * Namespace (used to query with xpath only the svg node)
     */
    const SVG_NAMESPACE_PREFIX = "svg";
    const CONF_SVG_OPTIMIZATION_ENABLE = "svgOptimizationEnable";

    /**
     * Optimization Configuration
     */
    const CONF_OPTIMIZATION_NAMESPACES_TO_KEEP = "svgOptimizationNamespacesToKeep";
    const CONF_OPTIMIZATION_ATTRIBUTES_TO_DELETE = "svgOptimizationAttributesToDelete";
    const CONF_OPTIMIZATION_ELEMENTS_TO_DELETE_IF_EMPTY = "svgOptimizationElementsToDeleteIfEmpty";
    const CONF_OPTIMIZATION_ELEMENTS_TO_DELETE = "svgOptimizationElementsToDelete";

    /**
     * The namespace of the editors
     * https://github.com/svg/svgo/blob/master/plugins/_collections.js#L1841
     */
    const EDITOR_NAMESPACE = [
        'http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd',
        'http://inkscape.sourceforge.net/DTD/sodipodi-0.dtd',
        'http://www.inkscape.org/namespaces/inkscape',
        'http://www.bohemiancoding.com/sketch/ns',
        'http://ns.adobe.com/AdobeIllustrator/10.0/',
        'http://ns.adobe.com/Graphs/1.0/',
        'http://ns.adobe.com/AdobeSVGViewerExtensions/3.0/',
        'http://ns.adobe.com/Variables/1.0/',
        'http://ns.adobe.com/SaveForWeb/1.0/',
        'http://ns.adobe.com/Extensibility/1.0/',
        'http://ns.adobe.com/Flows/1.0/',
        'http://ns.adobe.com/ImageReplacement/1.0/',
        'http://ns.adobe.com/GenericCustomNamespace/1.0/',
        'http://ns.adobe.com/XPath/1.0/',
        'http://schemas.microsoft.com/visio/2003/SVGExtensions/',
        'http://taptrix.com/vectorillustrator/svg_extensions',
        'http://www.figma.com/figma/ns',
        'http://purl.org/dc/elements/1.1/',
        'http://creativecommons.org/ns#',
        'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
        'http://www.serif.com/',
        'http://www.vector.evaxdesign.sk',
    ];

    /**
     * Default SVG values
     * https://github.com/svg/svgo/blob/master/plugins/_collections.js#L1579
     * The key are exact (not lowercase) to be able to look them up
     * for optimization
     */
    const SVG_DEFAULT_ATTRIBUTES_VALUE = array(
        "x" => '0',
        "y" => '0',
        "width" => '100%',
        "height" => '100%',
        "preserveAspectRatio" => 'xMidYMid meet',
        "zoomAndPan" => 'magnify',
        "version" => '1.1',
        "baseProfile" => 'none',
        "contentScriptType" => 'application/ecmascript',
        "contentStyleType" => 'text/css',
    );
    const CONF_PRESERVE_ASPECT_RATIO_DEFAULT = "svgPreserveAspectRatioDefault";
    const SVG_NAMESPACE_URI = "http://www.w3.org/2000/svg";


    /**
     * Type of svg
     *   * Icon and tile have the same characteristic (ie viewbox = 0 0 A A) and the color can be set)
     *   * An illustration does not have rectangle shape and the color is not set
     */
    const ICON_TYPE = "icon";
    const TILE_TYPE = "tile";
    const ILLUSTRATION_TYPE = "illustration";

    /**
     * There is only two type of svg icon / tile
     *   * fill color is on the surface (known also as Solid)
     *   * stroke, the color is on the path (known as Outline
     */
    const COLOR_TYPE_FILL_SOLID = "fill";
    const COLOR_TYPE_STROKE_OUTLINE = "stroke";
    const DEFAULT_ICON_WIDTH = "24";

    const CURRENT_COLOR = "currentColor";
    const VIEW_BOX = "viewBox";

    /**
     * @var string - a name identifier that is added in the SVG
     */
    private $name;

    /**
     * @var boolean do the svg should be optimized
     */
    private $shouldBeOptimized;
    /**
     * @var Path
     */
    private $path;


    public function __construct($text)
    {
        parent::__construct($text);

        $this->shouldBeOptimized = PluginUtility::getConfValue(self::CONF_SVG_OPTIMIZATION_ENABLE, 1);

    }

    /**
     * @param Path $path
     * @return SvgDocument
     * @throws ExceptionCombo
     */
    public static function createSvgDocumentFromPath(Path $path): SvgDocument
    {
        $text = FileSystems::getContent($path);
        $svg = new SvgDocument($text);
        $svg->setName($path->getLastNameWithoutExtension());
        $svg->setPath($path);
        return $svg;
    }

    /**
     * @throws ExceptionCombo
     */
    public static function createSvgDocumentFromMarkup($markup): SvgDocument
    {
        return new SvgDocument($markup);
    }

    /**
     * @param TagAttributes|null $tagAttributes
     * @return string
     *
     * TODO: What strange is that this is a XML document that is also an image
     *   This class should be merged with {@link ImageSvg}
     *   Because we use only {@link Image} function that are here not available because we loose the fact that this is an image
     *   For instance {@link Image::getCroppingDimensionsWithRatio()}
     * @throws ExceptionCombo
     */
    public function getXmlText(TagAttributes $tagAttributes = null): string
    {

        if ($tagAttributes === null) {
            $localTagAttributes = TagAttributes::createEmpty();
        } else {
            $localTagAttributes = TagAttributes::createFromTagAttributes($tagAttributes);
        }

        /**
         * ViewBox should exist
         */
        $viewBox = $this->getXmlDom()->documentElement->getAttribute(self::VIEW_BOX);
        if($viewBox===""){
            $width = $this->getXmlDom()->documentElement->getAttribute("width");
            if($width===""){
                LogUtility::msg("Svg processing stopped. Bad svg: We can't determine the width of the svg ($this) (The viewBox and the width does not exist) ", LogUtility::LVL_MSG_ERROR,self::CANONICAL);
                return parent::getXmlText();
            }
            $height =  $this->getXmlDom()->documentElement->getAttribute("height");
            if($height===""){
                LogUtility::msg("Svg processing stopped. Bad svg: We can't determine the height of the svg ($this) (The viewBox and the height does not exist) ", LogUtility::LVL_MSG_ERROR,self::CANONICAL);
                return parent::getXmlText();
            }
            $this->getXmlDom()->documentElement->setAttribute(self::VIEW_BOX,"0 0 $width $height");
        }

        if ($this->shouldOptimize()) {
            $this->optimize();
        }

        // Set the name (icon) attribute for test selection
        if ($localTagAttributes->hasComponentAttribute("name")) {
            $name = $localTagAttributes->getValueAndRemove("name");
            $this->setRootAttribute('data-name', $name);
        }

        // Handy variable
        $documentElement = $this->getXmlDom()->documentElement;


        /**
         * Svg type
         * The svg type is the svg usage
         * How the svg should be shown (the usage)
         *
         * We need it to make the difference between an icon
         *   * in a paragraph (the width and height are the same)
         *   * as an illustration in a page image (the width and height may be not the same)
         */
        $svgUsageType = $localTagAttributes->getValue(TagAttributes::TYPE_KEY, self::ILLUSTRATION_TYPE);
        switch ($svgUsageType) {
            case self::ICON_TYPE:
            case self::TILE_TYPE:
                /**
                 * Dimension
                 *
                 * Using a icon in the navbrand component of bootstrap
                 * require the set of width and height otherwise
                 * the svg has a calculated width of null
                 * and the bar component are below the brand text
                 *
                 */
                if ($svgUsageType == self::ICON_TYPE) {
                    $defaultWidth = self::DEFAULT_ICON_WIDTH;
                } else {
                    // tile
                    $defaultWidth = "192";
                }
                /**
                 * Dimension
                 * The default unit on attribute is pixel, no need to add it
                 * as in CSS
                 */
                $width = $localTagAttributes->getValueAndRemove(Dimension::WIDTH_KEY, $defaultWidth);
                $localTagAttributes->addHtmlAttributeValue("width", $width);
                $height = $localTagAttributes->getValueAndRemove(Dimension::HEIGHT_KEY, $width);
                $localTagAttributes->addHtmlAttributeValue("height", $height);
                break;
            default:
                /**
                 * Illustration / Image
                 */
                /**
                 * Responsive SVG
                 */
                if (!$localTagAttributes->hasComponentAttribute("preserveAspectRatio")) {
                    /**
                     *
                     * Keep the same height
                     * Image in the Middle and border deleted when resizing
                     * https://developer.mozilla.org/en-US/docs/Web/SVG/Attribute/preserveAspectRatio
                     * Default is xMidYMid meet
                     */
                    $defaultAspectRatio = PluginUtility::getConfValue(self::CONF_PRESERVE_ASPECT_RATIO_DEFAULT, "xMidYMid slice");
                    $localTagAttributes->addHTMLAttributeValue("preserveAspectRatio", $defaultAspectRatio);
                }

                /**
                 * Note on dimension width and height
                 * Width and height element attribute are in reality css style properties.
                 *   ie the max-width style
                 * They are treated in {@link PluginUtility::processStyle()}
                 */

                /**
                 * Adapt to the container by default
                 * Height `auto` and not `100%` otherwise you get a layout shift
                 */
                $localTagAttributes->addStyleDeclarationIfNotSet("width", "100%");
                $localTagAttributes->addStyleDeclarationIfNotSet("height", "auto");


                if ($localTagAttributes->hasComponentAttribute(Dimension::WIDTH_KEY)) {

                    /**
                     * If a dimension was set, it's seen by default as a max-width
                     * If it should not such as in a card, this property is already set
                     * and is not overwritten
                     */
                    $width = $localTagAttributes->getComponentAttributeValue(Dimension::WIDTH_KEY);
                    try {
                        $width = Dimension::toPixelValue($width);
                    } catch (ExceptionCombo $e) {
                        LogUtility::msg("The requested width $width could not be converted to pixel. It returns the following error ({$e->getMessage()}). Processing was stopped");
                        return parent::getXmlText();
                    }
                    $localTagAttributes->addStyleDeclarationIfNotSet("max-width", "{$width}px");

                }
                break;
        }

        /**
         * Svg Structure
         *
         * All attributes that are applied for all usage (output independent)
         * and that depends only on the structure of the icon
         *
         * Why ? Because {@link \syntax_plugin_combo_pageimage}
         * can be an icon or an illustrative image
         *
         */
        try {
            $mediaWidth = $this->getMediaWidth();
        } catch (ExceptionCombo $e) {
            LogUtility::msg("The media width of ($this) returns the following error ({$e->getMessage()}). The processing was stopped");
            return parent::getXmlText();
        }
        try {
            $mediaHeight = $this->getMediaHeight();
        } catch (ExceptionCombo $e) {
            LogUtility::msg("The media height of ($this) returns the following error ({$e->getMessage()}). The processing was stopped");
            return parent::getXmlText();
        }
        if ($mediaWidth !== null
            && $mediaHeight !== null
            && $mediaWidth == $mediaHeight
            && $mediaWidth < 400) // 356 for logos telegram are the size of the twitter emoji but tile may be bigger ?
        {
            $svgStructureType = self::ICON_TYPE;
        } else {
            $svgStructureType = self::ILLUSTRATION_TYPE;

            // some icon may be bigger
            // in size than 400. example 1024 for ant-design:table-outlined
            // https://github.com/ant-design/ant-design-icons/blob/master/packages/icons-svg/svg/outlined/table.svg
            // or not squared
            // if the usage is determined or the svg is in the icon directory, it just takes over.
            if ($svgUsageType === self::ICON_TYPE || $this->isInIconDirectory()) {
                $svgStructureType = self::ICON_TYPE;
            }

        }
        switch ($svgStructureType) {
            case self::ICON_TYPE:
            case self::TILE_TYPE:
                /**
                 * Determine if this is a:
                 *   * fill
                 *   * or stroke
                 * svg icon
                 *
                 * The color can be set:
                 *   * on fill (surface)
                 *   * on stroke (line)
                 *
                 * Feather set it on the stroke
                 * Example: view-source:https://raw.githubusercontent.com/feathericons/feather/master/icons/airplay.svg
                 *
                 * By default, the icon should have this property when downloaded
                 * but if this not the case (such as for Material design), we set them
                 */
                if (!$documentElement->hasAttribute("fill")) {

                    /**
                     * Note: if fill was not set, the default color would be black
                     */
                    $localTagAttributes->addHtmlAttributeValue("fill", self::CURRENT_COLOR);

                }

                /**
                 * Color
                 * Color should only be applied on icon.
                 * What if the svg is an illustrative image
                 */
                if ($localTagAttributes->hasComponentAttribute(ColorUtility::COLOR)) {
                    /**
                     *
                     * We say that this is used only for an icon (<72 px)
                     *
                     * Not that an icon svg file can also be used as {@link \syntax_plugin_combo_pageimage}
                     *
                     * We don't set it as a styling attribute
                     * because it's not taken into account if the
                     * svg is used as a background image
                     * fill or stroke should have at minimum "currentColor"
                     */

                    $color = $localTagAttributes->getValueAndRemove(ColorUtility::COLOR);
                    $colorValue = ColorUtility::getColorValue($color);

                    /**
                     * if the stroke element is not present this is a fill icon
                     */
                    $svgColorType = self::COLOR_TYPE_FILL_SOLID;
                    if ($documentElement->hasAttribute("stroke")) {
                        $svgColorType = self::COLOR_TYPE_STROKE_OUTLINE;
                    }

                    switch ($svgColorType) {
                        case self::COLOR_TYPE_FILL_SOLID:
                            $localTagAttributes->addHtmlAttributeValue("fill", $colorValue);

                            // Delete the fill property on sub-path
                            // if the fill is set on subpath, it will not work
                            if ($colorValue !== self::CURRENT_COLOR) {
                                $svgPaths = $this->xpath("//*[local-name()='path']");
                                for ($i = 0; $i < $svgPaths->length; $i++) {
                                    /**
                                     * @var DOMElement $nodeElement
                                     */
                                    $nodeElement = $svgPaths[$i];
                                    $value = $nodeElement->getAttribute("fill");
                                    if($value!=="none") {
                                        $this->removeAttributeValue("fill", $nodeElement);
                                    } else {
                                        $this->removeNode($nodeElement);
                                    }
                                }
                            }

                            break;
                        case self::COLOR_TYPE_STROKE_OUTLINE:
                            $localTagAttributes->addHtmlAttributeValue("fill", "none");
                            $localTagAttributes->addHtmlAttributeValue("stroke", $colorValue);
                            break;
                    }

                }


                break;

        }

        /**
         * Ratio / Cropping (used for ratio cropping)
         * Width and height used to set the viewBox of a svg
         * to crop it
         * (In a raster image, there is not this distinction)
         *
         * With an icon, the viewBox can be small but it can be zoomed out
         * via the {@link Dimension::WIDTH_KEY}
         */
        if (
        $localTagAttributes->hasComponentAttribute(Dimension::RATIO_ATTRIBUTE)
        ) {
            // We get a crop, it means that we need to change the viewBox
            $ratio = $localTagAttributes->getValueAndRemoveIfPresent(Dimension::RATIO_ATTRIBUTE);
            try {
                $targetRatio = Dimension::convertTextualRatioToNumber($ratio);
            } catch (ExceptionCombo $e) {
                LogUtility::msg("The target ratio attribute ($ratio) returns the following error ({$e->getMessage()}). The svg processing was stopped");
                return parent::getXmlText();
            }
            [$width, $height] = Image::getCroppingDimensionsWithRatio(
                $targetRatio,
                $mediaWidth,
                $mediaHeight
            );
            $x = 0;
            $y = 0;
            if ($svgStructureType === self::ICON_TYPE) {
                // icon case, we zoom out otherwise, this is ugly, the icon takes the whole place
                $zoomFactor = 3;
                $width = $zoomFactor * $width;
                $height = $zoomFactor * $height;
                // center
                $actualWidth = $mediaWidth;
                $actualHeight = $mediaHeight;
                $x = -($width - $actualWidth) / 2;
                $y = -($height - $actualHeight) / 2;
            }
            $this->setRootAttribute(self::VIEW_BOX, "$x $y $width $height");

        }


        // Add a class on each path for easy styling
        if (!empty($this->name)) {
            $svgPaths = $this->xpath("//*[local-name()='path']");
            for ($i = 0; $i < $svgPaths->length; $i++) {

                $stylingClass = $this->name . "-" . $i;
                $this->addAttributeValue("class", $stylingClass, $svgPaths[$i]);

            }
        }

        /**
         * Svg attribute are case sensitive
         * but not the component attribute key
         * we get the value and set it then as HTML to have the good casing
         * on this attribute
         */
        $caseSensitives = ["preserveAspectRatio"];
        foreach ($caseSensitives as $caseSensitive) {
            if ($localTagAttributes->hasComponentAttribute($caseSensitive)) {
                $aspectRatio = $localTagAttributes->getValueAndRemove($caseSensitive);
                $localTagAttributes->addHTMLAttributeValue($caseSensitive, $aspectRatio);
            }
        }

        /**
         * Old model where the src was parsed in the render
         * When the attributes are in the {@link Path} we can delete this
         */
        $localTagAttributes->removeAttributeIfPresent(PagePath::PROPERTY_NAME);

        /**
         * Set the attributes to the root
         */
        $toHtmlArray = $localTagAttributes->toHtmlArray();
        foreach ($toHtmlArray as $name => $value) {
            if (in_array($name, TagAttributes::MULTIPLE_VALUES_ATTRIBUTES)) {
                $actualValue = $this->getRootAttributeValue($name);
                if ($actualValue !== null) {
                    $value = TagAttributes::mergeClassNames($value, $actualValue);
                }
            }
            $this->setRootAttribute($name, $value);
        }

        return parent::getXmlText();

    }

    public function getOptimizedSvg($tagAttributes = null)
    {
        $this->optimize();

        return $this->getXmlText($tagAttributes);

    }

    /**
     * @param $boolean
     * @return SvgDocument
     */
    public function setShouldBeOptimized($boolean): SvgDocument
    {
        $this->shouldBeOptimized = $boolean;
        return $this;
    }

    /**
     * @throws ExceptionCombo
     */
    public function getMediaWidth(): int
    {
        $viewBox = $this->getXmlDom()->documentElement->getAttribute(self::VIEW_BOX);
        if ($viewBox !== "") {
            $attributes = explode(" ", $viewBox);
            $viewBoxWidth = $attributes[2];
            try {
                return DataType::toInteger($viewBoxWidth);
            } catch (ExceptionCombo $e) {
                throw new ExceptionCombo("The media with ($viewBoxWidth) of the svg image ($this) is not a valid integer value");
            }
        }

        /**
         * Case with some icon such as
         * https://raw.githubusercontent.com/fefanto/fontaudio/master/svgs/fad-random-1dice.svg
         */
        $width = $this->getXmlDom()->documentElement->getAttribute("width");
        if ($width === "") {
            throw new ExceptionCombo("The svg ($this) does not have a viewBox or width attribute, the intrinsic width cannot be determined");
        }
        try {
            return DataType::toInteger($width);
        } catch (ExceptionCombo $e) {
            throw new ExceptionCombo("The media width ($width) of the svg image ($this) is not a valid integer value");
        }

    }

    /**
     * @throws ExceptionCombo
     */
    public function getMediaHeight(): int
    {
        $viewBox = $this->getXmlDom()->documentElement->getAttribute(self::VIEW_BOX);
        if ($viewBox !== "") {
            $attributes = explode(" ", $viewBox);
            $viewBoxHeight = $attributes[3];
            try {
                return DataType::toInteger($viewBoxHeight);
            } catch (ExceptionCombo $e) {
                throw new ExceptionCombo("The media height of the svg image ($this) is not a valid integer value");
            }
        }
        /**
         * Case with some icon such as
         * https://raw.githubusercontent.com/fefanto/fontaudio/master/svgs/fad-random-1dice.svg
         */
        $height = $this->getXmlDom()->documentElement->getAttribute("height");
        if ($height === "") {
            throw new ExceptionCombo("The svg ($this) does not have a viewBox or height attribute, the intrinsic height cannot be determined");
        }
        try {
            return DataType::toInteger($height);
        } catch (ExceptionCombo $e) {
            throw new ExceptionCombo("The media width ($height) of the svg image ($this) is not a valid integer value");
        }

    }


    private function getSvgPaths()
    {
        if ($this->isXmlExtensionLoaded()) {

            /**
             * If the file was optimized, the svg namespace
             * does not exist anymore
             */
            $namespace = $this->getDocNamespaces();
            if (isset($namespace[self::SVG_NAMESPACE_PREFIX])) {
                $svgNamespace = self::SVG_NAMESPACE_PREFIX;
                $query = "//$svgNamespace:path";
            } else {
                $query = "//*[local-name()='path']";
            }
            return $this->xpath($query);
        } else {
            return array();
        }


    }


    /**
     * Optimization
     * Based on https://jakearchibald.github.io/svgomg/
     * (gui of https://github.com/svg/svgo)
     */
    public function optimize()
    {

        if ($this->shouldOptimize()) {

            /**
             * Delete Editor namespace
             * https://github.com/svg/svgo/blob/master/plugins/removeEditorsNSData.js
             */
            $confNamespaceToKeeps = PluginUtility::getConfValue(self::CONF_OPTIMIZATION_NAMESPACES_TO_KEEP);
            $namespaceToKeep = StringUtility::explodeAndTrim($confNamespaceToKeeps, ",");
            foreach ($this->getDocNamespaces() as $namespacePrefix => $namespaceUri) {
                if (
                    !empty($namespacePrefix)
                    && $namespacePrefix != "svg"
                    && !in_array($namespacePrefix, $namespaceToKeep)
                    && in_array($namespaceUri, self::EDITOR_NAMESPACE)
                ) {
                    $this->removeNamespace($namespaceUri);
                }
            }

            /**
             * Delete empty namespace rules
             */
            $documentElement = &$this->getXmlDom()->documentElement;
            foreach ($this->getDocNamespaces() as $namespacePrefix => $namespaceUri) {
                $nodes = $this->xpath("//*[namespace-uri()='$namespaceUri']");
                $attributes = $this->xpath("//@*[namespace-uri()='$namespaceUri']");
                if ($nodes->length == 0 && $attributes->length == 0) {
                    $result = $documentElement->removeAttributeNS($namespaceUri, $namespacePrefix);
                    if ($result === false) {
                        LogUtility::msg("Internal error: The deletion of the empty namespace ($namespacePrefix:$namespaceUri) didn't succeed", LogUtility::LVL_MSG_WARNING, "support");
                    }
                }
            }


            /**
             * Delete default value (version=1.1 for instance)
             */
            $defaultValues = self::SVG_DEFAULT_ATTRIBUTES_VALUE;
            foreach ($documentElement->attributes as $attribute) {
                /** @var DOMAttr $attribute */
                $name = $attribute->name;
                if (isset($defaultValues[$name])) {
                    if ($defaultValues[$name] == $attribute->value) {
                        $documentElement->removeAttributeNode($attribute);
                    }
                }
            }

            /**
             * Suppress the attributes (by default id and style)
             */
            $attributeConfToDelete = PluginUtility::getConfValue(self::CONF_OPTIMIZATION_ATTRIBUTES_TO_DELETE, "id, style");
            $attributesNameToDelete = StringUtility::explodeAndTrim($attributeConfToDelete, ",");
            foreach ($attributesNameToDelete as $value) {
                $nodes = $this->xpath("//@$value");
                foreach ($nodes as $node) {
                    /** @var DOMAttr $node */
                    /** @var DOMElement $DOMNode */
                    $DOMNode = $node->parentNode;
                    $DOMNode->removeAttributeNode($node);
                }
            }

            /**
             * Remove width/height that coincides with a viewBox attr
             * https://www.w3.org/TR/SVG11/coords.html#ViewBoxAttribute
             * Example:
             * <svg width="100" height="50" viewBox="0 0 100 50">
             * <svg viewBox="0 0 100 50">
             *
             */
            $widthAttributeValue = $documentElement->getAttribute("width");
            if (!empty($widthAttributeValue)) {
                $widthPixel = Unit::toPixel($widthAttributeValue);

                $heightAttributeValue = $documentElement->getAttribute("height");
                if (!empty($heightAttributeValue)) {
                    $heightPixel = Unit::toPixel($heightAttributeValue);

                    // ViewBox
                    $viewBoxAttribute = $documentElement->getAttribute(self::VIEW_BOX);
                    if (!empty($viewBoxAttribute)) {
                        $viewBoxAttributeAsArray = StringUtility::explodeAndTrim($viewBoxAttribute, " ");

                        if (sizeof($viewBoxAttributeAsArray) == 4) {
                            $minX = $viewBoxAttributeAsArray[0];
                            $minY = $viewBoxAttributeAsArray[1];
                            $widthViewPort = $viewBoxAttributeAsArray[2];
                            $heightViewPort = $viewBoxAttributeAsArray[3];
                            if (
                                $minX == 0 &
                                $minY == 0 &
                                $widthViewPort == $widthPixel &
                                $heightViewPort == $heightPixel
                            ) {
                                $documentElement->removeAttribute("width");
                                $documentElement->removeAttribute("height");
                            }

                        }
                    }
                }
            }


            /**
             * Suppress script metadata node
             * Delete of:
             *   * https://developer.mozilla.org/en-US/docs/Web/SVG/Element/script
             */
            $elementsToDeleteConf = PluginUtility::getConfValue(self::CONF_OPTIMIZATION_ELEMENTS_TO_DELETE, "script, style");
            $elementsToDelete = StringUtility::explodeAndTrim($elementsToDeleteConf, ",");

            foreach ($elementsToDelete as $elementToDelete) {
                if ($elementToDelete === "style" && $this->isInIconDirectory()) {
                    // icon library (downloaded) have high trust
                    // they may include style in the defs
                    // example carbon:SQL
                    continue;
                }
                $nodes = $this->xpath("//*[local-name()='$elementToDelete']");
                foreach ($nodes as $node) {
                    /** @var DOMElement $node */
                    $node->parentNode->removeChild($node);
                }
            }

            // Delete If Empty
            //   * https://developer.mozilla.org/en-US/docs/Web/SVG/Element/defs
            //   * https://developer.mozilla.org/en-US/docs/Web/SVG/Element/metadata
            $elementsToDeleteIfEmptyConf = PluginUtility::getConfValue(self::CONF_OPTIMIZATION_ELEMENTS_TO_DELETE_IF_EMPTY, "metadata, defs, g");
            $elementsToDeleteIfEmpty = StringUtility::explodeAndTrim($elementsToDeleteIfEmptyConf);
            foreach ($elementsToDeleteIfEmpty as $elementToDeleteIfEmpty) {
                $elementNodeList = $this->xpath("//*[local-name()='$elementToDeleteIfEmpty']");
                foreach ($elementNodeList as $element) {
                    /** @var DOMElement $element */
                    if (!$element->hasChildNodes()) {
                        $element->parentNode->removeChild($element);
                    }
                }
            }

            /**
             * Delete the svg prefix namespace definition
             * At the end to be able to query with svg as prefix
             */
            if (!in_array("svg", $namespaceToKeep)) {
                $documentElement->removeAttributeNS(self::SVG_NAMESPACE_URI, self::SVG_NAMESPACE_PREFIX);
            }

        }
    }

    public function shouldOptimize()
    {

        return $this->shouldBeOptimized;

    }

    /**
     * The name is used to add class in the svg
     * @param $name
     */
    private function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Set the context
     * @param Path $path
     */
    private function setPath(Path $path)
    {
        $this->path = $path;
    }

    public function __toString()
    {
        if ($this->path !== null) {
            return $this->path->__toString();
        }
        if ($this->name !== null) {
            return $this->name;
        }
        return "unknown";
    }

    private function isInIconDirectory(): bool
    {
        if ($this->path == null) {
            return false;
        }
        $iconNameSpace = PluginUtility::getConfValue(Icon::CONF_ICONS_MEDIA_NAMESPACE, Icon::CONF_ICONS_MEDIA_NAMESPACE_DEFAULT);
        if (strpos($this->path->toString(), $iconNameSpace) !== false) {
            return true;
        }
        return false;
    }

    /**
     * An utility function to know how to remove a node
     * @param DOMElement $nodeElement
     */
    private function removeNode(DOMElement $nodeElement)
    {
        $nodeElement->parentNode->removeChild($nodeElement);
    }


}
