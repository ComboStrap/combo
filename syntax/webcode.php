<?php
/**
 * Copyright (c) 2021. ComboStrap, Inc. and its affiliates. All Rights Reserved.
 *
 * This source code is licensed under the GPL license found in the
 * COPYING  file in the root directory of this source tree.
 *
 * @license  GPL 3 (https://www.gnu.org/licenses/gpl-3.0.en.html)
 * @author   ComboStrap <support@combostrap.com>
 *
 */

/**
 * Plugin Webcode: Show webcode (Css, HTML) in a iframe
 *
 */

// must be run within Dokuwiki
use ComboStrap\LogUtility;
use ComboStrap\PluginUtility;
use ComboStrap\Tag;
use ComboStrap\TagAttributes;

if (!defined('DOKU_INC')) die();

/**
 * Webcode
 */
class syntax_plugin_combo_webcode extends DokuWiki_Syntax_Plugin
{

    const EXTERNAL_RESOURCES_ATTRIBUTE_DISPLAY = 'externalResources'; // In the action bar
    const EXTERNAL_RESOURCES_ATTRIBUTE_KEY = 'externalresources'; // In the code

    // Simple cache bursting implementation for the webCodeConsole.(js|css) file
    // They must be incremented manually when they changed
    const WEB_CSS_VERSION = 1.1;
    const WEB_CONSOLE_JS_VERSION = 2.1;

    const TAG = 'webcode';

    /**
     * The tag that have codes
     */
    const CODE_TAGS = array("code", "plugin_combo_code");

    /**
     * The attribute names in the array
     */
    const CODES_ATTRIBUTE = "codes";
    const USE_CONSOLE_ATTRIBUTE = "useConsole";
    const RENDERINGMODE_ATTRIBUTE = 'renderingmode';
    const RENDERING_ONLY_RESULT = "onlyresult";

    /**
     * @var array that holds the iframe attributes
     */
    private $attributes = array();


    /**
     * Syntax Type.
     *
     * Needs to return one of the mode types defined in $PARSER_MODES in parser.php
     * @see https://www.dokuwiki.org/devel:syntax_plugins#syntax_types
     *
     * container because it may contain header in case of how to
     */
    public function getType()
    {
        return 'container';
    }

