<?php


// must be run within Dokuwiki
use ComboStrap\CallStack;
use ComboStrap\DataType;
use ComboStrap\EditButton;
use ComboStrap\EditButtonManager;
use ComboStrap\ExceptionBadArgument;
use ComboStrap\ExceptionCompile;
use ComboStrap\ExceptionNotEnabled;
use ComboStrap\ExceptionNotFound;
use ComboStrap\LogUtility;
use ComboStrap\Page;
use ComboStrap\PluginUtility;
use ComboStrap\TagAttributes;

if (!defined('DOKU_INC')) die();

/**
 * Class syntax_plugin_combo_box
 * Implementation of a div
 *
 */
class syntax_plugin_combo_bar extends DokuWiki_Syntax_Plugin
{

    const TAG_OLD = "slide";
    const CONF_ENABLE_BAR_EDITING = "enableBarEditing";
    const CANONICAL = self::TAG_OLD;
    const TAG = "bar";
    const HTML_TAG_ATTRIBUTES = "html_tag";
    const HTML_SECTION_TAG = "section";
    const SIZE_ATTRIBUTE = "size";
    private static $tags = [self::TAG, self::TAG_OLD];


    /**
     * Syntax Type.
     *
     * Needs to return one of the mode types defined in $PARSER_MODES in parser.php
     * @see DokuWiki_Syntax_Plugin::getType()
     */
    function getType(): string
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
        return 200;
    }


    function connectTo($mode)
    {

        foreach (self::$tags as $tag) {
            $pattern = PluginUtility::getContainerTagPattern($tag);
            $this->Lexer->addEntryPattern($pattern, $mode, PluginUtility::getModeFromTag($this->getPluginComponent()));
        }
    }


    function postConnect()
    {
        foreach (self::$tags as $tag) {
            $this->Lexer->addExitPattern('</' . $tag . '>', PluginUtility::getModeFromTag($this->getPluginComponent()));
        }

    }

    private static function getHtmlTag(): string
    {
        $tag = "div";
        try {
            $page = Page::createPageFromGlobalDokuwikiId();
            if ($page->isPrimarySlot()) {
                $tag = self::HTML_SECTION_TAG;
            }
            return $tag;
        } catch (ExceptionNotFound $e) {
            return $tag;
        }
    }

    function handle($match, $state, $pos, Doku_Handler $handler)
    {

        switch ($state) {

            case DOKU_LEXER_ENTER :

                $tagAttributes = TagAttributes::createFromTagMatch($match)
                    ->setLogicalTag(self::TAG);

                $htmlTag = self::getHtmlTag();

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $tagAttributes->toCallStackArray(),
                    self::HTML_TAG_ATTRIBUTES => $htmlTag
                );

            case DOKU_LEXER_UNMATCHED :
                return PluginUtility::handleAndReturnUnmatchedData(self::TAG, $match, $handler);

            case DOKU_LEXER_EXIT :

                $callStack = CallStack::createFromHandler($handler);
                $openingTag = $callStack->moveToPreviousCorrespondingOpeningCall();

                /**
                 * Section heading control
                 */
                $htmlTag = $openingTag->getPluginData(self::HTML_TAG_ATTRIBUTES);
                $message = null;
                if ($htmlTag === self::HTML_SECTION_TAG) {
                    $headingFound = false;
                    while ($actualCall = $callStack->next()) {
                        if (in_array($actualCall->getTagName(), action_plugin_combo_headingpostprocessing::HEADING_TAGS)) {
                            $headingFound = true;
                            break;
                        }
                    }
                    if (!$headingFound) {
                        $id = $openingTag->getIdOrDefault();
                        $message = "No heading was found in the section bar ($id). An heading is mandatory for navigation within device.";
                    }
                }

                if (PluginUtility::getConfValue(self::CONF_ENABLE_BAR_EDITING, 1)) {

                    $position = $openingTag->getFirstMatchedCharacterPosition();
                    try {
                        $startPosition = DataType::toInteger($position);
                    } catch (ExceptionBadArgument $e) {
                        LogUtility::error("The position of the slide is not an integer", self::CANONICAL);
                        $startPosition = null;
                    }
                    $id = $openingTag->getIdOrDefault();
                    // +1 to go at the line
                    $endPosition = $pos + strlen($match) + 1;
                    $tag = self::TAG;
                    $editButtonCall = EditButton::create("Edit $tag $id")
                        ->setStartPosition($startPosition)
                        ->setEndPosition($endPosition)
                        ->toComboCall();
                    $callStack->moveToEnd();
                    $callStack->insertBefore($editButtonCall);
                }


                return array(
                    PluginUtility::STATE => $state,
                    self::HTML_TAG_ATTRIBUTES => $htmlTag,
                    PluginUtility::EXIT_MESSAGE => $message
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

                    /**
                     * Attributes
                     */
                    $barTag = self::TAG;
                    $attributes = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES])
                        ->setLogicalTag($barTag);

                    $attributes->addClassName($barTag);

                    $sizeAttribute = self::SIZE_ATTRIBUTE;
                    $size = "md";
                    if ($attributes->hasComponentAttribute($sizeAttribute)) {
                        $size = $attributes->getValueAndRemove($sizeAttribute);
                    }
                    switch ($size) {
                        case "lg":
                        case "large":
                            $attributes->addClassName($barTag . "-lg");
                            break;
                        case "sm":
                        case "small":
                            $attributes->addClassName($barTag . "-sm");
                            break;
                        case "xl":
                        case "extra-large":
                            $attributes->addClassName($barTag . "-xl");
                            break;
                        case "md":
                        case "medium":
                            $attributes->addClassName($barTag . "-md");
                            break;
                        default:
                            break;
                    }

                    PluginUtility::getSnippetManager()->attachCssInternalStyleSheetForSlot($barTag);

                    $htmlTag = $attributes->getValueAndRemove(self::HTML_TAG_ATTRIBUTES);
                    $renderer->doc .= $attributes->toHtmlEnterTag($htmlTag);

                    $renderer->doc .= "<div class=\"$barTag-body position-relative\">";
                    break;

                case DOKU_LEXER_UNMATCHED :
                    $renderer->doc .= PluginUtility::renderUnmatched($data);
                    break;

                case DOKU_LEXER_EXIT :

                    $attributes = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES]);
                    /**
                     * End body
                     */
                    $renderer->doc .= '</div>';

                    /**
                     * End component
                     */
                    $htmlTag = $attributes->getValueAndRemove(self::HTML_TAG_ATTRIBUTES);
                    $renderer->doc .= "</$htmlTag>";

                    break;
            }
            return true;
        } elseif ($format == 'xml') {
            /** @var renderer_plugin_combo_xml $renderer */
            $state = $data[PluginUtility::STATE];
            switch ($state) {
                case DOKU_LEXER_ENTER :
                    $renderer->doc .= "<slide>";
                    break;
                case DOKU_LEXER_UNMATCHED :
                    $renderer->doc .= PluginUtility::renderUnmatched($data);
                    break;
                case DOKU_LEXER_EXIT :
                    $renderer->doc .= "</slide>";
                    break;
            }
        }

        // unsupported $mode
        return false;
    }


}

