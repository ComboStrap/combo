<?php

namespace ComboStrap;

/**
 * A class to modeling a tree
 */
class TreeNode
{

    private $id;
    /**
     * @var TreeNode[]
     */
    private array $children = [];

    private ?TreeNode $parent;
    private string $globalId;
    private $content;

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


    public static function createTreeRoot(string $id = "root"): TreeNode
    {
        return TreeNode::create($id, null);
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
                $container = $container->appendNode($parentName);
            }
            $container->appendNode($path->getLastNameWithoutExtension());
        }
        return $tree;
    }

    public function appendContainer(string $identifier): TreeNode
    {
        return $this->appendNode($identifier);
    }

    public function appendNode(string $levelIdentifier): TreeNode
    {
        $treeNode = $this->children[$levelIdentifier];
        if ($treeNode === null) {
            $treeNode = TreeNode::create($levelIdentifier, $this);
            $this->children[$levelIdentifier] = $treeNode;
        }
        return $treeNode;
    }

    public function appendChild(TreeNode $treeNode): TreeNode
    {
        $identifier = $treeNode->getIdentifier();
        $actualTreeNode = $this->children[$identifier];
        if ($actualTreeNode === null) {
            $this->children[$identifier] = $treeNode;
        }
        return $this;
    }


    public
    static function createFromWikiPath(string $wikiPath = ":"): TreeNode
    {
        $rootSpace = DokuPath::createPagePathFromId($wikiPath);
        $root = TreeNode::createTreeRoot($wikiPath)
            ->setContent($rootSpace);
        self::buildTreeFromWikiFileSystemRecursively($root);
        return $root;
    }

    private
    static function buildTreeFromWikiFileSystemRecursively(TreeNode $treeNode)
    {
        $wikiPath = $treeNode->getContent();
        $wikiPathChildren = FileSystems::getChildren($wikiPath);
        foreach ($wikiPathChildren as $wikiPathChild) {
            $childNode = $treeNode
                ->appendNode($wikiPathChild->getLastName())
                ->setContent($wikiPathChild);
            if (FileSystems::isDirectory($wikiPathChild)) {
                self::buildTreeFromWikiFileSystemRecursively($childNode);
            }
        }
    }

    public
    function __toString()
    {
        return $this->globalId;
    }


    public
    function getChildCount(): int
    {
        return sizeof($this->children);
    }

    /**
     * @return TreeNode[] - empty array for a leaf
     */
    public
    function getChildren(): array
    {
        return $this->children;
    }


    private function getGlobalId(): string
    {
        return $this->globalId;
    }

    /**
     * @param $content
     * @return TreeNode
     */
    public function setContent($content): TreeNode
    {
        $this->content = $content;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    public function hasChildren(): bool
    {
        return sizeof($this->children) > 0;
    }

    public function hasParent(): bool
    {
        return $this->parent !== null;
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

    private function getIdentifier(): string
    {
        return $this->id;
    }

}
