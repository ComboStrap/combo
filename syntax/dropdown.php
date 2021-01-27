<?php
/**
 * DokuWiki Syntax Plugin Combostrap.
 *
 */

use ComboStrap\HtmlUtility;
use ComboStrap\LinkUtility;
use ComboStrap\PluginUtility;



require_once(__DIR__ . '/../class/PluginUtility.php');
require_once(__DIR__ . '/../class/LinkUtility.php');
require_once(__DIR__ . '/../class/HtmlUtility.php');

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 *
 * The name of the class must follow a pattern (don't change it)
 * ie:
 *    syntax_plugin_PluginName_ComponentName
 */
class syntax_plugin_combo_dropdown extends DokuWiki_Syntax_Plugin
{

    const TAG = "dropdown";

    private $linkCounter = 0;
    private $dropdownCounter = 0;

    /**
     * Syntax Type.
     *
     * Needs to return one of the mode types defined in $PARSER_MODES in parser.php
     * @see DokuWiki_Syntax_Plugin::getType()
     */
    function getType()
    {
        return 'formatting';
    }

    /**
     * @return array
     * Allow which kind of plugin inside
     *
     * No one of array('container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs')
     * because we manage self the content and we call self the parser
     */
    public function getAllowedTypes()
    {
        return array('formatting');
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
        return 'normal';
    }

    /**
     * @see Doku_Parser_Mode::getSort()
     * Higher number than the teaser-columns
     * because the mode with the lowest sort number will win out
     */
    function getSort()
    {
        return 200;
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

        // Link
        $this->Lexer->addPattern(LinkUtility::LINK_PATTERN, PluginUtility::getModeForComponent($this->getPluginComponent()));


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

                $linkAttributes = PluginUtility::getTagAttributes($match);
                return array($state, $linkAttributes);

            case DOKU_LEXER_UNMATCHED :

                // Normally we don't get any here
                return array($state, $match);

            case DOKU_LEXER_MATCHED :

                $linkAttributes = LinkUtility::getAttributes($match);
                return array($state, $linkAttributes);

            case DOKU_LEXER_EXIT :

                return array($state, '');


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

        list($state, $payload) = $data;
        if ($format == 'xhtml') {

            /** @var Doku_Renderer_xhtml $renderer */

            switch ($state) {

                case DOKU_LEXER_ENTER :
                    $this->dropdownCounter++;
                    $dropDownId = "dropDown" . $this->dropdownCounter;
                    $name = 'Name';
                    if (array_key_exists("name", $payload)) {
                        $name = $payload["name"];
                    }
                    $renderer->doc .= '<li class="nav-item dropdown">'
                        . DOKU_TAB . '<a id="' . $dropDownId . '" href="#" class="nav-link dropdown-toggle active" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false" title="Title">' . $name . '</a>'
                        . DOKU_TAB . '<div class="dropdown-menu" aria-labelledby="' . $dropDownId . '">';
                    break;

                case DOKU_LEXER_UNMATCHED :

                    $renderer->doc .= PluginUtility::escape($payload);
                    break;

                case DOKU_LEXER_MATCHED:

                    $html = LinkUtility::renderAsAnchorElement($renderer, $payload);
                    $html = HtmlUtility::addAttributeValue($html, "class", "dropdown-item");
                    $html = LinkUtility::deleteDokuWikiClass($html);
                    $renderer->doc .= $html;
                    break;

                case DOKU_LEXER_EXIT :

                    $renderer->doc .= '</div></li>';

                    // Counter on NULL
                    $this->linkCounter = 0;
                    break;
            }
            return true;
        } else if ($format == 'metadata' && $state == DOKU_LEXER_MATCHED) {

            /**
             * Keep track of the backlinks ie meta['relation']['references']
             * @var Doku_Renderer_metadata $renderer
             */
            LinkUtility::handleMetadata($renderer, $payload);
            return true;

        }
        return false;
    }


}