    /**
     * @return array
     * Allow which kind of plugin inside
     *
     * array('container', 'baseonly','formatting', 'substition', 'protected', 'disabled', 'paragraphs')
     *
     */
    public function getAllowedTypes()
    {
        return array('container', 'baseonly', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs');
    }


    public function accepts($mode)
    {

        return syntax_plugin_combo_preformatted::disablePreformatted($mode);

    }

    /**
     * @see Doku_Parser_Mode::getSort()
     * The mode (plugin) with the lowest sort number will win out
     *
     * See {@link Doku_Parser_Mode_code}
     */
    public function getSort()
    {
        return 99;
    }

    /**
     * Called before any calls to ConnectTo
     * @return void
     */
    function preConnect()
    {
    }

    /**
     * Create a pattern that will called this plugin
     *
     * @param string $mode
     *
     * All dokuwiki mode can be seen in the parser.php file
     * @see Doku_Parser_Mode::connectTo()
     */
    public function connectTo($mode)
    {

        $pattern = PluginUtility::getContainerTagPattern(self::TAG);
        $this->Lexer->addEntryPattern($pattern, $mode, PluginUtility::getModeForComponent($this->getPluginComponent()));

    }


    // This where the addPattern and addExitPattern are defined
    public function postConnect()
    {
        $this->Lexer->addExitPattern('</' . self::TAG . '>', PluginUtility::getModeForComponent($this->getPluginComponent()));
    }


    /**
     * Handle the match
     * You get the match for each pattern in the $match variable
     * $state says if it's an entry, exit or match pattern
     *
     * This is an instruction block and is cached apart from the rendering output
     * There is two caches levels
     * This cache may be suppressed with the url parameters ?purge=true
     *
     * The returned values are cached in an array that will be passed to the render method
     * The handle function goal is to parse the matched syntax through the pattern function
     * and to return the result for use in the renderer
     * This result is always cached until the page is modified.
     * @param string $match
     * @param int $state
     * @param int $pos
     * @param Doku_Handler $handler
     * @return array|bool
     * @throws Exception
     * @see DokuWiki_Syntax_Plugin::handle()
     *
     */
    public function handle($match, $state, $pos, Doku_Handler $handler)
    {
        switch ($state) {

            case DOKU_LEXER_ENTER :

                // We got the first webcode tag and its attributes

                $match = substr($match, 8, -1); //9 = strlen("<webcode")

                // Reset of the attributes
                // With some framework the php object may be still persisted in memory
                // And you may get some attributes from other page
                $attributes = array();
                $attributes['frameborder'] = 1;
                $attributes['width'] = '100%';

                $renderingModeKey = self::RENDERINGMODE_ATTRIBUTE;
                $attributes[$renderingModeKey] = 'story';

                // config Parameters will get their value in lowercase
                $configAttributes = [$renderingModeKey];

                // /i not case sensitive
                $attributePattern = "\s*(\w+)\s*=\s*\"?([^\"\s]+)\"?\\s*";
                $result = preg_match_all('/' . $attributePattern . '/i', $match, $matches);


                if ($result != 0) {
                    foreach ($matches[1] as $key => $lang) {
                        $attributeKey = strtolower($lang);
                        $attributeValue = $matches[2][$key];
                        if (in_array($attributeKey, $configAttributes)) {
                            $attributeValue = strtolower($attributeValue);
                        }
                        $attributes[$attributeKey] = $attributeValue;
                    }
                }

                // We set the attributes on a class scope
                // to be used in the DOKU_LEXER_UNMATCHED step
                $this->attributes = $attributes;

                // Cache the values to be used by the render method
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $attributes
                );


            case DOKU_LEXER_UNMATCHED :

                return PluginUtility::handleAndReturnUnmatchedData(self::TAG, $match, $handler);


            case DOKU_LEXER_EXIT:

                /**
                 * Capture all codes
                 */
                $codes = array();
                /**
                 * Does the javascript contains a console statement
                 */
                $useConsole = false;
                $exitTag = new Tag(self::TAG, array(), $state, $handler);
                $openingTag = $exitTag->getOpeningTag();
                $renderingMode = strtolower($openingTag->getAttribute(self::RENDERINGMODE_ATTRIBUTE));
                if ($openingTag->hasDescendants()) {
                    $tags = $openingTag->getDescendants();
                    /**
                     * Mime and code content are in two differents
                     * tag. To be able to set the content to the good type
                     * we keep a trace of it
                     */
                    $actualCodeType = "";
                    foreach ($tags as $tag) {
                        if (in_array($tag->getName(), self::CODE_TAGS)) {

                            /**
                             * Only rendering mode
                             * on all node (unmatched also)
                             */
                            if ($renderingMode == self::RENDERING_ONLY_RESULT) {
                                $tag->addAttribute(TagAttributes::DISPLAY, "none");
                            }

                            if ($tag->getState() == DOKU_LEXER_ENTER) {
                                // Get the code (The content between the code nodes)
                                // We ltrim because the match gives us the \n at the beginning and at the end
                                $actualCodeType = strtolower(trim($tag->getType()));

                                // Xml is html
                                if ($actualCodeType == 'xml') {
                                    $actualCodeType = 'html';
                                }
                                // The code for a language may be scattered in multiple block
                                if (!isset($codes[$actualCodeType])) {
                                    $codes[$actualCodeType] = "";
                                }

                                continue;
                            }

                            if ($tag->getState() == DOKU_LEXER_UNMATCHED) {

                                $codeContent = $tag->getData()[PluginUtility::PAYLOAD];

                                if (empty($actualCodeType)) {
                                    LogUtility::msg("The type of the code should not be null for the code content " . $codeContent, LogUtility::LVL_MSG_WARNING, self::TAG);
                                    continue;
                                }

                                // Append it
                                $codes[$actualCodeType] = $codes[$actualCodeType] . $codeContent;

                                // Check if a javascript console function is used, only if the flag is not set to true
                                if (!$useConsole == true) {
                                    if (in_array($actualCodeType, array('babel', 'javascript', 'html', 'xml'))) {
                                        // if the code contains 'console.'
                                        $result = preg_match('/' . 'console\.' . '/is', $codeContent);
                                        if ($result) {
                                            $useConsole = true;
                                        }
                                    }
                                }
                                // Reset
                                $actualCodeType = "";
                            }
                        }
                    }
                }
//                if(isset($codes["dw"])){
//                    // http://php.net/manual/en/function.stream-context-create.php
//                    $dw = $codes["dw"];
//
//                    $url = Site::getAjaxUrl();
//                    $data = array(
//                        action_plugin_combo_webcode::DW_PARAM => $dw,
//                        action_plugin_combo_webcode::CALL_PARAM => action_plugin_combo_webcode::CALL_ID
//                    );
//
//                    // use key 'http' even if you send the request to https://...
//                    $options = array(
//                        'http' => array(
//                            'method'  => 'POST',
//                            'content' => http_build_query($data)
//                        )
//                    );
//                    $context  = stream_context_create($options);
//                    $result = file_get_contents($url, false, $context);
//                    if ($result === FALSE) { /* Handle error */ }
//
//                }
                return array(
                    PluginUtility::STATE => $state,
                    self::CODES_ATTRIBUTE => $codes,
                    self::USE_CONSOLE_ATTRIBUTE => $useConsole
                );

        }
        return false;

    }

