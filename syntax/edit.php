<?php

use ComboStrap\EditButton;
use ComboStrap\ExceptionBadArgument;
use ComboStrap\ExceptionNotEnabled;
use ComboStrap\LogUtility;
use ComboStrap\PluginUtility;
use ComboStrap\Site;
use ComboStrap\TagAttributes;


/**
 *
 * An edit button
 *
 * This is created in the parse tree for the following reason
 *
 *   * We need the start and end position (easier to catch at the parse tree creation because they are given in the handle function)
 *   * A component may not allow them (for instance:
 *       * an iterator will not allow edit button and delete them
 *       * or {@link syntax_plugin_combo_webcode}
 *   * The wiki id is mandatory and is given as global id in the handle function. In render, we may compose several parse tree (call stack)
 *
 */
class syntax_plugin_combo_edit extends DokuWiki_Syntax_Plugin
{

    const TAG = "edit";
    const CANONICAL = self::TAG;
    const START_POSITION = "start-position";
    const END_POSITION = "end-position";
    const LABEL = "label";

    /**
     * Syntax Type.
     *
     * Needs to return one of the mode types defined in $PARSER_MODES in parser.php
     * @see DokuWiki_Syntax_Plugin::getType()
     */
    function getType(): string
    {
        return 'substition';
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
    function getPType(): string
    {
        return 'block';
    }

    /**
     * @return array
     * Allow which kind of plugin inside
     *
     * Array('baseonly','container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs')
     *
     * Return an array of one or more of the mode types {@link $PARSER_MODES} in Parser.php
     */
    function getAllowedTypes(): array
    {
        return array();
    }


    function getSort(): int
    {
        return 201;
    }


    function connectTo($mode)
    {
        /**
         * Call is generated in {@link action_plugin_combo_headingpostprocessing}
         */
    }


    function postConnect()
    {

        /**
         * Call is generated in {@link action_plugin_combo_headingpostprocessing}
         */

    }

    function handle($match, $state, $pos, Doku_Handler $handler): array
    {

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

        /**
         *
         * The rendering is used only when exporting to another format
         * (XML) to render on another platform such as mobile
         */
        if ($format !== "xhtml") {
            return false;
        }

        $state = $data[PluginUtility::STATE];
        if ($state !== DOKU_LEXER_SPECIAL) {
            LogUtility::error("The edit button should be a special tag", self::CANONICAL);
            return false;
        }

        $editButton = EditButton::createFromCallStackArray($data[PluginUtility::ATTRIBUTES]);
        try {
            $renderer->doc .= " ".$editButton->toHtmlComment();
        } catch (ExceptionBadArgument $e) {
            LogUtility::error("Error while rendering the edit button ($editButton). Error: {$e->getMessage()}", self::CANONICAL);
            return false;
        } catch (ExceptionNotEnabled $e) {
            // ok
            return false;
        }
        return true;

    }


}

