<?php
/**
 * DokuWiki Syntax Plugin Combostrap.
 *
 */

use ComboStrap\BackgroundAttribute;
use ComboStrap\ContainerTag;
use ComboStrap\LogUtility;
use ComboStrap\PluginUtility;
use ComboStrap\Site;
use ComboStrap\SiteConfig;
use ComboStrap\TagAttributes;
use ComboStrap\XmlTagProcessing;

if (!defined('DOKU_INC')) {
    die();
}

require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 *
 * The name of the class must follow a pattern (don't change it)
 * ie:
 *    syntax_plugin_PluginName_ComponentName
 *
 * See also: doc :
 * https://getbootstrap.com/docs/5.0/components/navbar/
 * https://material.io/components/app-bars-top
 *
 * Name:
 *  * header bar: http://themenectar.com/docs/salient/theme-options/header-navigation
 *  * menu bar: https://en.wikipedia.org/wiki/Menu_bar
 *  * app bar: https://material.io/components/app-bars-top
 *  * navbar: https://getbootstrap.com/docs/5.0/examples/navbars/#
 */
class syntax_plugin_combo_menubar extends DokuWiki_Syntax_Plugin
{

    const TAG = 'menubar';
    const OLD_TAG = "navbar";
    const TAGS = [self::TAG, self::OLD_TAG];
    const BREAKPOINT_ATTRIBUTE = "breakpoint";
    const POSITION = "position";
    const CANONICAL = self::TAG;
    const THEME_ATTRIBUTE = "theme";
    const ALIGN_ATTRIBUTE = "align";
    const CONTAINER_ATTRIBUTE = "container";

    /**
     * Do we need to add a container
     * @var bool
     */
    private $containerInside = false;

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
     * All
     */
    public function getAllowedTypes()
    {
        return array('container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs');
    }

    public function accepts($mode)
    {

        $accept = syntax_plugin_combo_preformatted::disablePreformatted($mode);


        // Create P element
        if ($mode == "eol") {
            $accept = false;
        }

        return $accept;

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

        foreach (self::TAGS as $tag) {
            $pattern = XmlTagProcessing::getContainerTagPattern($tag);
            $this->Lexer->addEntryPattern($pattern, $mode, PluginUtility::getModeFromTag($this->getPluginComponent()));
        }

    }

    public function postConnect()
    {
        foreach (self::TAGS as $tag) {
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

                $default[BackgroundAttribute::BACKGROUND_COLOR] = 'light';
                $default[self::BREAKPOINT_ATTRIBUTE] = "lg";
                $default[self::THEME_ATTRIBUTE] = "light";
                $default[self::POSITION] = "normal";
                $default[ContainerTag::CONTAINER_ATTRIBUTE] = SiteConfig::getConfValue(
                    ContainerTag::DEFAULT_LAYOUT_CONTAINER_CONF,
                    ContainerTag::DEFAULT_LAYOUT_CONTAINER_DEFAULT_VALUE
                );
                $tagAttributes = TagAttributes::createFromTagMatch($match, $default);
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $tagAttributes->toCallStackArray()
                );

            case DOKU_LEXER_UNMATCHED:

                return PluginUtility::handleAndReturnUnmatchedData(self::TAG, $match, $handler);

            case DOKU_LEXER_EXIT :

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


                    $tagAttributes = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES]);
                    $tagAttributes->addClassName('navbar');


                    /**
                     * Without the expand, the flex has a row direction
                     * and not a column
                     */
                    $breakpoint = $tagAttributes->getValueAndRemoveIfPresent(self::BREAKPOINT_ATTRIBUTE);
                    $tagAttributes->addClassName("navbar-expand-$breakpoint");

                    // Grab the position

                    $position = $tagAttributes->getValueAndRemove(self::POSITION);
                    switch ($position) {
                        case "top":
                            $fixedTopClass = 'fixed-top';
                            /**
                             * We don't set the class directly
                             * because bootstrap uses `position: fixed`
                             * Meaning that the content comes below
                             *
                             * We calculate the padding-top via the javascript but
                             * it will then create a non-wanted layout-shift
                             *
                             * https://getbootstrap.com/docs/5.0/components/navbar/#placement
                             *
                             * We set the class and padding-top with the javascript
                             */
                            $tagAttributes->addOutputAttributeValue("data-type", $fixedTopClass);
                            $fixedTopSnippetId = self::TAG . "-" . $fixedTopClass;
                            // See http://stackoverflow.com/questions/17181355/boostrap-using-fixed-navbar-and-anchor-tags-to-jump-to-sections
                            PluginUtility::getSnippetManager()->attachJavascriptFromComponentId($fixedTopSnippetId);
                            break;
                        case "normal":
                            // nothing
                            break;
                        default:
                            LogUtility::error("The position value ($position) is not yet implemented", self::CANONICAL);
                            break;
                    }

                    // Theming
                    $theme = $tagAttributes->getValueAndRemove(self::THEME_ATTRIBUTE);
                    $tagAttributes->addClassName("navbar-$theme");

                    // Container
                    /**
                     * Deprecated
                     */
                    $align = $tagAttributes->getValueAndRemoveIfPresent(self::ALIGN_ATTRIBUTE);
                    $container = null;
                    if ($align !== null) {
                        LogUtility::warning("The align attribute has been deprecated, you should delete it or use the container instead", self::CANONICAL);

                        // Container
                        if ($align === "center") {
                            $container = "sm";
                        } else {
                            $container = "fluid";
                        }
                    }

                    if ($container === null) {
                        $container = $tagAttributes->getValueAndRemoveIfPresent(self::CONTAINER_ATTRIBUTE);
                    }
                    $containerClass = ContainerTag::getClassName($container);
                    // The container should always be be inside to allow background
                    $tagAttributes->addHtmlAfterEnterTag("<div class=\"$containerClass\">");
                    $renderer->doc .= $tagAttributes->toHtmlEnterTag("nav");

                    break;

                case DOKU_LEXER_UNMATCHED:
                    $renderer->doc .= PluginUtility::renderUnmatched($data);
                    break;

                case DOKU_LEXER_EXIT :
                    $renderer->doc .= "</div></nav>";
                    break;
            }
            return true;
        }
        return false;
    }


}
