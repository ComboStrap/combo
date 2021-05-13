<?php
/**
 * DokuWiki Syntax Plugin Combostrap.
 * Implementatiojn of https://getbootstrap.com/docs/4.1/content/typography/#blockquotes
 *
 */

use ComboStrap\StringUtility;
use ComboStrap\Tag;
use ComboStrap\TagAttributes;
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
    const TWEET = "tweet";
    const TWEET_SUPPORTED_LANG = array("en", "ar", "bn", "cs", "da", "de", "el", "es", "fa", "fi", "fil", "fr", "he", "hi", "hu", "id", "it", "ja", "ko", "msa", "nl", "no", "pl", "pt", "ro", "ru", "sv", "th", "tr", "uk", "ur", "vi", "zh-cn", "zh-tw");
    const CONF_TWEET_WIDGETS_THEME = "twitter:widgets:theme";
    const CONF_TWEET_WIDGETS_BORDER = "twitter:widgets:border-color";

    const BLOCKQUOTE_OPEN_TAG = "<blockquote class=\"blockquote mb-0\">" . DOKU_LF;


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
                $tag = new Tag(self::TAG, $tagAttributes, $state, $handler);
                $context = null;
                if ($tag->hasParent()) {
                    $context = $tag->getParent()->getName();
                }

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $tagAttributes,
                    PluginUtility::CONTEXT => $context
                );


            case DOKU_LEXER_UNMATCHED :

                return PluginUtility::handleAndReturnUnmatchedData(self::TAG, $match, $handler);

            case DOKU_LEXER_EXIT :
                // Important to get an exit in the render phase
                $node = new Tag(self::TAG, array(), $state, $handler);
                $openingTag = $node->getOpeningTag();
                $type = $openingTag->getType();
                if ($type=="card") {
                    /**
                     * Transform the eol in combo_eol
                     */

                    /**
                     * Go to the first paragraph
                     * (ie not {@link syntax_plugin_combo_header}
                     */
                    $nextTag = $openingTag->getNextTag();
                    while ($nextTag->getName() != syntax_plugin_combo_eol::TAG) {
                        $nextTag = $nextTag->getNextTag();
                    }
                }
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::CONTEXT => $type
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

                    /**
                     * Add the CSS
                     */
                    $snippetManager = PluginUtility::getSnippetManager();
                    $snippetManager->attachCssSnippetForBar(self::TAG);

                    /**
                     * Create the HTML
                     */
                    $blockquoteAttributes = $data[PluginUtility::ATTRIBUTES];
                    $type = $blockquoteAttributes["type"];
                    switch ($type) {
                        case "typo":

                            $tagAttributes = TagAttributes::createEmpty();
                            $tagAttributes->addClassName("blockquote");

                            $context = $data[PluginUtility::CONTEXT];
                            if ($context == syntax_plugin_combo_card::TAG) {
                                $tagAttributes->addClassName("mb-0");
                            }
                            $renderer->doc .= $tagAttributes->toHtmlEnterTag("blockquote") . DOKU_LF;
                            break;

                        case self::TWEET:

                            PluginUtility::getSnippetManager()->upsertTagsForBar(self::TWEET,
                                array("script" =>
                                    array(
                                        array(
                                            "id" => "twitter-wjs",
                                            "type" => "text/javascript",
                                            "aysnc" => true,
                                            "src" => "https://platform.twitter.com/widgets.js",
                                            "defer" => true
                                        ))));


                            $class = "twitter-tweet";
                            PluginUtility::addClass2Attributes($class, $blockquoteAttributes);

                            $tweetAttributesNames = ["cards", "dnt", "conversation", "align", "width", "theme", "lang"];
                            foreach ($tweetAttributesNames as $tweetAttributesName) {
                                if (isset($blockquoteAttributes[$tweetAttributesName])) {
                                    $blockquoteAttributes["data-" . $tweetAttributesName] = $blockquoteAttributes[$tweetAttributesName];
                                    unset($blockquoteAttributes[$tweetAttributesName]);
                                }
                            }

                            $inlineBlockQuoteAttributes = PluginUtility::array2HTMLAttributesAsString($blockquoteAttributes);
                            $renderer->doc .= "<blockquote $inlineBlockQuoteAttributes>" . DOKU_LF;
                            $renderer->doc .= "<p>" . DOKU_LF;
                            break;
                        case "card":
                        default:
                            $tagAttributes = TagAttributes::createEmpty();
                            $tagAttributes->addClassName("card");
                            $renderer->doc .= $tagAttributes->toHtmlEnterTag("div") . DOKU_LF;
                            /**
                             * The card body and blockquote body
                             * of the example (https://getbootstrap.com/docs/4.0/components/card/#header-and-footer)
                             * are added via call at
                             * the {@link DOKU_LEXER_EXIT} state of {@link syntax_plugin_combo_blockquote::handle()}
                             */
                            break;
                    }
                    break;

                case DOKU_LEXER_UNMATCHED:
                    $renderer->doc .= PluginUtility::renderUnmatched($data);
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
                        case self::TWEET:
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
