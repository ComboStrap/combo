<?php

namespace ComboStrap;

/**
 * A class to modeling a tree
 */
abstract class TreeNode
{

    /**
     * @var TreeNode[]
     */
    private array $children = [];

    private TreeNode $parentNode;



    public function appendChild(TreeNode $treeNode): TreeNode
    {
        $identifier = $treeNode->getGlobalIdentifier();
        $actualTreeNode = $this->children[$identifier];
        if ($actualTreeNode === null) {
            $this->children[$identifier] = $treeNode;
        }
        $treeNode->setParent($this);
        return $this;
    }


    public
    function __toString()
    {
        return $this->getGlobalIdentifier();
    }


    public
    function getChildCount(): int
    {
        return sizeof($this->children);
    }

    /**
     * @return TreeNode[] - empty array for a leaf
     */
    public function getChildren(): array
    {
        return $this->children;
    }


    public function hasChildren(): bool
    {
        return sizeof($this->children) > 0;
    }

    public function hasParent(): bool
    {
        return isset($this->parentNode);
    }

    /**
     * @return TreeNode
     * @throws ExceptionNotFound
     */
    public function getFirstChild(): TreeNode
    {
        $childrenKeys = array_keys($this->children);
        if (sizeof($childrenKeys) === 0) {
            throw new ExceptionNotFound("This node has no child");
        }
        return $this->children[$childrenKeys[0]];
    }

    abstract function getGlobalIdentifier(): ?string;



    private function setParent(TreeNode $parent)
    {
        $this->parentNode = $parent;
    }

    /**
     * @throws ExceptionNotFound
     */
    public function getParent(): TreeNode
    {
        if (!isset($this->parentNode)) {
            throw new ExceptionNotFound("No parent node");
        }
        return $this->parentNode;
    }


}
