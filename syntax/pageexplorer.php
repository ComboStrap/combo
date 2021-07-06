<?php


use ComboStrap\Background;
use ComboStrap\Call;
use ComboStrap\CallStack;
use ComboStrap\DokuPath;
use ComboStrap\FsWikiUtility;
use ComboStrap\LogUtility;
use ComboStrap\Page;
use ComboStrap\PluginUtility;
use ComboStrap\TagAttributes;
use ComboStrap\TemplateUtility;

require_once(__DIR__ . '/../class/PluginUtility.php');


/**
 * Class syntax_plugin_combo_pageexplorer
 * Implementation of an explorer for pages
 *
 *
 *
 * https://getbootstrap.com/docs/4.0/components/scrollspy/#example-with-list-group
 * https://getbootstrap.com/docs/4.0/components/scrollspy/#example-with-nested-nav
 *
 *
 *
 */
class syntax_plugin_combo_pageexplorer extends DokuWiki_Syntax_Plugin
{

    /**
     * Tag in Dokuwiki cannot have a `-`
     * This is the last part of the class
     */
    const TAG = "pageexplorer";

    /**
     * Page canonical and tag pattern
     */
    const CANONICAL = "page-explorer";
    const COMBO_TAG_PATTERNS = ["ntoc", self::CANONICAL];

    /**
     * Component attribute
     */
    const ATTR_NAMESPACE = "ns";


    /**
     * Attributes on the home node
     */
    const LIST_TYPE = "list";
    const TYPE_TREE = "tree";

    /**
     * A counter/index that keeps
     * the order of the namespace tree node
     * to create a unique id
     * in order to be able to collapse
     * the good HTML node
     * @var string $namespaceCounter
     */
    private $namespaceCounter = 0;


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

