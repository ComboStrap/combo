<?php

namespace ComboStrap;

/**
 * A class to modeling a tree
 */
class TreeNode
{


    const CONTAINER = "directory";
    const LEAF = "file";

    private $id;
    private $children = [];
    /**
     * @var string
     */
    private $type;
    private $parent;
    private $globalId;

    /**
     * @param string $id
     * @param TreeNode|null $parent
     */
    public function __construct(string $id, ?TreeNode $parent)
    {
        $this->id = $id;
        $this->parent = $parent;
        if ($parent != null) {
            $this->globalId = $parent->getGlobalId() . ":" . $id;
        } else {
            $this->globalId = "";
        }
    }


    public static function createTreeRoot(): TreeNode
    {
        return TreeNode::create("root", null)
            ->setType(self::CONTAINER);
    }

    private static function create(string $string, $parent): TreeNode
    {
        return new TreeNode($string, $parent);
    }

    public static function createFromIds(array $ids): TreeNode
    {
        $tree = TreeNode::createTreeRoot();
        foreach ($ids as $id) {
            $path = DokuPath::createPagePathFromId($id);
            $container = $tree;
            foreach ($path->getParent()->getNames() as $parentName) {
                $container = $container->appendContainer($parentName);
            }
            $container->appendLeaf($path->getLastNameWithoutExtension());
        }
        return $tree;
    }

    public function appendContainer(string $identifier): TreeNode
    {
        return $this->appendNode($identifier)
            ->setType(self::CONTAINER);
    }

    private function appendNode(string $string): TreeNode
    {
        $treeNode = $this->children[$string];
        if ($treeNode === null) {
            $treeNode = TreeNode::create($string, $this);
            $this->children[$string] = $treeNode;
        }
        return $treeNode;
    }

    public function appendLeaf(string $identifier): TreeNode
    {
        return $this->appendNode($identifier)
            ->setType(self::LEAF);
    }

    private function setType(string $type): TreeNode
    {
        $this->type = $type;
        return $this;
    }

    public static function print(TreeNode $tree)
    {
        self::printRec($tree, 0);
    }

    public static function printRec(TreeNode $tree, int $level)
    {
        for ($i = 0; $i < $level; $i++) {
            echo " ";
        }
        if ($tree->isContainer()) {
            if ($level !== 0) {
                echo "$tree\n";
            }
            $childLevel = $level++;
            foreach ($tree->getChildren() as $child) {
                self::printRec($child, $childLevel);
            }
        }
        echo "$tree\n";

    }

    public static function createFromWikiPath(string $id = ":"): TreeNode
    {
        $rootSpace = DokuPath::createPagePathFromId($id);
        $ids = [];
        self::gatherWikiIdRecursively($rootSpace, $ids);
        return TreeNode::createFromIds($ids);
    }

    private static function gatherWikiIdRecursively(DokuPath $dokuPath, array &$ids)
    {
        foreach ($dokuPath->getChildren() as $child) {
            if (FileSystems::isDirectory($child)) {
                self::gatherWikiIdRecursively($child, $ids);
            } else {
                $ids[] = $child->getDokuwikiId();
            }
        }
    }

    public function __toString()
    {
        return $this->globalId;
    }


    public function getChildCount(): int
    {
        return sizeof($this->children);
    }

    public function getChildren()
    {
        return $this->children;
    }

    public function getDescendantCount(): int
    {
        $count = -1;
        self::visitAndCountDescendant($this, $count);
        return $count;
    }

    static private function visitAndCountDescendant(TreeNode $node, &$count)
    {
        $count++;
        if ($node->isContainer()) {
            foreach ($node->getChildren() as $child) {
                self::visitAndCountDescendant($child, $count);
            }
        }
    }

    private function isLeaf(): bool
    {
        return $this->type === self::LEAF;
    }

    private function isContainer(): bool
    {
        return $this->type === self::CONTAINER;
    }

    private function getGlobalId(): string
    {
        return $this->globalId;
    }

}
