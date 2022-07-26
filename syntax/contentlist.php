<?php


use ComboStrap\Align;
use ComboStrap\Call;
use ComboStrap\CallStack;
use ComboStrap\DataType;
use ComboStrap\Dimension;
use ComboStrap\ExceptionBadArgument;
use ComboStrap\LogUtility;
use ComboStrap\PluginUtility;
use ComboStrap\TagAttributes;



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


    const FLUSH_TYPE = "flush";
    const NUMBERED = "numbered";
    const NUMBERED_DEFAULT = false;
    const CANONICAL = self::MARKI_TAG;

    /**
     * @throws ExceptionBadArgument
     */
    private static function insertNumberedWrapperCloseTag(CallStack $callStack)
    {

        $callStack->insertBefore(Call::createComboCall(
            syntax_plugin_combo_box::TAG,
            DOKU_LEXER_EXIT,
            [syntax_plugin_combo_box::TAG_ATTRIBUTE => "li"]
        ));

    }


    /**
     *
     * @param CallStack $callStack
     * @return void
     * @throws ExceptionBadArgument
     */
    private static function insertNumberedWrapperOpenTag(CallStack $callStack)
    {
        $attributesNumberedWrapper = [
            Align::ALIGN_ATTRIBUTE => Align::Y_TOP_CHILDREN, // To have the number at the top and not centered as for a combostrap flex
            TagAttributes::CLASS_KEY => syntax_plugin_combo_contentlistitem::LIST_GROUP_ITEM_CLASS,
            syntax_plugin_combo_box::TAG_ATTRIBUTE => "li"
        ];
        $callStack->insertBefore(Call::createComboCall(
            syntax_plugin_combo_box::TAG,
            DOKU_LEXER_ENTER,
            $attributesNumberedWrapper
        ));
    }


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
     * * 'normal' - Inline
     *  * 'block' - Block (p are not created inside)
     *  * 'stack' - Block (p can be created inside)
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
     * @return array
     * @see DokuWiki_Syntax_Plugin::handle()
     *
     */
    function handle($match, $state, $pos, Doku_Handler $handler): array
    {

        switch ($state) {

            case DOKU_LEXER_ENTER :

                $knownType = [self::FLUSH_TYPE];
                $default = [
                    Dimension::WIDTH_KEY => "fit",
                    self::NUMBERED => self::NUMBERED_DEFAULT
                ];
                $attributes = TagAttributes::createFromTagMatch($match, $default, $knownType);

                if ($attributes->hasComponentAttribute(TagAttributes::TYPE_KEY)) {
                    $type = trim(strtolower($attributes->getType()));
                    if ($type === self::FLUSH_TYPE) {
                        // https://getbootstrap.com/docs/5.0/components/list-group/#flush
                        // https://getbootstrap.com/docs/4.1/components/list-group/#flush
                        $attributes->addClassName("list-group-flush");
                    }
                }

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $attributes->toCallStackArray()
                );

            case DOKU_LEXER_UNMATCHED :

                return PluginUtility::handleAndReturnUnmatchedData(self::MARKI_TAG, $match, $handler);

            case DOKU_LEXER_EXIT :

                /**
                 * Add to all children the list-group-item
                 */
                $callStack = CallStack::createFromHandler($handler);
                $openingTag = $callStack->moveToPreviousCorrespondingOpeningCall();

                /**
                 * The number are inside (this is a **content** list)
                 * and not as with a marker box, outside.
                 *
                 * It's in the `before` box and is therefore a invisible box
                 * To make it easy for the user (it does need to known that),
                 * we wrap the user markup in a flex with a top placement
                 */
                $numbered = DataType::toBoolean($openingTag->getAttribute(self::NUMBERED, self::NUMBERED_DEFAULT));
                if ($numbered === true) {
                    $firstChild = $callStack->moveToFirstChildTag();
                    if ($firstChild !== false) {
                        try {
                            self::insertNumberedWrapperOpenTag($callStack);
                            while ($callStack->moveToNextSiblingTag()) {
                                self::insertNumberedWrapperCloseTag($callStack);
                                self::insertNumberedWrapperOpenTag($callStack);
                            }
                            self::insertNumberedWrapperCloseTag($callStack);
                        } catch (ExceptionBadArgument $e) {
                            LogUtility::error("We were unable to wrap the content list to enable numbering placement. Error: {$e->getMessage()}", self::CANONICAL);
                        }
                    }
                } else {
                    foreach ($callStack->getChildren() as $child) {
                        $child->addClassName(syntax_plugin_combo_contentlistitem::LIST_GROUP_ITEM_CLASS);
                        if ($child->getTagName() === syntax_plugin_combo_box::TAG_ATTRIBUTE) {
                            $child->addAttribute(syntax_plugin_combo_box::TAG_ATTRIBUTE, "li");
                        }
                    }
                }

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
    function render($format, Doku_Renderer $renderer, $data): bool
    {
        if ($format == 'xhtml') {

            /** @var Doku_Renderer_xhtml $renderer */
            $state = $data[PluginUtility::STATE];
            switch ($state) {
                case DOKU_LEXER_ENTER :

                    PluginUtility::getSnippetManager()->attachCssInternalStyleSheet(self::MARKI_TAG);
                    $tagAttributes = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES], self::MARKI_TAG);
                    $tagAttributes->addClassName("list-group");

                    $numbered = $tagAttributes->getBooleanValueAndRemoveIfPresent(self::NUMBERED, self::NUMBERED_DEFAULT);

                    $htmlElement = "ul";
                    if ($numbered) {
                        $tagAttributes->addClassName("list-group-numbered");
                        $htmlElement = "ol";
                    }

                    $renderer->doc .= $tagAttributes->toHtmlEnterTag($htmlElement);
                    break;
                case DOKU_LEXER_UNMATCHED :

                    $renderer->doc .= PluginUtility::renderUnmatched($data);
                    break;

                case DOKU_LEXER_EXIT :
                    $tagAttributes = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES], self::MARKI_TAG);
                    $numbered = $tagAttributes->getValueAndRemoveIfPresent(self::NUMBERED, self::NUMBERED_DEFAULT);
                    $htmlElement = "ul";
                    if ($numbered) {
                        $htmlElement = "ol";
                    }
                    $renderer->doc .= "</$htmlElement>";
                    break;
            }
            return true;
        }

        // unsupported $mode
        return false;
    }


}

