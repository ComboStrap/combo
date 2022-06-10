<?php


use ComboStrap\PluginUtility;
use ComboStrap\TagAttributes;

require_once(__DIR__ . '/../vendor/autoload.php');

/**
 *
 * backgrounds is an element that holds more than one background
 * We can set then parallax effect for instance
 *
 */
class syntax_plugin_combo_backgrounds extends DokuWiki_Syntax_Plugin
{

    const TAG = "backgrounds";


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
     * How Dokuwiki will add P element
     *
     *  * 'normal' - Inline
     *  * 'block' - Block (p are not created inside)
     *  * 'stack' - Block (p can be created inside)
     *
     * @see DokuWiki_Syntax_Plugin::getPType()
     */
    function getPType()
    {
        /**
         * normal (and not block) is important to not create p_open calls
         */
        return 'normal';
    }

    /**
     * @return array
     * Allow which kind of plugin inside
     *
     * Array('baseonly','container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs')
     *
     * Return an array of one or more of the mode types {@link $PARSER_MODES} in Parser.php
     */
    function getAllowedTypes()
    {
        return array('baseonly', 'container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs');
    }

    public function accepts($mode)
    {

        return syntax_plugin_combo_preformatted::disablePreformatted($mode);

    }

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

    function handle($match, $state, $pos, Doku_Handler $handler)
    {

        switch ($state) {

            case DOKU_LEXER_ENTER :
                $tagAttributes = TagAttributes::createFromTagMatch($match);
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $tagAttributes->toCallStackArray()
                );

            case DOKU_LEXER_UNMATCHED :
                /**
                 * We should not have anything here
                 * but in any case, we send the data for feedback
                 */
                return PluginUtility::handleAndReturnUnmatchedData(self::TAG, $match, $handler);

            case DOKU_LEXER_EXIT :

                /**
                 * Return state to keep the call stack structure
                 * and print the closing tag
                 */
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

                case DOKU_LEXER_ENTER:
                    $tagAttributes = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES]);
                    $tagAttributes->addClassName(self::TAG);
                    $renderer->doc .= $tagAttributes->toHtmlEnterTag("div");
                    break;
                case DOKU_LEXER_EXIT:
                    $renderer->doc .= "</div>";
                    break;
                case DOKU_LEXER_UNMATCHED:
                    /**
                     * In case anyone put text where it should not
                     */
                    $renderer->doc .= PluginUtility::renderUnmatched($data);
                    break;

            }
            return true;
        }

        // unsupported $mode
        return false;
    }


}

