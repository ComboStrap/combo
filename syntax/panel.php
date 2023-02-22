<?php
/**
 * DokuWiki Syntax Plugin Combostrap.
 *
 */

use ComboStrap\CallStack;
use ComboStrap\DataType;
use ComboStrap\EditButton;
use ComboStrap\EditButtonManager;
use ComboStrap\IdManager;
use ComboStrap\LogUtility;
use ComboStrap\PluginUtility;
use ComboStrap\Site;
use ComboStrap\SiteConfig;
use ComboStrap\TabsTag;
use ComboStrap\TagAttributes;

if (!defined('DOKU_INC')) {
    die();
}

require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');

/**
 *
 * The name of the class must follow a pattern (don't change it)
 * ie:
 *    syntax_plugin_PluginName_ComponentName
 */
class syntax_plugin_combo_panel extends DokuWiki_Syntax_Plugin
{

    const TAG = 'panel';
    const OLD_TAB_PANEL_TAG = 'tabpanel';
    const STATE = 'state';
    const SELECTED = 'selected';

    /**
     * When the panel is alone in the edit due to the sectioning
     */
    const CONTEXT_PREVIEW_ALONE = "preview_alone";
    const CONTEXT_PREVIEW_ALONE_ATTRIBUTES = array(
        self::SELECTED => true,
        TagAttributes::ID_KEY => "alone",
        TagAttributes::TYPE_KEY => TabsTag::ENCLOSED_TABS_TYPE
    );

    const CONF_ENABLE_SECTION_EDITING = "panelEnableSectionEditing";
    const CANONICAL = self::TAG;

    /**
     * @var int a counter to give an id to the accordion panel
     */
    private $accordionCounter = 0;
    private $tabCounter = 0;


    static function getSelectedValue(TagAttributes $tagAttributes)
    {
        $selected = $tagAttributes->getValueAndRemoveIfPresent(syntax_plugin_combo_panel::SELECTED);
        if ($selected !== null) {
            /**
             * Value may be false/true
             */
            return DataType::toBoolean($selected);

        }
        if ($tagAttributes->hasComponentAttribute(TagAttributes::TYPE_KEY)) {
            $type = $tagAttributes->getType();
            if (strtolower($type) === "selected") {
                return true;
            }
        }
        return false;

    }

    private static function getTags()
    {
        return [self::TAG, self::OLD_TAB_PANEL_TAG];
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
         * and replace it with {@link syntax_plugin_combo_heading}
         */
        if ($mode == "header") {
            return false;
        }
        return syntax_plugin_combo_preformatted::disablePreformatted($mode);

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
         * Only inside tabs and accordion
         * and tabpanels for history
         */
        $show = in_array($mode,
            [
                PluginUtility::getModeFromTag(TabsTag::TAG),
                PluginUtility::getModeFromTag(syntax_plugin_combo_accordion::TAG),
                PluginUtility::getModeFromTag(syntax_plugin_combo_tabpanels::TAG)
            ]);

        /**
         * In preview, the panel may be alone
         * due to the section edit button
         */
        if (!$show) {
            global $ACT;
            if ($ACT === "preview") {
                $show = true;
            }
        }

        /**
         * Let's connect
         */
        if ($show) {
            foreach (self::getTags() as $tag) {
                $pattern = PluginUtility::getContainerTagPattern($tag);
                $this->Lexer->addEntryPattern($pattern, $mode, PluginUtility::getModeFromTag($this->getPluginComponent()));
            }
        }

    }

