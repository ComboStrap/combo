<?php


// must be run within Dokuwiki
use ComboStrap\CallStack;
use ComboStrap\DataType;
use ComboStrap\EditButton;
use ComboStrap\EditButtonManager;
use ComboStrap\ExceptionBadArgument;
use ComboStrap\ExceptionCompile;
use ComboStrap\ExceptionNotEnabled;
use ComboStrap\LogUtility;
use ComboStrap\PluginUtility;
use ComboStrap\TagAttributes;

if (!defined('DOKU_INC')) die();

/**
 * Class syntax_plugin_combo_box
 * Implementation of a div
 *
 */
class syntax_plugin_combo_slide extends DokuWiki_Syntax_Plugin
{

    const TAG = "slide";
    const CONF_ENABLE_SECTION_EDITING = "enableSlideSectionEditing";
    const CANONICAL = self::TAG;


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
    function getAllowedTypes(): array
    {
        return array('container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs');
    }

    public function accepts($mode): bool
    {

        return syntax_plugin_combo_preformatted::disablePreformatted($mode);

    }

    function getSort()
    {
        return 200;
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

                $tagAttributes = TagAttributes::createFromTagMatch($match)
                    ->setLogicalTag(self::TAG);

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $tagAttributes->toCallStackArray(),
                    PluginUtility::POSITION => $pos
                );

            case DOKU_LEXER_UNMATCHED :
                return PluginUtility::handleAndReturnUnmatchedData(self::TAG, $match, $handler);

            case DOKU_LEXER_EXIT :


                /**
                 * End section
                 */
                if (PluginUtility::getConfValue(self::CONF_ENABLE_SECTION_EDITING, 1)) {
                    $callStack = CallStack::createFromHandler($handler);
                    $openingTag = $callStack->moveToPreviousCorrespondingOpeningCall();
                    try {
                        $startPosition = DataType::toInteger($openingTag->getAttribute(PluginUtility::POSITION));
                    } catch (ExceptionBadArgument $e) {
                        LogUtility::error("The position of the slide is not an integer", self::CANONICAL);
                        $startPosition = null;
                    }
                    $id = $openingTag->getIdOrDefault();
                    // +1 to go at the line
                    $endPosition = $pos + strlen($match) + 1;
                    $editButtonCall = EditButton::create("Edit slide $id")
                        ->setStartPosition($startPosition)
                        ->setEndPosition($endPosition)
                        ->toComboCall();
                    $callStack->moveToEnd();
                    $callStack->insertBefore($editButtonCall);
                }

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
    function render($format, Doku_Renderer $renderer, $data): bool
    {
        if ($format == 'xhtml') {

            /** @var Doku_Renderer_xhtml $renderer */
            $state = $data[PluginUtility::STATE];
            switch ($state) {
                case DOKU_LEXER_ENTER :

                    /**
                     * Section Edit
                     */
                    if (PluginUtility::getConfValue(self::CONF_ENABLE_SECTION_EDITING, 1)) {
                        $position = $data[PluginUtility::POSITION];
                        $name = IdManager::getOrCreate()->generateNewIdForComponent(self::TAG);
                        EditButtonManager::getOrCreate()->createAndAddEditButtonToStack($name, $position);
                    }

                    /**
                     * Attributes
                     */
                    $attributes = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES]);
                    $attributes->addClassName(self::TAG);

                    $sizeAttribute = "size";
                    $size = "md";
                    if ($attributes->hasComponentAttribute($sizeAttribute)) {
                        $size = $attributes->getValueAndRemove($sizeAttribute);
                    }
                    switch ($size) {
                        case "lg":
                        case "large":
                            $attributes->addClassName(self::TAG . "-lg");
                            break;
                        case "sm":
                        case "small":
                            $attributes->addClassName(self::TAG . "-sm");
                            break;
                        case "xl":
                        case "extra-large":
                            $attributes->addClassName(self::TAG . "-xl");
                            break;
                        default:
                            $attributes->addClassName(self::TAG . "-md");
                            break;
                    }

                    PluginUtility::getSnippetManager()->attachCssInternalStyleSheetForSlot(self::TAG);


                    $renderer->doc .= $attributes->toHtmlEnterTag("section");
                    $renderer->doc .= "<div class=\"slide-body\" style=\"z-index:1;position: relative;\">";
                    break;

                case DOKU_LEXER_UNMATCHED :
                    $renderer->doc .= PluginUtility::renderUnmatched($data);
                    break;

                case DOKU_LEXER_EXIT :

                    /**
                     * End body
                     */
                    $renderer->doc .= '</div>';

                    /**
                     * End component
                     */
                    $renderer->doc .= '</section>';

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