    /**
     * Render the output
     * @param string $mode
     * @param Doku_Renderer $renderer
     * @param array $data - what the function handle() return'ed
     * @return bool - rendered correctly (not used)
     *
     * The rendering process
     * @see DokuWiki_Syntax_Plugin::render()
     *
     */
    public function render($mode, Doku_Renderer $renderer, $data)
    {
        // The $data variable comes from the handle() function
        //
        // $mode = 'xhtml' means that we output html
        // There is other mode such as metadata where you can output data for the headers (Not 100% sure)
        if ($mode == 'xhtml') {


            /** @var Doku_Renderer_xhtml $renderer */

            $state = $data[PluginUtility::STATE];
            switch ($state) {

                case DOKU_LEXER_ENTER :

                    PluginUtility::getSnippetManager()->attachJavascriptSnippetForBar(self::TAG);

                    // The extracted data are the attribute of the webcode tag
                    // We put in a class variable so that we can use in the last step (DOKU_LEXER_EXIT)
                    $this->attributes = $data[PluginUtility::ATTRIBUTES];

                    break;

                case DOKU_LEXER_UNMATCHED :

                    $renderer->doc .= PluginUtility::renderUnmatched($data);
                    break;

                case DOKU_LEXER_EXIT :
                    $codes = $data[self::CODES_ATTRIBUTE];
                    // Create the real output of webcode
                    if (sizeof($codes) == 0) {
                        return false;
                    }

                    PluginUtility::getSnippetManager()->attachCssSnippetForBar(self::TAG);

                    // Dokuwiki Code ?
                    if (array_key_exists('dw', $codes)) {

                        $renderer->doc .= PluginUtility::render($codes['dw']);

                    } else {


                        // Js, Html, Css
                        $iframeHtml = '<html><head>';
                        $iframeHtml .= '<meta http-equiv="content-type" content="text/html; charset=UTF-8">';
                        $iframeHtml .= '<title>Made by Webcode</title>';
                        $iframeHtml .= '<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/3.0.3/normalize.min.css">';


                        // External Resources such as css stylesheet or js
                        $externalResources = array();
                        if (array_key_exists(self::EXTERNAL_RESOURCES_ATTRIBUTE_KEY, $this->attributes)) {
                            $externalResources = explode(",", $this->attributes[self::EXTERNAL_RESOURCES_ATTRIBUTE_KEY]);
                        }

                        // Babel Preprocessor, if babel is used, add it to the external resources
                        if (array_key_exists('babel', $codes)) {
                            $babelMin = "https://unpkg.com/babel-standalone@6/babel.min.js";
                            // a load of babel invoke it (be sure to not have it twice
                            if (!(array_key_exists($babelMin, $externalResources))) {
                                $externalResources[] = $babelMin;
                            }
                        }

                        // Add the external resources
                        foreach ($externalResources as $externalResource) {
                            $pathInfo = pathinfo($externalResource);
                            $fileExtension = $pathInfo['extension'];
                            switch ($fileExtension) {
                                case 'css':
                                    $iframeHtml .= '<link rel="stylesheet" type="text/css" href="' . $externalResource . '">';
                                    break;
                                case 'js':
                                    $iframeHtml .= '<script type="text/javascript" src="' . $externalResource . '"></script>';
                                    break;
                            }
                        }


                        // WebConsole style sheet
                        $iframeHtml .= '<link rel="stylesheet" type="text/css" href="' . PluginUtility::getResourceBaseUrl() . '/webcode/webcode-iframe.css?ver=' . self::WEB_CSS_VERSION . '"/>';

                        // A little margin to make it neater
                        // that can be overwritten via cascade
                        $iframeHtml .= '<style>body { margin:10px } /* default margin */</style>';

                        // The css
                        if (array_key_exists('css', $codes)) {
                            $iframeHtml .= '<!-- The CSS code -->';
                            $iframeHtml .= '<style>' . $codes['css'] . '</style>';
                        };
                        $iframeHtml .= '</head><body>';
                        if (array_key_exists('html', $codes)) {
                            $iframeHtml .= '<!-- The HTML code -->';
                            $iframeHtml .= $codes['html'];
                        }
                        // The javascript console area is based at the end of the HTML document
                        $useConsole = $data[self::USE_CONSOLE_ATTRIBUTE];
                        if ($useConsole) {
                            $iframeHtml .= '<!-- WebCode Console -->';
                            $iframeHtml .= '<div><p class=\'webConsoleTitle\'>Console Output:</p>';
                            $iframeHtml .= '<div id=\'webCodeConsole\'></div>';
                            $iframeHtml .= '<script type=\'text/javascript\' src=\'' . PluginUtility::getResourceBaseUrl() . '/webcode/webcode-console.js?ver=' . self::WEB_CONSOLE_JS_VERSION . '\'></script>';
                            $iframeHtml .= '</div>';
                        }
                        // The javascript comes at the end because it may want to be applied on previous HTML element
                        // as the page load in the IO order, javascript must be placed at the end
                        if (array_key_exists('javascript', $codes)) {
                            $iframeHtml .= '<!-- The Javascript code -->';
                            $iframeHtml .= '<script type="text/javascript">' . $codes['javascript'] . '</script>';
                        }
                        if (array_key_exists('babel', $codes)) {
                            $iframeHtml .= '<!-- The Babel code -->';
                            $iframeHtml .= '<script type="text/babel">' . $codes['babel'] . '</script>';
                        }
                        $iframeHtml .= '</body></html>';

                        // Here the magic from the plugin happens
                        // We add the Iframe and the JsFiddleButton
                        $iFrameHtml = '<iframe ';

                        // We add the name HTML attribute
                        $name = "WebCode iFrame";
                        if (array_key_exists('name', $this->attributes)) {
                            $name .= ' ' . $this->attributes['name'];
                        }
                        $iFrameHtml .= ' name="' . $name . '" ';

                        // The class to be able to select them
                        $iFrameHtml .= ' class="webCode" ';

                        // We add the others HTML attributes
                        $iFrameHtmlAttributes = array('width', 'height', 'frameborder', 'scrolling');
                        foreach ($this->attributes as $attribute => $value) {
                            if (in_array($attribute, $iFrameHtmlAttributes)) {
                                $iFrameHtml .= ' ' . $attribute . '=' . $value;
                            }
                        }
                        $iFrameHtml .= ' srcdoc="' . htmlentities($iframeHtml) . '" ></iframe>';//

                        // Credits bar
                        $bar = '<div class="webcode-bar">';
                        $bar .= '<div class="webcode-bar-item">' . PluginUtility::getUrl(self::TAG, "Rendered by Webcode", false) . '</div>';
                        $bar .= '<div class="webcode-bar-item">' . $this->addJsFiddleButton($codes, $this->attributes) . '</div>';
                        $bar .= '</div>';
                        $renderer->doc .= '<div class="webcode">' . $iFrameHtml . $bar . '</div>';
                    }

                    break;
            }

            return true;
        }
        return false;
    }

