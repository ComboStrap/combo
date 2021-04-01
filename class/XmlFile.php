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

require_once(__DIR__ . '/File.php');

class XmlFile extends File
{

    /**
     * @var \SimpleXMLElement
     */
    private $xmlDom = null;

    /**
     * XmlFile constructor.
     * @param $path
     */
    public function __construct($path)
    {
        parent::__construct($path);

        if ($this->isXmlExtensionLoaded()) {
            try {
                $this->xmlDom = simplexml_load_file($this->getPath());
            } catch (\Exception $e) {
                /**
                 * Don't use {@link \ComboStrap\LogUtility::msg()}
                 * or you will get a recursion
                 * because the URL has an SVG icon that calls this file
                 */
                $msg = "The file ($this) seems to not be an XML file. It could not be loaded. The error returned is $e";
                LogUtility::msg($msg, LogUtility::LVL_MSG_ERROR, "support");
                if (defined('DOKU_UNITTEST')) {
                    throw new \RuntimeException($msg);
                }
            }

        }

    }

    public function getXmlDom()
    {
        return $this->xmlDom;
    }

    public function setRootAttribute($string, $name)
    {
        if ($this->isXmlExtensionLoaded()) {
            XmlUtility::setAttribute($string, $name, $this->xmlDom);
        }
    }

    public function getXmlText()
    {
        if ($this->isXmlExtensionLoaded()) {
            return XmlUtility::asHtml($this->getXmlDom());
        } else {
            return file_get_contents($this->getPath());
        }
    }

    /**
     * @return bool
     */
    public function isXmlExtensionLoaded()
    {
        return extension_loaded(XmlUtility::SIMPLE_XML_EXTENSION);
    }


}
