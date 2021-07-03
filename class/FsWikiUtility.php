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
     * Return the main page id
     * (Not the sidebar)
     * @return mixed|string
     */
    public static function getMainPageId()
    {
        global $ID;
        global $INFO;
        $callingId = $ID;
        // If the component is in a sidebar, we don't want the ID of the sidebar
        // but the ID of the page.
        if ($INFO != null) {
            $callingId = $INFO['id'];
        }
        /**
         * This is the case with event triggered
         * before DokuWiki such as
         * https://www.dokuwiki.org/devel:event:init_lang_load
         */
        if ($callingId == null) {
            global $_REQUEST;
            if (isset($_REQUEST["id"])) {
                $callingId = $_REQUEST["id"];
            }
        }
        return $callingId;
    }

    /**
     * Return all pages and/of sub-namespaces (subdirectory) of a namespace (ie directory)
     * Adapted from feed.php
     *
     * @param string $id The container of the pages
     * @return array An array of the pages for the namespace
     */
    static function getChildren($id)
    {
        require_once(__DIR__ . '/../../../../inc/search.php');
        global $conf;

        $ns = ':' . cleanID($id);
        // ns as a path
        $ns = utf8_encodeFN(str_replace(':', '/', $ns));

        $data = array();

        // Options of the callback function search_universal
        // in the search.php file
        $search_opts = array(
            'depth' => 1,
            'pagesonly' => true,
            'listfiles' => true,
            'listdirs' => true,
            'firsthead' => true
        );
        // search_universal is a function in inc/search.php that accepts the $search_opts parameters
        search($data, // The returned data
            $conf['datadir'], // The root
            'search_universal', // The recursive function (callback)
            $search_opts, // The options given to the recursive function
            $ns, // The current directory
            1 // Only one level in the tree
        );

        return $data;
    }

    /**
     * Return the page index of a namespace of null if it does not exist
     * ie the index.html
     * @param $namespaceId
     * @return string|null
     */
    public static function getHomePagePath($namespaceId)
    {
        global $conf;

        if ($namespaceId != ":") {
            $namespaceId = $namespaceId . ":";
        }

        $startPageName = $conf['start'];
        if (page_exists($namespaceId . $startPageName)) {
            // start page inside namespace
            return $namespaceId . $startPageName;
        } elseif (page_exists($namespaceId . noNS(cleanID($namespaceId)))) {
            // page named like the NS inside the NS
            return $namespaceId . noNS(cleanID($namespaceId));
        } elseif (page_exists($namespaceId)) {
            // page like namespace exists
            return substr($namespaceId, 0, -1);
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

}
