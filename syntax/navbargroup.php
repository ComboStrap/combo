<?php
/**
 * DokuWiki Syntax Plugin Combostrap.
 *
 */

use ComboStrap\HtmlUtility;
use ComboStrap\LinkUtility;
use ComboStrap\NavBarUtility;
use ComboStrap\PluginUtility;

if (!defined('DOKU_INC')) {
    die();
}

require_once(__DIR__ . '/../class/PluginUtility.php');
require_once(__DIR__ . '/../class/LinkUtility.php');
require_once(__DIR__ . '/../class/HtmlUtility.php');

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 *
 * The name of the class must follow a pattern (don't change it)
 * ie:
 *    syntax_plugin_PluginName_ComponentName
 *
 * A navbar group is a navbar nav
 */
class syntax_plugin_combo_navbargroup extends DokuWiki_Syntax_Plugin
{

    const TAG = "group";

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
        return 'normal';
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
         * Only inside a navbar or collapse element
         */
        $authorizedMode = [
            PluginUtility::getModeForComponent(syntax_plugin_combo_navbarcollapse::COMPONENT),
            PluginUtility::getModeForComponent(syntax_plugin_combo_navbar::COMPONENT)
        ];


        if (in_array($mode, $authorizedMode)) {

            $pattern = PluginUtility::getContainerTagPattern(self::TAG);
            $this->Lexer->addEntryPattern($pattern, $mode, PluginUtility::getModeForComponent($this->getPluginComponent()));
            $this->Lexer->addPattern(LinkUtility::LINK_PATTERN, PluginUtility::getModeForComponent($this->getPluginComponent()));

        }

    }

    public function postConnect()
    {
        $this->Lexer->addExitPattern('</' . self::TAG . '>', 'plugin_' . PluginUtility::PLUGIN_BASE_NAME . '_' . $this->getPluginComponent());

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

                // Suppress the component name
                $tagAttributes = PluginUtility::getTagAttributes($match);
                return array($state, $tagAttributes);

            case DOKU_LEXER_UNMATCHED:
                return array($state, $match);

            case DOKU_LEXER_MATCHED:

                $linkAttributes = LinkUtility::getAttributes($match);
                return array($state, $linkAttributes);

            case DOKU_LEXER_EXIT :

                return array($state, '');


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

        list($state, $payload) = $data;
        if ($format == 'xhtml') {

            /** @var Doku_Renderer_xhtml $renderer */

            switch ($state) {

                case DOKU_LEXER_ENTER :
                    // https://getbootstrap.com/docs/4.0/components/navbar/#toggler splits the navbar-nav to another element
                    // navbar-nav implementation

                    $classValue = "navbar-nav";
                    if (array_key_exists("class", $payload)) {
                        $payload["class"] .= " {$classValue}";
                    } else {
                        $payload["class"] = $classValue;
                    }

                    if (array_key_exists("expand", $payload)) {
                        if ($payload["expand"]=="true") {
                            $payload["class"] .= " mr-auto";
                        }
                        unset($payload["expand"]);
                    }

                    $inlineAttributes = PluginUtility::array2HTMLAttributes($payload);
                    $renderer->doc .= "<ul {$inlineAttributes}>" . DOKU_LF;
                    break;
                case DOKU_LEXER_UNMATCHED :

                    $renderer->doc .= NavBarUtility::text(PluginUtility::escape($payload));
                    break;

                case DOKU_LEXER_MATCHED:

                    $html = LinkUtility::renderAsAnchorElement($renderer, $payload);
                    $renderer->doc .= '<li class="nav-item">'.NavBarUtility::switchDokuwiki2BootstrapClass($html).'</li>';
                    break;

                case DOKU_LEXER_EXIT :
                    $renderer->doc .= '</ul>' . DOKU_LF;
                    break;
            }
            return true;
        } else if ($format == 'metadata' && $state == DOKU_LEXER_MATCHED) {

            /**
             * Keep track of the backlinks ie meta['relation']['references']
             * @var Doku_Renderer_metadata $renderer
             */
            LinkUtility::handleMetadata($renderer, $payload);
            return true;

        }
        return false;
    }


}
