<?php


use ComboStrap\AnalyticsDocument;
use ComboStrap\CacheManager;
use ComboStrap\CacheDependencies;
use ComboStrap\Call;
use ComboStrap\CallStack;
use ComboStrap\Canonical;
use ComboStrap\DokuPath;
use ComboStrap\ExceptionCombo;
use ComboStrap\PageCreationDate;
use ComboStrap\Metadata;
use ComboStrap\PageImages;
use ComboStrap\ResourceName;
use ComboStrap\PagePath;
use ComboStrap\PageSql;
use ComboStrap\LogUtility;
use ComboStrap\Page;
use ComboStrap\Path;
use ComboStrap\PluginUtility;
use ComboStrap\PagePublicationDate;
use ComboStrap\Sqlite;
use ComboStrap\Template;
use ComboStrap\TemplateUtility;


require_once(__DIR__ . "/../ComboStrap/PluginUtility.php");

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

    const ATTRIBUTES_IN_PAGE_TABLE = [
        "id",
        Canonical::PROPERTY_NAME,
        PagePath::PROPERTY_NAME,
        ModificationDate::PROPERTY_NAME,
        PageCreationDate::PROPERTY_NAME,
        PagePublicationDate::PROPERTY_NAME,
        ResourceName::PROPERTY_NAME
    ];

    const CANONICAL = "template";
    const CALLSTACK = "callstack";

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
            /**
             * To allow the template to be indented
             * without triggering a {@link syntax_plugin_combo_preformatted}
             */
            $content = rtrim($content, " ");
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
        return 'formatting';
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
        /**
         * No P please
         */
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

    public function accepts($mode)
    {
        return syntax_plugin_combo_preformatted::disablePreformatted($mode);
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

                /**
                 * Gather template stack
                 */
                $callStack = CallStack::createFromHandler($handler);
                $templateEnterCall = $callStack->moveToPreviousCorrespondingOpeningCall();
                $templateStack = [];
                while ($actualCall = $callStack->next()) {
                    $templateStack[] = $actualCall->toCallArray();
                }
                $callStack->deleteAllCallsAfter($templateEnterCall);

                /**
                 * Cache dependent on the requested page
                 */
                CacheManager::getOrCreate()->addDependencyForCurrentSlot(CacheDependencies::REQUESTED_PAGE_DEPENDENCY);

                return array(
                    PluginUtility::STATE => $state,
                    self::CALLSTACK => $templateStack
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

        if ($format === "xhtml") {
            $state = $data[PluginUtility::STATE];
            switch ($state) {
                case DOKU_LEXER_UNMATCHED:
                    $renderer->doc .= PluginUtility::renderUnmatched($data);
                    return true;
                case DOKU_LEXER_EXIT:
                    $templateStack = $data[self::CALLSTACK];
                    if ($templateStack === null) {
                        $renderer->doc .= LogUtility::wrapInRedForHtml("Template instructions should not be null");
                        return false;
                    }
                    $page = Page::createPageFromRequestedPage();
                    $metadata = $page->getMetadataForRendering();
                    $instructionsInstance = TemplateUtility::renderInstructionsTemplateFromDataArray($templateStack, $metadata);
                    try {
                        $renderer->doc .= PluginUtility::renderInstructionsToXhtml($instructionsInstance);
                    } catch (ExceptionCombo $e) {
                        $renderer->doc .= LogUtility::wrapInRedForHtml("Error while rendering the template instruction. Error: {$e->getMessage()}");
                    }
                    return true;
            }
        }
        return false;

    }


}

