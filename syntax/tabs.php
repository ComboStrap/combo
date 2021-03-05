<?php
/**
 * DokuWiki Syntax Plugin Combostrap.
 *
 */

use ComboStrap\CallStack;
use ComboStrap\LogUtility;
use ComboStrap\PluginUtility;
use ComboStrap\Tag;

if (!defined('DOKU_INC')) {
    die();
}

require_once(__DIR__ . '/../class/PluginUtility.php');
require_once(__DIR__ . '/../class/CallStack.php');

/**
 *
 * The name of the class must follow a pattern (don't change it)
 * ie:
 *    syntax_plugin_PluginName_ComponentName
 *
 * The tabs component is a little bit a nasty one
 * because it's used in three cases:
 *   * the new syntax to enclose the panels
 *   * the new syntax to create the tabs
 *   * the old syntax to create the tabs
 * The code is using the context to manage this cases
 */
class syntax_plugin_combo_tabs extends DokuWiki_Syntax_Plugin
{

    const TAG = 'tabs';

    /**
     * A key attributes to set on in the instructions the attributes
     * of panel
     */
    const KEY_PANEL_ATTRIBUTES = "panels";
    const LABEL = 'label';

    /**
     * A tabs with this context will create
     * the HTML for a navigational element
     * The calls with this context are derived
     * and created
     */
    const NAVIGATIONAL_ELEMENT_CONTEXT = "tabHeader";


    public static function openTabPanelsElement()
    {
        return "<div class=\"tab-content\" id=\"myTabContent\">";
    }

    public static function closeTabPanelsElement()
    {
        return "</div>" . DOKU_LF;
    }

    public static function closeNavigationalTabElement()
    {
        return "</a>" . DOKU_LF . "</li>";
    }

    /**
     * @param array $attributes
     * @return string
     */
    public static function openNavigationalTabElement(array $attributes)
    {

        /**
         * Check all attributes for the link (not the li)
         * and delete them
         */
        $active = "false";
        $panel = "";
        if (isset($attributes[syntax_plugin_combo_panel::SELECTED])) {
            $active = $attributes[syntax_plugin_combo_panel::SELECTED];
            unset($attributes[syntax_plugin_combo_panel::SELECTED]);
        }
        $panelAttrName = "panel";
        if (isset($attributes[$panelAttrName])) {
            $panel = $attributes[$panelAttrName];
        } else {
            if (isset($attributes["id"])) {
                $panel = $attributes["id"];
                unset($attributes["id"]);
                $attributes[$panelAttrName] = $panel;
            } else {
                LogUtility::msg("A id attribute is missing on a panel tag", LogUtility::LVL_MSG_ERROR, syntax_plugin_combo_tabs::TAG);
            }
        }

        /**
         * Creating the li element
         */
        PluginUtility::addClass2Attributes("nav-item", $attributes);
        $html = "<li " . PluginUtility::array2HTMLAttributes($attributes) . ">" . DOKU_LF;

        /**
         * Creating the a element
         */
        $htmlAttributes = array();
        PluginUtility::addClass2Attributes("nav-link", $htmlAttributes);
        if ($active === "true") {
            PluginUtility::addClass2Attributes("active", $htmlAttributes);
            $htmlAttributes["aria-selected"] = "true";
        }
        $htmlAttributes['id'] = $panel . "-tab";
        $htmlAttributes['data-toggle'] = "tab";
        $htmlAttributes['aria-controls'] = $panel;
        $htmlAttributes['href'] = "#$panel";

        $html .= "<a " . PluginUtility::array2HTMLAttributes($htmlAttributes) . ">";
        return $html;
    }

    private static function closeNavigationalHeaderComponent()
    {
        return "</ul>";
    }

