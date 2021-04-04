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

use dokuwiki\Cache\Cache;
use DOMAttr;
use DOMElement;

require_once(__DIR__ . '/XmlFile.php');
require_once(__DIR__ . '/Unit.php');

class SvgFile extends XmlFile
{


    const CANONICAL = "svg";

    /**
     * Namespace (used to query with xpath only the svg node)
     */
    const SVG_NAMESPACE = "svg";
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


    public function __construct($path)
    {
        parent::__construct($path);


    }

    public static function createFromId($id)
    {
        return new SvgFile(mediaFN($id));
    }

    /**
     * @param TagAttributes $tagAttributes
     * @return false|string
     */
    public function getXmlText($tagAttributes = null)
    {

        if ($tagAttributes == null) {
            $tagAttributes = TagAttributes::createEmpty();
        }

        if ($this->shouldOptimize()) {
            $this->optimize($tagAttributes);
        }

        // Set the name (icon) attribute for test selection
        if ($tagAttributes->hasAttribute("name")) {
            $name = $tagAttributes->getValueAndRemove("name");
            $this->setRootAttribute('data-name', $name);
        }

        /**
         * Width and height are in reality style properties.
         *   ie the max-width style
         * They are treated in {@link PluginUtility::processStyle()}
         */


        // Icon will set by default a ''current color'' setting
        $fill = $tagAttributes->getValueAndRemove("fill");
        $svgPaths = $this->getSvgPaths();
        if (!empty($fill)) {
            foreach ($svgPaths as $pathXml) {
                XmlUtility::setAttribute("fill", $fill, $pathXml);
            }
        }

        // Add a class for easy styling
        for ($i = 0; $i < $svgPaths->length; $i++) {

            $stylingClass = $this->getFileNameWithoutExtension() . "-" . $i;
            $this->addAttributeValue("class", $stylingClass, $svgPaths[$i]);

        }

        if (!$tagAttributes->hasAttribute("preserveAspectRatio")) {
            /**
             *
             * Keep the same height
             * Image in the Middle and border deleted when resizing
             * https://developer.mozilla.org/en-US/docs/Web/SVG/Attribute/preserveAspectRatio
             * Default is xMidYMid meet
             */
            $defaultAspectRatio = PluginUtility::getConfValue(self::CONF_PRESERVE_ASPECT_RATIO_DEFAULT, "xMidYMid slice");
            $tagAttributes->addAttributeValue("preserveAspectRatio", $defaultAspectRatio);
        }

        $toHtmlArray = $tagAttributes->toHtmlArrayWithProcessing();
        foreach ($toHtmlArray as $name => $value) {
            $this->setRootAttribute($name, $value);
        }

        return parent::getXmlText();

    }

    public function getOptimizedSvg($tagAttributes = null)
    {
        $this->optimize();

        return $this->getXmlText($tagAttributes);

    }


    private function getSvgPaths()
    {
        if ($this->isXmlExtensionLoaded()) {

            /**
             * If the file was optimized, the svg namespace
             * does not exist anymore
             */
            $namespace = $this->getDocNamespaces();
            if (isset($namespace[self::SVG_NAMESPACE])) {
                $svgNamespace = self::SVG_NAMESPACE;
                $query = "//$svgNamespace:path";
            } else {
                $query = "//path";
            }
            return $this->xpath($query);
        } else {
            return array();
        }


    }

    public function getOptimizedSvgFile()
    {


        $cache = $this->getCache();
        $dependencies = array(
            'files' => [
                $this->getPath(),
                Resources::getComboHome() . "/plugin.info.txt"
            ]
        );
        $useCache = $cache->useCache($dependencies);
        if ($useCache) {
            $file = $cache->cache;
        } else {
            $content = $this->getOptimizedSvg();
            $cache->storeCache($content);
            $file = $cache->cache;
        }

        return $file;

    }

    public function hasSvgCache()
    {

        /**
         * $cache->cache is the file
         */
        return file_exists($this->getCache()->cache);
    }

    /**
     * @return Cache
     */
    private function getCache()
    {
        $cache = new Cache($this->getPath(), ".svg");
        return $cache;
    }

    /**
     * Optimization
     * Based on https://jakearchibald.github.io/svgomg/
     * (gui of https://github.com/svg/svgo)
     * @param TagAttributes $tagAttribute
     */
    public function optimize(&$tagAttribute = null)
    {

        if ($tagAttribute == null) {
            $tagAttribute = TagAttributes::createEmpty();
        }

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

            // Delete the svg namespace definition
            // We don't delete the svg namespace because this is also the default and will delete all the content
            $documentElement = $this->getXmlDom()->documentElement;
            if (!in_array("svg", $namespaceToKeep)) {
                $documentElement->removeAttributeNS("http://www.w3.org/2000/svg", self::SVG_NAMESPACE);
            }

            /**
             * Delete empty namespace rules
             */
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
            if (empty($widthAttributeValue)) {
                $widthAttributeValue = $tagAttribute->getValue("width");
            }
            if (!empty($widthAttributeValue)) {
                $widthPixel = Unit::toPixel($widthAttributeValue);

                $heightAttributeValue = $documentElement->getAttribute("height");
                if (empty($heightAttributeValue)) {
                    $heightAttributeValue = $tagAttribute->getValue("height");
                }
                if (!empty($heightAttributeValue)) {
                    $heightPixel = Unit::toPixel($heightAttributeValue);

                    // ViewBox
                    $viewBoxAttribute = $documentElement->getAttribute("viewBox");
                    if (!empty($viewBoxAttribute)) {
                        $viewBoxAttributeAsArray = StringUtility::explodeAndTrim($viewBoxAttribute, " ");

                        if (sizeof($viewBoxAttributeAsArray) == 4) {
                            $minX = $viewBoxAttributeAsArray[0];
                            $minY = $viewBoxAttributeAsArray[1];
                            $widthViewPort = $viewBoxAttributeAsArray[2];
                            $heightViewPort = $viewBoxAttributeAsArray[3];
                            if (
                                $minX === "0" &
                                $minY === "0" &
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
                $nodes = $this->xpath("//$elementToDelete");
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
                $elementNodeList = $this->xpath("//$elementToDeleteIfEmpty");
                foreach ($elementNodeList as $element) {
                    /** @var DOMElement $element */
                    if (!$element->hasChildNodes()) {
                        $element->parentNode->removeChild($element);
                    }
                }
            }

        }
    }

    private function shouldOptimize()
    {
        return PluginUtility::getConfValue(self::CONF_SVG_OPTIMIZATION_ENABLE, 1);
    }


}
