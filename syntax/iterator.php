<?php


use ComboStrap\ExceptionNotFound;
use ComboStrap\ExecutionContext;
use ComboStrap\FetcherMarkup;
use ComboStrap\FragmentTag;
use ComboStrap\MarkupCacheDependencies;
use ComboStrap\CacheManager;
use ComboStrap\Call;
use ComboStrap\CallStack;
use ComboStrap\MarkupDynamicRender;
use ComboStrap\ExceptionCompile;
use ComboStrap\LogUtility;
use ComboStrap\MarkupPath;
use ComboStrap\PagePath;
use ComboStrap\PageSql;
use ComboStrap\PageSqlTreeListener;
use ComboStrap\PluginUtility;
use ComboStrap\Sqlite;
use ComboStrap\TagAttributes;

require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');


/**
 *
 * An iterator to iterate over templates.
 *
 * *******************
 * Iteration driver
 * *******************
 * The end tag of the template node is driving the iteration.
 * This way, the tags just after the template
 * sees them in the {@link CallStack} and can change their context
 *
 * For instance, a {@link syntax_plugin_combo_masonry}
 * component will change the context of all card inside it.
 *
 * ********************
 * Header and footer delimitation
 * ********************
 * The iterator delimits also the header and footer.
 * Some component needs the header to be generate completely.
 * This is the case of a complex markup such as a table
 *
 * ******************************
 * Delete if no data
 * ******************************
 * It gives also the possibility to {@link syntax_plugin_combo_iterator::EMPTY_ROWS_COUNT_ATTRIBUTE
 * delete the whole block}
 * (header and footer also) if there is no data
 *
 * *****************************
 * Always Contextual
 * *****************************
 * We don't capture the text markup such as in a {@link syntax_plugin_combo_code}
 * in order to loop because you can't pass the actual handler (ie callstack)
 * when you {@link p_get_instructions() parse again} a markup.
 *
 * The markup is then seen as a new single page without any context.
 * That may lead to problems.
 * Example: `heading` may then think that they are `outline heading` ...
 *
 */
class syntax_plugin_combo_iterator extends DokuWiki_Syntax_Plugin
{

    /**
     * Tag in Dokuwiki cannot have a `-`
     * This is the last part of the class
     */
    const TAG = "iterator";

    /**
     * Page canonical and tag pattern
     */
    const CANONICAL = "iterator";
    const PAGE_SQL = "page-sql";
    const PAGE_SQL_ATTRIBUTES = "page-sql-attributes";
    const COMPLEX_MARKUP_FOUND = "complex-markup-found";
    const BEFORE_TEMPLATE_CALLSTACK = "header-callstack";
    const AFTER_TEMPLATE_CALLSTACK = "footer-callstack";
    const TEMPLATE_CALLSTACK = "template-callstack";


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

    function getSort(): int
    {
        return 201;
    }

