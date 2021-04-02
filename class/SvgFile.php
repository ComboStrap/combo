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
use dokuwiki\Cache\CacheRenderer;
use DOMAttr;
use DOMElement;
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


        // Icon setting
        $fill = $tagAttributes->getValueAndRemove("fill");
        if (!empty($fill)) {
            $this->setDescendantPathAttribute("fill", $fill);
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

    /**
     * Set this attribute to all descendant path object
     * @param $attName
     * @param $attValue
     */
    private function setDescendantPathAttribute($attName, $attValue)
    {

        if ($this->isXmlExtensionLoaded()) {
            $namespace = self::SVG_NAMESPACE;
            $pathsXml = $this->xpath("//$namespace:path");
            foreach ($pathsXml as $pathXml) {
                XmlUtility::setAttribute($attName, $attValue, $pathXml);
            }

        }

    }

    public function getOptimizedSvgFile()
    {

        $cache = $this->getCache();
        $dependencies = array(
            'files' => [$this->getPath()]
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

    public function optimize()
    {
        /**
         * Optimization
         * https://jakearchibald.github.io/svgomg/
         */

        foreach ($this->getDocNamespaces() as $namespacePrefix => $namespaceUri) {
            if (!empty($namespacePrefix) && $namespacePrefix!="svg") {
                $this->removeNamespace($namespaceUri);
            }
        }

        // Delete the svg namespace definition
        // We don't delete the svg namespace because this is also the default and will delete all
        $this->getXmlDom()->documentElement->removeAttributeNS("http://www.w3.org/2000/svg", "svg");

        // Suppress all id
        $attributesNameToDelete = ["id","style"];
        foreach($attributesNameToDelete as $value) {
            $nodes = $this->xpath("//@$value");
            foreach ($nodes as $node) {
                /** @var DOMAttr $node */
                /** @var DOMElement $DOMNode */
                $DOMNode = $node->parentNode;
                $DOMNode->removeAttributeNode($node);
            }
        }

        // Suppress root attribute
        $attributesNameToDelete = ["version", "docname","width","height"];
        foreach ($attributesNameToDelete as $childNode){
            $this->removeRootAttribute($childNode);
        }

        // Suppress root metadata node
        $childNodeToDelete = ["metadata"];
        foreach ($childNodeToDelete as $childNode){
            $this->removeRootChildNode($childNode);
        }
    }




}
