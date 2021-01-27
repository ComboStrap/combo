<?php
/**
 * DokuWiki Syntax Plugin Combostrap.
 * Implementatiojn of https://getbootstrap.com/docs/4.1/content/typography/#blockquotes
 *
 */

use ComboStrap\StringUtility;
use ComboStrap\Tag;
use ComboStrap\TitleUtility;
use ComboStrap\PluginUtility;

if (!defined('DOKU_INC')) {
    die();
}

require_once(__DIR__ . '/../class/PluginUtility.php');
require_once(__DIR__ . '/../class/TitleUtility.php');
require_once(__DIR__ . '/../class/StringUtility.php');
require_once(__DIR__ . '/../class/Tag.php');

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 *
 * The name of the class must follow a pattern (don't change it)
 * ie:
 *    syntax_plugin_PluginName_ComponentName
 */
class syntax_plugin_combo_blockquote extends DokuWiki_Syntax_Plugin
{

    const TAG = "blockquote";
    const BLOCKQUOTE_OPEN_TAG = "<blockquote class=\"blockquote mb-0\">" . DOKU_LF;
    const CARD_BODY_BLOCKQUOTE_OPEN_TAG = syntax_plugin_combo_card::CARD_BODY.self::BLOCKQUOTE_OPEN_TAG;


    /**
     * @var mixed|string
     */
    static public $type = "card";


    /**
     * How Dokuwiki will add P element
     *
     *  * 'normal' - The plugin can be used inside paragraphs (inline)
     *  * 'block'  - Open paragraphs need to be closed before plugin output - block should not be inside paragraphs
     *  * 'stack'  - Special case. Plugin wraps other paragraphs. - Stacks can contain paragraphs
     *
     * @see DokuWiki_Syntax_Plugin::getPType()
     * @see https://www.dokuwiki.org/devel:syntax_plugins#ptype
     */
    function getType()
    {
        return 'container';
    }

    /**
     * @return array
     * Allow which kind of plugin inside
     *
     * No one of array('container', 'baseonly', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs')
     * because we manage self the content and we call self the parser
     *
     * Return an array of one or more of the mode types {@link $PARSER_MODES} in Parser.php
     */
    public function getAllowedTypes()
    {
        return array('container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs');
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
     * the mode with the lowest sort number will win out
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
     * @param int $pos - byte position in the original source file
     * @param Doku_Handler $handler
     * @return array|bool
     * @see DokuWiki_Syntax_Plugin::handle()
     *
     */
    function handle($match, $state, $pos, Doku_Handler $handler)
    {

        switch ($state) {

            case DOKU_LEXER_ENTER:
                // Suppress the component name
                $defaultAttributes = array("type" => "card");
                $tagAttributes = PluginUtility::getTagAttributes($match);
                $tagAttributes = PluginUtility::mergeAttributes($tagAttributes, $defaultAttributes);


                $type = $tagAttributes["type"];
                if ($type == "typo") {
                    $class = "blockquote";
                } else {
                    $class = "card";
                }
                PluginUtility::addClass2Attributes($class, $tagAttributes);


                $html = "";
                if ($type == "typo") {
                    $tag = new Tag(self::TAG, $tagAttributes, $state, $handler->calls);
                    if ($tag->hasParent() && $tag->getParent()->getName() == "card") {
                        PluginUtility::addClass2Attributes("mb-0", $tagAttributes);
                    }
                    $inlineAttributes = PluginUtility::array2HTMLAttributes($tagAttributes);
                    $html .= "<blockquote {$inlineAttributes}>" . DOKU_LF;
                } else {
                    $inlineAttributes = PluginUtility::array2HTMLAttributes($tagAttributes);
                    $html = "<div {$inlineAttributes}>" . DOKU_LF;
                    /**
                     * Add the card body directly,
                     * the {@link syntax_plugin_combo_header} will delete it if present
                     * We use this methodology because a blockquote may have as direct
                     * child another combo/dokuwiki syntax that is not aware
                     * of where it lives and will then not open the body of the card
                     */
                    $html .= self::CARD_BODY_BLOCKQUOTE_OPEN_TAG;
                }

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $tagAttributes,
                    PluginUtility::PAYLOAD => $html);


            case DOKU_LEXER_UNMATCHED :
                $doc = PluginUtility::escape($match);

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::PAYLOAD => $doc);


            case DOKU_LEXER_EXIT :
                // Important to get an exit in the render phase
                $node = new Tag(self::TAG, array(), $state, $handler->calls);
                if ($node->getOpeningTag()->getType() == "card") {

                    $doc = "</blockquote>" . DOKU_LF;
                    $doc .= "</div>" . DOKU_LF;
                    $doc .= "</div>" . DOKU_LF;

                } else {

                    $doc = "</blockquote>" . DOKU_LF;

                }
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::PAYLOAD => $doc
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
                case DOKU_LEXER_UNMATCHED:
                    $renderer->doc .= $data[PluginUtility::PAYLOAD];
                    break;
                case DOKU_LEXER_EXIT:
                    // Because we can have several unmatched on a line we don't know if
                    // there is a eol
                    StringUtility::addEolIfNotPresent($renderer->doc);
                    $renderer->doc .= $data[PluginUtility::PAYLOAD];
                    break;



            }
            return true;
        }
        return true;
    }


}
