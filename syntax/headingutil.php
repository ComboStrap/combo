<?php


use ComboStrap\Bootstrap;
use ComboStrap\Call;
use ComboStrap\CallStack;
use ComboStrap\PluginUtility;
use ComboStrap\Tag;
use ComboStrap\TagAttributes;


if (!defined('DOKU_INC')) die();

/**
 * Headings Utils
 * A class to store all heading commonality
 * code and code
 *
 * This class does not participate in the
 * parsing but in the rendering of heading
 *
 * It has a {@link DOKU_LEXER_SPECIAL} in {@link syntax_plugin_combo_headingutil::render()}
 * that corrects the content of an HTML heading
 * to be able to add an image for instance
 *
 */
class syntax_plugin_combo_headingutil extends DokuWiki_Syntax_Plugin
{


    const PLUGIN_COMPONENT = "headingutil";

    /**
     * An heading may be printed
     * as outline and should be in the toc
     */
    const TYPE_OUTLINE = "outline";
    const TYPE_TITLE = "title";

    /**
     * @param Call|bool $parent
     * @return string the type of heading
     */
    public static function getHeadingType($parent)
    {
        if ($parent != false && $parent->getComponentName() != "section_open") {
            return self::TYPE_TITLE;
        } else {
            return self::TYPE_OUTLINE;
        }
    }

    /**
     * @param $context
     * @param TagAttributes $tagAttributes
     * @param Doku_Renderer_xhtml $renderer
     */
    public static function renderOpeningTag($context, $tagAttributes, &$renderer)
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
                PluginUtility::getSnippetManager()->attachCssSnippetForBar(syntax_plugin_combo_title::DISPLAY_BS_4);
            }
        }
        $tagAttributes->removeComponentAttributeIfPresent(syntax_plugin_combo_title::TITLE);

        $level = $tagAttributes->getValueAndRemove(syntax_plugin_combo_title::LEVEL);

        $renderer->doc .= $tagAttributes->toHtmlEnterTag("h$level");

    }

    /**
     * @param TagAttributes $tagAttributes
     * @return string
     */
    public static function renderClosingTag($tagAttributes)
    {
        $level = $tagAttributes->getValueAndRemove(syntax_plugin_combo_title::LEVEL);

        return "</h$level>" . DOKU_LF;
    }


    /**
     * Replace the last content of a tag
     * @param $input - the input should have a heading at the end (ie <h1>blabla</h1>)
     * @param $newContent -  the new content (ie <h1>newContent</h1>
     */
    public static function modifyLastTagContent(&$input, $newContent)
    {
        // the variable that will capture the heading tag
        $headingEndTag = "";
        // Set to true when the heading tag has completed
        $headingComplete = false;
        // We start from the edn
        $position = strlen($input) - 1;
        while ($position > 0) {
            $character = $input[$position];
            if (!$headingComplete) {
                $headingEndTag = $character . $headingEndTag;
            }
            if ($character == "<") {
                $headingComplete = true;
            }
            if ($character == ">" && $headingComplete) {
                // We have delete all character until the heading start tag
                break;
            } else {
                // position --
                $position--;
            }
        }
        $input = substr($input, 0, $position + 1) . $newContent . $headingEndTag;
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
        return 'stack';
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

    /**
     *
     * @return int
     */
    function getSort()
    {
        return 49;
    }


    function connectTo($mode)
    {

        /**
         * Not used
         */
    }

    public function postConnect()
    {
        /**
         * Not used
         */
    }


    function handle($match, $state, $pos, Doku_Handler $handler)
    {

        /**
         * Connect is not used, handle is therefore
         * never called
         */
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

                    self::modifyLastTagContent($renderer->doc, trim($data[PluginUtility::PAYLOAD]));
                    break;

            }
        }
        // unsupported $mode
        return false;
    }


}

