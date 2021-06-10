<?php

use ComboStrap\CallStack;
use ComboStrap\LogUtility;
use ComboStrap\PluginUtility;
use ComboStrap\TagAttributes;


/**
 * Class headingwiki
 * Taking over {@link \dokuwiki\Parsing\ParserMode\Header}
 */
class syntax_plugin_combo_headingwiki extends DokuWiki_Syntax_Plugin
{

    /**
     * Header pattern that we expect ie  ==== Hello =====
     * Found in {@link \dokuwiki\Parsing\ParserMode\Header}
     * One modification is that it permits one `=` to get the h6
     */

    const ENTRY_PATTERN = '^[\s\t]*={1,6}(?=.*={1,6}\s*\r??\n)';
    const EXIT_PATTERN = '={1,6}\s*(?=\r??\n)';
    const TAG = "headingwiki";

    const CONF_WIKI_HEADING_ENABLE = "headingWikiEnable";
    const CONF_DEFAULT_WIKI_ENABLE_VALUE = 1;

    public function getSort()
    {
        /**
         * Less than 50 from
         * {@link \dokuwiki\Parsing\ParserMode\Header::getSort()}
         */
        return 49;
    }

    public function getType()
    {
        return syntax_plugin_combo_heading::SYNTAX_TYPE;
    }


    /**
     *
     * How Dokuwiki will add P element
     *
     *  * 'normal' - The plugin can be used inside paragraphs (inline)
     *  * 'block'  - Open paragraphs need to be closed before plugin output - block should not be inside paragraphs
     *  * 'stack'  - Special case. Plugin wraps other paragraphs. - Stacks can contain paragraphs
     *
     * @see DokuWiki_Syntax_Plugin::getPType()
     *
     * This is the equivalent of inline or block for css
     */
    public function getPType()
    {
        return syntax_plugin_combo_heading::SYNTAX_PTYPE;
    }

    /**
     * @return array
     * Allow which kind of plugin inside
     *
     * No one of array('baseonly','container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs')
     * because we manage self the content and we call self the parser
     *
     * Return an array of one or more of the mode types {@link $PARSER_MODES} in Parser.php
     */
    function getAllowedTypes()
    {
        return array('formatting', 'substition', 'protected', 'disabled');
    }


    public function connectTo($mode)
    {
        if ($this->enableWikiHeading($mode)) {
            $this->Lexer->addEntryPattern(self::ENTRY_PATTERN, $mode, PluginUtility::getModeForComponent($this->getPluginComponent()));
        }
    }

    public function postConnect()
    {

        $this->Lexer->addExitPattern(self::EXIT_PATTERN, PluginUtility::getModeForComponent($this->getPluginComponent()));

    }


    /**
     * Handle the syntax
     *
     * At the end of the parser, the `section_open` and `section_close` calls
     * are created in {@link action_plugin_combo_headingpostprocessing}
     * and the text inside for the toc is captured
     *
     * @param string $match
     * @param int $state
     * @param int $pos
     * @param Doku_Handler $handler
     * @return array
     */
    public function handle($match, $state, $pos, Doku_Handler $handler)
    {
        switch ($state) {

            case DOKU_LEXER_ENTER:
                /**
                 * Title regexp
                 */
                $attributes[syntax_plugin_combo_heading::LEVEL] = $this->getLevelFromMatch($match);
                $callStack = CallStack::createFromHandler($handler);

                $parentTag = $callStack->moveToParent();
                $context = syntax_plugin_combo_heading::getContext($parentTag);

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $attributes,
                    PluginUtility::CONTEXT => $context
                );
            case DOKU_LEXER_UNMATCHED :

                return PluginUtility::handleAndReturnUnmatchedData(self::TAG, trim($match), $handler);

            case DOKU_LEXER_EXIT :
                $callStack = CallStack::createFromHandler($handler);
                $openingTag = $callStack->moveToPreviousCorrespondingOpeningCall();
                $openingAttributes = $openingTag->getAttributes();

                // Control of the Number of `=` before and after
                $levelFromMatch = $this->getLevelFromMatch($match);
                $levelFromStartTag = $openingAttributes[syntax_plugin_combo_heading::LEVEL];
                if ($levelFromMatch != $levelFromStartTag) {
                    $content = "";
                    while ($actualCall = $callStack->next()) {
                        $content .= $actualCall->getMatchedContent();
                    }
                    LogUtility::msg("The number of `=` character for a wiki heading is not the same before ($levelFromStartTag) and after ($levelFromMatch) the content ($content).", LogUtility::LVL_MSG_WARNING, syntax_plugin_combo_heading::CANONICAL);
                }
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $openingTag->getAttributes()
                );

        }
        return array();
    }

    public function render($format, Doku_Renderer $renderer, $data)
    {

        if ($format == "xhtml") {
            /**
             * @var Doku_Renderer_xhtml $renderer
             */
            $state = $data[PluginUtility::STATE];
            switch ($state) {

                case DOKU_LEXER_ENTER:
                    $callStackArray = $data[PluginUtility::ATTRIBUTES];
                    $tagAttributes = TagAttributes::createFromCallStackArray($callStackArray, syntax_plugin_combo_heading::TAG);
                    $context = $data[PluginUtility::CONTEXT];
                    syntax_plugin_combo_heading::renderOpeningTag($context, $tagAttributes, $renderer);
                    return true;
                case DOKU_LEXER_UNMATCHED:
                    $renderer->doc .= PluginUtility::renderUnmatched($data);
                    return true;
                case DOKU_LEXER_EXIT:
                    $callStackArray = $data[PluginUtility::ATTRIBUTES];
                    $tagAttributes = TagAttributes::createFromCallStackArray($callStackArray);
                    $renderer->doc .= syntax_plugin_combo_heading::renderClosingTag($tagAttributes);
                    return true;

            }
        } else if ($format == renderer_plugin_combo_analytics::RENDERER_FORMAT) {

            /**
             * @var renderer_plugin_combo_analytics $renderer
             */
            syntax_plugin_combo_heading::processMetadataAnalytics($data,$renderer);

        } else if ($format == "metadata") {

            /**
             * @var Doku_Renderer_metadata $renderer
             */
            syntax_plugin_combo_heading::processHeadingMetadata($data, $renderer);

        }

        return false;
    }

    /**
     * @param $match
     * @return int
     */
    public
    function getLevelFromMatch($match)
    {
        return 7 - strlen(trim($match));
    }


    private
    function enableWikiHeading($mode)
    {
        /**
         * Basically all mode that are not `base`
         * To not take the dokuwiki heading
         */
        if (!(in_array($mode, ['base', 'header']))) {
            return true;
        } else {
            return PluginUtility::getConfValue(self::CONF_WIKI_HEADING_ENABLE, self::CONF_DEFAULT_WIKI_ENABLE_VALUE);
        }


    }




}
