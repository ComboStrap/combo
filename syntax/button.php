<?php
/**
 * DokuWiki Syntax Plugin Combostrap.
 *
 */

use ComboStrap\LinkUtility;
use ComboStrap\PluginUtility;
use ComboStrap\Tag;

if (!defined('DOKU_INC')) {
    die();
}

if (!defined('DOKU_PLUGIN')) {
    define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
}


require_once(DOKU_PLUGIN . 'syntax.php');
require_once(DOKU_INC . 'inc/parserutils.php');
require_once(__DIR__ . '/../class/PluginUtility.php');

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 *
 * The name of the class must follow a pattern (don't change it)
 * ie:
 *    syntax_plugin_PluginName_ComponentName
 *
 * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
 * !!!!!!!!!!! The component name must be the name of the php file !!!
 * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
 */
class syntax_plugin_combo_button extends DokuWiki_Syntax_Plugin
{


    const TAG = "button";


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
     * No one of array('container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs')
     * because we manage self the content and we call self the parser
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
     *  * 'normal' - The plugin can be used inside paragraphs
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
     * the mode with the lowest sort number will win out
     * the lowest in the tree must have the lowest sort number
     * No idea why it must be low but inside a teaser, it will work
     * https://www.dokuwiki.org/devel:parser#order_of_adding_modes_important
     */
    function getSort()
    {
        return 10;
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
            $this->Lexer->addEntryPattern($pattern, $mode, 'plugin_' . PluginUtility::PLUGIN_BASE_NAME . '_' . $this->getPluginComponent());

        }

    }

    public function postConnect()
    {

        foreach (self::getTags() as $tag) {
            $this->Lexer->addExitPattern('</' . $tag . '>', 'plugin_' . PluginUtility::PLUGIN_BASE_NAME . '_' . $this->getPluginComponent());
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
     * @throws Exception
     * @see DokuWiki_Syntax_Plugin::handle()
     *
     */
    function handle($match, $state, $pos, Doku_Handler $handler)
    {

        switch ($state) {

            case DOKU_LEXER_ENTER:

                $defaultAttributes = array("skin" => "filled", "type" => "primary");
                $inLinesAttributes = PluginUtility::getTagAttributes($match);
                $attributes = PluginUtility::mergeAttributes($inLinesAttributes, $defaultAttributes);

                /**
                 * The parent
                 * to apply automatically styling in a bar
                 */
                $tag = new Tag(self::TAG, array(), $state, $handler);
                if ($tag->isDescendantOf(syntax_plugin_combo_navbar::TAG)) {
                    if (!isset($attributes["class"]) && !isset($attributes["spacing"])) {
                        $attributes["spacing"] = "mr-2 mb-2 mt-2 mb-lg-0 mt-lg-0";
                    }
                }

                /**
                 * The context give set if this is a button
                 * or a link button
                 * The context is checked in the exist
                 * Default context: This is not a link button
                 */
                $context = self::TAG;

                /**
                 * The parent is used to close
                 * the text of a card if any
                 */
                $parentName = "";
                $parent = $tag->getParent();
                if ($parent != null) {
                    $parentName = $parent->getName();
                }

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $attributes,
                    PluginUtility::CONTEXT => $context,
                    PluginUtility::PARENT => $parentName
                );

            case DOKU_LEXER_UNMATCHED :

                return PluginUtility::handleAndReturnUnmatchedData(self::TAG, $match, $handler);


            case DOKU_LEXER_EXIT :
                $tag = new Tag(self::TAG, array(), $state, $handler);
                $openingTag = $tag->getOpeningTag();
                $linkDescendant = $openingTag->getDescendant(syntax_plugin_combo_link::TAG);
                if ($linkDescendant != null) {
                    $context = syntax_plugin_combo_link::TAG;
                } else {
                    $context = self::TAG;
                }
                $openingTag->setContext($context);


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

        switch ($format) {

            case 'xhtml':
            {

                /** @var Doku_Renderer_xhtml $renderer */

                /**
                 * CSS if dokuwiki class name for link
                 */
                if ($this->getConf(LinkUtility::CONF_USE_DOKUWIKI_CLASS_NAME, false)) {
                    PluginUtility::getSnippetManager()->upsertCssSnippetForBar(self::TAG);
                }

                /**
                 * HTML
                 */
                $state = $data[PluginUtility::STATE];
                $attributes = $data[PluginUtility::ATTRIBUTES];
                $context = $data[PluginUtility::CONTEXT];
                switch ($state) {

                    case DOKU_LEXER_ENTER :

                        /**
                         * Closing the text tag of a card
                         */
                        $parent = $data[PluginUtility::PARENT];
                        if ($parent == syntax_plugin_combo_card::TAG) {
                            $renderer->doc .= "</p>";
                        }

                        /**
                         * If this not a link button
                         * The context is set on the handle exit
                         */
                        if ($context == self::TAG) {
                            self::processButtonAttributesToHtmlAttributes($attributes);
                            $inlineAttributes = PluginUtility::array2HTMLAttributesAsString($attributes);
                            $renderer->doc .= '<button type="button" ' . $inlineAttributes . '>';
                        }
                        break;

                    case DOKU_LEXER_UNMATCHED:


                        /**
                         * If this is a button and not a link button
                         */
                        $renderer->doc .= PluginUtility::renderUnmatched($data);
                        break;

                    case DOKU_LEXER_EXIT :


                        /**
                         * If this is a button and not a link button
                         */
                        if ($context == self::TAG) {
                            $renderer->doc .= '</button>';
                        }

                        break;
                }
                return true;
            }

        }
        return false;
    }


    public static function getTags()
    {
        $elements[] = self::TAG;
        $elements[] = 'btn';
        return $elements;
    }

    /**
     * @param $attributes
     */
    public static function processButtonAttributesToHtmlAttributes(&$attributes)
    {
        # A button
        PluginUtility::addClass2Attributes("btn", $attributes);

        $type = $attributes["type"];
        if (blank($type)) {
            $type = "primary";
        }
        $skin = $attributes["skin"];
        if (blank($skin)) {
            $skin = "filled";
        }
        $class = "btn";
        switch ($skin) {
            case "contained":
            {
                $class .= "-" . $type;
                $attributes["elevation"] = true;
                break;
            }
            case "filled":
            {
                $class .= "-" . $type;
                break;
            }
            case "outline":
            {
                $class .= "-outline-" . $type;
                break;
            }
            case "text":
            {
                $class .= "-link";
                $attributes["color"] = $type;
                break;
            }
        }
        unset($attributes["skin"]);
        PluginUtility::addClass2Attributes($class, $attributes);

        if (array_key_exists("align", $attributes)) {
            $align = $attributes["align"];
            if ($align == "center") {
                PluginUtility::addStyleProperty("display", "block", $attributes);
            }
        }

        $sizeAttribute = "size";
        if (array_key_exists($sizeAttribute, $attributes)) {
            $size = $attributes[$sizeAttribute];
            unset($attributes[$sizeAttribute]);
            switch ($size) {
                case "lg":
                case "large":
                    PluginUtility::addClass2Attributes("btn-lg", $attributes);
                    break;
                case "sm":
                case "small":
                    PluginUtility::addClass2Attributes("btn-sm", $attributes);
                    break;
            }
        }
    }


}
