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

    /**
     * @param TagAttributes $tagAttributes
     * @return false|string
     */
    public function getXmlText($tagAttributes = null)
    {

        // Set the name (icon) attribute for test selection
        if ($tagAttributes->hasAttribute("name")) {
            $name = $tagAttributes->getValueAndRemove("name");
            $this->setRootAttribute('data-name', $name);

        }

        // Width
        $widthName = "width";
        $widthValue = $tagAttributes->getValueAndRemove($widthName,"24px");
        $this->setRootAttribute($widthName, $widthValue);

        // Height
        $heightName = "height";
        $heightValue = $tagAttributes->getValueAndRemove($heightName,"24px");
        $this->setRootAttribute($heightName, $heightValue);


        // Icon setting
        $this->setDescendantPathAttribute("fill","currentColor");


        // for line item such as feather (https://github.com/feathericons/feather#2-use)
        // fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"

        // FYI: For whatever reason if you add a border the line icon are neater
        // PluginUtility::addStyleProperty("border","1px solid transparent",$attributes);


        $toHtmlArray = $tagAttributes->toHtmlArrayWithProcessing();
        foreach ($toHtmlArray as $name => $value) {
            $this->setRootAttribute($name, $value);
        }

        return parent::getXmlText();

    }

    private function setDescendantPathAttribute($string, $string1)
    {

        $namespace = $this->namespace;
        if ($namespace != "") {
            $pathsXml = $this->getXmlDom()->xpath("//$namespace:path");
            foreach ($pathsXml as $pathXml) {
                XmlUtility::setAttribute("fill", "currentColor", $pathXml);
            }
        }
    }




}