    public function postConnect()
    {

        foreach (self::getTags() as $tag) {
            $this->Lexer->addExitPattern('</' . $tag . '>', PluginUtility::getModeFromTag($this->getPluginComponent()));
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

                // tagname to check if this is the old tag name one
                $tagName = PluginUtility::getMarkupTag($match);

                // Context
                $tagAttributes = PluginUtility::getTagAttributes($match);
                $callStack = CallStack::createFromHandler($handler);
                $parent = $callStack->moveToParent();
                if ($parent !== false) {
                    $context = $parent->getTagName();
                } else {
                    /**
                     * The panel may be alone in preview
                     * due to the section edit button
                     */
                    global $ACT;
                    if ($ACT == "preview") {
                        $context = self::CONTEXT_PREVIEW_ALONE;
                    } else {
                        $context = $tagName;
                    }
                }


                if (!isset($tagAttributes["id"])) {
                    switch ($context) {
                        case syntax_plugin_combo_accordion::TAG:
                            $this->accordionCounter++;
                            $id = $context . $this->accordionCounter;
                            $tagAttributes["id"] = $id;
                            break;
                        case TabsTag::TAG:
                            $this->tabCounter++;
                            $id = $context . $this->tabCounter;
                            $tagAttributes["id"] = $id;
                            break;
                        case self::CONTEXT_PREVIEW_ALONE:
                            $id = "alone";
                            $tagAttributes["id"] = $id;
                            break;
                        default:
                            LogUtility::msg("An id should be given for the context ($context)", LogUtility::LVL_MSG_ERROR, self::TAG);

                    }
                } else {

                    $id = $tagAttributes["id"];
                }

                /**
                 * Old deprecated syntax
                 */
                if ($tagName == self::OLD_TAB_PANEL_TAG) {

                    $context = self::OLD_TAB_PANEL_TAG;

                    $siblingTag = $callStack->moveToPreviousSiblingTag();
                    if ($siblingTag != null) {
                        if ($siblingTag->getTagName() === TabsTag::TAG) {
                            $tagAttributes[self::SELECTED] = false;
                            while ($descendant = $callStack->next()) {
                                $descendantName = $descendant->getTagName();
                                $descendantPanel = $descendant->getAttribute("panel");
                                $descendantSelected = $descendant->getAttribute(self::SELECTED);
                                if (
                                    $descendantName == syntax_plugin_combo_tab::TAG
                                    && $descendantPanel === $id
                                    && $descendantSelected === "true") {
                                    $tagAttributes[self::SELECTED] = true;
                                    break;
                                }
                            }
                        } else {
                            LogUtility::msg("The direct element above a " . self::OLD_TAB_PANEL_TAG . " should be a `tabs` and not a `" . $siblingTag->getTagName() . "`", LogUtility::LVL_MSG_ERROR, "tabs");
                        }
                    }
                }

                $id = IdManager::getOrCreate()->generateNewHtmlIdForComponent(self::TAG);
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $tagAttributes,
                    PluginUtility::CONTEXT => $context,
                    PluginUtility::POSITION => $pos,
                    TagAttributes::ID_KEY => $id
                );

            case DOKU_LEXER_UNMATCHED:

                return PluginUtility::handleAndReturnUnmatchedData(self::TAG, $match, $handler);


            case DOKU_LEXER_EXIT :

                $callStack = CallStack::createFromHandler($handler);
                $openingTag = $callStack->moveToPreviousCorrespondingOpeningCall();

                /**
                 * Label Mandatory check
                 * (Only the presence of at minimum 1 and not the presence in each panel)
                 */
                if ($match !== "</" . self::OLD_TAB_PANEL_TAG . ">") {
                    $labelCall = null;
                    while ($actualCall = $callStack->next()) {
                        if ($actualCall->getTagName() === syntax_plugin_combo_label::TAG) {
                            $labelCall = $actualCall;
                            break;
                        }
                    }
                    if ($labelCall === null) {
                        LogUtility::msg("No label was found in the panel (number " . $this->tabCounter . "). They are mandatory to create tabs or accordion", LogUtility::LVL_MSG_ERROR, self::TAG);
                    }
                }


                /**
                 * End section
                 */
                if (SiteConfig::getConfValue(self::CONF_ENABLE_SECTION_EDITING, 1)) {
                    /**
                     * Section
                     * +1 to go at the line
                     */
                    $startPosition = $openingTag->getPluginData(PluginUtility::POSITION);
                    $id = $openingTag->getAttribute(TagAttributes::ID_KEY);
                    $endPosition = $pos + strlen($match) + 1;
                    $editButtonCall = EditButton::create("Edit panel $id")
                        ->setStartPosition($startPosition)
                        ->setEndPosition($endPosition)
                        ->toComboCallComboFormat();
                    $callStack->moveToEnd();
                    $callStack->insertBefore($editButtonCall);
                }

                return
                    array(
                        PluginUtility::STATE => $state,
                        PluginUtility::CONTEXT => $openingTag->getContext()
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
    function render($format, Doku_Renderer $renderer, $data): bool
    {

        if ($format == 'xhtml') {

            /** @var Doku_Renderer_xhtml $renderer */
            $state = $data[PluginUtility::STATE];
            switch ($state) {

                case DOKU_LEXER_ENTER :

                    /**
                     * Section (Edit button)
                     */
                    if (SiteConfig::getConfValue(self::CONF_ENABLE_SECTION_EDITING, 1)) {
                        $position = $data[PluginUtility::POSITION];
                        $name = IdManager::getOrCreate()->generateNewHtmlIdForComponent(self::TAG);
                        EditButtonManager::getOrCreate()->createAndAddEditButtonToStack($name, $position);
                    }

                    $context = $data[PluginUtility::CONTEXT];
                    switch ($context) {
                        case syntax_plugin_combo_accordion::TAG:
                            // A panel in a accordion
                            $renderer->doc .= "<div class=\"card\">";
                            break;
                        case self::OLD_TAB_PANEL_TAG: // Old deprecated syntax
                        case TabsTag::TAG: // new syntax

                            $tagAttributes = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES]);

                            $ariaLabelledValue = $tagAttributes->getValue("id") . "-tab";
                            $tagAttributes
                                ->addClassName("tab-pane fade")
                                ->addOutputAttributeValue("role", "tabpanel")
                                ->addOutputAttributeValue("aria-labelledby", $ariaLabelledValue);
                            $selected = self::getSelectedValue($tagAttributes);
                            if ($selected) {
                                $tagAttributes->addClassName("show active");
                            }
                            $renderer->doc .= $tagAttributes->toHtmlEnterTag("div");
                            break;
                        case self::CONTEXT_PREVIEW_ALONE:
                            $aloneAttributes = TagAttributes::createFromCallStackArray(syntax_plugin_combo_panel::CONTEXT_PREVIEW_ALONE_ATTRIBUTES);
                            $renderer->doc .= TabsTag::openTabPanelsElement($aloneAttributes);
                            break;
                        default:
                            LogUtility::log2FrontEnd("The context ($context) is unknown in enter rendering", LogUtility::LVL_MSG_ERROR, self::TAG);
                            break;
                    }

                    break;
                case DOKU_LEXER_EXIT :
                    $context = $data[PluginUtility::CONTEXT];
                    switch ($context) {
                        case syntax_plugin_combo_accordion::TAG:
                            $renderer->doc .= '</div>' . DOKU_LF . "</div>" . DOKU_LF;
                            break;
                        case self::CONTEXT_PREVIEW_ALONE:
                            $aloneVariable = TagAttributes::createFromCallStackArray(syntax_plugin_combo_panel::CONTEXT_PREVIEW_ALONE_ATTRIBUTES);
                            $renderer->doc .= TabsTag::closeTabPanelsElement($aloneVariable);
                            break;
                    }

                    /**
                     * End panel
                     */
                    $renderer->doc .= "</div>" . DOKU_LF;
                    break;
                case DOKU_LEXER_UNMATCHED:
                    $renderer->doc .= PluginUtility::renderUnmatched($data);
                    break;
            }
            return true;
        }
        return false;
    }


}
