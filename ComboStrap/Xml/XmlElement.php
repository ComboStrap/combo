<?php

namespace ComboStrap\Xml;

use ComboStrap\ArrayCaseInsensitive;
use ComboStrap\ArrayUtility;
use ComboStrap\ExceptionBadArgument;
use ComboStrap\ExceptionBadSyntax;
use ComboStrap\ExceptionNotEquals;
use ComboStrap\ExceptionNotFound;
use ComboStrap\ExceptionRuntime;
use ComboStrap\Html;
use ComboStrap\StringUtility;
use ComboStrap\TagAttribute\StyleAttribute;
use ComboStrap\Web\Url;
use ComboStrap\Xml\XmlSystems;
use DOMElement;
use DOMText;

class XmlElement
{

    private DOMElement $domElement;
    private XmlDocument $document;
    private array $styleDeclaration = [];

    /**
     * @param DOMElement $domElement - the dom element wrapped
     * @param XmlDocument $document - the document
     */
    public function __construct(DOMElement $domElement, XmlDocument $document)
    {
        $this->domElement = $domElement;
        $this->document = $document;

    }

    public static function create($domElement, XmlDocument $xmlDocument): XmlElement
    {
        return new XmlElement($domElement, $xmlDocument);
    }

    public function getAttribute(string $qualifiedName): string
    {
        return $this->domElement->getAttribute($qualifiedName);
    }

    public function getClass(): string
    {
        return $this->domElement->getAttribute("class");
    }

    /**
     * @throws ExceptionNotFound
     */
    public function getFirstChildElement(): XmlElement
    {
        $firstChild = $this->domElement->firstChild;
        while (!($firstChild instanceof DOMElement)) {
            if ($firstChild === null) {
                throw new ExceptionNotFound("No first child element");
            }
            $firstChild = $firstChild->nextSibling;
        }
        return new XmlElement($firstChild, $this->document);
    }

