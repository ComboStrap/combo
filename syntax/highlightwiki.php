<?php

use ComboStrap\ColorRgb;
use ComboStrap\ExceptionCombo;
use ComboStrap\LogUtility;
use ComboStrap\PluginUtility;
use ComboStrap\Site;
use ComboStrap\TagAttributes;


/**
 *
 * Taking over {@link \dokuwiki\Parsing\ParserMode\Formatting monospace}
 *
 * Known as code
 * https://spec.commonmark.org/0.30/#code-spans
 *
 * note supported but specific highlight is done with two `==`
 * in some processor
 * https://www.markdownguide.org/extended-syntax/#highlight
 *
 */
class syntax_plugin_combo_highlightwiki extends DokuWiki_Syntax_Plugin
{


    const TAG = "highlightwiki";
    // Only on one line
    const ENTRY_PATTERN = "\x27\x27(?=[^\n]*\x27\x27)(?!\n)";


    const CONF_HIGHLIGHT_WIKI_ENABLE = "highlightWikiEnable";
    const CONF_DEFAULT_HIGHLIGHT_WIKI_ENABLE_VALUE = 1;
    const EXIT_PATTERN = "\x27\x27";

    const CANONICAL = self::TAG;

    const HTML_TAG = "mark";

    /**
     * @throws ExceptionCombo
     */
    public static function toBackgroundColor(ColorRgb $primaryColor): ColorRgb
    {
        return $primaryColor
            ->toHsl()
            ->setLightness(98)
            ->toRgb()
            ->toMinimumContrastRatioAgainstWhite(1.1, 1);
    }

    public static function getOpenTagHighlight(string $tag): string
    {
        $htmlTag = self::HTML_TAG;
        if (!Site::isBrandingColorInheritanceEnabled()) {
            return "<$htmlTag>";
        }
        $primaryColor = Site::getPrimaryColor();
        if ($primaryColor === null) {
            return "<$htmlTag>";
        }
        $tagAttributes = TagAttributes::createEmpty($tag);
        try {
            $colorRgb = self::toBackgroundColor($primaryColor);
            $tagAttributes->addComponentAttributeValue(ColorRgb::BACKGROUND_COLOR, $colorRgb
                ->toRgbHex());
        } catch (ExceptionCombo $e) {
            LogUtility::msg("Error on highlight color calculation");
        }
        return $tagAttributes->toHtmlEnterTag($htmlTag);
    }

    public function getSort(): int
    {
        /**
         * It's 49 (on less than the original of 100) named monospace
         * {@link \dokuwiki\Parsing\ParserMode\Formatting}
         */
        return 49;
    }

    public function getType(): string
    {
        return 'formatting';
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
        return 'normal';
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
        $enabled = PluginUtility::getConfValue(self::CONF_HIGHLIGHT_WIKI_ENABLE, self::CONF_DEFAULT_HIGHLIGHT_WIKI_ENABLE_VALUE);
        if ($enabled) {
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
     * are created in {@link action_plugin_combo_headingpostprocessing}
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

            case DOKU_LEXER_EXIT:
            case DOKU_LEXER_ENTER:
                return array(
                    PluginUtility::STATE => $state
                );
            case DOKU_LEXER_UNMATCHED :

                return PluginUtility::handleAndReturnUnmatchedData(self::TAG, $match, $handler);

        }
        return array();
    }

    public function render($format, $renderer, $data): bool
    {

        switch($format) {
            case "xhtml":
            {
                /**
                 * @var Doku_Renderer_xhtml $renderer
                 */
                $state = $data[PluginUtility::STATE];
                switch ($state) {

                    case DOKU_LEXER_ENTER:
                        $renderer->doc .= self::getOpenTagHighlight(self::TAG);
                        return true;
                    case DOKU_LEXER_UNMATCHED:
                        $renderer->doc .= PluginUtility::renderUnmatched($data);
                        return true;
                    case DOKU_LEXER_EXIT:
                        $htmlTag = self::HTML_TAG;
                        $renderer->doc .= "</$htmlTag>";
                        return true;

                }
                break;
            }
            case "metadata":
                /**
                 * @var Doku_Renderer_metadata $renderer
                 */
                $state = $data[PluginUtility::STATE];
                switch ($state) {
                    case DOKU_LEXER_UNMATCHED:
                        $renderer->doc .= PluginUtility::renderUnmatched($data);
                }
                break;
        }

        return false;
    }


}
