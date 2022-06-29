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


    /**
     * @throws ExceptionBadState - if the node has already been added and is not the same than in the tree
     */
    public function appendChild(TreeNode $treeNode): TreeNode
    {
        try {
            $levelChildIdentifier = $treeNode->getLevelChildIdentifier();
        } catch (ExceptionNotFound $e) {
            // no level child identifier
            $childCount = count($this->children);
            $levelChildIdentifier = $childCount + 1;
            $treeNode->setLevelChildIdentifier($levelChildIdentifier);
            $treeNode->setParent($this);
            $this->children[$levelChildIdentifier] = $treeNode;
            return $this;
        }
        $actualTreeNode = $this->children[$levelChildIdentifier];
        if ($actualTreeNode !== $treeNode) {
            throw new ExceptionBadState("The node ($treeNode) was already added but not on this level");
        }
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

        $treeIdentifier = $this->getLevelChildIdentifier();
        $parent = $this;
        while (true) {
            try {
                $parent = $parent->getParent();
                $parentLevelNodeIdentifier = $parent->getLevelChildIdentifier();
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

    private function setLevelChildIdentifier(int $levelIdentifier)
    {
        $this->levelChildIdentifier = $levelIdentifier;
    }

    /**
     * @throws ExceptionNotFound
     */
    private function getLevelChildIdentifier(): int
    {
        if (!isset($this->levelChildIdentifier)) {
            throw new ExceptionNotFound("No child identifier level found");
        }
        return $this->levelChildIdentifier;
    }


}