        foreach (self::COMBO_TAG_PATTERNS as $tag) {
            $pattern = PluginUtility::getContainerTagPattern($tag);
            $this->Lexer->addEntryPattern($pattern, $mode, PluginUtility::getModeFromTag($this->getPluginComponent()));
        }

    }


    public function postConnect()
    {
        foreach (self::COMBO_TAG_PATTERNS as $tag) {
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
     * @return array|bool
     * @throws Exception
     * @see DokuWiki_Syntax_Plugin::handle()
     *
     */
    function handle($match, $state, $pos, Doku_Handler $handler)
    {

        switch ($state) {

            case DOKU_LEXER_ENTER :
                $default = [TagAttributes::TYPE_KEY => self::LIST_TYPE];
                $attributes = PluginUtility::getTagAttributes($match);
                $attributes = PluginUtility::mergeAttributes($attributes, $default);
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $attributes);

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
                 * {@link syntax_plugin_combo_pageexplorerpage}
                 * {@link syntax_plugin_combo_pageexplorernamespace}
                 * {@link syntax_plugin_combo_pageexplorernamehome}
                 */
                $openingTag = $callStack->moveToPreviousCorrespondingOpeningCall();
                /**
                 * @var Call[] $namespaceInstructions
                 * @var array $namespaceAttributes
                 */
                $namespaceInstructions = [];
                $namespaceAttributes = [];
                /**
                 * @var Call[] $pageInstructions
                 * @var array $pageAttributes
                 */
                $pageInstructions = [];
                $pageAttributes = [];
                /**
                 * @var Call[] $homeInstructions
                 * @var array $homeAttributes
                 */
                $homeInstructions = [];
                $homeAttributes = [];
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
                                case syntax_plugin_combo_pageexplorerpage::TAG:
                                    $pageAttributes = $actualCall->getAttributes();
                                    continue 3;
                                case syntax_plugin_combo_pageexplorernamespace::TAG:
                                    $namespaceAttributes = $actualCall->getAttributes();
                                    continue 3;
                                case syntax_plugin_combo_pageexplorerhome::TAG:
                                    $homeAttributes = $actualCall->getAttributes();
                                    continue 3;
                                default:
                                    $actualInstructionsStack[] = $actualCall;
                                    continue 3;
                            }
                        case DOKU_LEXER_EXIT:
                            switch ($tagName) {
                                case syntax_plugin_combo_pageexplorerpage::TAG:
                                    $pageInstructions = $actualInstructionsStack;
                                    $actualInstructionsStack = [];
                                    continue 3;
                                case syntax_plugin_combo_pageexplorernamespace::TAG:
                                    $namespaceInstructions = $actualInstructionsStack;
                                    $actualInstructionsStack = [];
                                    continue 3;
                                case syntax_plugin_combo_pageexplorerhome::TAG:
                                    $homeInstructions = $actualInstructionsStack;
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
                 * Remove all callstack from the opening tag
                 */
                $callStack->deleteAllCallsAfter($openingTag);


                /**
                 * Start
                 */
                // just an alias
                $rowTag = syntax_plugin_combo_contentlistitem::MARKI_TAG;

                /**
                 * Get the data
                 */
                // Namespace
                $tagAttributes = TagAttributes::createFromCallStackArray($openingTag->getAttributes(), self::CANONICAL);
                if ($tagAttributes->hasComponentAttribute(self::ATTR_NAMESPACE)) {
                    $nameSpacePath = $tagAttributes->getValueAndRemove(self::ATTR_NAMESPACE);
                } else {
                    $page = Page::createPageFromEnvironment();
                    $nameSpacePath = $page->getNamespacePath();
                }


                /**
                 * Creating the callstack
                 */
                $type = $tagAttributes->getType();
                switch ($type) {
                    default:
                    case self::LIST_TYPE:


                        /**
                         * Create the enter content list tag
                         */
                        $contentListTag = syntax_plugin_combo_contentlist::MARKI_TAG;
                        $tagAttributes->addClassName(self::CANONICAL . "-combo");
                        $marki = $tagAttributes->toMarkiEnterTag($contentListTag);


                        /**
                         * Get the index page name
                         */
                        $pageOrNamespaces = FsWikiUtility::getChildren($nameSpacePath);


                        /**
                         * Home
                         */
                        $currentHomePagePath = FsWikiUtility::getHomePagePath($nameSpacePath);
                        if ($currentHomePagePath != null && sizeof($homeInstructions) > 0) {
                            $tpl = TemplateUtility::render($homeInstructions, $currentHomePagePath);
                            $homeTagAttributes = TagAttributes::createFromCallStackArray($homeAttributes);
                            $homeTagAttributes->addComponentAttributeValue(Background::BACKGROUND_COLOR, "light");
                            $homeTagAttributes->addStyleDeclaration("border-bottom", "1px solid #e5e5e5");

                            $marki .= $homeTagAttributes->toHtmlEnterTag($rowTag) . $tpl . '</' . $rowTag . '>';
                        }
                        $pageNum = 0;

                        foreach ($pageOrNamespaces as $pageOrNamespace) {

                            $pageOrNamespacePath = DokuPath::IdToAbsolutePath($pageOrNamespace['id']);


                            if ($pageOrNamespace['type'] == "d") {

                                // Namespace
                                if (!empty($namespaceInstructions)) {
                                    $subHomePagePath = FsWikiUtility::getHomePagePath($pageOrNamespacePath);
                                    if ($subHomePagePath != null) {
                                        $tpl = TemplateUtility::render($namespaceInstructions, $subHomePagePath);
                                        $marki .= "<$rowTag>$tpl</$rowTag>";
                                    }
                                }

                            } else {

                                if (!empty($pageInstructions)) {
                                    $pageNum++;
                                    if ($pageOrNamespacePath != $currentHomePagePath) {
                                        $tpl = TemplateUtility::render($pageInstructions, $pageOrNamespacePath);
                                        $marki .= "<$rowTag>$tpl</$rowTag>";
                                    }
                                }
                            }

                        }
                        $marki .= "</$contentListTag>";
                        /**
                         * If the namespace has no children
                         */
                        if (!empty($marki)) {
                            $instructions = PluginUtility::getInstructionsWithoutRoot($marki);
                            $callStack->appendInstructions($instructions);
                        }
                        break;
                    case self::TYPE_TREE:

                        /**
                         * Printing the tree
                         *
                         * (Move to the end is not really needed, but yeah)
                         */
                        $callStack->moveToEnd();
                        self::treeProcessSubNamespace($callStack, $nameSpacePath, $namespaceInstructions, $pageInstructions);

                        break;

                }


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
    function render($format, Doku_Renderer $renderer, $data)
    {
        if ($format == 'xhtml') {

            /** @var Doku_Renderer_xhtml $renderer */
            $state = $data[PluginUtility::STATE];
            switch ($state) {
                case DOKU_LEXER_ENTER :

                    $tagAttributes = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES]);
                    $type = $tagAttributes->getType();
                    switch ($type) {
                        case self::TYPE_TREE:
                            $tagAttributes->addClassName("list-unstyled");
                            $tagAttributes->addClassName("ps-0");
                            $renderer->doc .= $tagAttributes->toHtmlEnterTag("ul") . DOKU_LF;
                            break;
                        case self::LIST_TYPE:
                            /**
                             * The {@link syntax_plugin_combo_contentlist} syntax
                             * output the HTML
                             */
                            break;
                    }

                    break;
                case DOKU_LEXER_UNMATCHED :
                    $renderer->doc .= PluginUtility::renderUnmatched($data);
                    break;

                case DOKU_LEXER_EXIT :


                    $tagAttributes = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES]);
                    $type = $tagAttributes->getType();
                    switch ($type) {
                        case self::TYPE_TREE:
                            $renderer->doc .= "</ul>" . DOKU_LF;
                            break;
                        case self::LIST_TYPE:
                            /**
                             * The {@link syntax_plugin_combo_contentlist} syntax
                             * output the HTML
                             */
                            break;
                    }


                    break;
            }
            return true;
        }

        // unsupported $mode
        return false;
    }

    /**
     * Process the
     * @param CallStack $callStack - the callstack
     * @param string $nameSpacePath
     * @param array $namespaceTemplateInstructions
     * @param array $pageTemplateInstructions
     */
    public function treeProcessSubNamespace(&$callStack, $nameSpacePath, $namespaceTemplateInstructions = [], $pageTemplateInstructions = [])
    {


        $pageExplorerTreeTag = syntax_plugin_combo_pageexplorertreesubnamespace::TAG;
        $pageExplorerTreeButtonTag = syntax_plugin_combo_pageexplorernamespace::TAG;
        $pageExplorerTreeListTag = syntax_plugin_combo_pageexplorertreesubnamespacelist::TAG;

        $pageOrNamespaces = FsWikiUtility::getChildren($nameSpacePath);
        foreach ($pageOrNamespaces as $pageOrNamespace) {

            $actualPageOrNamespacePath = DokuPath::IdToAbsolutePath($pageOrNamespace['id']);

            /**
             * Namespace
             */
            if ($pageOrNamespace['type'] == "d") {

                $subHomePagePath = FsWikiUtility::getHomePagePath($actualPageOrNamespacePath);
                if ($subHomePagePath != null) {
                    if (sizeof($namespaceTemplateInstructions) > 0) {
                        // Translate TODO
                        $actualNamespaceInstructions = TemplateUtility::processInstructions($namespaceTemplateInstructions, $subHomePagePath);
                    } else {
                        $actualNamespaceInstructions = [Call::createNativeCall("cdata", [$subHomePagePath])->toCallArray()];
                    }
                } else {
                    $actualNamespaceInstructions = [Call::createNativeCall("cdata", [$actualPageOrNamespacePath])->toCallArray()];
                }

                $this->namespaceCounter++;
                $targetIdAtt = syntax_plugin_combo_pageexplorernamespace::TARGET_ID_ATT;
                $id = PluginUtility::toHtmlId("page-explorer-{$actualPageOrNamespacePath}-{$this->namespaceCounter}-combo");

                /**
                 * Entering: Creating in instructions form
                 * the same as in markup form
                 *
                 * <$pageExplorerTreeTag>
                 *    <$pageExplorerTreeButtonTag $targetIdAtt="$id">
                 *      $buttonInstructions
                 *    </$pageExplorerTreeButtonTag>
                 *    <$pageExplorerTreeListTag id="$id">
                 */
                $callStack->appendCallAtTheEnd(
                    Call::createComboCall($pageExplorerTreeTag, DOKU_LEXER_ENTER)
                );
                $callStack->appendCallAtTheEnd(
                    Call::createComboCall($pageExplorerTreeButtonTag, DOKU_LEXER_ENTER, [$targetIdAtt => $id])
                );
                $callStack->appendInstructions($actualNamespaceInstructions);
                $callStack->appendCallAtTheEnd(
                    Call::createComboCall($pageExplorerTreeButtonTag, DOKU_LEXER_EXIT)
                );
                $callStack->appendCallAtTheEnd(
                    Call::createComboCall($pageExplorerTreeListTag, DOKU_LEXER_ENTER, [TagAttributes::ID_KEY => "$id"])
                );


                /**
                 * Recursion
                 */
                self::treeProcessSubNamespace($callStack, $actualPageOrNamespacePath, $namespaceTemplateInstructions, $pageTemplateInstructions);

                /**
                 * Closing: Creating in instructions form
                 * the same as in markup form
                 *
                 *   </$pageExplorerTreeListTag>
                 * </$pageExplorerTreeTag>
                 */
                $callStack->appendCallAtTheEnd(
                    Call::createComboCall($pageExplorerTreeListTag, DOKU_LEXER_EXIT)
                );
                $callStack->appendCallAtTheEnd(
                    Call::createComboCall($pageExplorerTreeTag, DOKU_LEXER_EXIT)
                );


            } else {
                /**
                 * Page
                 */
                self::treeProcessLeaf($callStack, $actualPageOrNamespacePath, $pageTemplateInstructions);
            }

        }


    }

    /**
     * @param CallStack $callStack
     * @param $pageOrNamespacePath
     * @param array $pageTemplateInstructions
     */
    private static function treeProcessLeaf(&$callStack, $pageOrNamespacePath, $pageTemplateInstructions = [])
    {
        $leafTag = syntax_plugin_combo_pageexplorerpage::TAG;
        if (sizeof($pageTemplateInstructions) > 0) {
            $actualPageInstructions = TemplateUtility::processInstructions($pageTemplateInstructions, $pageOrNamespacePath);
        } else {
            $actualPageInstructions = [Call::createNativeCall("cdata", [$pageOrNamespacePath])->toCallArray()];
        }

        /**
         * In callstack instructions
         * <$leafTag>
         *   $instructions
         * </$leafTag>
         */
        $callStack->appendCallAtTheEnd(
            Call::createComboCall($leafTag, DOKU_LEXER_ENTER)
        );
        $callStack->appendInstructions($actualPageInstructions);
        $callStack->appendCallAtTheEnd(
            Call::createComboCall($leafTag, DOKU_LEXER_EXIT)
        );


    }
}