    /**
     * @param $attributes
     * @return string - the opening HTML code of the tab navigational header
     */
    private static function openNavigationalTabsElement(&$attributes)
    {
        $htmlAttributes = $attributes;
        unset($htmlAttributes[self::KEY_PANEL_ATTRIBUTES]);
        /**
         * Creates the panel wrapper element
         */
        PluginUtility::addClass2Attributes("nav", $htmlAttributes);
        $skinClass = "nav-tabs";
        if (isset($attributes["skin"])) {
            $skin = $attributes["skin"];
            if ($skin == "pills") {
                $skinClass = "nav-pills";
            }
            unset($attributes["skin"]);
        }
        PluginUtility::addClass2Attributes($skinClass, $htmlAttributes);
        $htmlAttributes['role'] = 'tablist';
        return "<ul " . PluginUtility::array2HTMLAttributes($htmlAttributes) . ">";
    }


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
     * ************************
     * This function has no effect because {@link SyntaxPlugin::accepts()} is used
     * ************************
     */
    public function getAllowedTypes()
    {
        return array('container', 'base', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs');
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

                $tagAttributes = PluginUtility::getTagAttributes($match);
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $tagAttributes);

            case DOKU_LEXER_UNMATCHED:

                // We should never get there but yeah ...
                return
                    array(
                        PluginUtility::STATE => $state,
                        PluginUtility::PAYLOAD => trim($match)
                    );


            case DOKU_LEXER_EXIT :

                $tag = new Tag(self::TAG, array(), $state, $handler->calls);
                $openingTag = $tag->getOpeningTag();
                $descendant = $openingTag->getFirstMeaningFullDescendant();
                $context = null;
                if ($descendant != null) {
                    /**
                     * Add the context to the opening and ending tag
                     */
                    $context = $descendant->getName();
                    $openingTag->addContext($context);
                    if ($context == syntax_plugin_combo_panel::TAG) {

                        /**
                         * Copy the descendant before manipulating the stack
                         */
                        $descendants = $openingTag->getDescendants();

                        /**
                         * Start the navigation tabs element
                         * We add calls in the stack to create the tabs navigational element
                         */
                        $navigationalCallElements[] = CallStack::createCall(self::TAG,
                            DOKU_LEXER_ENTER,
                            $openingTag->getAttributes(),
                            self::NAVIGATIONAL_ELEMENT_CONTEXT
                        );
                        $labelStacksToDelete = array();
                        foreach ($descendants as $descendant) {

                            /**
                             * Define the panel attributes
                             * (May be null)
                             */
                            if (empty($panelAttributes)) {
                                $panelAttributes = array();
                            }

                            /**
                             * If this is a panel tag, we capture the attributes
                             */
                            if (
                                $descendant->getName() == syntax_plugin_combo_panel::TAG
                                &&
                                $descendant->getState() == DOKU_LEXER_ENTER
                            ) {
                                $panelAttributes = $descendant->getAttributes();
                                continue;
                            }

                            /**
                             * If this is a label tag, we capture the tags
                             */
                            if (
                                $descendant->getName() == syntax_plugin_combo_label::TAG
                                &&
                                $descendant->getState() == DOKU_LEXER_ENTER
                            ) {

                                $labelStacks = $descendant->getDescendants();

                                /**
                                 * Get the labels call to delete
                                 * (done at the end)
                                 */
                                $labelStacksSize = sizeof($labelStacks);
                                $firstPosition = $descendant->getPosition(); // the enter label is deleted
                                $lastPosition = $labelStacks[$labelStacksSize - 1]->getPosition() + 1; // the exit label is deleted
                                $labelStacksToDelete[] = [$firstPosition, $lastPosition];


                                /**
                                 * Build the navigational call stack for this label
                                 * with another context just to tag them and see them in the stack
                                 */
                                $firstLabelCall = $handler->calls[$descendant->getPosition()];
                                $firstLabelCall[1][PluginUtility::CONTEXT] = self::NAVIGATIONAL_ELEMENT_CONTEXT;
                                $navigationalCallElements[] = $firstLabelCall;
                                for ($i = 1; $i <= $labelStacksSize; $i++) {
                                    $intermediateLabelCall = $handler->calls[$descendant->getPosition() + $i];
                                    $intermediateLabelCall[1][PluginUtility::CONTEXT] = self::NAVIGATIONAL_ELEMENT_CONTEXT;
                                    $navigationalCallElements[] = $intermediateLabelCall;
                                }
                                $lastLabelCall = $handler->calls[$lastPosition];
                                $lastLabelCall[1][PluginUtility::CONTEXT] = self::NAVIGATIONAL_ELEMENT_CONTEXT;
                                $navigationalCallElements[] = $lastLabelCall;
                                continue;
                            }

                        }
                        $navigationalCallElements[] = CallStack::createCall(self::TAG,
                            DOKU_LEXER_EXIT,
                            $openingTag->getAttributes(),
                            self::NAVIGATIONAL_ELEMENT_CONTEXT
                        );


                        /**
                         * Deleting the labels first
                         * because the navigational tabs are added (and would then move the position)
                         */
                        foreach ($labelStacksToDelete as $labelStackToDelete) {
                            $start = $labelStackToDelete[0];
                            $end = $labelStackToDelete[1];
                            CallStack::deleteCalls($handler->calls, $start, $end);
                        }
                        CallStack::insertCallStackUpWards($handler->calls, $openingTag->getPosition(), $navigationalCallElements);
                    }
                }
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
            $state = $data[PluginUtility::STATE];

            switch ($state) {

                case DOKU_LEXER_ENTER :
                    $context = $data[PluginUtility::CONTEXT];
                    $attributes = $data[PluginUtility::ATTRIBUTES];

                    switch ($context) {
                        /**
                         * When the tag tabs enclosed the panels
                         */
                        case syntax_plugin_combo_panel::TAG:
                            $renderer->doc .= self::openTabPanelsElement();
                            break;
                        /**
                         * When the tag tabs are derived (new syntax)
                         */
                        case self::NAVIGATIONAL_ELEMENT_CONTEXT:
                            /**
                             * Old syntax, when the tag had to be added specifically
                             */
                        case syntax_plugin_combo_tab::TAG:
                            $renderer->doc .= self::openNavigationalTabsElement($attributes);
                            break;
                        default:
                            LogUtility::log2FrontEnd("The context ($context) is unknown in enter", LogUtility::LVL_MSG_ERROR, self::TAG);

                    }


                    break;
                case DOKU_LEXER_EXIT :
                    $context = $data[PluginUtility::CONTEXT];
                    switch ($context) {
                        /**
                         * New syntax (tabpanel enclosing)
                         */
                        case syntax_plugin_combo_panel::TAG:
                            $renderer->doc .= self::closeTabPanelsElement();
                            break;
                        /**
                         * Old syntax
                         */
                        case syntax_plugin_combo_tab::TAG:
                            /**
                             * New syntax (Derived)
                             */
                        case self::NAVIGATIONAL_ELEMENT_CONTEXT:
                            $renderer->doc .= self::closeNavigationalHeaderComponent();
                            break;
                        default:
                            LogUtility::log2FrontEnd("The context $context is unknown in exit", LogUtility::LVL_MSG_ERROR, self::TAG);
                    }
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
