<?php

use ComboStrap\CallStack;
use ComboStrap\ExceptionNotFound;
use ComboStrap\HeadingTag;
use ComboStrap\LogUtility;
use ComboStrap\PluginUtility;
use ComboStrap\SiteConfig;
use ComboStrap\TagAttributes;


/**
 * Class headingwiki
 * Taking over {@link \dokuwiki\Parsing\ParserMode\Header}
 */
class syntax_plugin_combo_headingwiki extends DokuWiki_Syntax_Plugin
{

    /**
     * Header pattern
     *   * Dokuwiki does not made a space mandatory after and before the opening an closing `=` character
     *   * No line break in the look ahead
     *   * The capture of the first spaces should be optional otherwise the {@link \dokuwiki\Parsing\ParserMode\Header} is taking over
     *
     * See also for information,
     * the original heading pattern of Dokuwiki {@link \dokuwiki\Parsing\ParserMode\Header}
     */
    const ENTRY_PATTERN = '[ \t]*={1,6}(?=[^\n]*={1,6}\s*\r??\n)';
    const EXIT_PATTERN = '={1,6}\s*(?=\r??\n)';
    const TAG = "headingwiki";

    const CONF_WIKI_HEADING_ENABLE = "headingWikiEnable";
    const CONF_DEFAULT_WIKI_ENABLE_VALUE = 1;


    /**
     * When we takes over the dokuwiki heading
     * we are also taking over the sectioning
     * and allows {@link syntax_plugin_combo_section}
     * @return int - 1 or 0
     */
    public static function isEnabled(): int
    {
        return SiteConfig::getConfValue(self::CONF_WIKI_HEADING_ENABLE, self::CONF_DEFAULT_WIKI_ENABLE_VALUE);
    }

    public function getSort(): int
    {
        /**
         * It's 49 (on less than the original heading)
         * {@link \dokuwiki\Parsing\ParserMode\Header::getSort()}
         */
        return 49;
    }

    public function getType(): string
    {
        return HeadingTag::SYNTAX_TYPE;
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
    public function getPType(): string
    {
        return HeadingTag::SYNTAX_PTYPE;
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
    function getAllowedTypes(): array
    {
        return array('formatting', 'substition', 'protected', 'disabled');
    }


    public function connectTo($mode)
    {
        if ($this->enableWikiHeading($mode)) {
            $this->Lexer->addEntryPattern(self::ENTRY_PATTERN, $mode, PluginUtility::getModeFromTag($this->getPluginComponent()));
        }
    }

    public function postConnect()
    {

        $this->Lexer->addExitPattern(self::EXIT_PATTERN, PluginUtility::getModeFromTag($this->getPluginComponent()));

    }


    /**
     * Handle the syntax
     *
     * At the end of the parser, the `section_open` and `section_close` calls
     * are created in {@link action_plugin_combo_instructionspostprocessing}
     * and the text inside for the toc is captured
     *
     * @param string $match
     * @param int $state
     * @param int $pos
     * @param Doku_Handler $handler
     * @return array
     */
    public function handle($match, $state, $pos, Doku_Handler $handler): array
    {
        switch ($state) {

            case DOKU_LEXER_ENTER:
                /**
                 * Title regexp
                 */
                $level = $this->getLevelFromMatch($match);


                $attributes = TagAttributes::createEmpty(self::TAG)
                    ->addComponentAttributeValue(HeadingTag::LEVEL, $level)
                    ->toCallStackArray();

                $callStack = CallStack::createFromHandler($handler);
                $context = HeadingTag::getContext($callStack);

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $attributes,
                    PluginUtility::CONTEXT => $context,
                    PluginUtility::POSITION => $pos
                );
            case DOKU_LEXER_UNMATCHED :

                return PluginUtility::handleAndReturnUnmatchedData(self::TAG, $match, $handler);

            case DOKU_LEXER_EXIT :

                $returnedData = HeadingTag::handleExit($handler);

                /**
                 * Control of the Number of `=` before and after
                 */
                $callStack = CallStack::createFromHandler($handler);
                $callStack->moveToEnd();
                $openingTag = $callStack->moveToPreviousCorrespondingOpeningCall();
                $levelFromMatch = $this->getLevelFromMatch($match);
                $levelFromStartTag = $openingTag->getAttribute(HeadingTag::LEVEL);
                if ($levelFromMatch != $levelFromStartTag) {
                    $content = "";
                    while ($actualCall = $callStack->next()) {
                        $content .= $actualCall->getCapturedContent();
                    }
                    LogUtility::msg("The number of `=` character for a wiki heading is not the same before ($levelFromStartTag) and after ($levelFromMatch) the content ($content).", LogUtility::LVL_MSG_INFO, HeadingTag::CANONICAL);
                }

                return $returnedData;

        }
        return array();
    }

    public function render($format, Doku_Renderer $renderer, $data): bool
    {

        switch ($format) {
            case "xhtml":
                /**
                 * @var Doku_Renderer_xhtml $renderer
                 */
                $state = $data[PluginUtility::STATE];
                $context = $data[PluginUtility::CONTEXT];
                switch ($state) {

                    case DOKU_LEXER_ENTER:
                        $callStackArray = $data[PluginUtility::ATTRIBUTES];
                        $tagAttributes = TagAttributes::createFromCallStackArray($callStackArray, HeadingTag::HEADING_TAG);
                        $pos = $data[PluginUtility::POSITION];
                        HeadingTag::processRenderEnterXhtml($context, $tagAttributes, $renderer, $pos);
                        return true;
                    case DOKU_LEXER_UNMATCHED:
                        $renderer->doc .= PluginUtility::renderUnmatched($data);
                        return true;
                    case DOKU_LEXER_EXIT:
                        $callStackArray = $data[PluginUtility::ATTRIBUTES];
                        $tagAttributes = TagAttributes::createFromCallStackArray($callStackArray);
                        $renderer->doc .= HeadingTag::renderClosingTag($tagAttributes, $context);
                        return true;

                }
                return false;
            case renderer_plugin_combo_analytics::RENDERER_FORMAT:

                /**
                 * @var renderer_plugin_combo_analytics $renderer
                 */
                HeadingTag::processMetadataAnalytics($data, $renderer);
                return true;

            case "metadata":

                /**
                 * @var Doku_Renderer_metadata $renderer
                 */
                HeadingTag::processHeadingEnterMetadata($data, $renderer);
                return true;

            case renderer_plugin_combo_xml::FORMAT:
                $state = $data[PluginUtility::STATE];
                switch ($state) {
                    case DOKU_LEXER_ENTER:
                        $level = $data[PluginUtility::ATTRIBUTES][HeadingTag::LEVEL];
                        $renderer->doc .= "<h$level>";
                        return true;
                    case DOKU_LEXER_UNMATCHED:
                        $renderer->doc .= PluginUtility::renderUnmatchedXml($data);
                        return true;
                    case DOKU_LEXER_EXIT:
                        $level = $data[PluginUtility::ATTRIBUTES][HeadingTag::LEVEL];
                        $renderer->doc .= "</h$level>";
                        return true;

                }
                return false;
            default:
                return false;
        }

    }

    /**
     * @param $match
     * @return int
     */
    public
    function getLevelFromMatch($match): int
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
        if (!(in_array($mode, ['base', 'header', 'table']))) {
            return true;
        } else {
            return SiteConfig::getConfValue(self::CONF_WIKI_HEADING_ENABLE, self::CONF_DEFAULT_WIKI_ENABLE_VALUE);
        }


    }


}
