<?php
/**
 * Plugin minimap : Displays mini-map for namespace
 *
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author  Nicolas GERARD
 */

use ComboStrap\LinkUtility;
use ComboStrap\PluginUtility;

if (!defined('DOKU_INC')) die();


class syntax_plugin_combo_minimap extends DokuWiki_Syntax_Plugin
{

    const MINIMAP_TAG_NAME = 'minimap';
    const INCLUDE_DIRECTORY_PARAMETERS = 'includedirectory';
    const SHOW_HEADER = 'showheader';
    const NAMESPACE_KEY_ATT = 'namespace';
    const POWERED_BY = 'poweredby';

    const STYLE_SNIPPET = <<<EOF
<style>
.nicon_folder_open {
    background-image: url('data:image/svg+xml;charset=utf8,<svg xmlns="http://www.w3.org/2000/svg" width="250" height="195"><g fill="rgb(204,204,204)" transform="translate(-7.897 -268.6)"><rect rx="0" y="286.829" x="12.897" height="175" width="200" opacity=".517"/><path d="M13.23 458.808l39.687-132.291h198.437l-39.687 132.291z" fill-rule="evenodd"/><rect rx="0" y="273.6" x="39.688" height="13" width="90"/></g></svg>');
    display: inline-block;
    width: 1.5em;
    height: 1em;
    vertical-align: middle;
    content: "";
    background-size: 100% 100%;
}
#minimap__plugin {
    font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
    font-size: 14px;
    line-height: 1.42857;
}

#minimap__plugin .panel-default {
    border-color: #ddd;
    box-sizing: border-box;
}

#minimap__plugin .panel {
    box-sizing: border-box;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    -moz-border-bottom-colors: none;
    -moz-border-left-colors: none;
    -moz-border-right-colors: none;
    -moz-border-top-colors: none;
    background-color: #fff;
    border-image-outset: 0 0 0 0;
    border-image-repeat: stretch stretch;
    border-image-slice: 100% 100% 100% 100%;
    border-image-source: none;
    border-image-width: 1 1 1 1;
    border-radius: 4px;
    border: 1px solid;
    margin-bottom: 20px;
    display: block;
    color: #ddd;
}

#minimap__plugin .panel-default > .panel-heading {
    background: #f5f5f5 linear-gradient(to bottom, #f5f5f5 0px, #e8e8e8 100%) repeat-x;
    border-color: #ddd;
    color: #333;
}

#minimap__plugin .panel-heading {
    border-bottom: 1px solid;
    border-top-left-radius: 3px;
    border-top-right-radius: 3px;
    padding: 10px 15px;
    box-sizing: border-box;
    display: block;
    font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
    font-size: 14px;
    line-height: 1.42857;
}

#minimap__plugin .panel > .list-group, #minimap__plugin .panel > .panel-collapse > .list-group {
    margin-bottom: 0;
}

#minimap__plugin .list-group {
    border-radius: 4px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.075);
    padding-left: 0;
    box-sizing: border-box;
    color: #333;
}

#minimap__plugin .panel-heading + .list-group .list-group-item:first-child {
    border-top-width: 0;
}

#minimap__plugin .panel > .list-group .list-group-item,
#minimap__plugin .panel > .panel-collapse > .list-group .list-group-item {
    border-bottom-width: 1px;
    border-left-width: 0;
    border-right-width: 0;
    border-radius: 0;
}

#minimap__plugin .list-group-item {
    -moz-border-bottom-colors: none;
    -moz-border-left-colors: none;
    -moz-border-right-colors: none;
    -moz-border-top-colors: none;
    background-color: #fff;
    border-image-outset: 0 0 0 0;
    border-image-repeat: stretch stretch;
    border-image-slice: 100% 100% 100% 100%;
    border-image-source: none;
    border-image-width: 1 1 1 1;
    /*border: solid #ddd;*/
    display: block;
    padding: 10px 15px;
    position: relative;
    box-sizing: border-box;
    margin: 0 0 -1px;
}

#minimap__plugin .label-primary {
    background-color: #337ab7;
}

#minimap__plugin .label {
    border-radius: 0.25em;
    color: #fff;
    display: inline;
    font-size: 75%;
    font-weight: 700;
    line-height: 1;
    padding: 0.2em 0.6em 0.3em;
    text-align: center;
    vertical-align: baseline;
    white-space: nowrap;
    box-sizing: border-box;
}

