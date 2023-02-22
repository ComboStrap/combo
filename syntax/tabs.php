<?php
/**
 * DokuWiki Syntax Plugin Combostrap.
 *
 */

use ComboStrap\Bootstrap;
use ComboStrap\Call;
use ComboStrap\CallStack;
use ComboStrap\LogUtility;
use ComboStrap\PluginUtility;
use ComboStrap\Spacing;
use ComboStrap\TabsTag;
use ComboStrap\TagAttributes;


/**
 *
 * The name of the class must follow a pattern (don't change it)
 * ie:
 *    syntax_plugin_PluginName_ComponentName
 *
 */
class syntax_plugin_combo_tabs extends DokuWiki_Syntax_Plugin
{


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
    public
    function getAllowedTypes()
    {
        return array('container', 'base', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs');
    }

    public
    function accepts($mode)
    {
        /**
         * header mode is disable to take over
         * and replace it with {@link syntax_plugin_combo_heading}
         */
        if ($mode == "header") {
            return false;
        }
        /**
         * If preformatted is disable, we does not accept it
         */
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

        $pattern = PluginUtility::getContainerTagPattern(TabsTag::TAG);
        $this->Lexer->addEntryPattern($pattern, $mode, PluginUtility::getModeFromTag($this->getPluginComponent()));

    }

    public
    function postConnect()
    {

        $this->Lexer->addExitPattern('</' . TabsTag::TAG . '>', PluginUtility::getModeFromTag($this->getPluginComponent()));

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

                $knownTypes = [TabsTag::ENCLOSED_PILLS_TYPE, TabsTag::ENCLOSED_TABS_TYPE, TabsTag::PILLS_TYPE, TabsTag::TABS_TYPE];
                $attributes = TagAttributes::createFromTagMatch($match,[],$knownTypes);

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $attributes->toCallStackArray());

            case DOKU_LEXER_UNMATCHED:

                // We should never get there but yeah ...
                return PluginUtility::handleAndReturnUnmatchedData(TabsTag::TAG, $match, $handler);


            case DOKU_LEXER_EXIT :

                $callStack = CallStack::createFromHandler($handler);
                $openingTag = $callStack->moveToPreviousCorrespondingOpeningCall();
                $previousOpeningTag = $callStack->previous();
                $callStack->next();
                $firstChild = $callStack->moveToFirstChildTag();
                $context = null;
                if ($firstChild !== false) {
                    /**
                     * Add the context to the opening and ending tag
                     */
                    $context = $firstChild->getTagName();
                    $openingTag->setContext($context);
                    /**
                     * Does tabs enclosed Panel (new syntax)
                     */
                    if ($context == syntax_plugin_combo_panel::TAG) {

                        /**
                         * We scan the tabs and derived:
                         * * the navigation tabs element (ie ul/li nav-tab)
                         * * the tab pane element
                         */

                        /**
                         * This call will create the ul
                         * <ul class="nav nav-tabs mb-3" role="tablist">
                         * thanks to the {@link TabsTag::NAVIGATION_CONTEXT}
                         */
                        $navigationalCalls[] = Call::createComboCall(
                            TabsTag::TAG,
                            DOKU_LEXER_ENTER,
                            $openingTag->getAttributes(),
                            TabsTag::NAVIGATION_CONTEXT
                        );

                        /**
                         * The tab pane elements
                         */
                        $tabPaneCalls = [$openingTag, $firstChild];

                        /**
                         * Copy the stack
                         */
                        $labelState = "label";
                        $nonLabelState = "non-label";
                        $scanningState = $nonLabelState;
                        while ($actual = $callStack->next()) {

                            if (
                                $actual->getTagName() == syntax_plugin_combo_label::TAG
                                &&
                                $actual->getState() == DOKU_LEXER_ENTER
                            ) {
                                $scanningState = $labelState;
                            }

                            if ($labelState === $scanningState) {
                                $navigationalCalls[] = $actual;
                            } else {
                                $tabPaneCalls[] = $actual;
                            }

                            if (
                                $actual->getTagName() == syntax_plugin_combo_label::TAG
                                &&
                                $actual->getState() == DOKU_LEXER_EXIT
                            ) {
                                $scanningState = $nonLabelState;
                            }


                        }

                        /**
                         * End navigational tabs
                         */
                        $navigationalCalls[] = Call::createComboCall(
                            TabsTag::TAG,
                            DOKU_LEXER_EXIT,
                            $openingTag->getAttributes(),
                            TabsTag::NAVIGATION_CONTEXT
                        );

                        /**
                         * Rebuild
                         */
                        $callStack->deleteAllCallsAfter($previousOpeningTag);
                        $callStack->appendCallsAtTheEnd($navigationalCalls);
                        $callStack->appendCallsAtTheEnd($tabPaneCalls);

                    }
                }

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::CONTEXT => $context,
                    PluginUtility::ATTRIBUTES => $openingTag->getAttributes()
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
            $tagAttributes = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES]);
            $context = $data[PluginUtility::CONTEXT];

            switch ($state) {

                case DOKU_LEXER_ENTER :

                    switch ($context) {
                        /**
                         * When the tag tabs enclosed the panels
                         */
                        case syntax_plugin_combo_panel::TAG:
                            $renderer->doc .= TabsTag::openTabPanelsElement($tagAttributes);
                            break;
                        /**
                         * When the tag tabs are derived (new syntax)
                         */
                        case TabsTag::NAVIGATION_CONTEXT:
                            /**
                             * Old syntax, when the tag had to be added specifically
                             */
                        case syntax_plugin_combo_tab::TAG:
                            $renderer->doc .= TabsTag::openNavigationalTabsElement($tagAttributes);
                            break;
                        default:
                            LogUtility::log2FrontEnd("The context ($context) is unknown in enter", LogUtility::LVL_MSG_ERROR, TabsTag::TAG);

                    }


                    break;
                case DOKU_LEXER_EXIT :

                    switch ($context) {
                        /**
                         * New syntax (tabpanel enclosing)
                         */
                        case syntax_plugin_combo_panel::TAG:
                            $renderer->doc .= TabsTag::closeTabPanelsElement($tagAttributes);
                            break;
                        /**
                         * Old syntax
                         */
                        case syntax_plugin_combo_tab::TAG:
                            /**
                             * New syntax (Derived)
                             */
                        case TabsTag::NAVIGATION_CONTEXT:
                            $tagAttributes = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES]);
                            $type = TabsTag::getComponentType($tagAttributes);
                            $renderer->doc .= TabsTag::closeNavigationalHeaderComponent($type);
                            break;
                        default:
                            LogUtility::log2FrontEnd("The context $context is unknown in exit", LogUtility::LVL_MSG_ERROR, TabsTag::TAG);
                    }
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
