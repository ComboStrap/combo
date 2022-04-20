<?php


// must be run within Dokuwiki
use ComboStrap\CallStack;
use ComboStrap\Dimension;
use ComboStrap\LogUtility;
use ComboStrap\PluginUtility;
use ComboStrap\TagAttributes;

if (!defined('DOKU_INC')) die();

/**
 * Class syntax_plugin_combo_box
 * Implementation of a div
 *
 */
class syntax_plugin_combo_box extends DokuWiki_Syntax_Plugin
{

    const TAG = "box";
    const TAG_ATTRIBUTE = "tag";
    const DEFAULT_TAG = "div";
    // Tag that may make external http requests are not authorized
    const NON_AUTHORIZED_TAG = ["script", "style", "img", "video"];

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
    function getPType()
    {
        return 'stack';
    }

    /**
     * @return array
     * Allow which kind of plugin inside
     *
     * Array('baseonly','container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs')
     *
     * Return an array of one or more of the mode types {@link $PARSER_MODES} in Parser.php
     */
    function getAllowedTypes()
    {
        return array('container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs');
    }

    public function accepts($mode)
    {

        return syntax_plugin_combo_preformatted::disablePreformatted($mode);

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

    function handle($match, $state, $pos, Doku_Handler $handler)
    {

        switch ($state) {

            case DOKU_LEXER_ENTER :
                $defaultAttributes[self::TAG_ATTRIBUTE] = self::DEFAULT_TAG;
                $knownTypes = [];
                $attributes = TagAttributes::createFromTagMatch($match, $defaultAttributes, $knownTypes);
                $tag = $attributes->getValue(self::TAG_ATTRIBUTE);
                if (in_array($tag, self::NON_AUTHORIZED_TAG)) {
                    LogUtility::error("The html tag ($tag) is not authorized.");
                    $attributes->setComponentAttributeValue(self::TAG_ATTRIBUTE, self::DEFAULT_TAG);
                }
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $attributes->toCallStackArray()
                );

            case DOKU_LEXER_UNMATCHED :
                return PluginUtility::handleAndReturnUnmatchedData(self::TAG, $match, $handler);

            case DOKU_LEXER_EXIT :
                /**
                 * Check and add a scroll toggle if the
                 * box is constrained by height
                 */
                $callStack = CallStack::createFromHandler($handler);
                $openingTag = $callStack->moveToPreviousCorrespondingOpeningCall();
                Dimension::addScrollToggleOnClickIfNoControl($callStack);
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
                    $attributes = $data[PluginUtility::ATTRIBUTES];
                    $tagAttributes = TagAttributes::createFromCallStackArray($attributes, self::TAG);
                    $tagName = $tagAttributes->getValueAndRemove(self::TAG_ATTRIBUTE, self::DEFAULT_TAG);
                    $renderer->doc .= $tagAttributes->toHtmlEnterTag($tagName) . DOKU_LF;
                    break;

                case DOKU_LEXER_UNMATCHED :
                    $renderer->doc .= PluginUtility::renderUnmatched($data);
                    break;

                case DOKU_LEXER_EXIT :
                    $attributes = $data[PluginUtility::ATTRIBUTES];
                    $tagAttributes = TagAttributes::createFromCallStackArray($attributes, self::TAG);
                    $tagName = $tagAttributes->getValueAndRemove(self::TAG_ATTRIBUTE, self::DEFAULT_TAG);
                    $renderer->doc .= "</$tagName>";
                    break;
            }
            return true;
        }

        // unsupported $mode
        return false;
    }


}

