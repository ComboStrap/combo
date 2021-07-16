<?php


use ComboStrap\Bootstrap;
use ComboStrap\CallStack;
use ComboStrap\PluginUtility;
use ComboStrap\Tag;
use ComboStrap\TagAttributes;

if (!defined('DOKU_INC')) die();

/**
 * Class syntax_plugin_combo_tooltip
 * Implementation of a tooltip
 *
 * A tooltip is implemented as a super title attribute
 * on a HTML element such as a link or a button
 *
 * The implementation pass the information that there is
 * a tooltip on the container which makes the output of {@link TagAttributes::toHtmlEnterTag()}
 * to print all attributes until the title and not closing.
 *
 * Bootstrap generate the <a href="https://getbootstrap.com/docs/5.0/components/tooltips/#markup">markup tooltip</a>
 * on the fly. It's possible to generate a bootstrap markup like and use popper directly
 * but this is far more difficult
 *
 *
 * https://material.io/components/tooltips
 * [[https://getbootstrap.com/docs/4.0/components/tooltips/|Tooltip Boostrap version 4]]
 * [[https://getbootstrap.com/docs/5.0/components/tooltips/|Tooltip Boostrap version 5]]
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
        PluginUtility::getSnippetManager()->attachJavascriptSnippetForBar(self::TAG);
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
        return 'formatting';
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
        $this->Lexer->addEntryPattern($pattern, $mode, PluginUtility::getModeFromTag($this->getPluginComponent()));

    }

    function postConnect()
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
                $tagAttributes = TagAttributes::createFromTagMatch($match);

                /**
                 * New Syntax, the tooltip attribute
                 * are applied to the aprent
                 */
                if (!$tagAttributes->hasComponentAttribute(self::TEXT_ATTRIBUTE)) {
                    $callStack = CallStack::createFromHandler($handler);
                    $parent = $callStack->moveToParent();
                    /**
                     * Do not close the tag
                     */
                    $parent->addAttribute(TagAttributes::OPEN_TAG, true);
                    /**
                     * Do not output the title
                     */
                    $parent->addAttribute(TagAttributes::TITLE_KEY, TagAttributes::UN_SET);
                }

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $tagAttributes->toCallStackArray()
                );

            case DOKU_LEXER_UNMATCHED :
                return PluginUtility::handleAndReturnUnmatchedData(self::TAG, $match, $handler);

            case DOKU_LEXER_EXIT :

                $callStack = CallStack::createFromHandler($handler);
                $openingTag = $callStack->moveToPreviousCorrespondingOpeningCall();

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $openingTag->getAttributes()
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
                    $tagAttributes = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES]);

                    /**
                     * Snippet
                     */
                    self::addToolTipSnippetIfNeeded();

                    /**
                     * Tooltip
                     */
                    $dataAttributeNamespace = Bootstrap::getDataNamespace();
                    $tagAttributes->addHtmlAttributeValue("data{$dataAttributeNamespace}-toggle", "tooltip");

                    /**
                     * Position
                     */
                    $position = $tagAttributes->getValueAndRemove(self::POSITION_ATTRIBUTE, "top");
                    $tagAttributes->addHtmlAttributeValue("data{$dataAttributeNamespace}-placement", "${position}");


                    /**
                     * Old tooltip syntax
                     */
                    if ($tagAttributes->hasComponentAttribute(self::TEXT_ATTRIBUTE)) {
                        $tagAttributes->addHtmlAttributeValue("title", $tagAttributes->getValueAndRemove(self::TEXT_ATTRIBUTE));
                        $tagAttributes->addClassName("d-inline-block");

                        // Arbitrary HTML elements (such as <span>s) can be made focusable by adding the tabindex="0" attribute
                        $tagAttributes->addHtmlAttributeValue("tabindex", "0");

                        $renderer->doc .= $tagAttributes->toHtmlEnterTag("span");
                    } else {
                        /**
                         * New Syntax
                         * (The new syntax just add the attributes to the previous element
                         */
                        $tagAttributes->addHtmlAttributeValue("data{$dataAttributeNamespace}-html", "true");
                        $renderer->doc .= " {$tagAttributes->toHTMLAttributeString()} title=\"";
                    }

                    break;

                case DOKU_LEXER_UNMATCHED:
                    $renderer->doc .= PluginUtility::renderUnmatched($data);
                    break;

                case DOKU_LEXER_EXIT:
                    if (isset($data[PluginUtility::ATTRIBUTES][self::TEXT_ATTRIBUTE])) {

                        $text = $data[PluginUtility::ATTRIBUTES][self::TEXT_ATTRIBUTE];
                        if (!empty($text)) {
                            $renderer->doc .= "</span>";
                        }

                    } else {
                        // Close the title
                        $renderer->doc .= "\">";
                    }
                    break;


            }
            return true;
        }

        // unsupported $mode
        return false;
    }


}

