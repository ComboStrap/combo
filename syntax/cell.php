<?php
/**
 * Copyright (c) 2020. ComboStrap, Inc. and its affiliates. All Rights Reserved.
 *
 * This source code is licensed under the GPL license found in the
 * COPYING  file in the root directory of this source tree.
 *
 * @license  GPL 3 (https://www.gnu.org/licenses/gpl-3.0.en.html)
 * @author   ComboStrap <support@combostrap.com>
 *
 */

use ComboStrap\Call;
use ComboStrap\CallStack;
use ComboStrap\Dimension;
use ComboStrap\LogUtility;
use ComboStrap\PluginUtility;
use ComboStrap\TagAttributes;


require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');

/**
 *
 *
 *
 * @deprecated - flex item are created now with the {@link \ComboStrap\Align} attribute
 * and the col class is set now on the row class.
 */
class syntax_plugin_combo_cell extends DokuWiki_Syntax_Plugin
{

    const TAG = "cell";

    const WIDTH_ATTRIBUTE = Dimension::WIDTH_KEY;
    const FLEX_CLASS = "d-flex";


    static function getTags(): array
    {
        return [self::TAG, "col", "column"];
    }

    /**
     * Syntax Type.
     *
     * Needs to return one of the mode types defined in $PARSER_MODES in parser.php
     * @see DokuWiki_Syntax_Plugin::getType()
     */
    function getType(): string
    {
        return 'container';
    }

    /**
     * @return array
     * Allow which kind of plugin inside
     * All
     */
    public function getAllowedTypes(): array
    {
        return array('container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs');
    }

    public function accepts($mode): bool
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
        return 'stack';
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

        // A cell can be anywhere
        foreach (self::getTags() as $tag) {
            $pattern = PluginUtility::getContainerTagPattern($tag);
            $this->Lexer->addEntryPattern($pattern, $mode, PluginUtility::getModeFromTag($this->getPluginComponent()));
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
     * @see DokuWiki_Syntax_Plugin::handle()
     *
     */
    function handle($match, $state, $pos, Doku_Handler $handler)
    {

        switch ($state) {

            case DOKU_LEXER_ENTER:

                $knownTypes = [];
                $defaultAttributes = [];
                $attributes = TagAttributes::createFromTagMatch($match, $defaultAttributes, $knownTypes)->toCallStackArray();

                LogUtility::warning("Cell/Col has been deprecated. You can use now any component in a row.", syntax_plugin_combo_grid::TAG);
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $attributes);

            case DOKU_LEXER_UNMATCHED:
                return PluginUtility::handleAndReturnUnmatchedData(self::TAG, $match, $handler);

            case DOKU_LEXER_EXIT :

                $callStack = CallStack::createFromHandler($handler);
                $openingTag = $callStack->moveToPreviousCorrespondingOpeningCall();
                $firstChild = $callStack->moveToFirstChildTag();

                /**
                 * A cell is a flex container that helps place its children
                 * It should contain one or more container
                 * It should have at minimum one
                 */
                $addChildContainer = true;
                if ($firstChild !== false) {
                    if (in_array($firstChild->getTagName(), TagAttributes::CONTAINER_LOGICAL_ELEMENTS)) {
                        $addChildContainer = false;
                    }
                }
                if ($addChildContainer === true) {
                    /**
                     * A cell should have one or more container as child
                     * If the container is not in the markup, we add it
                     */
                    $callStack->moveToCall($openingTag);
                    $callStack->insertAfter(
                        Call::createComboCall(
                            syntax_plugin_combo_box::TAG,
                            DOKU_LEXER_ENTER
                        ));
                    $callStack->moveToEnd();
                    $callStack->insertBefore(
                        Call::createComboCall(
                            syntax_plugin_combo_box::TAG,
                            DOKU_LEXER_EXIT
                        ));
                }


                return array(
                    PluginUtility::STATE => $state
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

                    PluginUtility::getSnippetManager()->attachCssInternalStyleSheetForSlot(self::TAG);
                    $callStackArray = $data[PluginUtility::ATTRIBUTES];
                    $attributes = TagAttributes::createFromCallStackArray($callStackArray, self::TAG);
                    /**
                     * A flex to be able to align the children (horizontal/vertical)
                     * if they are constraint in width
                     */
                    $attributes->addClassName(self::FLEX_CLASS);
                    /**
                     * Horizontal (center)
                     */
                    $attributes->addClassName("justify-content-center");

                    $renderer->doc .= $attributes->toHtmlEnterTag("div");
                    break;

                case DOKU_LEXER_UNMATCHED :

                    $renderer->doc .= PluginUtility::renderUnmatched($data);
                    break;

                case DOKU_LEXER_EXIT :

                    $renderer->doc .= '</div>';
                    break;
            }
            return true;
        }
        return false;
    }


}
