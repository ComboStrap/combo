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
use DOMXPath;

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
        /**
         * Optimization adapted from inlineSVG from common.php
         */
        //$this->getXmlDom()->
        $svgXml = $this->getXmlText($tagAttributes);
        $svgXml = preg_replace('/<!--.*?(-->)/s', '', $svgXml); // comments
        $svgXml = preg_replace('/<\?xml .*?\?>/i', '', $svgXml); // xml header
        $svgXml = preg_replace('/xmlns:xlink="[a-z0-9\/.:]*"/i', '', $svgXml); // xmlns
        $svgXml = preg_replace('/version="[0-9.]*"/i', '', $svgXml); // version
        $svgXml = preg_replace('/<!DOCTYPE .*?>/i', '', $svgXml); // doc type
        $svgXml = preg_replace('/>\s+</s', '><', $svgXml); // newlines between tags
        $svgXml = preg_replace('/\s{2,}/s', ' ', $svgXml); // double space
        return trim($svgXml);

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


}
