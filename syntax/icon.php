<?php
/**
 * DokuWiki Syntax Plugin Combostrap.
 *
 */

use ComboStrap\IconUtility;
use ComboStrap\LogUtility;
use ComboStrap\PluginUtility;
use ComboStrap\Tag;
use ComboStrap\XmlUtility;


require_once(__DIR__ . '/../class/PluginUtility.php');
require_once(__DIR__ . '/../class/IconUtility.php');

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
 *
 */
class syntax_plugin_combo_icon extends DokuWiki_Syntax_Plugin
{
    const TAG = "icon";


    /**
     * Syntax Type.
     *
     * Needs to return one of the mode types defined in $PARSER_MODES in parser.php
     * @see DokuWiki_Syntax_Plugin::getType()
     */
    function getType()
    {
        return 'substition';
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
        // You can't put anything in a icon
        return array();
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

        $pattern = PluginUtility::getEmptyTagPattern(self::TAG);
        $this->Lexer->addSpecialPattern($pattern, $mode, PluginUtility::getModeForComponent($this->getPluginComponent()));

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

            case DOKU_LEXER_SPECIAL:

                // Get the parameters
                $originalAttributes = PluginUtility::getTagAttributes($match);
                $attributesToRender = $originalAttributes;
                $tag = new Tag(self::TAG,$originalAttributes,$state,$handler->calls);
                if($tag->isDescendantOf(syntax_plugin_combo_list::TAG)){
                    PluginUtility::addClass2Attributes("mr-2",$attributesToRender);
                }
                /**
                 * TODO: When getting the instructions, the file is loaded. This should go to the render if there is too much file open
                 */
                $html = IconUtility::renderIconByAttributes($attributesToRender);
                return array(
                    PluginUtility::STATE=> $state,
                    PluginUtility::ATTRIBUTES => $originalAttributes,
                    PluginUtility::PAYLOAD => $html);


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
                    $state = $data[PluginUtility::STATE];
                    if ($state === DOKU_LEXER_SPECIAL) {
                        $renderer->doc .= $data[PluginUtility::PAYLOAD];
                    }

                }
                break;

        }
        return true;
    }





}
