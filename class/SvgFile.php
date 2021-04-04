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

use dokuwiki\Action\Plugin;
use dokuwiki\Cache\Cache;
use dokuwiki\Cache\CacheRenderer;
use DOMAttr;
use DOMElement;
use DOMNode;
use DOMXPath;
use http\Exception\RuntimeException;

require_once(__DIR__ . '/XmlFile.php');

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

        // Set the name (icon) attribute for test selection
        if ($tagAttributes->hasAttribute("name")) {
            $name = $tagAttributes->getValueAndRemove("name");
            $this->setRootAttribute('data-name', $name);
        }

        // Width
        $widthName = "width";
        $widthValue = $tagAttributes->getValueAndRemove($widthName);
        if (!empty($widthValue)) {
            $this->setRootAttribute($widthName, $widthValue);
        }

        // Height
        $heightName = "height";
        $heightValue = $tagAttributes->getValueAndRemove($heightName);
        if (!empty($heightValue)) {
            $this->setRootAttribute($heightName, $heightValue);
        }


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
             */
            $tagAttributes->addAttributeValue("preserveAspectRatio", "xMidYMid slice");
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
     */
    public function optimize()
    {

        if ($this->shouldOptimize()) {

            $confNamespaceToKeeps = PluginUtility::getConfValue(self::CONF_OPTIMIZATION_NAMESPACES_TO_KEEP);
            $namespaceToKeep = StringUtility::explodeAndTrim($confNamespaceToKeeps,",");
            foreach ($this->getDocNamespaces() as $namespacePrefix => $namespaceUri) {
                if (
                    !empty($namespacePrefix)
                    && $namespacePrefix != "svg"
                    && !in_array($namespacePrefix, $namespaceToKeep)
                ) {
                    $this->removeNamespace($namespaceUri);
                }
            }

            // Delete the svg namespace definition
            // We don't delete the svg namespace because this is also the default and will delete all the content
            if(!in_array("svg",$namespaceToKeep)) {
                $this->getXmlDom()->documentElement->removeAttributeNS("http://www.w3.org/2000/svg", self::SVG_NAMESPACE);
            }

            // Suppress all attribute id and style
            $attributeConfToDelete = PluginUtility::getConfValue(self::CONF_OPTIMIZATION_ATTRIBUTES_TO_DELETE, "id, style");
            $attributesNameToDelete = StringUtility::explodeAndTrim($attributeConfToDelete,",");
            foreach ($attributesNameToDelete as $value) {
                $nodes = $this->xpath("//@$value");
                foreach ($nodes as $node) {
                    /** @var DOMAttr $node */
                    /** @var DOMElement $DOMNode */
                    $DOMNode = $node->parentNode;
                    $DOMNode->removeAttributeNode($node);
                }
            }

            // Suppress root attribute
            $attributesNameToDelete = ["version", "docname", "width", "height"];
            foreach ($attributesNameToDelete as $attributeNameToDelete) {
                $this->removeRootAttribute($attributeNameToDelete);
            }

            // Suppress script metadata node
            // Delete of:
            //   * https://developer.mozilla.org/en-US/docs/Web/SVG/Element/script
            $elementsToDeleteConf = PluginUtility::getConfValue(self::CONF_OPTIMIZATION_ELEMENTS_TO_DELETE, "script, style");
            $elementsToDelete = StringUtility::explodeAndTrim($elementsToDeleteConf,",");
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
