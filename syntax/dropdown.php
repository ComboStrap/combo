<?php
/**
 * DokuWiki Syntax Plugin Combostrap.
 *
 */

use ComboStrap\Bootstrap;
use ComboStrap\ExceptionNotFound;
use ComboStrap\ExecutionContext;
use ComboStrap\IdManager;
use ComboStrap\LogUtility;
use ComboStrap\PluginUtility;


require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');


/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 *
 * The name of the class must follow a pattern (don't change it)
 * ie:
 *    syntax_plugin_PluginName_ComponentName
 */
class syntax_plugin_combo_dropdown extends DokuWiki_Syntax_Plugin
{

    const TAG = "dropdown";


    /**
     * Syntax Type.
     *
     * Needs to return one of the mode types defined in $PARSER_MODES in parser.php
     * @see https://www.dokuwiki.org/devel:syntax_plugins#syntax_types
     * @see DokuWiki_Syntax_Plugin::getType()
     */
    function getType(): string
    {
        return 'container';
    }

    public function accepts($mode): bool
    {
        return syntax_plugin_combo_preformatted::disablePreformatted($mode);
    }

    /**
     * @return array
     * Allow which kind of plugin inside
     *
     * An array('container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs')
     */
    public function getAllowedTypes(): array
    {
        return array('formatting', 'substition');
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
    function getPType()
    {
        return 'normal';
    }

    /**
     * @see Doku_Parser_Mode::getSort()
     * Higher number than the teaser-columns
     * because the mode with the lowest sort number will win out
     */
    function getSort()
    {
        return 200;
    }

    /**
     * Create a pattern that will called this plugin
     *
     * @param string $mode
     * @see Doku_Parser_Mode::connectTo()
     */
    function connectTo($mode)
    {


        $pattern = PluginUtility::getContainerTagPattern(self::TAG);
        $this->Lexer->addEntryPattern($pattern, $mode, PluginUtility::getModeFromTag($this->getPluginComponent()));


    }

    public function postConnect()
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

                $linkAttributes = PluginUtility::getTagAttributes($match);
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $linkAttributes
                );

            case DOKU_LEXER_UNMATCHED :

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
    function render($format, Doku_Renderer $renderer, $data)
    {


        if ($format == 'xhtml') {

            /**
             * Cache fighting
             */
            if (isset($data[PluginUtility::STATE])) {
                $state = $data[PluginUtility::STATE];
            } else {
                $state = $data[0];
            }


            /** @var Doku_Renderer_xhtml $renderer */

            switch ($state) {

                case DOKU_LEXER_ENTER :


                    $dropDownId = ExecutionContext::getActualOrCreateFromEnv()
                        ->getIdManager()
                        ->generateNewHtmlIdForComponent(self::TAG);

                    $attributes = $data[PluginUtility::ATTRIBUTES] ?? $data[1];
                    $name = "Name attribute not set";
                    if (array_key_exists("name", $attributes)) {
                        $name = $attributes["name"];
                        unset($attributes["name"]);
                    }
                    PluginUtility::addClass2Attributes("nav-item", $attributes);
                    PluginUtility::addClass2Attributes("dropdown", $attributes);

                    /**
                     * New namespace for data attribute
                     */
                    $bootstrapNameSpace = Bootstrap::getDataNamespace();
                    $dataToggleAttribute = "data{$bootstrapNameSpace}-toggle";
                    $htmlAttributes = PluginUtility::array2HTMLAttributesAsString($attributes);
                    $renderer->doc .= <<<EOF
<li $htmlAttributes>
    <a id="$dropDownId" href="#" class="nav-link dropdown-toggle active" {$dataToggleAttribute}="dropdown" role="button" aria-haspopup="true" aria-expanded="false" title="$name">$name</a>
    <div class="dropdown-menu" aria-labelledby="$dropDownId">
EOF;
                    break;

                case DOKU_LEXER_UNMATCHED :

                    $renderer->doc .= PluginUtility::renderUnmatched($data);
                    break;


                case DOKU_LEXER_EXIT :

                    $renderer->doc .= '</div></li>';

                    // Counter on NULL
                    $this->linkCounter = 0;
                    break;
            }
            return true;
        }
        return false;
    }


}
