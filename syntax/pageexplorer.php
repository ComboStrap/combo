<?php


use ComboStrap\Call;
use ComboStrap\CallStack;
use ComboStrap\ExceptionBadSyntax;
use ComboStrap\ExceptionCompile;
use ComboStrap\ExceptionNotFound;
use ComboStrap\ExecutionContext;
use ComboStrap\FileSystems;
use ComboStrap\Html;
use ComboStrap\Icon;
use ComboStrap\IdManager;
use ComboStrap\LinkMarkup;
use ComboStrap\LogUtility;
use ComboStrap\MarkupCacheDependencies;
use ComboStrap\MarkupPath;
use ComboStrap\MarkupRenderUtility;
use ComboStrap\PageExplorerTag;
use ComboStrap\PathTreeNode;
use ComboStrap\PluginUtility;
use ComboStrap\StyleUtility;
use ComboStrap\TagAttributes;
use ComboStrap\TreeNode;
use ComboStrap\WikiPath;

require_once(__DIR__ . '/../vendor/autoload.php');


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

//        foreach (self::COMBO_TAG_PATTERNS as $tag) {
//            $pattern = PluginUtility::getContainerTagPattern($tag);
//            $this->Lexer->addEntryPattern($pattern, $mode, PluginUtility::getModeFromTag($this->getPluginComponent()));
//        }

    }


    public function postConnect()
    {
//        foreach (self::COMBO_TAG_PATTERNS as $tag) {
//            $this->Lexer->addExitPattern('</' . $tag . '>', PluginUtility::getModeFromTag($this->getPluginComponent()));
//        }

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

                $default = [TagAttributes::TYPE_KEY => PageExplorerTag::LIST_TYPE];
                $knownTypes = [PageExplorerTag::TYPE_TREE, PageExplorerTag::LIST_TYPE];
                $tagAttributes = TagAttributes::createFromTagMatch($match, $default, $knownTypes);

                $callStackArray = $tagAttributes->toCallStackArray();
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $callStackArray
                );

            case DOKU_LEXER_UNMATCHED :

                // We should not ever come here but a user does not not known that
                return PluginUtility::handleAndReturnUnmatchedData(PageExplorerTag::LOGICAL_TAG, $match, $handler);

            case DOKU_LEXER_MATCHED :

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => PluginUtility::getTagAttributes($match),
                    PluginUtility::PAYLOAD => PluginUtility::getTagContent($match),
                    PluginUtility::TAG => PluginUtility::getMarkupTag($match)
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
                $namespaceInstructions = null;
                $namespaceAttributes = null;
                /**
                 * @var Call[] $templatePageInstructions
                 * @var array $pageAttributes
                 */
                $templatePageInstructions = null;
                $pageAttributes = null;
                /**
                 * @var Call[] $templateHomeInstructions
                 * @var array $homeAttributes
                 */
                $templateHomeInstructions = null;
                $homeAttributes = null;
                /**
                 * The instructions for the parent item in a page explorer list
                 * if any
                 * @var Call[] $parentInstructions
                 * @var array $parentAttributes
                 */
                $parentInstructions = null;
                $parentAttributes = null;
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
                                case syntax_plugin_combo_pageexplorerparent::TAG:
                                    $parentAttributes = $actualCall->getAttributes();
                                    continue 3;
                                default:
                                    $actualInstructionsStack[] = $actualCall->toCallArray();
                                    continue 3;
                            }
                        case DOKU_LEXER_EXIT:
                            switch ($tagName) {
                                case syntax_plugin_combo_pageexplorerpage::TAG:
                                    $templatePageInstructions = $actualInstructionsStack;
                                    $actualInstructionsStack = [];
                                    continue 3;
                                case syntax_plugin_combo_pageexplorernamespace::TAG:
                                    $namespaceInstructions = $actualInstructionsStack;
                                    $actualInstructionsStack = [];
                                    continue 3;
                                case syntax_plugin_combo_pageexplorerhome::TAG:
                                    $templateHomeInstructions = $actualInstructionsStack;
                                    $actualInstructionsStack = [];
                                    continue 3;
                                case syntax_plugin_combo_pageexplorerparent::TAG:
                                    $parentInstructions = $actualInstructionsStack;
                                    $actualInstructionsStack = [];
                                    continue 3;
                                default:
                                    $actualInstructionsStack[] = $actualCall->toCallArray();
                                    continue 3;

                            }
                        default:
                            $actualInstructionsStack[] = $actualCall->toCallArray();
                            break;

                    }
                }
                /**
                 * Remove all callstack from the opening tag
                 */
                $callStack->deleteAllCallsAfter($openingTag);

                /**
                 * The container/block should have at minimum an enter tag
                 * to be able to set the {@link action_plugin_combo_sideslotpostprocessing} auto collapsible
                 * parameter
                 */
                $openingTag->setPluginData(PageExplorerTag::NAMESPACE_INSTRUCTIONS, $namespaceInstructions);
                $openingTag->setPluginData(PageExplorerTag::NAMESPACE_ATTRIBUTES, $namespaceAttributes);
                $openingTag->setPluginData(PageExplorerTag::PAGE_INSTRUCTIONS, $templatePageInstructions);
                $openingTag->setPluginData(PageExplorerTag::PAGE_ATTRIBUTES, $pageAttributes);
                $openingTag->setPluginData(PageExplorerTag::INDEX_INSTRUCTIONS, $templateHomeInstructions);
                $openingTag->setPluginData(PageExplorerTag::INDEX_ATTRIBUTES, $homeAttributes);
                $openingTag->setPluginData(PageExplorerTag::PARENT_INSTRUCTIONS, $parentInstructions);
                $openingTag->setPluginData(PageExplorerTag::PARENT_ATTRIBUTES, $parentAttributes);

                return [PluginUtility::STATE => $state];


        }
        return array();

    }

    /**
     * Render the output
     * @param string $format
     * @param Doku_Renderer $renderer
     * @param array $data - what the function handle() return'ed
     * @return boolean - rendered correctly? (however, returned value is not used at the moment)
     * @throws \ComboStrap\ExceptionBadArgument
     * @see DokuWiki_Syntax_Plugin::render()
     */
    function render($format, Doku_Renderer $renderer, $data): bool
    {
        if ($format == 'xhtml') {

            /** @var Doku_Renderer_xhtml $renderer */
            $state = $data[PluginUtility::STATE];
            switch ($state) {
                case DOKU_LEXER_EXIT :
                    break;
                case DOKU_LEXER_UNMATCHED :
                    $renderer->doc .= PluginUtility::renderUnmatched($data);
                    break;
                case DOKU_LEXER_ENTER :

                    $pageExplorerTagAttributes = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES], PageExplorerTag::CANONICAL);

                    /**
                     * Id (id is mandatory for toggle)
                     */
                    $id = $pageExplorerTagAttributes->getValue(TagAttributes::ID_KEY);
                    if ($id === null) {
                        $id = IdManager::getOrCreate()->generateNewHtmlIdForComponent(PageExplorerTag::CANONICAL);
                        $pageExplorerTagAttributes->setComponentAttributeValue(TagAttributes::ID_KEY, $id);
                    }

                    $executionContext = ExecutionContext::getActualOrCreateFromEnv();


                    try {

                        /**
                         * {@link MarkupCacheDependencies::PAGE_PRIMARY_META_DEPENDENCY}
                         * The cache output is composed of primary metadata
                         * (If it changes, the content change)
                         *
                         * {@link MarkupCacheDependencies::PAGE_SYSTEM_DEPENDENCY}
                         * The content depend on the file system tree
                         * (if a file is added or deleted, the content will change)
                         */
                        $executionContext
                            ->getExecutingMarkupHandler()
                            ->getCacheDependencies()
                            ->addDependency(MarkupCacheDependencies::PAGE_PRIMARY_META_DEPENDENCY)
                            ->addDependency(MarkupCacheDependencies::PAGE_SYSTEM_DEPENDENCY);
                    } catch (ExceptionNotFound $e) {
                        // no fetcher markup running
                        // ie markup
                    }

                    /**
                     * Context
                     */
                    $requestedContextPath = $executionContext->getContextPath();

                    /**
                     * NameSpacePath determination
                     */
                    $pageExplorerType = $pageExplorerTagAttributes->getType();
                    $namespaceAttribute = $pageExplorerTagAttributes->getValueAndRemove(PageExplorerTag::ATTR_NAMESPACE);
                    if ($namespaceAttribute !== null) {
                        WikiPath::addNamespaceEndSeparatorIfNotPresent($namespaceAttribute);
                        $namespacePath = WikiPath::createMarkupPathFromPath($namespaceAttribute);
                    } else {
                        try {
                            $namespacePath = $requestedContextPath->getParent();
                        } catch (ExceptionNotFound $e) {
                            $namespacePath = WikiPath::createRootNamespacePathOnMarkupDrive();
                        }
                        try {
                            $executionContext
                                ->getExecutingMarkupHandler()
                                ->getCacheDependencies()
                                ->addDependency(MarkupCacheDependencies::REQUESTED_NAMESPACE_DEPENDENCY);
                        } catch (ExceptionNotFound $e) {
                            // ok
                        }
                    }


                    /**
                     * Class Prefix
                     */
                    $componentClassPrefix = PageExplorerTag::getClassPrefix($pageExplorerType);


                    /**
                     * Rendering
                     */
                    switch ($pageExplorerType) {
                        default:
                        case PageExplorerTag::LIST_TYPE:

                            /**
                             * Class
                             */
                            $classContainer = "list-group";
                            $classItem = "list-group-item";

                            /**
                             * Css
                             */
                            $executionContext
                                ->getSnippetSystem()
                                ->attachCssInternalStyleSheet($componentClassPrefix);

                            /**
                             * Create the enter content list tag
                             */
                            $renderer->doc .= $pageExplorerTagAttributes
                                ->addClassName($classContainer)
                                ->removeAttributeIfPresent(TagAttributes::WIKI_ID)
                                ->setLogicalTag(PageExplorerTag::CANONICAL)
                                ->toHtmlEnterTag("ul");


                            /**
                             * Home
                             */
                            $indexInstructions = $data[PageExplorerTag::INDEX_INSTRUCTIONS];
                            $indexAttributes = $data[PageExplorerTag::INDEX_ATTRIBUTES];
                            $currentIndexPage = MarkupPath::createPageFromPathObject($namespacePath);
                            if (!($indexInstructions === null && $indexAttributes !== null)) {

                                if (FileSystems::exists($currentIndexPage)) {


                                    $indexTagAttributes = TagAttributes::createFromCallStackArray($indexAttributes);


                                    /**
                                     * Enter home tag
                                     */
                                    $indexPageType = "index";
                                    $renderer->doc .= $indexTagAttributes
                                        ->addClassName($classItem)
                                        ->setLogicalTag(PageExplorerTag::CANONICAL . "-{$pageExplorerType}-{$indexPageType}")
                                        ->toHtmlEnterTag("li");
                                    /**
                                     * Content
                                     */
                                    if ($indexInstructions !== null) {
                                        try {
                                            $renderer->doc .= MarkupRenderUtility::renderInstructionsToXhtml($indexInstructions, $currentIndexPage->getMetadataForRendering());
                                        } catch (ExceptionCompile $e) {
                                            $renderer->doc .= LogUtility::wrapInRedForHtml("Error while rendering the home. Error: {$e->getMessage()}");
                                        }
                                    } else {
                                        try {
                                            $renderer->doc .= LinkMarkup::createFromPageIdOrPath($currentIndexPage->getWikiId())
                                                ->toAttributes()
                                                ->toHtmlEnterTag("a");
                                            $renderer->doc .= "{$currentIndexPage->getNameOrDefault()}</a>";
                                        } catch (ExceptionCompile $e) {
                                            $renderer->doc .= LogUtility::wrapInRedForHtml("Error while rendering the default home. Error: {$e->getMessage()}");
                                        }
                                    }
                                    /**
                                     * End home tag
                                     */
                                    $renderer->doc .= "</li>";
                                }

                            }

                            /**
                             * Parent ?
                             */
                            $parentInstructions = $data[PageExplorerTag::PARENT_INSTRUCTIONS];
                            $parentAttributes = $data[PageExplorerTag::PARENT_ATTRIBUTES];
                            if (!($parentInstructions === null && $indexAttributes !== null)) {
                                try {
                                    $parentPage = $currentIndexPage->getParent();
                                    if ($parentPage->exists()) {

                                        $parentTagAttributes = TagAttributes::createFromCallStackArray($parentAttributes);
                                        /**
                                         * Enter parent tag
                                         */
                                        $pageType = "parent";
                                        $renderer->doc .= $parentTagAttributes
                                            ->addClassName($classItem)
                                            ->setLogicalTag(PageExplorerTag::CANONICAL . "-{$pageExplorerType}-{$pageType}")
                                            ->toHtmlEnterTag("li");
                                        /**
                                         * Content
                                         */
                                        if ($parentInstructions !== null) {
                                            try {
                                                $renderer->doc .= MarkupRenderUtility::renderInstructionsToXhtml($parentInstructions, $parentPage->getMetadataForRendering());
                                            } catch (ExceptionCompile $e) {
                                                $renderer->doc .= LogUtility::wrapInRedForHtml("Error while rendering the parent instructions. Error: {$e->getMessage()}");
                                            }
                                        } else {
                                            try {
                                                $parentWikiId = $parentPage->getPathObject()->getWikiId();
                                                $renderer->doc .= LinkMarkup::createFromPageIdOrPath($parentWikiId)
                                                    ->toAttributes()
                                                    ->toHtmlEnterTag("a");
                                                $renderer->doc .= Icon::createFromComboResource(PageExplorerTag::LEVEL_UP_ICON)
                                                    ->toHtml();
                                                $renderer->doc .= " {$parentPage->getNameOrDefault()}</a>";
                                            } catch (ExceptionCompile $e) {
                                                $renderer->doc .= LogUtility::wrapInRedForHtml("Error while rendering the default parent. Error: {$e->getMessage()}");
                                            }
                                        }
                                        /**
                                         * End parent tag
                                         */
                                        $renderer->doc .= "</li>";
                                    }
                                } catch (ExceptionNotFound $e) {
                                    // no parent page
                                }

                            }

                            /**
                             * Children (Namespaces/Pages)
                             */

                            $namespaceEnterTag = TagAttributes::createFromCallStackArray($data[PageExplorerTag::NAMESPACE_ATTRIBUTES])
                                ->addClassName($classItem)
                                ->setLogicalTag(PageExplorerTag::CANONICAL . "-{$pageExplorerType}-namespace")
                                ->toHtmlEnterTag("li");
                            $pageEnterTag = TagAttributes::createFromCallStackArray($data[PageExplorerTag::PAGE_ATTRIBUTES])
                                ->addClassName($classItem)
                                ->setLogicalTag(PageExplorerTag::CANONICAL . "-{$pageExplorerType}-page")
                                ->toHtmlEnterTag("li");


                            $pageInstructions = $data[PageExplorerTag::PAGE_INSTRUCTIONS];
                            $pageAttributes = $data[PageExplorerTag::PAGE_ATTRIBUTES];
                            $namespaceInstructions = $data[PageExplorerTag::NAMESPACE_INSTRUCTIONS];
                            $namespaceAttributes = $data[PageExplorerTag::NAMESPACE_ATTRIBUTES];

                            $pageNum = 0;
                            foreach (FileSystems::getChildrenContainer($namespacePath) as $subNamespacePath) {

                                // Namespace
                                if (!($namespaceInstructions === null && $namespaceAttributes !== null)) {
                                    try {
                                        $subNamespacePage = MarkupPath::getIndexPageFromNamespace($subNamespacePath->toQualifiedId());
                                    } catch (ExceptionBadSyntax $e) {
                                        LogUtility::msg("Bad syntax for the namespace $namespacePath. Error: {$e->getMessage()}", LogUtility::LVL_MSG_ERROR, PageExplorerTag::CANONICAL);
                                        return false;
                                    }
                                    if ($subNamespacePage->isHidden()) {
                                        continue;
                                    }
                                    if ($subNamespacePage->exists()) {
                                        /**
                                         * SubNamespace Enter tag
                                         */
                                        $renderer->doc .= $namespaceEnterTag;

                                        /**
                                         * SubNamespace Content
                                         */
                                        if ($namespaceInstructions !== null) {
                                            try {
                                                $renderer->doc .= MarkupRenderUtility::renderInstructionsToXhtml($namespaceInstructions, $subNamespacePage->getMetadataForRendering());
                                            } catch (ExceptionCompile $e) {
                                                $renderer->doc .= LogUtility::wrapInRedForHtml("Error while rendering the sub-namespace. Error: {$e->getMessage()}");
                                            }
                                        } else {
                                            try {
                                                $renderer->doc .= LinkMarkup::createFromPageIdOrPath($subNamespacePage->getWikiId())
                                                    ->toAttributes()
                                                    ->toHtmlEnterTag("a");
                                                $renderer->doc .= Icon::createFromComboResource(PageExplorerTag::FOLDER_ICON)
                                                    ->toHtml();
                                                $renderer->doc .= " {$subNamespacePage->getNameOrDefault()}</a>";
                                            } catch (ExceptionCompile $e) {
                                                $renderer->doc .= LogUtility::wrapInRedForHtml("Error while rendering the default namespace. Error: {$e->getMessage()}");
                                            }

                                        }
                                        /**
                                         * SubNamespace Exit tag
                                         */
                                        $renderer->doc .= "</li>";
                                    }

                                }

                            }

                            $childrenLeaf = FileSystems::getChildrenLeaf($namespacePath);
                            foreach ($childrenLeaf as $childPagePath) {
                                $childPage = MarkupPath::createPageFromPathObject($childPagePath);
                                if ($childPage->isHidden()) {
                                    continue;
                                }
                                if (!($pageInstructions === null && $pageAttributes !== null)) {
                                    $pageNum++;
                                    if ($currentIndexPage !== null
                                        && $childPagePath->getWikiId() !== $currentIndexPage->getWikiId()
                                        && FileSystems::exists($childPagePath)
                                    ) {
                                        /**
                                         * Page Enter tag
                                         */
                                        $renderer->doc .= $pageEnterTag;
                                        /**
                                         * Page Content
                                         */
                                        if ($pageInstructions !== null) {

                                            try {
                                                $renderer->doc .= MarkupRenderUtility::renderInstructionsToXhtmlFromPage($pageInstructions, $childPage);
                                            } catch (ExceptionCompile $e) {
                                                $renderer->doc .= LogUtility::wrapInRedForHtml("Error while rendering the page. Error: {$e->getMessage()}");
                                            }
                                        } else {
                                            try {
                                                $renderer->doc .= LinkMarkup::createFromPageIdOrPath($childPagePath->getWikiId())
                                                    ->toAttributes()
                                                    ->toHtmlEnterTag("a");
                                                $renderer->doc .= "{$childPage->getNameOrDefault()}</a>";
                                            } catch (ExceptionCompile $e) {
                                                $renderer->doc .= LogUtility::wrapInRedForHtml("Error while rendering the default page. Error: {$e->getMessage()}");
                                            }
                                        }
                                        /**
                                         * Page Exit tag
                                         */
                                        $renderer->doc .= "</li>";
                                    }
                                }
                            }


                            /**
                             * End container tag
                             */
                            $renderer->doc .= "</ul>";


                            break;
                        case PageExplorerTag::TYPE_TREE:

                            /**
                             * Printing the tree
                             *
                             * data-wiki-id, needed for the
                             * javascript that open the tree
                             * to the actual page
                             */
                            $namespaceId = $namespacePath->getWikiId();
                            if (!blank($namespaceId)) {
                                $pageExplorerTagAttributes->addOutputAttributeValue("data-" . TagAttributes::WIKI_ID, $namespaceId);
                            } else {
                                $pageExplorerTagAttributes->addBooleanOutputAttributeValue("data-" . TagAttributes::WIKI_ID);
                            }


                            $snippetId = PageExplorerTag::CANONICAL . "-" . $pageExplorerType;
                            /**
                             * Open the tree until the current page
                             * and make it active
                             */
                            PluginUtility::getSnippetManager()->attachJavascriptFromComponentId($snippetId);
                            /**
                             * Styling
                             */
                            PluginUtility::getSnippetManager()->attachCssInternalStyleSheet($snippetId);
                            $renderer->doc .= $pageExplorerTagAttributes->toHtmlEnterTag("nav") . DOKU_LF;
                            $renderer->doc .= "<ul>" . DOKU_LF;

                            try {
                                $tree = PathTreeNode::buildTreeViaFileSystemChildren($namespacePath);
                                PageExplorerTag::treeProcessTree($renderer->doc, $tree, $data);
                            } catch (ExceptionBadSyntax $e) {
                                $renderer->doc .= LogUtility::wrapInRedForHtml("Error while rendering the tree sub-namespace. Error: {$e->getMessage()}");
                            }

                            $renderer->doc .= "</ul>";
                            $renderer->doc .= "</nav>";
                            break;

                    }


                    break;
            }
            return true;
        }

        // unsupported $mode
        return false;
    }

}