    /**
     * @return XmlElement[]
     */
    public function getChildrenElement(): array
    {
        $childNodes = [];
        foreach ($this->domElement->childNodes as $childNode) {
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
        foreach ($this->domElement->childNodes as $childNode) {
            if ($childNode instanceof DOMText) {
                $childNodes[] = $childNode->nodeValue;
            } else {
                $childNodes[] = implode('', XmlElement::create($childNode, $this->document)->getChildrenNodeTextValues());
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
        foreach ($this->document->xpath($xpath, $this->domElement) as $child) {
            if ($child instanceof DOMElement) {
                $nodes[] = new XmlElement($child, $this->document);
            }
        }
        return $nodes;
    }

    public function getXmlTextNormalized(): string
    {

        return $this->document->toXmlNormalized($this->domElement);

    }

    public function removeAttribute($attributeName): XmlElement
    {
        $attr = $this->domElement->getAttributeNode($attributeName);
        if ($attr == false) {
            return $this;
        }
        $result = $this->domElement->removeAttributeNode($attr);
        if ($result === false) {
            throw new ExceptionRuntime("Not able to delete the attribute $attributeName of the node element {$this->domElement->tagName} in the Xml document");
        }
        return $this;
    }

    public function remove(): XmlElement
    {
        $this->domElement->parentNode->removeChild($this->domElement);
        return $this;
    }

    public function getStyle(): string
    {
        return $this->domElement->getAttribute("style");
    }

    public function getNodeValue()
    {
        return $this->domElement->nodeValue;
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
        return $this->domElement->localName;
    }

    public function addClass(string $class): XmlElement
    {
        $classes = Html::mergeClassNames($class, $this->getClass());
        $this->domElement->setAttribute("class", $classes);
        return $this;
    }

    public function setAttribute(string $name, string $value): XmlElement
    {
        $this->domElement->setAttribute($name, $value);
        return $this;
    }

    public function hasAttribute(string $name): bool
    {
        return $this->domElement->hasAttribute($name);
    }

    public function getDomElement(): DOMElement
    {
        return $this->domElement;
    }

    /**
     * Append a text node as a child
     * @param string $string - the text
     * @param string $position - the position on where to insert the text node
     * @return $this
     * @throws ExceptionBadArgument - if the text is not a valid text xml expression
     */
    public function insertAdjacentTextNode(string $string, string $position = 'afterbegin'): XmlElement
    {
        $textNode = $this->domElement->ownerDocument->createTextNode($string);
        $this->insertAdjacentDomElement($position, $textNode);
        return $this;
    }

    public function toHtml()
    {
        return $this->domElement->ownerDocument->saveHTML($this->domElement);
    }

    public function toXhtml()
    {
        return $this->domElement->ownerDocument->saveXML($this->domElement);
    }

    public function getNodeValueWithoutCdata()
    {
        return XmlSystems::extractTextWithoutCdata($this->getNodeValue());
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
        $this->insertAdjacentDomElement($position, $internalElement);
        return $this;
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
        $element = $this->importIfExternal($xmlElement->domElement);
        $this->domElement->appendChild($element);
        return $this;
    }

    public function getDocument(): XmlDocument
    {
        return $this->document;
    }

    public function setNodeValue(string $nodeValue)
    {
        $this->domElement->nodeValue = $nodeValue;
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
        XmlSystems::diffNode(
            $this->domElement,
            $rightDocument->domElement,
            $error,
            $attributeFilter
        );
        if ($error !== null) {
            throw new ExceptionNotEquals($error);
        }
    }

    public function removeClass(string $string): XmlElement
    {
        $class = $this->getClass();
        $newClass = str_replace($string, "", $class);
        $this->setAttribute("class", $newClass);
        return $this;
    }

    /**
     * @throws ExceptionNotFound
     */
    public function getParent(): XmlElement
    {
        $parentNode = $this->domElement->parentNode;
        if ($parentNode !== null) {
            while (!($parentNode instanceof DOMElement)) {
                $parentNode = $parentNode->parentNode;
                if ($parentNode === null) {
                    break;
                }
            }
        }
        if ($parentNode === null) {
            throw new ExceptionNotFound("No parent node found");
        }
        return new XmlElement($parentNode, $this->document);
    }

    /**
     * @param string $position
     * @param \DOMNode $domNode - ie {@Link \DOMElement} or {@link \DOMNode}
     * @return XmlElement
     * @throws ExceptionBadArgument
     */
    public function insertAdjacentDomElement(string $position, \DOMNode $domNode): XmlElement
    {
        switch ($position) {
            case 'beforeend':
                $this->domElement->appendChild($domNode);
                return $this;
            case 'afterbegin':
                $firstChild = $this->domElement->firstChild;
                if ($firstChild === null) {
                    $this->domElement->appendChild($domNode);
                } else {
                    // The object on which you actually call the insertBefore()
                    // on the parent node of the reference node
                    // otherwise you get a `not found`
                    // https://www.php.net/manual/en/domnode.insertbefore.php#53506
                    $firstChild->parentNode->insertBefore($domNode, $firstChild);
                }
                return $this;
            case 'beforebegin':
                $this->domElement->parentNode->insertBefore($domNode, $this->domElement);
                return $this;
            default:
                throw new ExceptionBadArgument("The position ($position) is unknown");
        }
    }

    public function getInnerText(): string
    {
        if ($this->hasChildrenElement()) {
            return implode('', $this->getChildrenNodeTextValues());
        } else {
            return $this->domElement->nodeValue;
        }
    }

    public function getInnerTextWithoutCdata()
    {
        return XmlSystems::extractTextWithoutCdata($this->getInnerText());
    }

    public function getStyleProperties(): ArrayCaseInsensitive
    {
        $source = StyleAttribute::HtmlStyleValueToArray($this->getStyle());
        return new ArrayCaseInsensitive($source);
    }

    public function getStyleProperty(string $property): string
    {
        return $this->getStyleProperties()[$property];
    }

    /**
     * @throws ExceptionBadSyntax
     * @throws ExceptionBadArgument
     */
    public function getAttributeAsUrl(string $attributeName): Url
    {
        $value = $this->getAttribute($attributeName);
        if (empty($value)) {
            return Url::createEmpty();
        }
        return Url::createFromString($value);
    }

    public function __toString()
    {
        $toString = $this->getLocalName();
        $class = $this->getClass();
        if ($class !== "") {
            $classes = StringUtility::explodeAndTrim($class, " ");
            $toString .= '.' . implode(".", $classes);
        }
        if ($this->getId() !== "") {
            $toString .= "#" . $this->getId();
        }
        return $toString;
    }

    public function hasClass(string $needleClass): bool
    {
        $classes = preg_split("/\s/", $this->getClass());
        if (in_array($needleClass, $classes)) {
            return true;
        }
        return false;
    }


}
