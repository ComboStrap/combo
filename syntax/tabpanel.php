<?php
/**
 * DokuWiki Syntax Plugin Combostrap.
 *
 */

use ComboStrap\LogUtility;
use ComboStrap\PluginUtility;
use ComboStrap\Tag;

if (!defined('DOKU_INC')) {
    die();
}

require_once(__DIR__ . '/../class/PluginUtility.php');

/**
 *
 * The name of the class must follow a pattern (don't change it)
 * ie:
 *    syntax_plugin_PluginName_ComponentName
 */
class syntax_plugin_combo_tabpanel extends DokuWiki_Syntax_Plugin
{

    const TAG = 'tabpanel';
    const STATE = 'state';


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

        /**
         * Only inside {@link syntax_plugin_combo_tabpanels}
         */
        if ($mode == PluginUtility::getModeForComponent(syntax_plugin_combo_tabpanels::TAG)) {
            $pattern = PluginUtility::getContainerTagPattern(self::TAG);
            $this->Lexer->addEntryPattern($pattern, $mode, PluginUtility::getModeForComponent($this->getPluginComponent()));
        }

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
     * @throws Exception
     * @see DokuWiki_Syntax_Plugin::handle()
     *
     */
    function handle($match, $state, $pos, Doku_Handler $handler)
    {

        switch ($state) {

            case DOKU_LEXER_ENTER:


                $tagAttributes = PluginUtility::getTagAttributes($match);
                $htmlAttributes = $tagAttributes;
                $id = "";
                if (!isset($htmlAttributes["id"])) {
                    LogUtility::msg("The id attribute is mandatory for a " . self::TAG . "");
                } else {
                    $id = $htmlAttributes["id"];
                }

                PluginUtility::addClass2Attributes("tab-pane fade", $htmlAttributes);
                $htmlAttributes["role"] = "tabpanel";
                $htmlAttributes["aria-labelledby"] = $id . "-tab";

                /**
                 * Selected ?
                 */
                $tag = new Tag(self::TAG, $tagAttributes, $state, $handler->calls);
                $parent = $tag->getParent();
                if ($parent !== null) {
                    $siblingTag = $parent->getSibling();
                    if ($siblingTag != null) {
                        if ($siblingTag->getName() === syntax_plugin_combo_tabs::TAG) {
                            $descendants = $siblingTag->getDescendants();
                            foreach ($descendants as $descendant) {
                                $descendantName = $descendant->getName();
                                $descendantPanel = $descendant->getAttribute(syntax_plugin_combo_tab::PANEL);
                                $descendantSelected = $descendant->getAttribute(syntax_plugin_combo_tab::SELECTED);
                                if (
                                    $descendantName == syntax_plugin_combo_tab::TAG
                                    && $descendantPanel === $id
                                    && $descendantSelected === "true") {
                                    PluginUtility::addClass2Attributes("show active", $htmlAttributes);
                                    break;
                                }
                            }
                        } else {
                            LogUtility::msg("The direct element above a tabpanels should be a tabs", LogUtility::LVL_MSG_ERROR, "tabs");
                        }
                    }
                }
                $html = '<div ' . PluginUtility::array2HTMLAttributes($htmlAttributes) . '>';
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $tagAttributes,
                    PluginUtility::PAYLOAD => $html
                );

            case DOKU_LEXER_UNMATCHED:

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::PAYLOAD => PluginUtility::escape($match)
                );


            case DOKU_LEXER_EXIT :

                return
                    array(
                        PluginUtility::STATE => $state,
                        PluginUtility::PAYLOAD => "</div>"
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
                case DOKU_LEXER_EXIT :
                    $renderer->doc .= $data[PluginUtility::PAYLOAD] . DOKU_LF;
                    break;
                case DOKU_LEXER_UNMATCHED:
                    $renderer->doc .= $data[PluginUtility::PAYLOAD];
                    break;
            }
            return true;
        }
        return false;
    }


}
