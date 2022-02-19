<?php


// must be run within Dokuwiki
use ComboStrap\Bootstrap;
use ComboStrap\ColorRgb;
use ComboStrap\ExceptionCombo;
use ComboStrap\LogUtility;
use ComboStrap\PluginUtility;
use ComboStrap\Site;
use ComboStrap\Skin;
use ComboStrap\Tag;
use ComboStrap\TagAttributes;

if (!defined('DOKU_INC')) die();

/**
 * Class syntax_plugin_combo_badge
 * Implementation of a badge
 * called an alert in <a href="https://getbootstrap.com/docs/4.0/components/badge/">bootstrap</a>
 */
class syntax_plugin_combo_badge extends DokuWiki_Syntax_Plugin
{

    const TAG = "badge";

    const CONF_DEFAULT_ATTRIBUTES_KEY = 'defaultBadgeAttributes';

    const ATTRIBUTE_TYPE = "type";
    const ATTRIBUTE_ROUNDED = "rounded";

    /**
     * Syntax Type.
     *
     * Needs to return one of the mode types defined in $PARSER_MODES in parser.php
     * @see https://www.dokuwiki.org/devel:syntax_plugins#syntax_types
     * @see DokuWiki_Syntax_Plugin::getType()
     */
    function getType()
    {
        return 'formatting';
    }

    /**
     * How Dokuwiki will add P element
     *
     *  * 'normal' - The plugin can be used inside paragraphs (inline or inside)
     *  * 'block'  - Open paragraphs need to be closed before plugin output (box) - block should not be inside paragraphs
     *  * 'stack'  - Special case. Plugin wraps other paragraphs. - Stacks can contain paragraphs
     *
     * @see DokuWiki_Syntax_Plugin::getPType()
     * @see https://www.dokuwiki.org/devel:syntax_plugins#ptype
     */
    function getPType()
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
    function getAllowedTypes()
    {
        return array('container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs');
    }

    /**
     * @see Doku_Parser_Mode::getSort()
     * the mode with the lowest sort number will win out
     */
    function getSort()
    {
        return 201;
    }


    function connectTo($mode)
    {

        $pattern = PluginUtility::getContainerTagPattern(self::TAG);
        $this->Lexer->addEntryPattern($pattern, $mode, PluginUtility::getModeFromTag($this->getPluginComponent()));

    }

    function postConnect()
    {

        $this->Lexer->addExitPattern('</' . self::TAG . '>', PluginUtility::getModeFromTag($this->getPluginComponent()));

    }

