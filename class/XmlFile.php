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


class XmlFile extends File
{

    /**
     * @var \SimpleXMLElement
     */
    private $xmlDom;

    /**
     * XmlFile constructor.
     * @param $path
     */
    public function __construct($path)
    {
        parent::__construct($path);

        if (extension_loaded(XmlUtility::SIMPLE_XML_EXTENSION)) {
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
            // A namespace must be registered to be able to query it with xpath
            $docNamespaces = $this->xmlDom->getDocNamespaces();
            $namespace = "";
            foreach ($docNamespaces as $nsKey => $nsValue) {
                if (strlen($nsKey) == 0) {
                    if (strpos($nsValue, "svg")) {
                        $nsKey = self::SVG_NAMESPACE;
                        $namespace = self::SVG_NAMESPACE;
                    }
                }
                if (strlen($nsKey) != 0) {
                    $this->xmlDom->registerXPathNamespace($nsKey, $nsValue);
                }
            }
            if ($namespace == "") {
                $msg = "The svg namespace was not found (http://www.w3.org/2000/svg). This can lead to problem with the setting of attributes such as the color due to bad xpath selection.";
                LogUtility::log2FrontEnd($msg, LogUtility::LVL_MSG_WARNING, self::CANONICAL);
                LogUtility::log2file($msg);
            }
        }

    }

    public function getXmlDom()
    {
        return $this->xmlDom;
    }

    public function setAttribute($string, $name)
    {
        XmlUtility::setAttribute($string, $name, $this->xmlDom);
    }

}
