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

    /**
     * When the blockquote is a tweet
     */
    const TWEET_CONTEXT = "tweet";


    const BLOCKQUOTE_OPEN_TAG = "<blockquote class=\"blockquote mb-0\">" . DOKU_LF;
    const CARD_BODY_BLOCKQUOTE_OPEN_TAG = syntax_plugin_combo_card::CARD_BODY . self::BLOCKQUOTE_OPEN_TAG;


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
     * ***************
     * This function has no effect because {@link syntax_plugin_combo_blockquote::accepts()}
     * is used
     * ***************
     */
    public function getAllowedTypes()
    {
        return array('container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs');
    }

    /**
     * @param string $mode
     * @return bool
     * Allowed type
     */
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
         * Empty line will create an empty that will
         * go and takes also the cite element (???)
         * Not fighting this
         */
        if ($mode == "eol") {
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


                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $tagAttributes
                );


            case DOKU_LEXER_UNMATCHED :

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::PAYLOAD => $match
                );

            case DOKU_LEXER_EXIT :
                // Important to get an exit in the render phase
                $node = new Tag(self::TAG, array(), $state, $handler);
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::CONTEXT => $node->getOpeningTag()->getType()
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

                case DOKU_LEXER_ENTER:
                    $blockquoteAttributes = $data[PluginUtility::ATTRIBUTES];
                    $type = $blockquoteAttributes["type"];
                    switch ($type) {
                        case "typo":
                            $class = "blockquote";
                            PluginUtility::addClass2Attributes($class, $blockquoteAttributes);
                            $tag = new Tag(self::TAG, $blockquoteAttributes, $state, $handler);
                            if ($tag->hasParent() && $tag->getParent()->getName() == "card") {
                                PluginUtility::addClass2Attributes("mb-0", $blockquoteAttributes);
                            }
                            $inlineBlockQuoteAttributes = PluginUtility::array2HTMLAttributes($blockquoteAttributes);
                            $renderer->doc .= "<blockquote {$inlineBlockQuoteAttributes}>" . DOKU_LF;
                            break;
                        case self::TWEET_CONTEXT:

                            PluginUtility::getSnippetManager()->addHeadTagsOnce(self::TWEET_CONTEXT,
                                array("script" =>
                                    array(
                                        array(
                                            "aysnc" => true,
                                            "src" => "https://platform.twitter.com/widgets.js",
                                            "charset" => "utf-8"
                                        ))));
                            $class = "twitter-tweet";
                            PluginUtility::addClass2Attributes($class, $blockquoteAttributes);
                            $pAttributesNames = ["lang", "dir"];
                            $pAttributes = array();
                            foreach ($pAttributesNames as $pAttributesName) {
                                if (isset($blockquoteAttributes[$pAttributesName])) {
                                    $pAttributes[$pAttributesName] = $blockquoteAttributes[$pAttributesName];
                                    unset($blockquoteAttributes[$pAttributesName]);
                                }
                            }
                            $inlineBlockQuoteAttributes = PluginUtility::array2HTMLAttributes($blockquoteAttributes);
                            $renderer->doc .= "<blockquote $inlineBlockQuoteAttributes>" . DOKU_LF;
                            $inlinePAttributes = PluginUtility::array2HTMLAttributes($pAttributes);
                            $renderer->doc .= "<p $inlinePAttributes>" . DOKU_LF;
                            break;
                        default:
                            $class = "card";
                            PluginUtility::addClass2Attributes($class, $blockquoteAttributes);
                            $inlineBlockQuoteAttributes = PluginUtility::array2HTMLAttributes($blockquoteAttributes);
                            $renderer->doc .= "<div {$inlineBlockQuoteAttributes}>" . DOKU_LF;
                            /**
                             * Add the card body directly,
                             * the {@link syntax_plugin_combo_header} will delete it if present
                             * We use this methodology because a blockquote may have as direct
                             * child another combo/dokuwiki syntax that is not aware
                             * of where it lives and will then not open the body of the card
                             */
                            $renderer->doc .= self::CARD_BODY_BLOCKQUOTE_OPEN_TAG;
                            break;
                    }
                    break;

                case DOKU_LEXER_UNMATCHED:
                    $renderer->doc .= PluginUtility::escape($data[PluginUtility::PAYLOAD]);
                    break;

                case DOKU_LEXER_EXIT:
                    // Because we can have several unmatched on a line we don't know if
                    // there is a eol
                    StringUtility::addEolCharacterIfNotPresent($renderer->doc);
                    $context = $data[PluginUtility::CONTEXT];
                    switch ($context) {
                        case "card":

                            $renderer->doc .= "</blockquote>" . DOKU_LF;
                            $renderer->doc .= "</div>" . DOKU_LF;
                            $renderer->doc .= "</div>" . DOKU_LF;
                            break;
                        case self::TWEET_CONTEXT:
                        default:

                            $renderer->doc .= "</blockquote>" . DOKU_LF;
                            break;

                    }
                    break;


            }
            return true;
        }
        return true;
    }


}
