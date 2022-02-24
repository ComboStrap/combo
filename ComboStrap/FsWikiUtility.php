<?php
/**
 * Copyright (c) 2020. ComboStrap, Inc. and its affiliates. All Rights Reserved.
 *
 * This source code is licensed under the GPL license found in the
 * COPYING  file in the root directory of this source tree.
 *
 * @license  GPL 3 (https://www.gnu.org/licenses/gpl-3.0.en.html)
 * @author   ComboStrap <support@combostrap.com>
 *
 */

namespace ComboStrap;

/**
 * Class PageUtility
 * @package ComboStrap
 * See also {@link pageutils.php}
 */
class FsWikiUtility
{


    /**
     * Determine if the current page is a sidebar (a bar)
     * @return bool
     * TODO: Duplicate of {@link Page::isSecondarySlot()}
     */
    public static function isSideBar()
    {
        global $INFO;
        global $ID;
        $isSidebar = false;
        if ($INFO != null) {
            $id = $INFO['id'];
            if ($ID != $id) {
                $isSidebar = TRUE;
            }
        }
        return $isSidebar;
    }


    /**
     * Return all pages and/of sub-namespaces (subdirectory) of a namespace (ie directory)
     * Adapted from feed.php
     *
     * @param string $path The container of the pages
     * @return array An array of the pages for the namespace
     */
    static function getChildren(string $path): array
    {
        require_once(__DIR__ . '/../../../../inc/search.php');
        global $conf;


        /**
         * To a relative file system path
         */
        $dokuPath = DokuPath::createPagePathFromPath($path);
        $relativeFileSystemPath = str_replace(":", "/", $dokuPath->getDokuwikiId());


        $data = array();

        // Options of the callback function search_universal
        // in the search.php file
        $search_opts = array(
            'depth' => 1,
            'pagesonly' => true,
            'listfiles' => true,
            'listdirs' => true,
            'skipacl' => true
            //'firsthead' => true
        );
        // search_universal is a function in inc/search.php that accepts the $search_opts parameters
        search($data, // The returned data
            $conf['datadir'], // The root
            'search_universal', // The recursive function (callback)
            $search_opts, // The options given to the recursive function
            $relativeFileSystemPath, // The id
            1 // Only one level in the tree
        );

        return $data;
    }

    /**
     * Return the page index of a namespace or null if it does not exist
     * ie the index.html
     * @param $namespacePath - in dokuwiki format
     * @return string - the dokuwiki path
     * @deprecated use {@link Page::getHomePageFromNamespace()} instead
     */
    public static function getHomePagePath($namespacePath): ?string
    {
        $homePage = Page::getHomePageFromNamespace($namespacePath);
        if ($homePage->exists()) {
            return $homePage->getAbsolutePath();
        } else {
            return null;
        }
    }

    public static function getChildrenNamespace($nameSpacePath)
    {
        require_once(__DIR__ . '/../../../../inc/search.php');
        global $conf;

        $data = array();

        // Options of the callback function search_universal
        // in the search.php file
        $search_opts = array();
        // search_universal is a function in inc/search.php that accepts the $search_opts parameters
        search_namespaces($data, // The returned data
            $conf['datadir'], // The root
            $nameSpacePath, // The directory to search
            'd',
            1, // Only one level in the tree
            $search_opts
        );

        return $data;
    }

    /**
     * @param $namespacePath
     * @return Page|null the page path of the parent or null if it does not exist
     */
    public static function getParentPagePath($namespacePath): ?Page
    {

        /**
         * Root case
         */
        if ($namespacePath === ":") {
            return null;
        }

        /**
         * A namespace path does not have a `:` at the end
         * only for the root
         */
        $pos = strrpos($namespacePath, ':');
        if ($pos !== false) {
            if ($pos == 0) {
                $parentNamespacePath = ":";
            } else {
                $parentNamespacePath = substr($namespacePath, 0, $pos);
            }
            return Page::getHomePageFromNamespace($parentNamespacePath);

        } else {
            return null;
        }


    }


    /**
     * Find the pages in the tree
     * @param $startPath
     * @param int $depth
     * @return array
     */
    public static function getPages($startPath, int $depth = 0): array
    {

        if ($startPath === null || $startPath === "") {
            throw new \RuntimeException("A start path is mandatory");
        }


        // Run as admin to overcome the fact that
        // anonymous user cannot set all links and backlinks
        global $conf;
        $dataDir = $conf['datadir'];

        $pages = array();

        // This is a page
        if (page_exists($startPath)) {
            $pages[] = array(
                'id' => $startPath,
                'ns' => getNS($startPath),
                'title' => p_get_first_heading($startPath, false),
                'size' => filesize(wikiFN($startPath)),
                'mtime' => filemtime(wikiFN($startPath)),
                'perm' => 16,
                'type' => 'f',
                'level' => 0,
                'open' => 1,
            );
        } else {

            $startPath = str_replace(':', '/', $startPath);

            /**
             * Directory
             */
            search(
                $pages,
                $dataDir,
                'search_universal',
                array(
                    'depth' => $depth,
                    'listfiles' => true,
                    'listdirs' => false,
                    'pagesonly' => true,
                    'skipacl' => true,
                    'firsthead' => false,
                    'meta' => false,
                ),
                $startPath
            );
        }
        return $pages;
    }

}
