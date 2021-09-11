<?php


use ComboStrap\Call;
use ComboStrap\CallStack;
use ComboStrap\LogUtility;
use ComboStrap\Page;
use ComboStrap\PluginUtility;
use ComboStrap\TemplateUtility;


/**
 *
 * Template
 *
 * A template capture the string
 * and does not let the parser create the instructions.
 *
 * Why ?
 * Because when you create a list with an {@link syntax_plugin_combo_iterator}
 * A single list item such as
 * `
 *   * list
 * `
 * would be parsed as a complete list
 *
 * We create then the markup and we parse it.
 *
 */
class syntax_plugin_combo_template extends DokuWiki_Syntax_Plugin
{


    const TAG = "template";


    /**
     * The template context
     * To known if the template is inside a template generator
     * or not
     */
    const STANDALONE_CONTEXT = "standalone";
    const ITERATOR_CONTEXT = "iterator";
    const CANONICAL = "template";

    /**
     * @param Call $call
     */
    public static function getCapturedTemplateContent($call)
    {
        $content = $call->getCapturedContent();
        if (!empty($content)) {
            if ($content[0] === DOKU_LF) {
                $content = substr($content, 1);
            }
        }
        return $content;
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
        return 'protected';
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

        /**
         * A template capture the string
         * and does not let the parser create the instructions.
         *
         * See {@link syntax_plugin_combo_template template} documentation for more
         */
        return array();
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


    public function postConnect()
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
     * @throws Exception
     * @see DokuWiki_Syntax_Plugin::handle()
     *
     */
    function handle($match, $state, $pos, Doku_Handler $handler)
    {

        switch ($state) {

            case DOKU_LEXER_ENTER :

                $attributes = PluginUtility::getTagAttributes($match);
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $attributes
                );

            case DOKU_LEXER_UNMATCHED :

                // We should not ever come here but a user does not not known that
                return PluginUtility::handleAndReturnUnmatchedData(self::TAG, $match, $handler);


            case DOKU_LEXER_EXIT :

                $callStack = CallStack::createFromHandler($handler);


                /**
                 * The context
                 */
                $callStack->moveToPreviousCorrespondingOpeningCall();
                $context = self::STANDALONE_CONTEXT;
                while ($parent = $callStack->moveToParent()) {
                    if ($parent->getTagName() === syntax_plugin_combo_iterator::TAG) {
                        $context = self::ITERATOR_CONTEXT;
                    }
                }

                /**
                 * The array returned if any error
                 */
                $returnedArray = array(
                    PluginUtility::STATE => $state
                );

                if ($context === self::STANDALONE_CONTEXT) {

                    /**
                     * Gather template string
                     */
                    $callStack->moveToEnd();;
                    $unmatchedCall = $callStack->previous();
                    $content = "";
                    if ($unmatchedCall->getState() === DOKU_LEXER_UNMATCHED) {
                        $content = self::getCapturedTemplateContent($unmatchedCall);
                    }

                    if (empty($content)) {
                        LogUtility::msg("The content of a template is empty", LogUtility::LVL_MSG_WARNING, self::CANONICAL);
                        return $returnedArray;
                    }

                    $page = Page::createPageFromRequestedPage();
                    $metadata = $page->getMetadataStandard();
                    $marki = TemplateUtility::renderStringTemplateFromDataArray($content, $metadata);
                    $instructions = p_get_instructions($marki);
                    $callStack->appendInstructionsFromNativeArray($instructions);

                }
                return $returnedArray;


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

        // template is not rendering
        // it captures content that is used to create instructions
        return false;

    }


}

