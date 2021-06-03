<?php


use ComboStrap\Bootstrap;
use ComboStrap\StringUtility;
use ComboStrap\Tag;
use ComboStrap\PluginUtility;
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


    private static function getParent(Tag $tag)
    {
        $parentTag = $tag->getParent();
        $parentTagName = "";
        if ($parentTag != null) {
            $parentTagName = $parentTag->getName();
        }
        return $parentTagName;
    }

    /**
     * @param $context
     * @param TagAttributes $tagAttributes
     * @return string
     */
    public static function renderClosingTag($context, $tagAttributes)
    {
        $level = $tagAttributes->getValueAndRemove(self::LEVEL);

        return "</h$level>" . DOKU_LF;
    }

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
                $parentTagName = $tag->getParent();


                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $attributes,
                    PluginUtility::CONTEXT => $parentTagName
                );

            case DOKU_LEXER_UNMATCHED :

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::PAYLOAD => PluginUtility::htmlEncode($match),
                );

            case DOKU_LEXER_EXIT :

                $tag = new Tag(self::TAG, array(), $state, $handler);
                $parent = $tag->getParent();
                $parentTagName = "";
                /**
                 * Heading may lived outside a component
                 */
                if ($parent != null) {
                    $parentTagName = $parent->getName();
                }

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::CONTEXT => $parentTagName,
                    PluginUtility::ATTRIBUTES => $tag->getOpeningTag()->getAttributes()

                );

            /**
             * Title regexp
             */
            case DOKU_LEXER_SPECIAL :

                $attributes = self::parseHeading($match);
                $tag = new Tag(self::TAG, $attributes, $state, $handler);
                $parentTag = self::getParent($tag);

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $attributes,
                    PluginUtility::CONTEXT => $parentTag
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
                    self::renderOpeningTag($context, $tagAttributes, $renderer);
                    $renderer->doc .= PluginUtility::htmlEncode($title);
                    $renderer->doc .= self::renderClosingTag($context, $tagAttributes);
                    break;
                case DOKU_LEXER_ENTER:
                    $parentTag = $data[PluginUtility::CONTEXT];
                    $attributes = $data[PluginUtility::ATTRIBUTES];
                    $tagAttributes = TagAttributes::createFromCallStackArray($attributes);
                    self::renderOpeningTag($parentTag, $tagAttributes, $renderer);
                    break;
                case DOKU_LEXER_UNMATCHED:
                    $renderer->doc .= PluginUtility::renderUnmatched($data);
                    break;
                case DOKU_LEXER_EXIT:
                    $attributes = $data[PluginUtility::ATTRIBUTES];
                    $tagAttributes = TagAttributes::createFromCallStackArray($attributes);
                    $context = $data[PluginUtility::CONTEXT];
                    $renderer->doc .= self::renderClosingTag($context, $tagAttributes);
                    break;

            }
        }
        // unsupported $mode
        return false;
    }

    /**
     * @param $context
     * @param TagAttributes $tagAttributes
     * @param Doku_Renderer_xhtml $renderer
     */
    static function renderOpeningTag($context, $tagAttributes, &$renderer)
    {


        switch ($context) {

            case syntax_plugin_combo_blockquote::TAG:
            case syntax_plugin_combo_card::TAG:
                $tagAttributes->addClassName("card-title");
                break;

        }

        /**
         * Printing
         */
        $type = $tagAttributes->getType();
        if ($type != 0) {
            $tagAttributes->addClassName("display-" . $type);
            if (Bootstrap::getBootStrapMajorVersion() == "4") {
                /**
                 * Make Bootstrap display responsive
                 */
                PluginUtility::getSnippetManager()->attachCssSnippetForBar(self::DISPLAY_BS_4);
            }
        }
        $tagAttributes->removeComponentAttributeIfPresent(self::TITLE);

        $level = $tagAttributes->getValueAndRemove(self::LEVEL);

        $renderer->doc .= $tagAttributes->toHtmlEnterTag("h$level");

    }

    public
    static function parseHeading($match)
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

