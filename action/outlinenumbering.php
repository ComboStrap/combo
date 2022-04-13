<?php

use ComboStrap\ExceptionBadSyntax;
use ComboStrap\ExceptionNotEnabled;
use ComboStrap\PluginUtility;
use ComboStrap\TocUtility;

if (!defined('DOKU_INC')) die();

/**
 * Add the heading numbering snippet
 *
 * Page on DokuWiki
 * https://www.dokuwiki.org/tips:numbered_headings
 */
class action_plugin_combo_outlinenumbering extends DokuWiki_Action_Plugin
{


    const CONF_OUTLINE_NUMBERING_COUNTER_STYLE_LEVEL2 = "outlineNumberingCounterStyleLevel2";
    const CONF_OUTLINE_NUMBERING_COUNTER_STYLE_LEVEL3 = "outlineNumberingCounterStyleLevel3";
    const CONF_OUTLINE_NUMBERING_COUNTER_STYLE_LEVEL4 = "outlineNumberingCounterStyleLevel4";
    const CONF_OUTLINE_NUMBERING_COUNTER_STYLE_LEVEL5 = "outlineNumberingCounterStyleLevel5";
    const CONF_OUTLINE_NUMBERING_COUNTER_STYLE_LEVEL6 = "outlineNumberingCounterStyleLevel6";
    const CONF_OUTLINE_NUMBERING_COUNTER_SEPARATOR = "outlineNumberingCounterSeparator";
    const CONF_OUTLINE_NUMBERING_PREFIX = "outlineNumberingPrefix";
    const CONF_OUTLINE_NUMBERING_SUFFIX = "outlineNumberingSuffix";
    const CANONICAL = "outline";
    const CONF_OUTLINE_NUMBERING_ENABLE = "outlineNumberingEnable";


    const HEADING_NUMBERING = "heading-numbering";
    const TOC_NUMBERING = "toc-numbering";

