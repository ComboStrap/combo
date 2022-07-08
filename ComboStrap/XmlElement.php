<?php

namespace ComboStrap;

use DOMElement;
use DOMText;

class XmlElement
{

    private DOMElement $element;
    private XmlDocument $document;
    private array $styleDeclaration = [];

    /**
     * @param DOMElement $domElement - the dom element wrapped
     * @param XmlDocument $document - the document
     */
    public function __construct(DOMElement $domElement, XmlDocument $document)
    {
        $this->element = $domElement;
        $this->document = $document;

    }

    public static function create($domElement, XmlDocument $xmlDocument): XmlElement
    {
        return new XmlElement($domElement, $xmlDocument);
    }

    public function getAttribute(string $qualifiedName): string
    {
        return $this->element->getAttribute($qualifiedName);
    }

    public function getClass(): string
    {
        return $this->element->getAttribute("class");
    }

    /**
     * @throws ExceptionNotFound
     */
    public function getFirstChild(): XmlElement
    {
        $domNode = $this->element->firstChild;
        if ($domNode === null) {
            throw new ExceptionNotFound("No first child");
        }
        if (!($domNode instanceof DOMElement)) {
            throw new ExceptionNotFound("The first child is not a DOM Element");
        }
        return new XmlElement($domNode, $this->document);
    }

    /**
     * @return XmlElement[]
     */
    public function getChildrenElement(): array
    {
        $childNodes = [];
        foreach ($this->element->childNodes as $childNode) {
            if ($childNode instanceof DOMElement) {
                $childNodes[] = new XmlElement($childNode, $this->document);
            }
        }
        return $childNodes;
    }

    /**
     * @return array - the node text values in an array
     */
    public function getChildrenNodeTextValues(): array
    {
        $childNodes = [];
        foreach ($this->element->childNodes as $childNode) {
            if ($childNode instanceof DOMText) {
                $childNodes[] = $childNode->nodeValue;
            }
        }
        return $childNodes;
    }

    /**
     * @return XmlElement[]
     * @throws ExceptionBadSyntax
     */
    public function querySelectorAll(string $selector): array
    {
        $xpath = $this->document->cssSelectorToXpath($selector);
        $nodes = [];
        foreach ($this->document->xpath($xpath, $this->element) as $child) {
            if ($child instanceof DOMElement) {
                $nodes[] = new XmlElement($child, $this->document);
            }
        }
        return $nodes;
    }

    public function getXmlTextNormalized(): string
    {

        return $this->document->toXmlNormalized($this->element);

    }

    public function removeAttribute($attributeName): XmlElement
    {
        $attr = $this->element->getAttributeNode($attributeName);
        if ($attr == false) {
            return $this;
        }
        $result = $this->element->removeAttributeNode($attr);
        if ($result === false) {
            throw new ExceptionRuntime("Not able to delete the attribute $attributeName of the node element {$this->element->tagName} in the Xml document");
        }
        return $this;
    }

    public function remove(): XmlElement
    {
        $this->element->parentNode->removeChild($this->element);
        return $this;
    }

    public function getStyle(): string
    {
        return $this->element->getAttribute("style");
    }

    public function getNodeValue()
    {
        return $this->element->nodeValue;
    }

    /**
     * @throws ExceptionBadSyntax
     * @throws ExceptionNotFound
     */
    public function querySelector(string $selector): XmlElement
    {
        $domNodeList = $this->querySelectorAll($selector);
        if (sizeof($domNodeList) >= 1) {
            return $domNodeList[0];
        }
        throw new ExceptionNotFound("No element was found with the selector $selector");
    }

    public function getLocalName()
    {
        return $this->element->localName;
    }

    public function addClass(string $class): XmlElement
    {
        $classes = Html::mergeClassNames($class, $this->getClass());
        $this->element->setAttribute("class", $classes);
        return $this;
    }

    public function setAttribute(string $name, string $value): XmlElement
    {
        $this->element->setAttribute($name, $value);
        return $this;
    }