    /**
     *
     * The handle function goal is to parse the matched syntax through the pattern function
     * and to return the result for use in the renderer
     * This result is always cached until the page is modified.
     * @param string $match
     * @param int $state
     * @param int $pos - byte position in the original source file
     * @param Doku_Handler $handler
     * @return array|bool
     * @see DokuWiki_Syntax_Plugin::handle()
     *
     */
    function handle($match, $state, $pos, Doku_Handler $handler)
    {

        switch ($state) {

            case DOKU_LEXER_ENTER :

                $defaultConfValue = PluginUtility::parseAttributes($this->getConf(self::CONF_DEFAULT_ATTRIBUTES_KEY));

                $knownTypes = ["primary", "secondary", "success", "danger", "warning", "info", "tip", "light", "dark"];
                $tagAttributes = TagAttributes::createFromTagMatch($match, $defaultConfValue, $knownTypes);


                /**
                 * Brand and tip colors
                 */
                $tagAttributes->addClassName("badge");
                $type = $tagAttributes->getType();
                $color = null;
                switch ($type) {
                    case "tip":
                        $color = ColorRgb::TIP_COLOR;
                        break;
                    case ColorRgb::PRIMARY_VALUE:
                        $color = Site::getPrimaryColorValue();
                        break;
                    case ColorRgb::SECONDARY_VALUE:
                        $color = Site::getSecondaryColorValue();
                        break;
                    default:
                }
                $colorObject = null;
                if ($color !== null) {
                    try {
                        $colorObject = ColorRgb::createFromString($color);
                    } catch (ExceptionCombo $e) {
                        LogUtility::msg("The color value ($color) for the badge type ($type) is not valid. Error: {$e->getMessage()}");
                    }
                }
                if ($colorObject !== null) {
                    /**
                     * https://getbootstrap.com/docs/5.0/components/alerts/
                     * $alert-bg-scale:                -80%;
                     * $alert-border-scale:            -70%;
                     * $alert-color-scale:             40%;
                     */
                    $backgroundColor = $tagAttributes->getValue(ColorRgb::BACKGROUND_COLOR);
                    if ($backgroundColor === null) {
                        try {
                            $backgroundColor = $colorObject
                                ->scale(-80)
                                ->toHsl()
                                ->setLightness(80)
                                ->toRgb()
                                ->toCssValue();
                        } catch (ExceptionCombo $e) {
                            LogUtility::msg("Error while trying to set the lightness for the badge background color");
                            $backgroundColor = $colorObject
                                ->scale(-80)
                                ->toCssValue();
                        }
                        $tagAttributes->addStyleDeclarationIfNotSet(ColorRgb::BACKGROUND_COLOR, $backgroundColor);
                    }
                    if (!$tagAttributes->hasComponentAttribute(ColorRgb::BORDER_COLOR)) {
                        $borderColor = $colorObject->scale(-70)->toCssValue();
                        $tagAttributes->addStyleDeclarationIfNotSet(ColorRgb::BORDER_COLOR, $borderColor);
                    }
                    if (!$tagAttributes->hasComponentAttribute(ColorRgb::COLOR)) {
                        try {
                            $textColor = $colorObject
                                ->scale(40)
                                ->toMinimumContrastRatio($backgroundColor)
                                ->toCssValue();
                        } catch (ExceptionCombo $e) {
                            LogUtility::msg("Error while scaling the text color ($color) for the badge type ($type). Error: {$e->getMessage()}");
                            $textColor = $colorObject
                                ->scale(40)
                                ->toCssValue();
                        }
                        $tagAttributes->addStyleDeclarationIfNotSet(ColorRgb::COLOR, $textColor);
                    }
                } else {
                    $tagAttributes->addClassName("alert-" . $type);
                }

                $rounded = $tagAttributes->getValueAndRemove(self::ATTRIBUTE_ROUNDED);
                if (!empty($rounded)) {
                    $badgePillClass = "badge-pill";
                    if (Bootstrap::getBootStrapMajorVersion() == Bootstrap::BootStrapFiveMajorVersion) {
                        // https://getbootstrap.com/docs/5.0/migration/#badges-1
                        $badgePillClass = "rounded-pill";
                    }
                    $tagAttributes->addClassName($badgePillClass);
                }

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $tagAttributes->toCallStackArray()
                );

            case DOKU_LEXER_UNMATCHED :
                return PluginUtility::handleAndReturnUnmatchedData(self::TAG, $match, $handler);

            case DOKU_LEXER_EXIT :

                // Important otherwise we don't get an exit in the render
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
    function render($format, Doku_Renderer $renderer, $data)
    {
        if ($format == 'xhtml') {

            /** @var Doku_Renderer_xhtml $renderer */
            $state = $data[PluginUtility::STATE];
            switch ($state) {

                case DOKU_LEXER_ENTER :

                    PluginUtility::getSnippetManager()->attachCssInternalStyleSheetForSlot(self::TAG);

                    $attributes = $data[PluginUtility::ATTRIBUTES];
                    $tagAttributes = TagAttributes::createFromCallStackArray($attributes, self::TAG);
                    // badge on boostrap does not allow
                    $tagAttributes->addStyleDeclarationIfNotSet("white-space", "normal");
                    $renderer->doc .= $tagAttributes->toHtmlEnterTag("span") . DOKU_LF;
                    break;

                case DOKU_LEXER_UNMATCHED :
                    $renderer->doc .= PluginUtility::renderUnmatched($data);
                    break;

                case DOKU_LEXER_EXIT :
                    $renderer->doc .= "</span>";
                    break;

            }
            return true;
        }

        // unsupported $mode
        return false;
    }


}

