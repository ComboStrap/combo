<?php


// must be run within Dokuwiki
use ComboStrap\PluginUtility;

if (!defined('DOKU_INC')) die();

/**
 * Class syntax_plugin_combo_box
 * Implementation of a div
 *
 */
class syntax_plugin_combo_slide extends DokuWiki_Syntax_Plugin
{

    const TAG = "slide";

    /**
     * @var int a slide counter
     */
    var $slideCounter = 0;

    const EDIT_SECTION_TARGET = 'section';// 'plugin_combo_' . self::TAG;

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
        return array('container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs');
    }

    public function accepts($mode)
    {

        /**
         * header mode is disable to take over
         * and replace it with {@link syntax_plugin_combo_title}
         */
        if ($mode == "header") {
            return false;
        }

        if (!$this->getConf(syntax_plugin_combo_preformatted::CONF_PREFORMATTED_ENABLE)) {
            return PluginUtility::disablePreformatted($mode);
        } else {
            return true;
        }
    }

    function getSort()
    {
        return 200;
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

    function handle($match, $state, $pos, Doku_Handler $handler)
    {

        switch ($state) {

            case DOKU_LEXER_ENTER :
                $defaultAttributes = array();
                $inlineAttributes = PluginUtility::getTagAttributes($match);
                $attributes = PluginUtility::mergeAttributes($inlineAttributes, $defaultAttributes);
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $attributes,
                    PluginUtility::POSITION => $pos
                );

            case DOKU_LEXER_UNMATCHED :
                return PluginUtility::handleAndReturnUnmatchedData(self::TAG, $match, $handler);

            case DOKU_LEXER_EXIT :

                // +1 to go at the line ?
                $endPosition = $pos + strlen($match) + 1;
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::POSITION => $endPosition
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

                    // Id
                    $this->slideCounter++;
                    $name = "slide" . $this->slideCounter;

                    // Section Edit button
                    // for DokuWiki Greebo and more recent versions
                    $position = $data[PluginUtility::POSITION];
                    if (defined('SEC_EDIT_PATTERN')) {

                        $renderer->startSectionEdit($position, array('target' => self::EDIT_SECTION_TARGET, 'name' => $name));
                    } else {
                        $renderer->startSectionEdit($position, self::EDIT_SECTION_TARGET, $name);
                    }

                    $attributes = $data[PluginUtility::ATTRIBUTES];

                    $sizeAttribute = "size";
                    $size = "md";
                    if (array_key_exists($sizeAttribute, $attributes)) {
                        $size = $attributes[$sizeAttribute];
                        unset($attributes[$sizeAttribute]);
                    }
                    switch ($size) {
                        case "lg":
                        case "large":
                            PluginUtility::addClass2Attributes(self::TAG . "-lg", $attributes);
                            break;
                        case "sm":
                        case "small":
                            PluginUtility::addClass2Attributes(self::TAG . "-sm", $attributes);
                            break;
                        case "xl":
                        case "extra-large":
                            PluginUtility::addClass2Attributes(self::TAG . "-xl", $attributes);
                            break;
                        default:
                            PluginUtility::addClass2Attributes(self::TAG, $attributes);
                            break;
                    }

                    PluginUtility::getSnippetManager()->upsertCssSnippetForBar(self::TAG);

                    /**
                     * By default, this is rounded
                     * BUt for a slide, this is by default not wanted
                     */
                    PluginUtility::addStyleProperty("border-radius", 0, $attributes);

                    $renderer->doc .= '<section';
                    if (sizeof($attributes) > 0) {
                        $renderer->doc .= ' ' . PluginUtility::array2HTMLAttributesAsString($attributes);
                    }
                    $renderer->doc .= '>';
                    break;

                case DOKU_LEXER_UNMATCHED :
                    $renderer->doc .= PluginUtility::renderUnmatched($data);
                    break;

                case DOKU_LEXER_EXIT :
                    $renderer->doc .= '</section>';
                    $renderer->finishSectionEdit($data[PluginUtility::POSITION]);
                    break;
            }
            return true;
        }

        // unsupported $mode
        return false;
    }


}

