<?php


use ComboStrap\CacheManager;
use ComboStrap\CacheDependencies;
use ComboStrap\Call;
use ComboStrap\CallStack;
use ComboStrap\ExceptionCombo;
use ComboStrap\LogUtility;
use ComboStrap\Page;
use ComboStrap\PageImages;
use ComboStrap\PageSql;
use ComboStrap\PageSqlTreeListener;
use ComboStrap\PluginUtility;
use ComboStrap\Sqlite;
use ComboStrap\TagAttributes;
use ComboStrap\Template;
use ComboStrap\TemplateUtility;

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
    const VARIABLE_NAMES = "variable-names";
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
     *  * 'normal' - The plugin can be used inside paragraphs (inline or inside)
     *  * 'block'  - Open paragraphs need to be closed before plugin output (box) - block should not be inside paragraphs
     *  * 'stack'  - Special case. Plugin wraps other paragraphs. - Stacks can contain paragraphs
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
                $beforeTemplateCallStack = [];
                $templateStack = [];
                $afterTemplateCallStack = [];
                $parsingState = "before";
                $complexMarkupFound = false;
                $variableNames = [];
                while ($actualCall = $callStack->next()) {
                    $tagName = $actualCall->getTagName();
                    switch ($tagName) {
                        case syntax_plugin_combo_iteratordata::TAG:
                            if ($actualCall->getState() === DOKU_LEXER_UNMATCHED) {
                                $pageSql = $actualCall->getCapturedContent();
                            }
                            continue 2;
                        case syntax_plugin_combo_template::TAG:
                            $parsingState = "after";
                            if ($actualCall->getState() === DOKU_LEXER_EXIT) {
                                $templateStack = $actualCall->getPluginData(syntax_plugin_combo_template::CALLSTACK);
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

                                    /**
                                     * Capture variable names
                                     * to be able to find their value
                                     * in the metadata if they are not in sql
                                     */
                                    $textWithVariables = $templateCall->getCapturedContent();
                                    $attributes = $templateCall->getAttributes();
                                    if ($attributes !== null) {
                                        $sep = " ";
                                        foreach ($attributes as $key => $attribute) {
                                            $textWithVariables .= $sep . $key . $sep . $attribute;
                                        }
                                    }

                                    if (!empty($textWithVariables)) {
                                        $template = Template::create($textWithVariables);
                                        $variablesDetected = $template->getVariablesDetected();
                                        $variableNames = array_merge($variableNames, $variablesDetected);
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
                $variableNames = array_unique($variableNames);

                /**
                 * Wipe the content of iterator
                 */
                $callStack->deleteAllCallsAfter($openTag);

                return array(
                    PluginUtility::STATE => $state,
                    self::PAGE_SQL => $pageSql,
                    self::VARIABLE_NAMES => $variableNames,
                    self::COMPLEX_MARKUP_FOUND => $complexMarkupFound,
                    self::BEFORE_TEMPLATE_CALLSTACK => $beforeTemplateCallStack,
                    self::AFTER_TEMPLATE_CALLSTACK => $afterTemplateCallStack,
                    self::TEMPLATE_CALLSTACK => $templateStack
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
                case DOKU_LEXER_ENTER:
                    return true;
                case DOKU_LEXER_UNMATCHED:
                    $renderer->doc .= PluginUtility::renderUnmatched($data);
                    return true;
                case DOKU_LEXER_EXIT:

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
                        $pageSql = PageSql::create($pageSql);
                    } catch (Exception $e) {
                        $renderer->doc .= "The page sql is not valid. Error Message: {$e->getMessage()}. Page Sql: ($pageSql)";
                        return false;
                    }

                    $table = $pageSql->getTable();
                    $cacheManager = CacheManager::getOrCreate();
                    switch ($table) {
                        case PageSqlTreeListener::BACKLINKS:
                            $cacheManager->addDependencyForCurrentSlot(CacheDependencies::BACKLINKS_DEPENDENCY);
                            break;
                        default:
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
                        } catch (ExceptionCombo $e) {
                            $renderer->doc .= "The sql statement generated returns an error. Sql statement: $executableSql";
                            return false;
                        } finally {
                            $request->close();
                        }

                        $variableNames = $data[self::VARIABLE_NAMES];
                        $rows = [];
                        foreach ($rowsInDb as $sourceRow) {
                            $analytics = $sourceRow["ANALYTICS"];
                            /**
                             * @deprecated
                             * We use id until path is full in the database
                             */
                            $id = $sourceRow["ID"];
                            $page = Page::createPageFromId($id);
                            if ($page->isHidden()) {
                                continue;
                            }
                            $standardMetadata = $page->getMetadataForRendering();

                            $jsonArray = json_decode($analytics, true);
                            $targetRow = [];
                            foreach ($variableNames as $variableName) {

                                if ($variableName === PageImages::PROPERTY_NAME) {
                                    LogUtility::msg("To add an image, you must use the page image component, not the image metadata", LogUtility::LVL_MSG_ERROR, syntax_plugin_combo_pageimage::CANONICAL);
                                    continue;
                                }

                                /**
                                 * Data in the pages tables
                                 */
                                if (isset($sourceRow[strtoupper($variableName)])) {
                                    $variableValue = $sourceRow[strtoupper($variableName)];
                                    $targetRow[$variableName] = $variableValue;
                                    continue;
                                }

                                /**
                                 * In the analytics
                                 */
                                $value = $jsonArray["metadata"][$variableName];
                                if (!empty($value)) {
                                    $targetRow[$variableName] = $value;
                                    continue;
                                }

                                /**
                                 * Computed
                                 * (if the table is empty because of migration)
                                 */
                                $value = $standardMetadata[$variableName];
                                if (isset($value)) {
                                    $targetRow[$variableName] = $value;
                                    continue;
                                }

                                /**
                                 * Bad luck
                                 */
                                $targetRow[$variableName] = "$variableName attribute is unknown.";


                            }
                            $rows[] = $targetRow;
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
                    $iteratorHeaderInstructions = $data[self::BEFORE_TEMPLATE_CALLSTACK];


                    $iteratorTemplateGeneratedInstructions = [];


                    /**
                     * List and table syntax in template ?
                     */
                    $complexMarkupFound = $data[self::COMPLEX_MARKUP_FOUND];
                    if ($complexMarkupFound) {

                        /**
                         * Splits the template into header, main and footer
                         * @var Call $actualCall
                         */
                        $templateCallStack = CallStack::createFromInstructions($iteratorTemplateInstructions);
                        $templateHeader = array();
                        $templateMain = array();
                        $actualStack = array();
                        $templateCallStack->moveToStart();
                        while ($actualCall = $templateCallStack->next()) {
                            switch ($actualCall->getComponentName()) {
                                case "listitem_open":
                                case "tablerow_open":
                                    $templateHeader = $actualStack;
                                    $actualStack = [$actualCall];
                                    continue 2;
                                case "listitem_close":
                                case "tablerow_close":
                                    $actualStack[] = $actualCall;
                                    $templateMain = $actualStack;
                                    $actualStack = [];
                                    continue 2;
                                default:
                                    $actualStack[] = $actualCall;
                            }
                        }
                        $templateFooter = $actualStack;

                        /**
                         * Table with an header
                         * If this is the case, the table_close of the header
                         * and the table_open of the template should be
                         * deleted to create one table
                         */
                        if (!empty($templateHeader)) {
                            $firstTemplateCall = $templateHeader[0];
                            if ($firstTemplateCall->getComponentName() === "table_open") {
                                $lastIterationHeaderElement = sizeof($iteratorHeaderInstructions) - 1;
                                $lastIterationHeaderInstruction = Call::createFromInstruction($iteratorHeaderInstructions[$lastIterationHeaderElement]);
                                if ($lastIterationHeaderInstruction->getComponentName() === "table_close") {
                                    unset($iteratorHeaderInstructions[$lastIterationHeaderElement]);
                                    unset($templateHeader[0]);
                                }
                            }
                        }

                        /**
                         * Loop and recreate the call stack in instructions  form for rendering
                         */
                        $iteratorTemplateGeneratedInstructions = [];
                        foreach ($templateHeader as $templateHeaderCall) {
                            $iteratorTemplateGeneratedInstructions[] = $templateHeaderCall->toCallArray();
                        }
                        foreach ($rows as $row) {
                            $templateInstructionForInstance = TemplateUtility::renderInstructionsTemplateFromDataArray($templateMain, $row);
                            $iteratorTemplateGeneratedInstructions = array_merge($iteratorTemplateGeneratedInstructions, $templateInstructionForInstance);
                        }
                        foreach ($templateFooter as $templateFooterCall) {
                            $iteratorTemplateGeneratedInstructions[] = $templateFooterCall->toCallArray();
                        }


                    } else {

                        /**
                         * No Complex Markup
                         * We can use the calls form
                         */


                        /**
                         * Append the new instructions by row
                         */
                        foreach ($rows as $row) {
                            $templateInstructionForInstance = TemplateUtility::renderInstructionsTemplateFromDataArray($iteratorTemplateInstructions, $row);
                            $iteratorTemplateGeneratedInstructions = array_merge($iteratorTemplateGeneratedInstructions, $templateInstructionForInstance);
                        }


                    }
                    /**
                     * Rendering
                     */
                    $totalInstructions = [];
                    // header
                    if (!empty($iteratorHeaderInstructions)) {
                        $totalInstructions = $iteratorHeaderInstructions;
                    }
                    // content
                    if (!empty($iteratorTemplateGeneratedInstructions)) {
                        $totalInstructions = array_merge($totalInstructions, $iteratorTemplateGeneratedInstructions);
                    }
                    // footer
                    $callStackFooterInstructions = $data[self::AFTER_TEMPLATE_CALLSTACK];
                    if (!empty($callStackFooterInstructions)) {
                        $totalInstructions = array_merge($totalInstructions, $callStackFooterInstructions);
                    }
                    if (!empty($totalInstructions)) {

                        /**
                         * Advertise the total count to the
                         * {@link syntax_plugin_combo_carrousel}
                         * for the bullets if any
                         */
                        $totalCallStack = CallStack::createFromInstructions($totalInstructions);
                        $totalCallStack->moveToEnd();
                        while ($actualCall = $totalCallStack->previous()) {
                            if (
                                $actualCall->getTagName() === syntax_plugin_combo_carrousel::TAG
                                && in_array($actualCall->getState(), [DOKU_LEXER_ENTER, DOKU_LEXER_EXIT])
                            ) {
                                $actualCall->setPluginData(syntax_plugin_combo_carrousel::ELEMENT_COUNT, $elementCounts);
                                if ($actualCall->getState() === DOKU_LEXER_ENTER) {
                                    break;
                                }
                            }
                        }

                        try {
                            $renderer->doc .= PluginUtility::renderInstructionsToXhtml($totalCallStack->getStack());
                        } catch (ExceptionCombo $e) {
                            $renderer->doc .= "Error while rendering the iterators instructions. Error: {$e->getMessage()}";
                        }
                    }
                    return true;
            }
        }
        // unsupported $mode
        return false;
    }


}

