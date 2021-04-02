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

use DOMDocument;
use DOMElement;
use DOMXPath;

require_once(__DIR__ . '/File.php');

class XmlFile extends File
{

    /**
     * @var DOMDocument
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
                //https://www.php.net/manual/en/libxml.constants.php
                $options = LIBXML_NOCDATA + LIBXML_NOBLANKS + LIBXML_NOEMPTYTAG + LIBXML_NSCLEAN;
                $this->xmlDom = new DOMDocument();
                $this->xmlDom->load($this->getPath(), $options);
                // namespace error : Namespace prefix dc on format is not defined
                // missing the ns declaration in the file. example:
                // xmlns:dc="http://purl.org/dc/elements/1.1/"
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
            $this->xmlDom->documentElement->setAttribute($string, $name);
        }
    }

    public function getXmlText()
    {
        if ($this->isXmlExtensionLoaded()) {
            return $this->getXmlDom()->saveHTML($this->getXmlDom()->ownerDocument);
        } else {
            return file_get_contents($this->getPath());
        }
    }

    /**
     * @return bool
     */
    public function isXmlExtensionLoaded()
    {
        // https://www.php.net/manual/en/dom.requirements.php
        return extension_loaded("libxml");
        // return extension_loaded(XmlUtility::SIMPLE_XML_EXTENSION);
    }

    /**
     * https://stackoverflow.com/questions/30257438/how-to-completely-remove-a-namespace-using-domdocument
     * @param $namespace
     */
    function removeNamespace($namespace)
    {
        $xpath = new DOMXPath($this->xmlDom);

        $nodes = $xpath->query("//*[namespace::{$namespace} and not(../namespace::{$namespace})]");
        foreach ($nodes as $node) {
            $ns_uri = $node->lookupNamespaceURI($namespace);
            $node->removeAttributeNS($ns_uri, $namespace);
        }
    }

    public function getDocNamespaces()
    {
        $xpath = new DOMXPath($this->getXmlDom());
        // `namespace::*` means  selects all the namespace attribute of the context node
        // See section 2 https://www.w3.org/TR/1999/REC-xpath-19991116/#location-paths
        $DOMNodeList = $xpath->query('namespace::*', $this->getXmlDom()->ownerDocument);
        $nameSpace = array();
        foreach ($DOMNodeList as  $node) {
            /** @var DOMElement $node */
            $namespaceURI = $node->namespaceURI;
            $localName = $node->prefix;
            $nameSpace[$localName] = $namespaceURI;
        }
        return $nameSpace;
    }

    /**
     * A wrapper that register namespace for the query
     * with the defined prefix
     * See comment:
     * https://www.php.net/manual/en/domxpath.registernamespace.php#51480
     * @param $query
     * @return \DOMNodeList|false
     */
    public function xpath($query)
    {
        $xpath = new DOMXPath($this->getXmlDom());
        foreach($this->getDocNamespaces() as $prefix => $namespaceUri)
        $xpath->registerNamespace($prefix,$namespaceUri);
        return $xpath->query($query, $this->getXmlDom());

    }



}
