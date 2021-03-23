<?php


use ComboStrap\SnippetManager;
use ComboStrap\PluginUtility;
use ComboStrap\Tag;

if (!defined('DOKU_INC')) die();

/**
 * Class syntax_plugin_combo_tooltip
 * Implementation of a tooltip
 */
class syntax_plugin_combo_tooltip extends DokuWiki_Syntax_Plugin
{

    const TAG = "tooltip";
    const TEXT_ATTRIBUTE = "text";
    const POSITION_ATTRIBUTE = "position";


    /**
     * tooltip is used also in page protection
     */
    public static function addToolTipSnippetIfNeeded()
    {
        $script = "window.addEventListener('load', function () { jQuery('[data-toggle=\"tooltip\"]').tooltip() })";
        PluginUtility::getSnippetManager()->upsertJavascriptForBar(self::TAG, $script);
    }


    /**
     * Syntax Type.
     *
     * Needs to return one of the mode types defined in $PARSER_MODES in parser.php
     * @see https://www.dokuwiki.org/devel:syntax_plugins#syntax_types
     * @see DokuWiki_Syntax_Plugin::getType()
     */
    function getType()
    {
        return 'container';
    }

    /**
     * How Dokuwiki will add P element
     *
     *  * 'normal' - The plugin can be used inside paragraphs (inline)
     *  * 'block'  - Open paragraphs need to be closed before plugin output - block should not be inside paragraphs
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
        return array('baseonly', 'container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs');
    }

    function getSort()
    {
        return 201;
    }


    function connectTo($mode)
    {

        $pattern = PluginUtility::getContainerTagPattern(self::TAG);
        $this->Lexer->addEntryPattern($pattern, $mode, PluginUtility::getModeForComponent($this->getPluginComponent()));

    }

    function postConnect()
    {

        $this->Lexer->addExitPattern('</' . self::TAG . '>', PluginUtility::getModeForComponent($this->getPluginComponent()));

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
                $attributes = PluginUtility::getTagAttributes($match);

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $attributes
                );

            case DOKU_LEXER_UNMATCHED :
                return PluginUtility::handleAndReturnUnmatchedData(self::TAG,$match,$handler);

            case DOKU_LEXER_EXIT :

                $tag = new Tag(self::TAG, array(), $state, $handler);

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $tag->getOpeningTag()->getAttributes()
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

                case DOKU_LEXER_UNMATCHED:
                    $renderer->doc .= PluginUtility::renderUnmatched($data);
                    break;
                case DOKU_LEXER_ENTER :
                    $attributes = $data[PluginUtility::ATTRIBUTES];

                    if (isset($attributes[self::TEXT_ATTRIBUTE])) {
                        $position = "top";
                        if (isset($attributes[self::POSITION_ATTRIBUTE])) {
                            $position = $attributes[self::POSITION_ATTRIBUTE];
                        }
                        $renderer->doc .= "<span class=\"d-inline-block\" tabindex=\"0\" data-toggle=\"tooltip\" data-placement=\"${position}\" title=\"" . $attributes[self::TEXT_ATTRIBUTE] . "\">" . DOKU_LF;
                    };

                    break;

                case DOKU_LEXER_EXIT:
                    if (isset($data[PluginUtility::ATTRIBUTES][self::TEXT_ATTRIBUTE])) {

                        $text = $data[PluginUtility::ATTRIBUTES][self::TEXT_ATTRIBUTE];
                        if (!empty($text)) {
                            $renderer->doc .= "</span>";
                            self::addToolTipSnippetIfNeeded();
                        }

                    }
                    break;


            }
            return true;
        }

        // unsupported $mode
        return false;
    }


}

