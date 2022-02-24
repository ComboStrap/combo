<?php


use ComboStrap\Bootstrap;
use ComboStrap\CallStack;
use ComboStrap\LogUtility;
use ComboStrap\PluginUtility;
use ComboStrap\TagAttributes;
use ComboStrap\Tooltip;

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

    /**
     * Class added to the parent
     */
    const CANONICAL = "tooltip";
    public const TEXT_ATTRIBUTE = "text";

    /**
     * To see the tooltip immediately when hovering the class d-inline-block
     *
     * The inline block is to make the element (span) take the whole space
     * of the image (ie dimension) otherwise it has no dimension and
     * you can't click on it
     *
     * TODO: Add this to the {@link Tooltip} ???
     */
    const TOOLTIP_CLASS_INLINE_BLOCK = "d-inline-block";

    /**
     * Syntax Type.
     *
     * Needs to return one of the mode types defined in $PARSER_MODES in parser.php
     * @see https://www.dokuwiki.org/devel:syntax_plugins#syntax_types
     * @see DokuWiki_Syntax_Plugin::getType()
     */
    function getType(): string
    {
        /**
         * You could add a tooltip to a {@link syntax_plugin_combo_itext}
         */
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
    function getPType(): string
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
    function getAllowedTypes(): array
    {
        return array('baseonly', 'container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs');
    }

    function getSort(): int
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
     * @return array
     * @see DokuWiki_Syntax_Plugin::handle()
     *
     */
    function handle($match, $state, $pos, Doku_Handler $handler): array
    {

        switch ($state) {

            case DOKU_LEXER_ENTER :
                $tagAttributes = TagAttributes::createFromTagMatch($match);
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $tagAttributes->toCallStackArray()
                );


            case DOKU_LEXER_UNMATCHED :
                return PluginUtility::handleAndReturnUnmatchedData(self::TAG, $match, $handler);

            case DOKU_LEXER_EXIT :

                $callStack = CallStack::createFromHandler($handler);
                $openingTag = $callStack->moveToPreviousCorrespondingOpeningCall();
                if ($openingTag->hasAttribute(self::TEXT_ATTRIBUTE)) {
                    /**
                     * Old syntax where the tooltip was the wrapper
                     */
                    return array(
                        PluginUtility::STATE => $state,
                        PluginUtility::ATTRIBUTES=>$openingTag->getAttributes()
                    );
                }
                $parent = $callStack->moveToParent();
                if ($parent === false) {
                    return array(
                        PluginUtility::STATE => $state,
                        PluginUtility::EXIT_MESSAGE => "A parent is mandatory for a tooltip",
                        PluginUtility::EXIT_CODE => 1
                    );
                }

                /**
                 * Capture the callstack
                 */
                $callStack->moveToCall($openingTag);
                $toolTipCallStack = null;
                while ($actualCall = $callStack->next()) {
                    $toolTipCallStack[] = $actualCall->toCallArray();
                }
                $callStack->deleteAllCallsAfter($openingTag);

                /**
                 * Set on the parent the tooltip attributes
                 * It will be processed by the {@link Tooltip}
                 * class at the end of {@link TagAttributes::toHtmlEnterTag()}
                 */
                $attributes = $openingTag->getAttributes();
                $attributes[Tooltip::CALLSTACK] = $toolTipCallStack;
                $parent->addAttribute(Tooltip::TOOLTIP_ATTRIBUTE, $attributes);

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
                     * Old syntax
                     * where tooltip was enclosing the text with the tooltip
                     */
                    $callStackArray = $data[PluginUtility::ATTRIBUTES];
                    $tagAttributes = TagAttributes::createFromCallStackArray($callStackArray);
                    $text = $tagAttributes->getValue(self::TEXT_ATTRIBUTE);
                    if ($text !== null) {
                        /**
                         * Old syntax where the tooltip was the wrapper
                         */
                        $renderer->doc .= TagAttributes::createFromCallStackArray([Tooltip::TOOLTIP_ATTRIBUTE => $callStackArray])
                            ->addClassName(self::TOOLTIP_CLASS_INLINE_BLOCK)
                            ->toHtmlEnterTag("span");
                    }
                    break;

                case DOKU_LEXER_UNMATCHED:
                    $renderer->doc .= PluginUtility::renderUnmatched($data);
                    break;

                case DOKU_LEXER_EXIT:
                    $message = $data[PluginUtility::EXIT_MESSAGE];
                    if ($message !== null) {
                        $renderer->doc .= LogUtility::wrapInRedForHtml($message);
                        return false;
                    }

                    $callStackArray = $data[PluginUtility::ATTRIBUTES];
                    $tagAttributes = TagAttributes::createFromCallStackArray($callStackArray);
                    $text = $tagAttributes->getValue(self::TEXT_ATTRIBUTE);
                    if ($text !== null) {
                        /**
                         * Old syntax where the tooltip was the wrapper
                         */
                        $renderer->doc .= "</span>";
                    }

                    break;


            }
            return true;
        }

        // unsupported $mode
        return false;
    }


}

