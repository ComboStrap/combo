<?php
/**
 * DokuWiki Syntax Plugin Combostrap.
 *
 */

use ComboStrap\PluginUtility;
use ComboStrap\Tag;

if (!defined('DOKU_INC')) {
    die();
}

if (!defined('DOKU_PLUGIN')) {
    define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
}

require_once(__DIR__ . '/../class/PluginUtility.php');

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 *
 * The name of the class must follow a pattern (don't change it)
 * ie:
 *    syntax_plugin_PluginName_ComponentName
 */
class syntax_plugin_combo_card extends DokuWiki_Syntax_Plugin
{


    const TAG = 'card';

    /**
     * The card body html
     * It's created as a constant because
     * the below card property such as {@link syntax_plugin_combo_img}
     * may remove it if they are used
     */
    const CARD_BODY = '<div class="card-body">' . DOKU_LF;

    /**
     * Key of the attributes that says if the card has an image illustration
     */
    const HAS_IMAGE_ILLUSTRATION_KEY = "hasImageIllustration";


    /**
     * @var int a counter for an unknown card type
     */
    private $cardCounter = 0;
    /**
     * @var int a counter to give an id to the tabs card
     */
    private $tabCounter = 0;


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
     *
     * ***************
     * This function has no effect because {@link SyntaxPlugin::accepts()}
     * is used
     * ***************
     */
    public function getAllowedTypes()
    {
        return array('container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs');
    }

    public function accepts($mode)
    {
        /**
         * header mode is disable to take over
         * and replace it with {@link syntax_plugin_combo_title}
         */
        if ($mode == "header") {
            return false;
        }
        /**
         * If preformatted is disable, we does not accept it
         */
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

        foreach (self::getTags() as $tag) {
            $pattern = PluginUtility::getContainerTagPattern($tag);
            $this->Lexer->addEntryPattern($pattern, $mode, PluginUtility::getModeForComponent($this->getPluginComponent()));
        }


    }

    public function postConnect()
    {

        foreach (self::getTags() as $tag) {
            $this->Lexer->addExitPattern('</' . $tag . '>', PluginUtility::getModeForComponent($this->getPluginComponent()));
        }


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

                // A card alone

                $attributes = PluginUtility::getTagAttributes($match);

                $tag = new Tag(self::TAG, $attributes, $state, $handler->calls);

                $parentTag = $tag->getParent();
                if ($parentTag == null) {
                    $context = self::TAG;
                } else {
                    $context = $parentTag->getName();
                }


                $this->cardCounter++;
                $id = $this->cardCounter;

                /** A card without context */
                PluginUtility::addClass2Attributes("card", $attributes);
                /**
                 * Image illustration is checked on exit
                 * but we add the attributes now to avoid null exception
                 * on render
                 */
                $attributes[self::HAS_IMAGE_ILLUSTRATION_KEY] = false;


                if (!in_array("id", $attributes)) {
                    $attributes["id"] = $context . $id;
                }

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $attributes,
                    PluginUtility::CONTEXT => $context
                );

            case DOKU_LEXER_UNMATCHED :


                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::PAYLOAD => $match,
                );


            case DOKU_LEXER_EXIT :

                $tag = new Tag(self::TAG, array(), $state, $handler->calls);
                $openingTag = $tag->getOpeningTag();
                $firstDescendant = $openingTag->getFirstMeaningFullDescendant();
                if ($firstDescendant->getName() == syntax_plugin_combo_img::TAG) {
                    $openingTag->addAttribute(self::HAS_IMAGE_ILLUSTRATION_KEY, true);
                }
                $context = $openingTag->getContext();
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::CONTEXT => $context
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
            $attributes = $data[PluginUtility::ATTRIBUTES];
            $state = $data[PluginUtility::STATE];
            switch ($state) {
                case DOKU_LEXER_ENTER:

                    $hasImageIllustration = $attributes[self::HAS_IMAGE_ILLUSTRATION_KEY];
                    unset($attributes[self::HAS_IMAGE_ILLUSTRATION_KEY]);

                    $renderer->doc .= '<div ' . PluginUtility::array2HTMLAttributes($attributes) . '>' . DOKU_LF;

                    if (!$hasImageIllustration) {
                        $renderer->doc .= self::CARD_BODY;
                    }

                    break;

                case DOKU_LEXER_EXIT:
                    $context = $data[PluginUtility::CONTEXT];
                    switch ($context) {
                        case syntax_plugin_combo_cardcolumns::TAG:
                        case syntax_plugin_combo_cardcolumns::TAG_TEASER:
                            $renderer->doc .= '</div>' . DOKU_LF;
                            break;
                        default:
                            $renderer->doc .= '</div>' . DOKU_LF . "</div>" . DOKU_LF;
                    }
                    break;

                case DOKU_LEXER_UNMATCHED:
                    $renderer->doc .= PluginUtility::escape($data[PluginUtility::PAYLOAD]);
                    break;


            }

            return true;
        }
        return false;
    }


    public
    static function getTags()
    {
        $elements[] = self::TAG;
        $elements[] = 'teaser';
        return $elements;
    }


}
