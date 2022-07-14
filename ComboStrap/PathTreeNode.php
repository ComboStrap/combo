<?php

namespace ComboStrap;

class PathTreeNode extends TreeNode
{


    const CANONICAL = "path-tree";

    private Path $path;

    public function __construct(?Path $path)
    {
        $this->path = $path;
    }

    /**
     * Build the tree from the file systems with the {@link FileSystems::getChildren()}
     * function
     */
    public static function buildTreeViaFileSystemChildren(Path $rootSpace = null): PathTreeNode
    {
        $root = PathTreeNode::createPathTreeNodeFromPath($rootSpace);
        self::buildTreeFromFileSystemRecursively($root);
        return $root;
    }

    /**
     * Build the tree from the file systems with the {@link FileSystems::getChildren()}
     * function
     *
     * @param PathTreeNode $parentPathTreeNode
     * @return void
     */
    public static function buildTreeFromFileSystemRecursively(PathTreeNode $parentPathTreeNode)
    {
        $parentPath = $parentPathTreeNode->getPath();
        $childrenPath = FileSystems::getChildren($parentPath);
        foreach ($childrenPath as $childPath) {
            $childTreeNode = PathTreeNode::createPathTreeNodeFromPath($childPath);
            try {
                $parentPathTreeNode->appendChild($childTreeNode);
            } catch (ExceptionBadState $e) {
                throw new ExceptionRuntimeInternal("We shouldn't add the node two times. ", self::CANONICAL, 1, $e);
            }
            if (FileSystems::isDirectory($childPath)) {
                self::buildTreeFromFileSystemRecursively($childTreeNode);
            }
        }
    }


    private static function createPathTreeNodeFromPath(Path $path): PathTreeNode
    {
        return new PathTreeNode($path);
    }

    /**
     * Build the tree from a array of ids. The build is done via the {@link Path::getParent()}
     * @param array $ids
     * @return TreeNode
     */
    public static function buildTreeViaParentPath(array $ids): TreeNode
    {

        $rootNode = null;
        /**
         * @var PathTreeNode[]
         */
        $nodeByIds = [];
        foreach ($ids as $id) {
            $path = WikiPath::createPagePathFromId($id);
            $actualNode = $nodeByIds[$path->getWikiId()];
            if ($actualNode === null) {
                $actualNode = PathTreeNode::createPathTreeNodeFromPath($path);
                $nodeByIds[$path->getWikiId()] = $actualNode;
            }
            while (true) {
                try {
                    /**
                     * @var WikiPath $parentPath
                     */
                    $parentPath = $actualNode->getPath()->getParent();
                    $parentPathNode = $nodeByIds[$parentPath->getWikiId()];
                    if ($parentPathNode === null) {
                        $parentPathNode = PathTreeNode::createPathTreeNodeFromPath($parentPath);
                        $nodeByIds[$parentPath->getWikiId()] = $parentPathNode;
                    }
                    $parentPathNode->appendChild($actualNode);

                    // Loop
                    $actualNode = $parentPathNode;
                } catch (ExceptionNotFound $e) {
                    break;
                }
            }
            if ($rootNode === null) {
                $rootNode = $actualNode;
            }

        }
        return $rootNode;
    }

    public function getPath(): Path
    {
        return $this->path;
    }

    function getTreeIdentifier(): string
    {
        return $this->path->toPathString();
    }


}
