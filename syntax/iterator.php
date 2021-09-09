<?php


use ComboStrap\Call;
use ComboStrap\CallStack;
use ComboStrap\LogUtility;
use ComboStrap\PluginUtility;
use ComboStrap\Sqlite;
use ComboStrap\SqlParser;
use ComboStrap\TagAttributes;
use ComboStrap\TemplateUtility;

require_once(__DIR__ . '/../class/PluginUtility.php');


/**
 *
 *
 *
 * An iterator to iterate over template
 *
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

                $tagAttributes = TagAttributes::createFromTagMatch($match);
                $callStackArray = $tagAttributes->toCallStackArray();
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $callStackArray
                );

            case DOKU_LEXER_UNMATCHED :

                // We should not ever come here but a user does not not known that
                return PluginUtility::handleAndReturnUnmatchedData(self::TAG, $match, $handler);

            case DOKU_LEXER_MATCHED :

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => PluginUtility::getTagAttributes($match),
                    PluginUtility::PAYLOAD => PluginUtility::getTagContent($match),
                    PluginUtility::TAG => PluginUtility::getTag($match)
                );

            case DOKU_LEXER_EXIT :

                $callStack = CallStack::createFromHandler($handler);

                /**
                 * Capture the instructions for
                 * {@link syntax_plugin_combo_iteratordata}
                 * {@link syntax_plugin_combo_iteratorbody}
                 */
                $openingTag = $callStack->moveToPreviousCorrespondingOpeningCall();
                /**
                 * @var Call[] $dataInstructions
                 * @var array $dataAttributes
                 */
                $dataInstructions = null;
                $dataAttributes = null;
                /**
                 * @var Call[] $bodyInstructions
                 * @var array $bodyAttributes
                 */
                $bodyInstructions = null;
                $bodyAttributes = null;

                /**
                 * @var Call[] $actualInstructionsStack
                 */
                $actualInstructionsStack = [];
                while ($callStack->next()) {
                    $actualCall = $callStack->getActualCall();
                    $tagName = $actualCall->getTagName();
                    switch ($actualCall->getState()) {
                        case DOKU_LEXER_ENTER:
                            switch ($tagName) {
                                case syntax_plugin_combo_iteratordata::TAG:
                                    $actualInstructionsStack = [];
                                    $dataAttributes = $actualCall->getAttributes();
                                    continue 3;
                                case syntax_plugin_combo_iteratorbody::TAG:
                                    $bodyAttributes = $actualCall->getAttributes();
                                    $actualInstructionsStack = [];
                                    continue 3;
                                default:
                                    $actualInstructionsStack[] = $actualCall;
                                    continue 3;
                            }
                        case DOKU_LEXER_EXIT:
                            switch ($tagName) {
                                case syntax_plugin_combo_iteratordata::TAG:
                                    $dataInstructions = $actualInstructionsStack;
                                    $actualInstructionsStack = [];
                                    continue 3;
                                case syntax_plugin_combo_iteratorbody::TAG:
                                    $bodyInstructions = $actualInstructionsStack;
                                    $actualInstructionsStack = [];
                                    continue 3;
                                default:
                                    $actualInstructionsStack[] = $actualCall;
                                    continue 3;

                            }
                        default:
                            $actualInstructionsStack[] = $actualCall;
                            break;

                    }
                }

                /**
                 * The returned array
                 * in case there is a problem early
                 */
                $returnArray = array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $openingTag->getAttributes()
                );

                /**
                 * Remove all callstack from the opening tag
                 */
                $callStack->deleteAllCallsAfter($openingTag);


                /**
                 * Processing
                 */
                if ($dataInstructions === null) {
                    LogUtility::msg("The iterator needs a data definition", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                    return $returnArray;
                }

                $sql = $dataInstructions[0]->getCapturedContent();

                /**
                 * Create the SQL
                 */
                $columns = SqlParser::create($sql)
                    ->parse()
                    ->getColumnIdentifiers();
                if (sizeof($columns) === 0) {
                    LogUtility::msg("The parsed sql ($sql) contains no columns", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                    return $returnArray;
                }

                /**
                 * Sqlite available ?
                 */
                $sqlite = Sqlite::getSqlite();
                if ($sqlite === null) {
                    LogUtility::msg("iterator needs Sqlite to be able to work", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                    return $returnArray;
                }

                /**
                 * Json support
                 */
                $res = $sqlite->query("PRAGMA compile_options");
                $isJsonEnabled = false;
                foreach ($sqlite->res2arr($res) as $row) {
                    if ($row["compile_option"] === "ENABLE_JSON1") {
                        $isJsonEnabled = true;
                        break;
                    }
                };
                $sqlite->res_close($res);

                if ($isJsonEnabled) {
                    $expressionMetadataMapping = [
                        "title" => "json_extract(analytics , '$.metadata.title')"
                    ];
                    $executableSql = "select";
                    foreach ($columns as $alias => $expression) {
                        $expression = $expressionMetadataMapping[$executableSql];
                        $executableSql .= " $expression as $alias,";
                    }
                    $executableSql = trim($executableSql, ",");
                    $executableSql .= " from pages";
                    $res = $sqlite->query($executableSql);
                    if (!$res) {
                        LogUtility::msg("An exception has occurred with the sql ($executableSql).", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                        return $returnArray;
                    }
                    $rows = $sqlite->res2arr($res);
                    $sqlite->res_close($res);
                } else {
                    $executableSql = "select analytics from pages";
                    $res = $sqlite->query($executableSql);
                    if (!$res) {
                        LogUtility::msg("An exception has occurred with the sql ($executableSql).", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                        return $returnArray;
                    }
                    $res2arr = $sqlite->res2arr($res);
                    $rows = [];
                    foreach ($res2arr as $sourceRow) {
                        $analytics = $sourceRow["ANALYTICS"];
                        $jsonArray = json_decode($analytics, true);
                        $targetRow = [];
                        foreach ($columns as $alias => $expression) {
                            if(isset($jsonArray["metadata"][$alias])) {
                                $targetRow[$alias] = $jsonArray["metadata"][$alias];
                            } else {
                                $targetRow[$alias] = "NotFound";
                            }
                        }
                        $rows[] = $targetRow;
                    }
                    $sqlite->res_close($res);
                }


                /**
                 * Loop
                 */
                foreach ($rows as $row) {

                    $instructionsInstance = TemplateUtility::renderInstructionsTemplateFromDataArray($bodyInstructions, $row);
                    $callStack->appendInstructions($instructionsInstance);

                }

                return $returnArray;


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

