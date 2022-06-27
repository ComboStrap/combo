<?php

namespace ComboStrap;

class PathTreeNode extends TreeNode
{


    private Path $path;

    public function __construct(?Path $path)
    {
        $this->path = $path;
    }

    /**
     * Build the tree from the file systems with the {@link FileSystems::getChildren()}
     * function
     */
    public static function buildTreeViaFileSystemChildren(string $wikiPath = ":"): PathTreeNode
    {
        $rootSpace = DokuPath::createPagePathFromId($wikiPath);
        $root = PathTreeNode::createPathTreeNodeFromPath($rootSpace);
        self::buildTreeFromWikiFileSystemRecursively($root);
        return $root;
    }

    /**
     * Build the tree from the file systems with the {@link FileSystems::getChildren()}
     * function
     *
     * @param PathTreeNode $parentPathTreeNode
     * @return void
     */
    public static function buildTreeFromWikiFileSystemRecursively(PathTreeNode $parentPathTreeNode)
    {
        $parentPath = $parentPathTreeNode->getPath();
        $childrenPath = FileSystems::getChildren($parentPath);
        foreach ($childrenPath as $childPath) {
            $childTreeNode = PathTreeNode::createPathTreeNodeFromPath($childPath);
            $parentPathTreeNode->appendChild($childTreeNode);
            if (FileSystems::isDirectory($childPath)) {
                self::buildTreeFromWikiFileSystemRecursively($childTreeNode);
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
            $path = DokuPath::createPagePathFromId($id);
            $actualNode = $nodeByIds[$path->getWikiId()];
            if ($actualNode === null) {
                $actualNode = PathTreeNode::createPathTreeNodeFromPath($path);
                $nodeByIds[$path->getWikiId()] = $actualNode;
            }
            while (true) {
                try {
                    /**
                     * @var DokuPath $parentPath
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