    public function accepts($mode): bool
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
     * @return array
     * @see DokuWiki_Syntax_Plugin::handle()
     *
     */
    function handle($match, $state, $pos, Doku_Handler $handler): array
    {

        switch ($state) {

            case DOKU_LEXER_ENTER :

                $tagAttributes = TagAttributes::createFromTagMatch($match);
                $callStackArray = $tagAttributes->toCallStackArray();
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $callStackArray
                );

            case DOKU_LEXER_UNMATCHED :

                // We should not ever come here but a user does not not known that
                return PluginUtility::handleAndReturnUnmatchedData(self::TAG, $match, $handler);


            case DOKU_LEXER_EXIT :

                $callStack = CallStack::createFromHandler($handler);
                $openTag = $callStack->moveToPreviousCorrespondingOpeningCall();
                /**
                 * Scanning the callstack and extracting the information
                 * such as sql and template instructions
                 */
                $pageSql = null;
                $pageSqlAttribute = [];
                $beforeTemplateCallStack = [];
                $templateStack = [];
                $afterTemplateCallStack = [];
                $parsingState = "before";
                $complexMarkupFound = false;
                while ($actualCall = $callStack->next()) {
                    $tagName = $actualCall->getTagName();

                    if ($tagName === syntax_plugin_combo_edit::TAG) {
                        /**
                         * Not capturing the edit button because the markup is generated
                         */
                        continue;
                    }

                    switch ($tagName) {
                        case syntax_plugin_combo_iteratordata::TAG:
                            switch ($actualCall->getState()) {
                                case DOKU_LEXER_UNMATCHED:
                                    $pageSql = $actualCall->getCapturedContent();
                                    break;
                                case DOKU_LEXER_ENTER:
                                    $pageSqlAttribute = $actualCall->getAttributes();
                                    break;
                            }
                            continue 2;
                        case FragmentTag::FRAGMENT_TAG:
                            $parsingState = "after";
                            if ($actualCall->getState() === DOKU_LEXER_EXIT) {
                                $templateStack = $actualCall->getPluginData(FragmentTag::CALLSTACK);
                                /**
                                 * Do we have markup where the instructions should be generated at once
                                 * and not line by line
                                 *
                                 * ie a list or a table
                                 */
                                foreach ($templateStack as $templateInstructions) {
                                    $templateCall = Call::createFromInstruction($templateInstructions);
                                    if (in_array($templateCall->getComponentName(), Call::BLOCK_MARKUP_DOKUWIKI_COMPONENTS)) {
                                        $complexMarkupFound = true;
                                    }

                                }
                            }
                            continue 2;
                        default:
                            if ($parsingState === "before") {
                                $beforeTemplateCallStack[] = $actualCall->toCallArray();
                            } else {
                                $afterTemplateCallStack[] = $actualCall->toCallArray();
                            };
                            break;
                    }
                }

                /**
                 * Wipe the content of iterator
                 */
                $callStack->deleteAllCallsAfter($openTag);

                /**
                 * Enter Tag is the driver tag
                 * (To be able to add class by third party component)
                 */
                $openTag->setPluginData(self::PAGE_SQL, $pageSql);
                $openTag->setPluginData(self::PAGE_SQL_ATTRIBUTES, $pageSqlAttribute);
                $openTag->setPluginData(self::COMPLEX_MARKUP_FOUND, $complexMarkupFound);
                $openTag->setPluginData(self::BEFORE_TEMPLATE_CALLSTACK, $beforeTemplateCallStack);
                $openTag->setPluginData(self::AFTER_TEMPLATE_CALLSTACK, $afterTemplateCallStack);
                $openTag->setPluginData(self::TEMPLATE_CALLSTACK, $templateStack);

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
        if ($format === "xhtml") {
            $state = $data[PluginUtility::STATE];
            switch ($state) {
                case DOKU_LEXER_EXIT:
                    return true;
                case DOKU_LEXER_UNMATCHED:
                    $renderer->doc .= PluginUtility::renderUnmatched($data);
                    return true;
                case DOKU_LEXER_ENTER:

                    $pageSql = $data[self::PAGE_SQL];

                    /**
                     * Data Processing
                     */
                    if ($pageSql === null) {
                        $renderer->doc .= "A data node could not be found as a child of the iterator.";
                        return false;
                    }
                    if (empty($pageSql)) {
                        $renderer->doc .= "The data node definition needs a logical sql content";
                        return false;
                    }

                    /**
                     * Sqlite available ?
                     */
                    $sqlite = Sqlite::createOrGetSqlite();
                    if ($sqlite === null) {
                        $renderer->doc .= "The iterator component needs Sqlite to be able to work";
                        return false;
                    }


                    /**
                     * Create the SQL
                     */
                    try {
                        $tagAttributes = TagAttributes::createFromCallStackArray($data[self::PAGE_SQL_ATTRIBUTES]);
                        $path = $tagAttributes->getValue(PagePath::PROPERTY_NAME);
                        if ($path !== null) {
                            $contextualPage = MarkupPath::createPageFromQualifiedId($path);
                        } else {
                            $contextualPage = MarkupPath::createFromRequestedPage();
                        }
                        $pageSql = PageSql::create($pageSql, $contextualPage);
                    } catch (Exception $e) {
                        $renderer->doc .= "The page sql is not valid. Error Message: {$e->getMessage()}. Page Sql: ($pageSql)";
                        return false;
                    }

                    $table = $pageSql->getTable();
                    try {
                        $cacheDependencies = ExecutionContext::getActualOrCreateFromEnv()
                            ->getExecutingMarkupHandler()
                            ->getCacheDependencies();

                        switch ($table) {
                            case PageSqlTreeListener::BACKLINKS:
                                $cacheDependencies->addDependency(MarkupCacheDependencies::BACKLINKS_DEPENDENCY);
                                // The requested page dependency could be determined by the backlinks dependency
                                $cacheDependencies->addDependency(MarkupCacheDependencies::REQUESTED_PAGE_DEPENDENCY);
                                break;
                            case PageSqlTreeListener::DESCENDANTS:
                                $cacheDependencies->addDependency(MarkupCacheDependencies::PAGE_SYSTEM_DEPENDENCY);
                                break;
                            default:
                        }
                    } catch (ExceptionNotFound $e) {
                        // not a fetcher markup run
                    }

                    /**
                     * Execute the generated SQL
                     */
                    try {
                        $executableSql = $pageSql->getExecutableSql();
                        $parameters = $pageSql->getParameters();
                        $request = $sqlite
                            ->createRequest()
                            ->setQueryParametrized($executableSql, $parameters);
                        $rowsInDb = [];
                        try {
                            $rowsInDb = $request
                                ->execute()
                                ->getRows();
                        } catch (ExceptionCompile $e) {
                            $renderer->doc .= "The sql statement generated returns an error. Sql statement: $executableSql";
                            return false;
                        } finally {
                            $request->close();
                        }

                        $rows = [];
                        foreach ($rowsInDb as $sourceRow) {

                            /**
                             * @deprecated
                             * We use id until path is full in the database
                             */
                            $id = $sourceRow["ID"];
                            $contextualPage = MarkupPath::createMarkupFromId($id);
                            if ($contextualPage->isHidden()) {
                                continue;
                            }
                            if (!$contextualPage->exists()) {
                                LogUtility::error("Internal Error: the page selected ($contextualPage) was not added. It does not exist and was deleted from the database index.", self::CANONICAL);
                                $contextualPage->getDatabasePage()->delete();
                                continue;
                            }
                            $standardMetadata = $contextualPage->getMetadataForRendering();
                            $rows[] = $standardMetadata;
                        }
                    } catch (Exception $e) {
                        $renderer->doc .= "Error during Sql Execution. Error: {$e->getMessage()}";
                        return false;
                    }


                    /**
                     * Loop
                     */
                    $elementCounts = sizeof($rows);
                    if ($elementCounts === 0) {
                        $parametersString = implode(", ", $parameters);
                        LogUtility::msg("The physical query (Sql: {$pageSql->getExecutableSql()}, Parameters: $parametersString) does not return any data", LogUtility::LVL_MSG_INFO, syntax_plugin_combo_iterator::CANONICAL);
                        return true;
                    }


                    /**
                     * Template stack processing
                     */
                    $iteratorTemplateInstructions = $data[self::TEMPLATE_CALLSTACK];
                    if ($iteratorTemplateInstructions === null) {
                        $renderer->doc .= "No template was found in this iterator.";
                        return false;
                    }


                    /**
                     * Split template
                     * Splits the template into header, main and footer
                     * in case of complex header
                     */
                    $templateHeader = array();
                    $templateMain = $iteratorTemplateInstructions;
                    $templateFooter = array();
                    $complexMarkupFound = $data[self::COMPLEX_MARKUP_FOUND];
                    if ($complexMarkupFound) {

                        /**
                         * @var Call $actualCall
                         */
                        $templateCallStack = CallStack::createFromInstructions($iteratorTemplateInstructions);

                        $actualStack = array();
                        $templateCallStack->moveToStart();
                        while ($actualCall = $templateCallStack->next()) {
                            switch ($actualCall->getComponentName()) {
                                case "listitem_open":
                                case "tablerow_open":
                                    $templateHeader = $actualStack;
                                    $actualStack = [$actualCall->toCallArray()];
                                    continue 2;
                                case "listitem_close":
                                case "tablerow_close":
                                    $actualStack[] = $actualCall->toCallArray();
                                    $templateMain = $actualStack;
                                    $actualStack = [];
                                    continue 2;
                                default:
                                    $actualStack[] = $actualCall->toCallArray();
                            }
                        }
                        $templateFooter = $actualStack;
                    }

                    $contextPath = ExecutionContext::getActualOrCreateFromEnv()->getContextPath();

                    /**
                     * Rendering
                     */
                    $renderDoc = "";

                    /**
                     * Header
                     */
                    $iteratorHeaderInstructions = $data[self::BEFORE_TEMPLATE_CALLSTACK];
                    if (!empty($iteratorHeaderInstructions)) {
                        /**
                         * Table with an header
                         * If this is the case, the table_close of the header
                         * and the table_open of the template should be
                         * deleted to create one table
                         */
                        if (!empty($templateHeader)) {
                            $firstTemplateCall = Call::createFromInstruction($templateHeader[0]);
                            if ($firstTemplateCall->getComponentName() === "table_open") {
                                $lastIterationHeaderElement = sizeof($iteratorHeaderInstructions) - 1;
                                $lastIterationHeaderInstruction = Call::createFromInstruction($iteratorHeaderInstructions[$lastIterationHeaderElement]);
                                if ($lastIterationHeaderInstruction->getComponentName() === "table_close") {
                                    unset($iteratorHeaderInstructions[$lastIterationHeaderElement]);
                                    unset($templateHeader[0]);
                                }
                            }
                        }
                        try {
                            $renderDoc .= FetcherMarkup::getBuilder()
                                ->setBuilderRequestedInstructions($iteratorHeaderInstructions)
                                ->setRequestedContextPath($contextPath)
                                ->setRequestedMimeToXhtml()
                                ->setIsDocument(false)
                                ->build()
                                ->getFetchString();
                        } catch (ExceptionCompile $e) {
                            LogUtility::error("Error while rendering the iterator header. Error: {$e->getMessage()}", self::CANONICAL);
                            return false;
                        }
                    }

                    /**
                     * Template
                     */
                    try {
                        $renderDoc .= FetcherMarkup::getBuilder()
                            ->setBuilderRequestedInstructions($templateHeader)
                            ->setRequestedContextPath($contextPath)
                            ->setRequestedMimeToXhtml()
                            ->setIsDocument(false)
                            ->build()
                            ->getFetchString();
                    } catch (ExceptionCompile $e) {
                        LogUtility::error("Error while rendering the template header. Error: {$e->getMessage()}", self::CANONICAL);
                        return false;
                    }
                    foreach ($rows as $row) {
                        try {
                            $renderDoc .= FetcherMarkup::getBuilder()
                                ->setBuilderRequestedInstructions($templateMain)
                                ->setContextData($row)
                                ->setRequestedContextPath($contextPath)
                                ->setRequestedMimeToXhtml()
                                ->setIsDocument(false)
                                ->build()
                                ->getFetchString();
                        } catch (ExceptionCompile $e) {
                            LogUtility::error("Error while rendering a data row. Error: {$e->getMessage()}", self::CANONICAL);
                            continue;
                        }
                    }
                    try {
                        $renderDoc .= FetcherMarkup::getBuilder()
                            ->setBuilderRequestedInstructions($templateFooter)
                            ->setRequestedContextPath($contextPath)
                            ->setRequestedMimeToXhtml()
                            ->setIsDocument(false)
                            ->build()
                            ->getFetchString();
                    } catch (ExceptionCompile $e) {
                        LogUtility::error("Error while rendering the template footer. Error: {$e->getMessage()}", self::CANONICAL);
                        return false;
                    }


                    /**
                     * Iterator Footer
                     */
                    $callStackFooterInstructions = $data[self::AFTER_TEMPLATE_CALLSTACK];
                    if (!empty($callStackFooterInstructions)) {
                        try {
                            $renderDoc .= FetcherMarkup::getBuilder()
                                ->setBuilderRequestedInstructions($callStackFooterInstructions)
                                ->setRequestedContextPath($contextPath)
                                ->setRequestedMimeToXhtml()
                                ->setIsDocument(false)
                                ->build()
                                ->getFetchString();
                        } catch (ExceptionCompile $e) {
                            LogUtility::error("Error while rendering the iterator footer. Error: {$e->getMessage()}", self::CANONICAL);
                            return false;
                        }
                    }

                    /**
                     * Renderer
                     */
                    $renderer->doc .= $renderDoc;
                    return true;

            }
        }
        // unsupported $mode
        return false;
    }


}

