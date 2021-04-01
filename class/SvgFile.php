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

require_once(__DIR__ . '/XmlFile.php');

class SvgFile extends XmlFile
{


    const CANONICAL = "svg";

    /**
     * Arbitrary default namespace to be able to query with xpath
     */
    const SVG_NAMESPACE = "svg";

    /**
     * @var string svg namespace
     */
    private $namespace;

    public function __construct($path)
    {
        parent::__construct($path);

        if ($this->isXmlExtensionLoaded()) {
            // A namespace must be registered to be able to query it with xpath
            $docNamespaces = $this->getXmlDom()->getDocNamespaces();
            foreach ($docNamespaces as $nsKey => $nsValue) {
                if (strlen($nsKey) == 0) {
                    if (strpos($nsValue, "svg")) {
                        $nsKey = self::SVG_NAMESPACE;
                        $this->namespace = self::SVG_NAMESPACE;
                    }
                }
                if (strlen($nsKey) != 0) {
                    $this->getXmlDom()->registerXPathNamespace($nsKey, $nsValue);
                }
            }
            if ($this->namespace == "") {
                $msg = "The svg namespace was not found (http://www.w3.org/2000/svg). This can lead to problem with the setting of attributes such as the color due to bad xpath selection.";
                LogUtility::log2FrontEnd($msg, LogUtility::LVL_MSG_WARNING, self::CANONICAL);
                LogUtility::log2file($msg);
            }
        }
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

    private function setDescendantPathAttribute($string, $string1)
    {

        if ($this->isXmlExtensionLoaded()) {
            $namespace = $this->namespace;
            if ($namespace != "") {
                $pathsXml = $this->getXmlDom()->xpath("//$namespace:path");
                foreach ($pathsXml as $pathXml) {
                    XmlUtility::setAttribute("fill", "currentColor", $pathXml);
                }
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