    /**
     * @param array $codes the array containing the codes
     * @param array $attributes the attributes of a call (for now the externalResources)
     * @return string the HTML form code
     *
     * Specification, see http://doc.jsfiddle.net/api/post.html
     */
    public function addJsFiddleButton($codes, $attributes)
    {

        $postURL = "https://jsfiddle.net/api/post/library/pure/"; //No Framework

        $externalResources = array();
        if (array_key_exists(self::EXTERNAL_RESOURCES_ATTRIBUTE_KEY, $attributes)) {
            $externalResources = explode(",", $attributes[self::EXTERNAL_RESOURCES_ATTRIBUTE_KEY]);
        }


        if ($this->useConsole) {
            // If their is a console.log function, add the Firebug Lite support of JsFiddle
            // Seems to work only with the Edge version of jQuery
            // $postURL .= "edge/dependencies/Lite/";
            // The firebug logging is not working anymore because of 404
            // Adding them here
            $externalResources[] = 'The firebug resources for the console.log features';
            $externalResources[] = PluginUtility::getResourceBaseUrl() . '/firebug/firebug-lite.css';
            $externalResources[] = PluginUtility::getResourceBaseUrl() . '/firebug/firebug-lite-1.2.js';
        }

        // The below code is to prevent this JsFiddle bug: https://github.com/jsfiddle/jsfiddle-issues/issues/726
        // The order of the resources is not guaranteed
        // We pass then the resources only if their is one resources
        // Otherwise we pass them as a script element in the HTML.
        if (count($externalResources) <= 1) {
            $externalResourcesInput = '<input type="hidden" name="resources" value="' . implode(",", $externalResources) . '">';
        } else {
            $codes['html'] .= "\n\n\n\n\n<!-- The resources -->\n";
            $codes['html'] .= "<!-- They have been added here because their order is not guarantee through the API. -->\n";
            $codes['html'] .= "<!-- See: https://github.com/jsfiddle/jsfiddle-issues/issues/726 -->\n";
            foreach ($externalResources as $externalResource) {
                if ($externalResource != "") {
                    $extension = pathinfo($externalResource)['extension'];
                    switch ($extension) {
                        case "css":
                            $codes['html'] .= "<link href=\"" . $externalResource . "\" rel=\"stylesheet\">\n";
                            break;
                        case "js":
                            $codes['html'] .= "<script src=\"" . $externalResource . "\"></script>\n";
                            break;
                        default:
                            $codes['html'] .= "<!-- " . $externalResource . " -->\n";
                    }
                }
            }
        }

        $jsCode = $codes['javascript'];
        $jsPanel = 0; // language for the js specific panel (0 = JavaScript)
        if (array_key_exists('babel', $codes)) {
            $jsCode = $codes['babel'];
            $jsPanel = 3; // 3 = Babel
        }

        // Title and description
        global $ID;
        $title = $attributes['name'];
        $pageTitle = tpl_pagetitle($ID, true);
        if (!$title) {

            $title = "Code from " . $pageTitle;
        }
        $description = "Code from the page '" . $pageTitle . "' \n" . wl($ID, $absolute = true);
        return '<form  method="post" action="' . $postURL . '" target="_blank">' .
            '<input type="hidden" name="title" value="' . htmlentities($title) . '">' .
            '<input type="hidden" name="description" value="' . htmlentities($description) . '">' .
            '<input type="hidden" name="css" value="' . htmlentities($codes['css']) . '">' .
            '<input type="hidden" name="html" value="' . htmlentities("<!-- The HTML -->" . $codes['html']) . '">' .
            '<input type="hidden" name="js" value="' . htmlentities($jsCode) . '">' .
            '<input type="hidden" name="panel_js" value="' . htmlentities($jsPanel) . '">' .
            '<input type="hidden" name="wrap" value="b">' .  //javascript no wrap in body
            $externalResourcesInput .
            '<button>Try the code</button>' .
            '</form>';

    }

    /**
     * @param $codes the array containing the codes
     * @param $attributes the attributes of a call (for now the externalResources)
     * @return string the HTML form code
     */
    public function addCodePenButton($codes, $attributes)
    {
        // TODO
        // http://blog.codepen.io/documentation/api/prefill/
    }


}
