<?php


use ComboStrap\CallStack;
use ComboStrap\PluginUtility;
use ComboStrap\StyleUtility;
use ComboStrap\TagAttributes;

require_once(__DIR__ . '/../class/StyleUtility.php');
require_once(__DIR__ . '/../class/SnippetManager.php');


/**
 * Class syntax_plugin_combo_list
 * Implementation of a list
 *
 * Content list
 *
 * https://getbootstrap.com/docs/4.1/components/list-group/
 * https://getbootstrap.com/docs/5.0/components/list-group/
 *
 * https://getbootstrap.com/docs/5.0/utilities/flex/
 * https://getbootstrap.com/docs/5.0/utilities/flex/#media-object
 * https://getbootstrap.com/docs/4.0/layout/media-object/#media-list - Bootstrap media list
 *
 * https://github.com/material-components/material-components-web/tree/master/packages/mdc-list - mdc list
 *
 * https://getbootstrap.com/docs/5.0/utilities/flex/#media-object
 */
class syntax_plugin_combo_contentlist extends DokuWiki_Syntax_Plugin
{

    const TAG = "contentlist";

    /**
     * To allow a minus
     */
    const COMBO_TAG = "content-list";
    const COMBO_TAG_OLD = "list";
    const COMBO_TAGS = [self::COMBO_TAG, self::COMBO_TAG_OLD];


    /**
     * Syntax Type.
     *
     * Needs to return one of the mode types defined in $PARSER_MODES in parser.php
     * @see https://www.dokuwiki.org/devel:syntax_plugins#syntax_types
     * @see DokuWiki_Syntax_Plugin::getType()
     */
    function getType()
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
        return array('container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs');
    }

    public function accepts($mode)
    {

        return syntax_plugin_combo_preformatted::disablePreformatted($mode);

    }


    function getSort()
    {
        return 15;
    }


    function connectTo($mode)
    {

        foreach (self::COMBO_TAGS as $tag) {
            $pattern = PluginUtility::getContainerTagPattern($tag);
            $this->Lexer->addEntryPattern($pattern, $mode, PluginUtility::getModeForComponent($this->getPluginComponent()));
        }

    }

    public function postConnect()
    {
        foreach (self::COMBO_TAGS as $tag) {
            $this->Lexer->addExitPattern('</' . $tag . '>', PluginUtility::getModeForComponent($this->getPluginComponent()));
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

                $attributes = TagAttributes::createFromTagMatch($match);

                if ($attributes->hasComponentAttribute(TagAttributes::TYPE_KEY)) {
                    $type = trim(strtolower($attributes->getType()));
                    if ($type == "flush") {
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

                return PluginUtility::handleAndReturnUnmatchedData(self::COMBO_TAG, $match, $handler);

            case DOKU_LEXER_EXIT :

                /**
                 * Add to all row the list-group-item
                 */
                $callStack = CallStack::createFromHandler($handler);
                $callStack->moveToPreviousCorrespondingOpeningCall();
                while ($actualCall = $callStack->next()) {
                    if ($actualCall->getTagName() == syntax_plugin_combo_row::TAG) {
                        $actualState = $actualCall->getState();
                        if ($actualState == DOKU_LEXER_ENTER) {
                            $actualCall->addClassName("list-group-item");
                            $actualCall->addClassName("d-flex");
                            $actualCall->addClassName("content-list-item-combo");
                        }
                        if (in_array($actualState, [DOKU_LEXER_ENTER, DOKU_LEXER_EXIT])) {
                            $actualCall->addAttribute(syntax_plugin_combo_row::HTML_TAG_ATT, "li");
                        }
                    }
                }

                /**
                 * Process the P to make them container friendly
                 * Needed to make the diff between a p added
                 * by the user via the {@link syntax_plugin_combo_para text}
                 * and a p added automatically by Dokuwiki
                 *
                 */
                $callStack->moveToPreviousCorrespondingOpeningCall();
                // Follow the bootstrap and combo convention
                // ie text for bs and combo as suffix
                $class = "content-list-text-combo";
                $callStack->processEolToEndStack(["class" => $class]);

                return array(PluginUtility::STATE => $state);


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

                    PluginUtility::getSnippetManager()->attachCssSnippetForBar(self::COMBO_TAG);
                    $tagAttributes = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES], self::COMBO_TAG);
                    $tagAttributes->addClassName("list-group");
                    $renderer->doc .= $tagAttributes->toHtmlEnterTag("ul");

                    break;
                case DOKU_LEXER_EXIT :
                    $renderer->doc .= "</ul>" . DOKU_LF;
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

