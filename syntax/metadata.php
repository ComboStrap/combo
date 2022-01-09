<?php


use ComboStrap\MetadataUtility;
use ComboStrap\PluginUtility;

if (!defined('DOKU_INC')) die();

require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');

/**
 * Class syntax_plugin_combo_metadata
 * Add the metadata box
 */
class syntax_plugin_combo_metadata extends DokuWiki_Syntax_Plugin
{
    /**
     * A regular expression to filter the output
     */
    public const EXCLUDE_ATTRIBUTE = "exclude";
    /**
     * The default attributes
     */
    public const CONF_METADATA_DEFAULT_ATTRIBUTES = "metadataViewerDefaultAttributes";
    public const TITLE_ATTRIBUTE = "title";
    /**
     * The HTML tag
     */
    public const TAG = "metadata";
    /**
     * The HTML id of the box (for testing purpose)
     */
    public const META_MESSAGE_BOX_ID = "metadata-viewer";

    /**
     *
     * @param \dokuwiki\Extension\Plugin $plugin - the calling dokuwiki plugin
     * @param $inlineAttributes - the inline attribute of a component if any
     * @return string - an HTML box of the array
     */
    public static function getHtmlMetadataBox($plugin, $inlineAttributes = array()): string
    {

        // Attributes processing
        $defaultStringAttributes = $plugin->getConf(self::CONF_METADATA_DEFAULT_ATTRIBUTES);
        $defaultAttributes = PluginUtility::parseAttributes($defaultStringAttributes);
        $attributes = PluginUtility::mergeAttributes($inlineAttributes, $defaultAttributes);

        // Building the box
        $content = '<div id="' . self::META_MESSAGE_BOX_ID . '" class="alert alert-success " role="note">';
        if (array_key_exists(self::TITLE_ATTRIBUTE, $attributes)) {
            $content .= '<h2 class="alert-heading" ">' . $attributes[self::TITLE_ATTRIBUTE] . '</h2>';
        }
        global $ID;
        $metadata = p_read_metadata($ID);
        $metas = $metadata['persistent'];


        if (array_key_exists(self::EXCLUDE_ATTRIBUTE, $attributes)) {
            $filter = $attributes[self::EXCLUDE_ATTRIBUTE];
            \ComboStrap\ArrayUtility::filterArrayByKey($metas, $filter);
        }
        if (!array_key_exists("canonical", $metas)) {
            $metas["canonical"] = PluginUtility::getDocumentationHyperLink("canonical", "No Canonical");
        }

        $content .= \ComboStrap\ArrayUtility::formatAsHtmlList($metas);


        $referenceStyle = array(
            "font-size" => "95%",
            "clear" => "both",
            "bottom" => "10px",
            "right" => "15px",
            "position" => "absolute",
            "font-style" => "italic"
        );

        $content .= '<div style="' . PluginUtility::array2InlineStyle($referenceStyle) . '">' . $plugin->getLang('message_come_from') . PluginUtility::getDocumentationHyperLink("metadata:viewer", "ComboStrap Metadata Viewer") . '</div>';
        $content .= '</div>';
        return $content;

    }

    /**
     * Syntax Type.
     *
     * Needs to return one of the mode types defined in $PARSER_MODES in parser.php
     * @see DokuWiki_Syntax_Plugin::getType()
     */
    function getType()
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
    function getPType()
    {
        return 'block';
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
        return array();
    }

    function getSort()
    {
        return 201;
    }


    function connectTo($mode)
    {


        $pattern = PluginUtility::getEmptyTagPattern(self::TAG);
        $this->Lexer->addSpecialPattern($pattern, $mode, PluginUtility::getModeFromTag($this->getPluginComponent()));


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
        /**
         * There is only one state call ie DOKU_LEXER_SPECIAL
         * because of the connect to
         */

        return PluginUtility::getTagAttributes($match);

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

            $renderer->doc .= self::getHtmlMetadataBox($this, $data);
            return true;

        }

        // unsupported $mode
        return false;
    }


}

