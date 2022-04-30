<?php


use ComboStrap\Call;
use ComboStrap\CallStack;
use ComboStrap\Dimension;
use ComboStrap\PluginUtility;
use ComboStrap\TagAttributes;

require_once(__DIR__ . '/../ComboStrap/StyleUtility.php');
require_once(__DIR__ . '/../ComboStrap/SnippetManager.php');


/**
 * Class syntax_plugin_combo_list
 * Implementation of a list
 *
 * Content list is a list implementation that permits to
 * create simple and complex list such as media list
 *
 * https://getbootstrap.com/docs/4.0/layout/media-object/#media-list - Bootstrap media list
 * https://getbootstrap.com/docs/5.0/utilities/flex/#media-object
 * https://github.com/material-components/material-components-web/tree/master/packages/mdc-list - mdc list
 *
 * It's implemented on the basis of:
 *   * bootstrap list-group
 *   * flex utility on the list-group-item
 *   * with the row/cell (grid) adjusted in order to add automatically a space between col (cell)
 *
 * Note:
 *   * The cell inside a row are centered vertically automatically
 *   * The illustrative image does not get any [[ui:image#link|link]]
 *
 * Documentation:
 * https://getbootstrap.com/docs/4.1/components/list-group/
 * https://getbootstrap.com/docs/5.0/components/list-group/
 *
 * https://getbootstrap.com/docs/5.0/utilities/flex/
 * https://getbootstrap.com/docs/5.0/utilities/flex/#media-object
 *
 */
class syntax_plugin_combo_contentlist extends DokuWiki_Syntax_Plugin
{

    const DOKU_TAG = "contentlist";

    /**
     * To allow a minus
     */
    const MARKI_TAG = "content-list";
    const COMBO_TAG_OLD = "list";
    const COMBO_TAGS = [self::MARKI_TAG, self::COMBO_TAG_OLD];

    /**
     * With number, this a li, without a div
     */
    const HTML_TAG_ATTRIBUTE = "html-tag";


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

    /**
     * How Dokuwiki will add P element
     *
     *  * 'normal' - The plugin can be used inside paragraphs (inline or inside)
     *  * 'block'  - Open paragraphs need to be closed before plugin output (box) - block should not be inside paragraphs
     *  * 'stack'  - Special case. Plugin wraps other paragraphs. - Stacks can contain paragraphs
     *
     * @see DokuWiki_Syntax_Plugin::getPType()
     * @see https://www.dokuwiki.org/devel:syntax_plugins#ptype
     */
    function getPType(): string
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
    function getAllowedTypes(): array
    {
        return array('container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs');
    }

    public function accepts($mode): bool
    {

        return syntax_plugin_combo_preformatted::disablePreformatted($mode);

    }


    function getSort(): int
    {
        return 15;
    }


    function connectTo($mode)
    {

        foreach (self::COMBO_TAGS as $tag) {
            $pattern = PluginUtility::getContainerTagPattern($tag);
            $this->Lexer->addEntryPattern($pattern, $mode, PluginUtility::getModeFromTag($this->getPluginComponent()));
        }

    }

    public function postConnect()
    {
        foreach (self::COMBO_TAGS as $tag) {
            $this->Lexer->addExitPattern('</' . $tag . '>', PluginUtility::getModeFromTag($this->getPluginComponent()));
        }

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

                $default = [Dimension::WIDTH_KEY => "fit"];
                $attributes = TagAttributes::createFromTagMatch($match, $default, []);

                if ($attributes->hasComponentAttribute(TagAttributes::TYPE_KEY)) {
                    $type = trim(strtolower($attributes->getType()));
                    if ($type === "flush") {
                        // https://getbootstrap.com/docs/5.0/components/list-group/#flush
                        // https://getbootstrap.com/docs/4.1/components/list-group/#flush
                        $attributes->addClassName("list-group-flush");
                    }
                }
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $attributes->toCallStackArray(),
                    self::HTML_TAG_ATTRIBUTE => "div"
                );

            case DOKU_LEXER_UNMATCHED :

                return PluginUtility::handleAndReturnUnmatchedData(self::MARKI_TAG, $match, $handler);

            case DOKU_LEXER_EXIT :

                /**
                 * Add to all row the list-group-item
                 */
                $callStack = CallStack::createFromHandler($handler);
                $openingTag = $callStack->moveToPreviousCorrespondingOpeningCall();
                $firstChild = $callStack->moveToFirstChildTag();
                if ($firstChild !== false) {
                    $firstChild->addClassName(syntax_plugin_combo_contentlistitem::LIST_GROUP_ITEM_CLASS);
                    while ($actualCall = $callStack->moveToNextSiblingTag()) {
                        $actualCall->addClassName(syntax_plugin_combo_contentlistitem::LIST_GROUP_ITEM_CLASS);
                    }
//                        if ($actualCall->getTagName() == syntax_plugin_combo_contentlistitem::DOKU_TAG) {
//                            // List item were added by the user
//                            break;
//                        }
//                        if ($actualCall->getTagName() == syntax_plugin_combo_row::TAG) {
//                            $actualState = $actualCall->getState();
//                            switch ($actualState) {
//                                case DOKU_LEXER_ENTER:
//                                    $callStack->insertBefore(Call::createComboCall(
//                                        syntax_plugin_combo_contentlistitem::DOKU_TAG,
//                                        DOKU_LEXER_ENTER
//                                    ));
//                                    break;
//                                case DOKU_LEXER_EXIT:
//                                    $callStack->insertAfter(Call::createComboCall(
//                                        syntax_plugin_combo_contentlistitem::DOKU_TAG,
//                                        DOKU_LEXER_EXIT
//                                    ));
//                                    $callStack->next();
//                                    break;
//                            }
//
//                        }
//                    }
                }

                return array(
                    PluginUtility::STATE => $state,
                    self::HTML_TAG_ATTRIBUTE => $openingTag->getPluginData(self::HTML_TAG_ATTRIBUTE)
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

                    PluginUtility::getSnippetManager()->attachCssInternalStyleSheetForSlot(self::MARKI_TAG);
                    $tagAttributes = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES], self::MARKI_TAG);
                    $tagAttributes->addClassName("list-group");
                    $tag = $data[self::HTML_TAG_ATTRIBUTE];
                    $renderer->doc .= $tagAttributes->toHtmlEnterTag($tag);

                    break;
                case DOKU_LEXER_EXIT :
                    $tag = $data[self::HTML_TAG_ATTRIBUTE];
                    $renderer->doc .= "</$tag>";
                    break;
                case DOKU_LEXER_UNMATCHED :
                    $renderer->doc .= PluginUtility::renderUnmatched($data);
                    break;
            }
            return true;
        }

        // unsupported $mode
        return false;
    }


}

