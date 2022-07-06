<?php


use ComboStrap\CacheDependencies;
use ComboStrap\CacheManager;
use ComboStrap\CallStack;
use ComboStrap\Canonical;
use ComboStrap\ExceptionCompile;
use ComboStrap\LogUtility;
use ComboStrap\PageFragment;
use ComboStrap\PageCreationDate;
use ComboStrap\PagePath;
use ComboStrap\PagePublicationDate;
use ComboStrap\PluginUtility;
use ComboStrap\RenderUtility;
use ComboStrap\ResourceName;
use ComboStrap\TagAttributes;


require_once(__DIR__ . "/../ComboStrap/PluginUtility.php");

/**
 *
 * Fragment
 *
 * A fragment is a part of a markup file.
 *
 * It can be then rendered with different {@link \ComboStrap\ContextManager} (page)
 *
 * Used inside an iterator.
 *
 * The content is the
 *
 *
 *
 */
class syntax_plugin_combo_fragment extends DokuWiki_Syntax_Plugin
{


    const TAG = "fragment";
    const TAG_OLD = "template";


    const CANONICAL = syntax_plugin_combo_variable::CANONICAL;
    const CALLSTACK = "callstack";
    const TAGS = [self::TAG, self::TAG_OLD];


    /**
     * Syntax Type.
     *
     * Needs to return one of the mode types defined in $PARSER_MODES in parser.php
     * @see https://www.dokuwiki.org/devel:syntax_plugins#syntax_types
     * @see DokuWiki_Syntax_Plugin::getType()
     */
    function getType(): string
    {
        return 'formatting';
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
    function getAllowedTypes(): array
    {

        return array('baseonly', 'container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs');
    }

    function getSort(): int
    {
        return 201;
    }

    public function accepts($mode)
    {
        return syntax_plugin_combo_preformatted::disablePreformatted($mode);
    }


    function connectTo($mode)
    {

        foreach (self::TAGS as $tag) {
            $pattern = PluginUtility::getContainerTagPattern($tag);
            $this->Lexer->addEntryPattern($pattern, $mode, PluginUtility::getModeFromTag($this->getPluginComponent()));
        }


    }


    public function postConnect()
    {
        foreach (self::TAGS as $tag) {
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
     * @throws Exception
     * @see DokuWiki_Syntax_Plugin::handle()
     *
     */
    function handle($match, $state, $pos, Doku_Handler $handler): array
    {

        switch ($state) {

            case DOKU_LEXER_ENTER :

                if (substr($match, 1, strlen(self::TAG_OLD)) === self::TAG_OLD) {
                    LogUtility::warning("The template component has been deprecated and replaced by the fragment component. Why ? Because a whole page is now a template. ", syntax_plugin_combo_iterator::CANONICAL);
                }
                $tagAttributes = TagAttributes::createFromTagMatch($match);
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $tagAttributes->toCallStackArray()
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
                CacheManager::getOrCreateFromRequestedPath()->addDependencyForCurrentSlot(CacheDependencies::REQUESTED_PAGE_DEPENDENCY);

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
                    $page = PageFragment::createFromRequestedPage();
                    $metadata = $page->getMetadataForRendering();
                    try {
                        $renderer->doc .= RenderUtility::renderInstructionsToXhtml($templateStack, $metadata);
                    } catch (ExceptionCompile $e) {
                        $renderer->doc .= LogUtility::wrapInRedForHtml("Error while rendering the instruction. Error: {$e->getMessage()}");
                    }
                    LogUtility::warning("There is no need anymore to use a template to render variable", self::CANONICAL);
                    return true;
            }
        }
        return false;

    }


}

