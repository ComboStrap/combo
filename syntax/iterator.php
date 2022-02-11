<?php


use ComboStrap\CacheManager;
use ComboStrap\CacheDependencies;
use ComboStrap\Call;
use ComboStrap\CallStack;
use ComboStrap\LogUtility;
use ComboStrap\PageSql;
use ComboStrap\PageSqlTreeListener;
use ComboStrap\PluginUtility;
use ComboStrap\Sqlite;
use ComboStrap\TagAttributes;
use ComboStrap\Template;

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
     * @return array|bool
     * @throws Exception
     * @see DokuWiki_Syntax_Plugin::handle()
     *
     */
    function handle($match, $state, $pos, Doku_Handler $handler)
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
                $openIteratorTag = $callStack->moveToPreviousCorrespondingOpeningCall();
                /**
                 * Scanning the callstack and extracting the information
                 * such as sql and template instructions
                 */
                $pageSql = null;
                /**
                 * @var Call[]
                 */
                $actualStack = [];
                $complexMarkupFound = false;
                $variableNames = [];
                while ($actualCall = $callStack->next()) {

                    /**
                     * Capture Variable Names
                     */
                    $textWithVariables = $actualCall->getCapturedContent();
                    $attributes = $actualCall->getAttributes();
                    if ($attributes != null) {
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

                    /**
                     * Other capture
                     */
                    switch ($actualCall->getTagName()) {
                        case syntax_plugin_combo_iteratordata::TAG:
                            if ($actualCall->getState() === DOKU_LEXER_UNMATCHED) {
                                $pageSql = $actualCall->getCapturedContent();
                            }
                            break;
                        case self::TAG:
                            if ($actualCall->getState() === DOKU_LEXER_ENTER) {
                                $headerStack = $actualStack;
                                $actualStack = [];
                            } else {
                                $actualStack[] = $actualCall;
                            }
                            break;
                        default:
                            $actualStack[] = $actualCall;
                            /**
                             * Do we have markup where the instructions should be generated at once
                             * and not line by line
                             *
                             * ie a list or a table
                             */
                            if (in_array($actualCall->getComponentName(), Call::BLOCK_MARKUP_DOKUWIKI_COMPONENTS)) {
                                $complexMarkupFound = true;
                            }

                    }
                }
                $templateStack = $actualStack;
                $variableNames = array_unique($variableNames);


                /**
                 * Data Processing
                 */
                if ($pageSql === null) {
                    $returnedArray[PluginUtility::EXIT_CODE] = 1;
                    $returnedArray[PluginUtility::EXIT_MESSAGE] = "A data node could not be found in the iterator";
                    return $returnedArray;
                }
                if (empty($pageSql)) {
                    $returnedArray[PluginUtility::EXIT_CODE] = 1;
                    $returnedArray[PluginUtility::EXIT_MESSAGE] = "The data node definition needs a logical sql content";
                    return $returnedArray;
                }

                /**
                 * Sqlite available ?
                 */
                $sqlite = Sqlite::createOrGetSqlite();
                if ($sqlite === null) {
                    $returnedArray[PluginUtility::EXIT_CODE] = 1;
                    $returnedArray[PluginUtility::EXIT_MESSAGE] = "The iterator component needs Sqlite to be able to work";
                    return $returnedArray;
                }


                /**
                 * Create the SQL
                 */
                try {
                    $pageSql = PageSql::create($pageSql);
                } catch (Exception $e) {
                    $returnedArray[PluginUtility::EXIT_CODE] = 1;
                    $returnedArray[PluginUtility::EXIT_MESSAGE] = "The page sql is not valid. Error Message: {$e->getMessage()}. Page Sql: ($pageSql)";
                    return $returnedArray;
                }

                $table = $pageSql->getTable();
                $cacheManager = CacheManager::getOrCreate();
                switch ($table){
                    case PageSqlTreeListener::BACKLINKS:
                        $cacheManager->addDependency(CacheDependencies::BACKLINKS_DEPENDENCY);
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
                        LogUtility::msg("The sql statement generated returns an error. Sql statement: $executableSql", LogUtility::LVL_MSG_ERROR);
                    } finally {
                        $request->close();
                    }

                    $rows = [];
                    foreach ($rowsInDb as $sourceRow) {
                        $analytics = $sourceRow["ANALYTICS"];
                        /**
                         * @deprecated
                         * We use id until path is full in the database
                         */
                        $id = $sourceRow["ID"];
                        $page = Page::createPageFromId($id);
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
                                $data = $sourceRow[strtoupper($variableName)];
                                $targetRow[$variableName] = $data;
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
                    LogUtility::msg($e->getMessage(), LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                    return $returnedArray;
                }


                /**
                 * Loop
                 */
                if (sizeof($rows) == 0) {
                    $parametersString = implode($parameters, ", ");
                    LogUtility::msg("The physical query (Sql: {$pageSql->getExecutableSql()}, Parameters: $parametersString) does not return any data", LogUtility::LVL_MSG_INFO, syntax_plugin_combo_iterator::CANONICAL);
                    return $returnedArray;
                }

                /**
                 * List and table
                 */
                if ($complexMarkupFound) {

                    /**
                     * Splits the template into header, main and footer
                     * @var Call $actualCall
                     */
                    $templateHeader = array();
                    $templateMain = array();
                    $actualStack = array();
                    foreach ($templateStack as $actualCall) {
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
                     * Delete the template calls
                     */
                    $callStack->moveToEnd();;
                    $openingTemplateCall = $callStack->moveToPreviousCorrespondingOpeningCall();
                    $callStack->deleteAllCallsAfter($openingTemplateCall);

                    /**
                     * Table with an header
                     * If this is the case, the table_close of the header
                     * and the table_open of the template should be
                     * deleted to create one table
                     */
                    if (!empty($templateHeader)) {
                        $firstTemplateCall = $templateHeader[0];
                        if ($firstTemplateCall->getComponentName() === "table_open") {
                            $callStack->moveToEnd();
                            $callStack->moveToPreviousCorrespondingOpeningCall();
                            $previousCall = $callStack->previous();
                            if ($previousCall->getComponentName() === "table_close") {
                                $callStack->deleteActualCallAndPrevious();
                                unset($templateHeader[0]);
                            }
                        }
                    }
                    /**
                     * Loop and recreate the call stack
                     */
                    $callStack->appendInstructionsFromCallObjects($templateHeader);
                    foreach ($rows as $row) {
                        $instructionsInstance = TemplateUtility::renderInstructionsTemplateFromDataArray($templateMain, $row);
                        $callStack->appendInstructionsFromNativeArray($instructionsInstance);
                    }
                    $callStack->appendInstructionsFromCallObjects($templateFooter);


                } else {

                    /**
                     * No Complex Markup
                     * We can use the calls form
                     */

                    /**
                     * Delete the template
                     */
                    $callStack->moveToEnd();
                    $templateEnterCall = $callStack->moveToPreviousCorrespondingOpeningCall();
                    $callStack->deleteAllCallsAfter($templateEnterCall);

                    /**
                     * Append the new instructions by row
                     */
                    foreach ($rows as $row) {
                        $instructionsInstance = TemplateUtility::renderInstructionsTemplateFromDataArray($templateStack, $row);
                        $callStack->appendInstructionsFromNativeArray($instructionsInstance);
                    }


                }
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
        // unsupported $mode
        return false;
    }


}

