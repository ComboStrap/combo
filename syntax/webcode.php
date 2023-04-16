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
use ComboStrap\CallStack;
use ComboStrap\Dimension;
use ComboStrap\Display;
use ComboStrap\ExceptionBadState;
use ComboStrap\ExceptionCompile;
use ComboStrap\ExceptionNotFound;
use ComboStrap\ExecutionContext;
use ComboStrap\FetcherMarkup;
use ComboStrap\FetcherMarkupWebcode;
use ComboStrap\FetcherRawLocalPath;
use ComboStrap\LogUtility;
use ComboStrap\PluginUtility;
use ComboStrap\TagAttribute\StyleAttribute;
use ComboStrap\Tag\WebCodeTag;
use ComboStrap\TagAttributes;
use ComboStrap\WikiPath;
use ComboStrap\XmlTagProcessing;


/**
 * Webcode
 */
class syntax_plugin_combo_webcode extends DokuWiki_Syntax_Plugin
{

    // In the action bar
    // In the code


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

    public function getPType()
    {
        return "stack";
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

        $pattern = XmlTagProcessing::getContainerTagPattern(WebCodeTag::TAG);
        $this->Lexer->addEntryPattern($pattern, $mode, PluginUtility::getModeFromTag($this->getPluginComponent()));

    }


    // This where the addPattern and addExitPattern are defined
    public function postConnect()
    {
        $this->Lexer->addExitPattern('</' . WebCodeTag::TAG . '>', PluginUtility::getModeFromTag($this->getPluginComponent()));
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

                // Default
                $defaultAttributes = WebCodeTag::getDefaultAttributes();

                // Parse and create the call stack array
                $knownTypes = WebCodeTag::getKnownTypes();
                $tagAttributes = TagAttributes::createFromTagMatch($match, $defaultAttributes, $knownTypes);
                $callStackArray = $tagAttributes->toCallStackArray();

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $callStackArray
                );


            case DOKU_LEXER_UNMATCHED :

                return PluginUtility::handleAndReturnUnmatchedData(WebCodeTag::TAG, $match, $handler);


            case DOKU_LEXER_EXIT:

                $array = WebCodeTag::handleExit($handler);
                $array[PluginUtility::STATE] = $state;
                return $array;


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
    public function render($mode, Doku_Renderer $renderer, $data): bool
    {
        // The $data variable comes from the handle() function
        //
        // $mode = 'xhtml' means that we output html
        // There is other mode such as metadata where you can output data for the headers (Not 100% sure)
        if ($mode == 'xhtml') {


            /** @var Doku_Renderer_xhtml $renderer */

            $state = $data[PluginUtility::STATE];
            switch ($state) {


                case DOKU_LEXER_UNMATCHED :

                    $renderer->doc .= PluginUtility::renderUnmatched($data);
                    break;
                case DOKU_LEXER_EXIT :
                    $callStackArray = $data[PluginUtility::ATTRIBUTES];
                    $tagAttributes = TagAttributes::createFromCallStackArray($callStackArray, WebCodeTag::TAG);
                    $renderer->doc .= WebCodeTag::renderExit($tagAttributes, $data);
                    break;
            }

            return true;
        }
        return false;
    }

    /**
     * @param $codes - the array containing the codes
     * @param $attributes - the attributes of a call (for now the externalResources)
     * @return void the HTML form code
     */
    public function addCodePenButton($codes, $attributes)
    {
        // TODO
        // http://blog.codepen.io/documentation/api/prefill/
    }


}
