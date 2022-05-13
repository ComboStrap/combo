<?php

namespace ComboStrap;

use DOMElement;

class XmlElement
{
    /**
     * @var DOMElement
     */
    private $element;
    /**
     * @var XmlDocument
     */
    private $document;

    /**
     * @param DOMElement $domNode
     * @param XmlDocument $document
     */
    public function __construct(DOMElement $domNode, XmlDocument $document)
    {
        $this->element = $domNode;
        $this->document = $document;

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
    public function getChildren(): array
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
     * @return XmlElement[]
     * @throws ExceptionBadSyntax
     */
    public function querySelectAll(string $selector): array
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

}
