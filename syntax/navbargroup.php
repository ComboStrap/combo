<?php
/**
 * DokuWiki Syntax Plugin Combostrap.
 *
 */

use ComboStrap\Bootstrap;
use ComboStrap\NavBarUtility;
use ComboStrap\PluginUtility;
use ComboStrap\XmlTagProcessing;


require_once(__DIR__ . '/../vendor/autoload.php');


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
    const COMPONENT = "navbargroup";

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
    public function getAllowedTypes(): array
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
    function getPType(): string
    {
        return 'normal';
    }

    public function accepts($mode): bool
    {
        return syntax_plugin_combo_preformatted::disablePreformatted($mode);
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
            PluginUtility::getModeFromTag(syntax_plugin_combo_navbarcollapse::COMPONENT),
            PluginUtility::getModeFromTag(syntax_plugin_combo_menubar::TAG)
        ];


        if (in_array($mode, $authorizedMode)) {

            $pattern = XmlTagProcessing::getContainerTagPattern(self::TAG);
            $this->Lexer->addEntryPattern($pattern, $mode, PluginUtility::getModeFromTag($this->getPluginComponent()));

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
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES=> $tagAttributes
                );

            case DOKU_LEXER_UNMATCHED:
                return PluginUtility::handleAndReturnUnmatchedData(self::TAG,$match,$handler);


            case DOKU_LEXER_EXIT :

                return array(PluginUtility::STATE => $state);


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

        $state = $data[PluginUtility::STATE];
        if ($format == 'xhtml') {

            /** @var Doku_Renderer_xhtml $renderer */

            switch ($state) {

                case DOKU_LEXER_ENTER :
                    // https://getbootstrap.com/docs/4.0/components/navbar/#toggler splits the navbar-nav to another element
                    // navbar-nav implementation
                    $attributes = $data[PluginUtility::ATTRIBUTES];
                    $classValue = "navbar-nav";
                    if (array_key_exists("class", $attributes)) {
                        $attributes["class"] .= " {$classValue}";
                    } else {
                        $attributes["class"] = $classValue;
                    }

                    if (array_key_exists("expand", $attributes)) {
                        if ($attributes["expand"]=="true") {
                            $bootstrapVersion = Bootstrap::getBootStrapMajorVersion();
                            if($bootstrapVersion== Bootstrap::BootStrapFiveMajorVersion){
                                $attributes["class"] .= " me-auto";
                            } else {
                                $attributes["class"] .= " mr-auto";
                            }
                        }
                        unset($attributes["expand"]);
                    }

                    $inlineAttributes = PluginUtility::array2HTMLAttributesAsString($attributes);
                    $renderer->doc .= "<ul {$inlineAttributes}>" . DOKU_LF;
                    break;
                case DOKU_LEXER_UNMATCHED :
                    $renderer->doc .= NavBarUtility::text(PluginUtility::renderUnmatched($data));
                    break;

                case DOKU_LEXER_EXIT :
                    $renderer->doc .= '</ul>' . DOKU_LF;
                    break;
            }
            return true;
        }
        return false;
    }


}
