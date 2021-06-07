<?php


use ComboStrap\CallStack;
use ComboStrap\PluginUtility;
use ComboStrap\Tag;
use ComboStrap\TagAttributes;


if (!defined('DOKU_INC')) die();

/**
 * Class syntax_plugin_combo_title
 * Title in container component
 * Taking over {@link \dokuwiki\Parsing\ParserMode\Header}
 */
class syntax_plugin_combo_title extends DokuWiki_Syntax_Plugin
{


    const TAG = "title";

    /**
     * Header pattern that we expect ie  ==== Hello =====
     * Found in {@link \dokuwiki\Parsing\ParserMode\Header}
     * One modification is that it permits one `=` to get the h6
     */
    const HEADING_PATTERN = '[ \t]*={1,}[^\n]+={1,}[ \t]*(?=\n)';

    const TITLE = 'title';
    const LEVEL = 'level';
    const DISPLAY_BS_4 = "display-bs-4";


    function getType()
    {
        return 'formatting';
    }

    /**
     *
     * How Dokuwiki will add P element
     *
     *  * 'normal' - The plugin can be used inside paragraphs (inline)
     *  * 'block'  - Open paragraphs need to be closed before plugin output - block should not be inside paragraphs
     *  * 'stack'  - Special case. Plugin wraps other paragraphs. - Stacks can contain paragraphs
     *
     * @see DokuWiki_Syntax_Plugin::getPType()
     *
     * This is the equivalent of inline or block for css
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
        return array('formatting', 'substition', 'protected', 'disabled', 'paragraphs');
    }

    /**
     * Less than {@link \dokuwiki\Parsing\ParserMode\Header::getSort()}
     * @return int
     */
    function getSort()
    {
        return 49;
    }


    function connectTo($mode)
    {
        /**
         * Title regexp
         */
        $modes = [
            PluginUtility::getModeForComponent(syntax_plugin_combo_blockquote::TAG),
            PluginUtility::getModeForComponent(syntax_plugin_combo_card::TAG),
            PluginUtility::getModeForComponent(syntax_plugin_combo_note::TAG),
            PluginUtility::getModeForComponent(syntax_plugin_combo_jumbotron::TAG),
            PluginUtility::getModeForComponent(syntax_plugin_combo_panel::TAG),
            PluginUtility::getModeForComponent(syntax_plugin_combo_panel::OLD_TAB_PANEL_TAG),
            PluginUtility::getModeForComponent(syntax_plugin_combo_slide::TAG),
            PluginUtility::getModeForComponent(syntax_plugin_combo_column::TAG),
        ];
        if (in_array($mode, $modes)) {
            $this->Lexer->addSpecialPattern(self::HEADING_PATTERN, $mode, PluginUtility::getModeForComponent($this->getPluginComponent()));
        }

        /**
         * Title tag
         */
        $this->Lexer->addEntryPattern(PluginUtility::getContainerTagPattern(self::TAG), $mode, PluginUtility::getModeForComponent($this->getPluginComponent()));
    }

    public function postConnect()
    {
        $this->Lexer->addExitPattern("</" . self::TAG . ">", PluginUtility::getModeForComponent($this->getPluginComponent()));
    }


    function handle($match, $state, $pos, Doku_Handler $handler)
    {

        switch ($state) {


            case DOKU_LEXER_ENTER :

                $defaultAttributes = array(
                    "level" => 1
                );
                $tagAttributes = PluginUtility::getTagAttributes($match);
                $attributes = PluginUtility::mergeAttributes($tagAttributes, $defaultAttributes);
                $tag = new Tag(self::TAG, $attributes, $state, $handler);
                $context = $tag->getParent();


                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $attributes,
                    PluginUtility::CONTEXT => $context
                );

            case DOKU_LEXER_UNMATCHED :

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::PAYLOAD => PluginUtility::htmlEncode($match),
                );

            case DOKU_LEXER_EXIT :

                $callStack = CallStack::createFromHandler($handler);
                $openingTag = $callStack->moveToPreviousCorrespondingOpeningCall();

                /**
                 * Heading may lived outside a component
                 */
                $parent = $callStack->moveToParent();
                if ($parent ===false) {
                    $context = "";
                } else {
                    $context = $parent->getTagName();
                }

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::CONTEXT => $context,
                    PluginUtility::ATTRIBUTES => $openingTag->getAttributes()

                );

            /**
             * Title regexp
             */
            case DOKU_LEXER_SPECIAL :

                $attributes = self::parseWikiHeading($match);
                $callStack = CallStack::createFromHandler($handler);

                $parentTag = $callStack->moveToParent();
                if ($parentTag == false) {
                    $context = "";
                } else {
                    $context = $parentTag->getTagName();
                }


                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $attributes,
                    PluginUtility::CONTEXT => $context
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

                case DOKU_LEXER_SPECIAL:
                    /**
                     * The short title ie ( === title === )
                     */
                    $callStackArray = $data[PluginUtility::ATTRIBUTES];
                    $tagAttributes = TagAttributes::createFromCallStackArray($callStackArray);
                    $context = $data[PluginUtility::CONTEXT];
                    $title = $tagAttributes->getValueAndRemove(self::TITLE);
                    syntax_plugin_combo_headingutil::renderOpeningTag($context, $tagAttributes, $renderer);
                    $renderer->doc .= PluginUtility::htmlEncode($title);
                    $renderer->doc .= syntax_plugin_combo_headingutil::renderClosingTag($tagAttributes);
                    break;
                case DOKU_LEXER_ENTER:
                    $parentTag = $data[PluginUtility::CONTEXT];
                    $attributes = $data[PluginUtility::ATTRIBUTES];
                    $tagAttributes = TagAttributes::createFromCallStackArray($attributes);
                    syntax_plugin_combo_headingutil::renderOpeningTag($parentTag, $tagAttributes, $renderer);
                    break;
                case DOKU_LEXER_UNMATCHED:
                    $renderer->doc .= PluginUtility::renderUnmatched($data);
                    break;
                case DOKU_LEXER_EXIT:
                    $attributes = $data[PluginUtility::ATTRIBUTES];
                    $tagAttributes = TagAttributes::createFromCallStackArray($attributes);
                    $renderer->doc .= syntax_plugin_combo_headingutil::renderClosingTag($tagAttributes);
                    break;

            }
        }
        // unsupported $mode
        return false;
    }

    public
    static function parseWikiHeading($match)
    {
        $title = trim($match);
        $level = 7 - strspn($title, '=');
        if ($level < 1) $level = 1;
        $title = trim($title, '=');
        $title = trim($title);
        $parameters[self::TITLE] = $title;
        $parameters[self::LEVEL] = $level;
        return $parameters;
    }

}