    public function hasAttribute(string $name): bool
    {
        return $this->element->hasAttribute($name);
    }

    public function getDomElement(): DOMElement
    {
        return $this->element;
    }

    /**
     * Append a text node as a child
     * @param string $string
     * @return $this
     */
    public function appendTextNode(string $string): XmlElement
    {
        $textNode = $this->element->ownerDocument->createTextNode($string);
        $this->element->appendChild($textNode);
        return $this;
    }

    public function toHtml()
    {
        return $this->element->ownerDocument->saveHTML($this->element);
    }

    public function toXhtml()
    {
        return $this->element->ownerDocument->saveXML($this->element);
    }

    public function getNodeValueWithoutCdata()
    {
        return XmlUtility::extractTextWithoutCdata($this->getNodeValue());
    }

    /**
     * @throws ExceptionBadSyntax
     * @throws ExceptionBadArgument
     */
    public function insertAdjacentHTML(string $position, string $html): XmlElement
    {
        $externalElement = XmlDocument::createHtmlDocFromMarkup($html)->getElement()->getDomElement();
        // import/copy item from external document to internal document
        $internalElement = $this->importIfExternal($externalElement);
        switch ($position) {
            case 'beforeend':
                $this->element->appendChild($internalElement);
                return $this;
            case 'afterbegin':
                $firstChild = $this->element->firstChild;
                if ($firstChild === null) {
                    $this->element->appendChild($internalElement);
                } else {
                    // The object on which you actually call the insertBefore()
                    // on the parent node of the reference node
                    // otherwise you get a `not found`
                    // https://www.php.net/manual/en/domnode.insertbefore.php#53506
                    $firstChild->parentNode->insertBefore($internalElement, $firstChild);
                }
                return $this;
            case 'beforebegin':
                $this->element->parentNode->insertBefore($internalElement, $this->element);
                return $this;
            default:
                throw new ExceptionBadArgument("The position ($position) is unknown");
        }

    }


    public function getId(): string
    {
        return $this->getAttribute("id");
    }

    public function hasChildrenElement(): bool
    {
        return sizeof($this->getChildrenElement()) !== 0;
    }

    public function getAttributeOrDefault(string $string, string $default): string
    {
        if (!$this->hasAttribute($string)) {
            return $default;
        }
        return $this->getAttribute($string);

    }

    public function appendChild(XmlElement $xmlElement): XmlElement
    {
        $element = $this->importIfExternal($xmlElement->element);
        $this->element->appendChild($element);
        return $this;
    }

    public function getDocument(): XmlDocument
    {
        return $this->document;
    }

    public function setNodeValue(string $nodeValue)
    {
        $this->element->nodeValue = $nodeValue;
    }

    public function addStyle(string $name, string $value): XmlElement
    {
        ArrayUtility::addIfNotSet($this->styleDeclaration, $name, $value);
        $this->setAttribute("style", Html::array2InlineStyle($this->styleDeclaration));
        return $this;
    }

    /**
     *
     * Utility to change the owner document
     * otherwise you get an error:
     * ```
     * DOMException : Wrong Document Error
     * ```
     *
     * @param DOMElement $domElement
     * @return DOMElement
     */
    private function importIfExternal(DOMElement $domElement): DOMElement
    {
        if ($domElement->ownerDocument !== $this->getDocument()->getDomDocument()) {
            return $this->getDocument()->getDomDocument()->importNode($domElement, true);
        }
        return $domElement;
    }

    /**
     * @throws ExceptionNotEquals
     */
    public function equals(XmlElement $rightDocument, array $attributeFilter = [])
    {
        $error = "";
        XmlUtility::diffNode(
            $this->element,
            $rightDocument->element,
            $error,
            $attributeFilter
        );
        if($error!==null){
            throw new ExceptionNotEquals($error);
        }
    }

    public function removeClass(string $string): XmlElement
    {
        $class = $this->getClass();
        $newClass = str_replace($string,"",$class);
        $this->setAttribute("class",$newClass);
        return $this;
    }
}
