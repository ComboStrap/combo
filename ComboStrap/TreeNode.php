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

    /**
     * @var int the child number when added
     * used to get a unique identifier in the tree
     */
    private int $levelChildIdentifier;


    public function appendChild(TreeNode $treeNode): TreeNode
    {

        $childCount = count($this->children);
        $treeNode->setLevelChildIdentifier($childCount + 1);
        $treeNode->setParent($this);
        $this->children[] = $treeNode;
        return $this;
    }

    public function getLocalId()
    {

    }


    public
    function __toString()
    {
        return $this->getTreeIdentifier();
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

    /**
     * A hierarchical tree identifier composed
     * of the node id of each level separated by a point
     * @return string
     */
    function getTreeIdentifier(): string
    {

        $treeIdentifier = $this->getLevelNodeIdentifier();
        $parent = $this;
        while (true) {
            try {
                $parent = $parent->getParent();
                $parentLevelNodeIdentifier = $parent->getLevelNodeIdentifier();
                if ($parentLevelNodeIdentifier !== "") {
                    $treeIdentifier = "{$parentLevelNodeIdentifier}.$treeIdentifier";
                } else {
                    $treeIdentifier = "$treeIdentifier";
                }
            } catch (ExceptionNotFound $e) {
                // no parent anymore
                break;
            }

        }
        return $treeIdentifier;
    }


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

    private function setLevelChildIdentifier(int $param)
    {
        $this->levelChildIdentifier = $param;
    }

    private function getLevelNodeIdentifier(): string
    {
        if (isset($this->levelChildIdentifier)) {
            return "$this->levelChildIdentifier";
        }
        return "";
    }


}