/* Active link css */
#minimap__plugin .list-group-item.active,
#minimap__plugin .list-group-item.active:focus,
#minimap__plugin .list-group-item.active:hover {
    background: #f5f5f5 linear-gradient(to bottom, #f5f5f5 0px, #e8e8e8 100%) repeat-x;
    border-color: #ddd;
    color: #333;
    text-shadow: none;
}

#minimap__plugin .list-group-item.active {
    background-color: #e8e8e8 ! important;
    z-index: 2;
}


#minimap__plugin .panel-body {
    clear: both;
    content: " ";
    box-sizing: border-box;
    display: table;
    padding: 15px;
    unicode-bidi: -moz-isolate;
    color: #333;
}

#minimap__plugin .glyphicon {
    /*already same color than the header*/
    color: #d8d2d2;
}

#minimap__plugin .panel-footing {
    display: flex;
    padding: 0.10rem;
    background: #f5f5f5 linear-gradient(to bottom,#f5f5f5 0px,#e8e8e8 100%) repeat-x;
}

#minimap__plugin .minimap_badge {

    border-radius: 0.25em;
    background-color: #d8d2d2;
    font-size: 0.6rem;
    padding: 0.25rem;
    margin-left: auto !important;
    margin-right: 0.3rem;
    color: #1d4a71;
    margin: 0.1rem;
}
</style>
EOF;


    function connectTo($aMode)
    {
        $pattern = '<' . self::MINIMAP_TAG_NAME . '[^>]*>';
        $this->Lexer->addSpecialPattern($pattern, $aMode, PluginUtility::getModeForComponent($this->getPluginComponent()));
    }

    function getSort()
    {
        /**
         * One less than the old one
         */
        return 149;
    }

    /**
     * No p element please
     * @return string
     */
    function getPType()
    {
        return 'block';
    }

    function getType()
    {
        // The spelling is wrong but this is a correct value
        // https://www.dokuwiki.org/devel:syntax_plugins#syntax_types
        return 'substition';
    }

    /**
     *
     * The handle function goal is to parse the matched syntax through the pattern function
     * and to return the result for use in the renderer
     * This result is always cached until the page is modified.
     * @param string $match
     * @param int $state
     * @param int $pos
     * @param Doku_Handler $handler
     * @return array|bool
     * @see DokuWiki_Syntax_Plugin::handle()
     *
     */
    function handle($match, $state, $pos, Doku_Handler $handler)
    {

        switch ($state) {

            // As there is only one call to connect to in order to a add a pattern,
            // there is only one state entering the function
            // but I leave it for better understanding of the process flow
            case DOKU_LEXER_SPECIAL :

                // Parse the parameters
                $match = substr($match, 8, -1); //9 = strlen("<minimap")

                // Init
                $parameters = array();
                $parameters['substr'] = 1;
                $parameters[self::INCLUDE_DIRECTORY_PARAMETERS] = $this->getConf(self::INCLUDE_DIRECTORY_PARAMETERS);
                $parameters[self::SHOW_HEADER] = $this->getConf(self::SHOW_HEADER);


                // /i not case sensitive
                $attributePattern = "\\s*(\w+)\\s*=\\s*[\'\"]{1}([^\`\"]*)[\'\"]{1}\\s*";
                $result = preg_match_all('/' . $attributePattern . '/i', $match, $matches);
                if ($result != 0) {
                    foreach ($matches[1] as $key => $parameterKey) {
                        $parameter = strtolower($parameterKey);
                        $value = $matches[2][$key];
                        if (in_array($parameter, [self::SHOW_HEADER, self::INCLUDE_DIRECTORY_PARAMETERS])) {
                            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                        }
                        $parameters[$parameter] = $value;
                    }
                }
                // Cache the values
                return array($state, $parameters);

        }

        return false;
    }


    function render($mode, Doku_Renderer $renderer, $data)
    {

        // The $data variable comes from the handle() function
        //
        // $mode = 'xhtml' means that we output html
        // There is other mode such as metadata where you can output data for the headers (Not 100% sure)
        if ($mode == 'xhtml') {

            /** @var Doku_Renderer_xhtml $renderer */

            // Unfold the $data array in two separates variables
            list($state, $parameters) = $data;

            // As there is only one call to connect to in order to a add a pattern,
            // there is only one state entering the function
            // but I leave it for better understanding of the process flow
            switch ($state) {

                case DOKU_LEXER_SPECIAL :

                    if (!PluginUtility::htmlSnippetAlreadyAdded($renderer->info,self::MINIMAP_TAG_NAME)){
                        $renderer->doc .= self::STYLE_SNIPPET;
                    };

                    global $ID;
                    global $INFO;
                    $callingId = $ID;
                    // If mini-map is in a sidebar, we don't want the ID of the sidebar
                    // but the ID of the page.
                    if ($INFO != null) {
                        $callingId = $INFO['id'];
                    }

                    $nameSpacePath = getNS($callingId); // The complete path to the directory
                    if (array_key_exists(self::NAMESPACE_KEY_ATT, $parameters)) {
                        $nameSpacePath = $parameters[self::NAMESPACE_KEY_ATT];
                    }
                    $currentNameSpace = curNS($callingId); // The name of the container directory
                    $includeDirectory = $parameters[self::INCLUDE_DIRECTORY_PARAMETERS];
                    $pagesOfNamespace = $this->getNamespaceChildren($nameSpacePath, $sort = 'natural', $listdirs = $includeDirectory);

                    // Set the two possible home page for the namespace ie:
                    //   - the name of the containing map ($homePageWithContainingMapName)
                    //   - the start conf parameters ($homePageWithStartConf)
                    global $conf;
                    $parts = explode(':', $nameSpacePath);
                    $lastContainingNameSpace = $parts[count($parts) - 1];
                    $homePageWithContainingMapName = $nameSpacePath . ':' . $lastContainingNameSpace;
                    $startConf = $conf['start'];
                    $homePageWithStartConf = $nameSpacePath . ':' . $startConf;

                    // Build the list of page
                    $miniMapList = '<ul class="list-group">';
                    $pageNum = 0;
                    $startPageFound = false;
                    $homePageFound = false;
                    //$pagesCount = count($pagesOfNamespace); // number of pages in the namespace
                    foreach ($pagesOfNamespace as $pageArray) {

                        // The title of the page
                        $title = '';

                        // If it's a directory
                        if ($pageArray['type'] == "d") {

                            $pageId = $this->getNamespaceStartId($pageArray['id']);

                        } else {

                            $pageNum++;
                            $pageId = $pageArray['id'];

                        }
                        $link = new LinkUtility($pageId);


                        /**
                         * Set name and title
                         */
                        // Name if the variable that it's shown. A part of it can be suppressed
                        // Title will stay full in the link
                        $h1TargetPage = $link->getInternalPage()->getH1();
                        $title = $link->getInternalPage()->getTitle();

                        $link->setName(noNSorNS($pageId));
                        if ($h1TargetPage !=null) {
                            $link->setName($h1TargetPage);
                        } else {
                            if ($title!=null) {
                                $link->setName($title);
                            }
                        }
                        $link->setTitle(noNSorNS($pageId));
                        if ($title!=null) {
                            $link->setTitle($title);
                        }

                        // If debug mode
                        if ($parameters['debug']) {
                            $link->setTitle($link->getTitle().' (' . $pageId . ')');
                        }

                        // Add the page number in the URL title
                        $link->setTitle($link->getTitle() .' (' . $pageNum . ')');

                        // Suppress the parts in the name with the regexp defines in the 'suppress' params
                        if ($parameters['suppress']) {
                            $substrPattern = '/' . $parameters['suppress'] . '/i';
                            $replacement = '';
                            $name = preg_replace($substrPattern, $replacement, $link->getName());
                            $link->setName($name);
                        }

                        // See in which page we are
                        // The style will then change
                        $active = '';
                        if ($callingId == $pageId) {
                            $active = 'active';
                        }

                        // Not all page are printed
                        // sidebar are not for instance

                        // Are we in the root ?
                        if ($pageArray['ns']) {
                            $nameSpacePathPrefix = $pageArray['ns'] . ':';
                        } else {
                            $nameSpacePathPrefix = '';
                        }
                        $print = true;
                        if ($pageArray['id'] == $nameSpacePathPrefix . $currentNameSpace) {
                            // If the start page exists, the page with the same name
                            // than the namespace must be shown
                            if (page_exists($nameSpacePathPrefix . $startConf)) {
                                $print = true;
                            } else {
                                $print = false;
                            }
                            $homePageFound = true;
                        } else if ($pageArray['id'] == $nameSpacePathPrefix . $startConf) {
                            $print = false;
                            $startPageFound = true;
                        } else if ($pageArray['id'] == $nameSpacePathPrefix . $conf['sidebar']) {
                            $pageNum -= 1;
                            $print = false;
                        };


                        // If the page must be printed, build the link
                        if ($print) {

                            // Open the item tag
                            $miniMapList .= "<li class=\"list-group-item " . $active . "\">";

                            // Add a glyphicon if it's a directory
                            if ($pageArray['type'] == "d") {
                                $miniMapList .= "<span class=\"nicon_folder_open\" aria-hidden=\"true\"></span>&nbsp;&nbsp;";
                            }

                            $miniMapList .= $link->render($renderer);;


                            // Close the item
                            $miniMapList .= "</li>";

                        }

                    }
                    $miniMapList .= '</ul>'; // End list-group


                    // Build the panel header
                    $miniMapHeader = "";
                    $startId = "";
                    if ($startPageFound) {
                        $startId = $homePageWithStartConf;
                    } else {
                        if ($homePageFound) {
                            $startId = $homePageWithContainingMapName;
                        }
                    }

                    $panelHeaderContent = "";
                    if ($startId == "") {
                        if ($parameters[self::SHOW_HEADER] == true) {
                            $panelHeaderContent = 'No Home Page found';
                        }
                    } else {
                        $startLink = new LinkUtility($startId);
                        $startLink->setName($startId);
                        $h1 = $startLink->getInternalPage()->getH1();
                        if ($h1!=null){
                            $startLink->setName($h1);
                        }
                        $panelHeaderContent = $startLink->render($renderer);
                        // We are not counting the header page
                        $pageNum--;
                    }

                    if ($panelHeaderContent != "") {
                        $miniMapHeader .= '<div class="panel-heading">' . $panelHeaderContent . '  <span class="label label-primary">' . $pageNum . ' pages</span></div>';
                    }

                    if ($parameters['debug']) {
                        $miniMapHeader .= '<div class="panel-body">' .
                            '<B>Debug Information:</B><BR>' .
                            'CallingId: (' . $callingId . ')<BR>' .
                            'Suppress Option: (' . $parameters['suppress'] . ')<BR>' .
                            '</div>';
                    }

                    // Header + list
                    $renderer->doc .= '<div id="minimap__plugin"><div class="panel panel-default">'
                        . $miniMapHeader
                        . $miniMapList
                        . '</div></div>';
                    break;
            }

            return true;
        }
        return false;

    }

    /**
     * Return all pages and/of sub-namespaces (subdirectory) of a namespace (ie directory)
     * Adapted from feed.php
     *
     * @param $namespace The container of the pages
     * @param string $sort 'natural' to use natural order sorting (default); 'date' to sort by filemtime
     * @param $listdirs - Add the directory to the list of files
     * @return array An array of the pages for the namespace
     */
    function getNamespaceChildren($namespace, $sort = 'natural', $listdirs = false)
    {
        require_once(DOKU_INC . 'inc/search.php');
        global $conf;

        $ns = ':' . cleanID($namespace);
        // ns as a path
        $ns = utf8_encodeFN(str_replace(':', '/', $ns));

        $data = array();

        // Options of the callback function search_universal
        // in the search.php file
        $search_opts = array(
            'depth' => 1,
            'pagesonly' => true,
            'listfiles' => true,
            'listdirs' => $listdirs,
            'firsthead' => true
        );
        // search_universal is a function in inc/search.php that accepts the $search_opts parameters
        search($data, $conf['datadir'], 'search_universal', $search_opts, $ns, $lvl = 1, $sort);

        return $data;
    }

    /**
     * Return the id of the start page of a namespace
     *
     * @param $id an id of a namespace (directory)
     * @return string the id of the home page
     */
    function getNamespaceStartId($id)
    {

        global $conf;

        $id = $id . ":";

        if (page_exists($id . $conf['start'])) {
            // start page inside namespace
            $homePageId = $id . $conf['start'];
        } elseif (page_exists($id . noNS(cleanID($id)))) {
            // page named like the NS inside the NS
            $homePageId = $id . noNS(cleanID($id));
        } elseif (page_exists($id)) {
            // page like namespace exists
            $homePageId = substr($id, 0, -1);
        } else {
            // fall back to default
            $homePageId = $id . $conf['start'];
        }
        return $homePageId;
    }

    /**
     * @param $get_called_class
     * @return string
     */
    public static function getTagName($get_called_class)
    {
        list(/* $t */, /* $p */, $c) = explode('_', $get_called_class, 3);
        return (isset($c) ? $c : '');
    }

    /**
     * @return string - the tag
     */
    public static function getTag()
    {
        return self::getTagName(get_called_class());
    }


}
