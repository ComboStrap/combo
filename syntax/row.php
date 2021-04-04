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

use ComboStrap\Bootstrap;
use ComboStrap\PluginUtility;

if (!defined('DOKU_INC')) {
    die();
}

require_once(__DIR__ . '/../class/PluginUtility.php');

/**
 * The {@link https://combostrap.com/row row} of a {@link https://combostrap.com/grid grid}
 *
 *
 * Note: The name of the class must follow this pattern ie syntax_plugin_PluginName_ComponentName
 */
class syntax_plugin_combo_row extends DokuWiki_Syntax_Plugin
{

    const TAG = "row";
    const SNIPPET_ID = "grid";

    /**
     * Syntax Type.
     *
     * Needs to return one of the mode types defined in $PARSER_MODES in parser.php
     * @see DokuWiki_Syntax_Plugin::getType()
     */
    function getType()
    {
        return 'container';
    }

    /**
     * @return array
     * Allow which kind of plugin inside
     * All
     */
    public function getAllowedTypes()
    {
        return array('container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs');
    }


    public function accepts($mode)
    {
        if (!$this->getConf(syntax_plugin_combo_preformatted::CONF_PREFORMATTED_ENABLE)) {
            return PluginUtility::disablePreformatted($mode);
        } else {
            return true;
        }
    }


    /**
     * How Dokuwiki will add P element
     *
     * * 'normal' - The plugin can be used inside paragraphs
     *  * 'block'  - Open paragraphs need to be closed before plugin output - block should not be inside paragraphs
     *  * 'stack'  - Special case. Plugin wraps other paragraphs. - Stacks can contain paragraphs
     *
     * @see DokuWiki_Syntax_Plugin::getPType()
     */
    function getPType()
    {
        return 'block';
    }

    /**
     * @see Doku_Parser_Mode::getSort()
     *
     * the mode with the lowest sort number will win out
     * the container (parent) must then have a lower number than the child
     */
    function getSort()
    {
        return 100;
    }

    /**
     * Create a pattern that will called this plugin
     *
     * @param string $mode
     * @see Doku_Parser_Mode::connectTo()
     */
    function connectTo($mode)
    {

        $pattern = PluginUtility::getContainerTagPattern(self::TAG);
        $this->Lexer->addEntryPattern($pattern, $mode, PluginUtility::getModeForComponent($this->getPluginComponent()));

    }

    public function postConnect()
    {

        $this->Lexer->addExitPattern('</' . self::TAG . '>', PluginUtility::getModeForComponent($this->getPluginComponent()));

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

            case DOKU_LEXER_ENTER:

                $attributes = PluginUtility::getTagAttributes($match);

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $attributes
                );

            case DOKU_LEXER_UNMATCHED:
                return PluginUtility::handleAndReturnUnmatchedData(self::TAG, $match, $handler);

            case DOKU_LEXER_EXIT :

                return array(
                    PluginUtility::STATE => $state
                );


        }

        return array();

    }

    /**
     * Render the output
     * @param string $format
     * @param Doku_Renderer $renderer
     * @param array $data - what the function handle() return'ed
     * @return boolean - rendered correctly? (however, returned value is not used at the moment)
     * @see DokuWiki_Syntax_Plugin::render()
     *
     *
     */
    function render($format, Doku_Renderer $renderer, $data)
    {

        if ($format == 'xhtml') {

            /** @var Doku_Renderer_xhtml $renderer */
            $state = $data[PluginUtility::STATE];
            switch ($state) {

                case DOKU_LEXER_ENTER :
                    $attributes = $data[PluginUtility::ATTRIBUTES];
                    if (array_key_exists("class", $attributes)) {
                        $attributes["class"] .= " " . self::TAG;
                    } else {
                        $attributes["class"] = self::TAG;
                    }

                    $type = "";
                    if (isset($attributes['type'])) {
                        $type = $attributes['type'];
                        unset($attributes['type']);
                    }

                    if ($type=="auto"){
                        PluginUtility::addClass2Attributes("row-cols-auto",$attributes);
                    }
                    if (Bootstrap::getBootStrapMajorVersion() != Bootstrap::BootStrapFiveMajorVersion
                        && $type == "auto") {
                        // row-cols-auto is not in 4.0
                        PluginUtility::getSnippetManager()->upsertCssSnippetForBar(self::SNIPPET_ID);
                    }

                    $inlineAttributes = PluginUtility::array2HTMLAttributesAsString($attributes);
                    $renderer->doc .= "<div $inlineAttributes>" . DOKU_LF;
                    break;

                case DOKU_LEXER_UNMATCHED :

                    $renderer->doc .= PluginUtility::renderUnmatched($data);
                    break;

                case DOKU_LEXER_EXIT :

                    $renderer->doc .= '</div>' . DOKU_LF;

                    break;
            }
            return true;
        }
        return false;
    }


}