    /**
     * @param string $type heading or toc
     * @return string
     * @throws ExceptionNotEnabled
     * @throws ExceptionBadSyntax
     */
    public static function getCssOutlineNumberingRuleFor(string $type): string
    {
        $enable = PluginUtility::getConfValue(self::CONF_OUTLINE_NUMBERING_ENABLE, 0);
        if (!$enable) {
            throw new ExceptionNotEnabled();
        }

        $level2CounterStyle = PluginUtility::getConfValue(self::CONF_OUTLINE_NUMBERING_COUNTER_STYLE_LEVEL2, "decimal");
        $level3CounterStyle = PluginUtility::getConfValue(self::CONF_OUTLINE_NUMBERING_COUNTER_STYLE_LEVEL3, "decimal");
        $level4CounterStyle = PluginUtility::getConfValue(self::CONF_OUTLINE_NUMBERING_COUNTER_STYLE_LEVEL4, "decimal");
        $level5CounterStyle = PluginUtility::getConfValue(self::CONF_OUTLINE_NUMBERING_COUNTER_STYLE_LEVEL5, "decimal");
        $level6CounterStyle = PluginUtility::getConfValue(self::CONF_OUTLINE_NUMBERING_COUNTER_STYLE_LEVEL6, "decimal");
        $counterSeparator = PluginUtility::getConfValue(self::CONF_OUTLINE_NUMBERING_COUNTER_SEPARATOR, ".");
        $prefix = PluginUtility::getConfValue(self::CONF_OUTLINE_NUMBERING_PREFIX, "");
        $suffix = PluginUtility::getConfValue(self::CONF_OUTLINE_NUMBERING_SUFFIX, " - ");

        switch ($type) {

            case self::HEADING_NUMBERING:
                global $ACT;
                if ($ACT == "preview") {
                    $mainContainerSelector = ".pad";
                } else {
                    $mainContainerSelector = "#main-content";
                }
                $wikiEnabled = syntax_plugin_combo_headingwiki::isEnabled();
                $sectionElement = "";
                if($wikiEnabled){
                    $sectionElement = "section";
                }
                return <<<EOF
$mainContainerSelector { counter-set: h2 h3 h4 h5 h6; }
$mainContainerSelector $sectionElement h2::before { counter-increment: h2; content: "$prefix" counter(h2, $level2CounterStyle) "$suffix\A"; }
$mainContainerSelector $sectionElement $sectionElement h3::before { counter-increment: h3; content: "$prefix" counter(h2, $level2CounterStyle) "$counterSeparator" counter(h3,$level3CounterStyle) "$suffix\A"; }
$mainContainerSelector $sectionElement $sectionElement $sectionElement h4::before { counter-increment: h4; content: "$prefix" counter(h2, $level2CounterStyle) "$counterSeparator" counter(h3,$level3CounterStyle) "$counterSeparator" counter(h4,$level4CounterStyle) "$suffix\A"; }
$mainContainerSelector $sectionElement $sectionElement $sectionElement $sectionElement h5::before { counter-increment: h5; content: "$prefix" counter(h2, $level2CounterStyle) "$counterSeparator" counter(h3,$level3CounterStyle) "$counterSeparator" counter(h4,$level4CounterStyle) "$counterSeparator" counter(h5,$level5CounterStyle) "$suffix\A"; }
$mainContainerSelector $sectionElement $sectionElement $sectionElement $sectionElement $sectionElement h6::before { counter-increment: h6; content: "$prefix" counter(h2, $level2CounterStyle) "$counterSeparator" counter(h3,$level3CounterStyle) "$counterSeparator" counter(h4,$level4CounterStyle) "$counterSeparator" counter(h5,$level5CounterStyle) "$counterSeparator" counter(h6,$level6CounterStyle) "$suffix\A"; }
EOF;
            case self::TOC_NUMBERING:
                /**
                 * The level counter on the toc are based
                 * on the https://www.dokuwiki.org/config:toptoclevel
                 * configuration
                 * if toptoclevel = 2, then level1 = h2 and not h1
                 * @deprecated
                 */
                // global $conf;
                // $topTocLevel = $conf['toptoclevel'];

                $tocSelector = "#" . TocUtility::TOC_ID;
                return <<<EOF
$tocSelector li { counter-increment: toc2; }
$tocSelector li li { counter-increment: toc3; }
$tocSelector li li li { counter-increment: toc4; }
$tocSelector li li li li { counter-increment: toc5; }
$tocSelector li li li li li { counter-increment: toc6; }
$tocSelector li a::before { content: "$prefix" counter(toc2, $level2CounterStyle) "$suffix\A"; }
$tocSelector li li a::before { content: "$prefix" counter(toc2, $level2CounterStyle) "$counterSeparator" counter(toc3,$level3CounterStyle) "$suffix\A"; }
$tocSelector li li li a::before { content: "$prefix" counter(toc2, $level2CounterStyle) "$counterSeparator" counter(toc3,$level3CounterStyle) "$counterSeparator" counter(toc4,$level4CounterStyle) "$suffix\A"; }
$tocSelector li li li li a::before { content: "$prefix" counter(toc2, $level2CounterStyle) "$counterSeparator" counter(toc3,$level3CounterStyle) "$counterSeparator" counter(toc4,$level4CounterStyle) "$counterSeparator" counter(toc5,$level5CounterStyle) "$suffix\A"; }
$tocSelector li li li li li a::before { content: "$prefix" counter(toc2, $level2CounterStyle) "$counterSeparator" counter(toc3,$level3CounterStyle) "$counterSeparator" counter(toc4,$level4CounterStyle) "$counterSeparator" counter(toc5,$level5CounterStyle) "$counterSeparator" counter(toc6,$level6CounterStyle) "$suffix\A"; }
EOF;

            default:
                throw new ExceptionBadSyntax("The type ($type) is unknown");
        }


    }


    public function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('TPL_ACT_RENDER', 'BEFORE', $this, '_outline_numbering', array());
    }

    /**
     * As seen on
     * https://drafts.csswg.org/css-counter-styles-3/#predefined-counters
     */
    const CONF_COUNTER_STYLES_CHOICES = [
        'arabic-indic',
        'bengali',
        'cambodian/khmer',
        'cjk-decimal',
        'decimal',
        'decimal-leading-zero',
        'devanagari',
        'georgian',
        'gujarati',
        'gurmukhi',
        'hebrew',
        'hiragana',
        'hiragana-iroha',
        'kannada',
        'katakana',
        'katakana-iroha',
        'lao',
        'lower-alpha',
        'lower-armenian',
        'lower-greek',
        'lower-roman',
        'malayalam',
        'mongolian',
        'myanmar',
        'oriya',
        'persian',
        'tamil',
        'telugu',
        'thai',
        'tibetan',
        'upper-alpha',
        'upper-armenian',
        'upper-roman'
    ];

    /**
     *
     * @param $event
     */
    function _outline_numbering($event)
    {

        try {
            $css = self::getCssOutlineNumberingRuleFor(self::HEADING_NUMBERING);
        } catch (ExceptionNotEnabled|ExceptionBadSyntax $e) {
            return;
        }
        PluginUtility::getSnippetManager()->attachCssInternalStylesheetForRequest(self::HEADING_NUMBERING, $css);


    }


}
