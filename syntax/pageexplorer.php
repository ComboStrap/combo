<?php


use ComboStrap\MarkupCacheDependencies;
use ComboStrap\CacheManager;
use ComboStrap\Call;
use ComboStrap\CallStack;
use ComboStrap\WikiPath;
use ComboStrap\ExceptionBadSyntax;
use ComboStrap\ExceptionCompile;
use ComboStrap\ExceptionNotFound;
use ComboStrap\FileSystems;
use ComboStrap\Html;
use ComboStrap\Icon;
use ComboStrap\IconDownloader;
use ComboStrap\IdManager;
use ComboStrap\LogUtility;
use ComboStrap\LinkMarkup;
use ComboStrap\MarkupPath;
use ComboStrap\Path;
use ComboStrap\PathTreeNode;
use ComboStrap\PluginUtility;
use ComboStrap\MarkupRenderUtility;
use ComboStrap\StyleUtility;
use ComboStrap\TagAttributes;
use ComboStrap\TreeNode;

require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');


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
     * Namespace attribute
     * that contains the namespace information
     * (ie
     *   * a namespace path
     *   * or current, for the namespace of the current requested page
     */
    const ATTR_NAMESPACE = "ns";


    /**
     * Attributes on the home node
     */
    const LIST_TYPE = "list";
    const TYPE_TREE = "tree";

    const NAMESPACE_INSTRUCTIONS = "namespace-instructions";
    const NAMESPACE_ATTRIBUTES = "namespace-attributes";
    const PAGE_INSTRUCTIONS = "page-instructions";
    const PAGE_ATTRIBUTES = "page-attributes";
    const INDEX_INSTRUCTIONS = "index-instructions";
    const INDEX_ATTRIBUTES = "index-attributes";
    const PARENT_INSTRUCTIONS = "parent-instructions";
    const PARENT_ATTRIBUTES = "parent-attributes";
    const HOME_TYPE = "home";
    const PAGE_TYPE = "page";
    const LEVEL_UP_ICON = "images:page-explorer-icons8-level-up";
    const FOLDER_ICON = "images:page-explorer-folder";

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
     * A class prefix added in elements
     * @param string $type
     * @return string
     */
    private static function getClassPrefix(string $type): string
    {
        return self::CANONICAL . "-$type";
    }

    /**
     * @param WikiPath $namespacePath
     * @return string the last part with a uppercase letter and where underscore became a space
     */
    private static function toNamespaceName(WikiPath $namespacePath): string
    {
        return ucfirst(trim(str_replace("_", " ", $namespacePath->getLastNameWithoutExtension())));
    }


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
     * @return array
     * @throws Exception
     * @see DokuWiki_Syntax_Plugin::handle()
     *
     */
    function handle($match, $state, $pos, Doku_Handler $handler): array
    {

        switch ($state) {

            case DOKU_LEXER_ENTER :

                $default = [
                    TagAttributes::TYPE_KEY => self::LIST_TYPE
                ];
                $tagAttributes = TagAttributes::createFromTagMatch($match, $default);

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
                $openingTag->setPluginData(self::NAMESPACE_INSTRUCTIONS, $namespaceInstructions);
                $openingTag->setPluginData(self::NAMESPACE_ATTRIBUTES, $namespaceAttributes);
                $openingTag->setPluginData(self::PAGE_INSTRUCTIONS, $templatePageInstructions);
                $openingTag->setPluginData(self::PAGE_ATTRIBUTES, $pageAttributes);
                $openingTag->setPluginData(self::INDEX_INSTRUCTIONS, $templateHomeInstructions);
                $openingTag->setPluginData(self::INDEX_ATTRIBUTES, $homeAttributes);
                $openingTag->setPluginData(self::PARENT_INSTRUCTIONS, $parentInstructions);
                $openingTag->setPluginData(self::PARENT_ATTRIBUTES, $parentAttributes);

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

                    $pageExplorerTagAttributes = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES], self::CANONICAL);

                    /**
                     * Id (id is mandatory for toggle)
                     */
                    $id = $pageExplorerTagAttributes->getValue(TagAttributes::ID_KEY);
                    if ($id === null) {
                        $id = IdManager::getOrCreate()->generateNewHtmlIdForComponent(self::CANONICAL);
                        $pageExplorerTagAttributes->setComponentAttributeValue(TagAttributes::ID_KEY, $id);
                    }

                    /**
                     * The cache output is composed of primary metadata
                     * (If it changes, the content change)
                     */
                    CacheManager::getOrCreateFromRequestedPath()->addDependencyForCurrentSlot(MarkupCacheDependencies::PAGE_PRIMARY_META_DEPENDENCY);
                    /**
                     * The content depend on the file system tree
                     * (if a file is added or deleted, the content will change)
                     */
                    CacheManager::getOrCreateFromRequestedPath()->addDependencyForCurrentSlot(MarkupCacheDependencies::PAGE_SYSTEM_DEPENDENCY);

                    /**
                     * NameSpacePath determination
                     */
                    $pageExplorerType = $pageExplorerTagAttributes->getType();
                    $namespaceAttribute = $pageExplorerTagAttributes->getValueAndRemove(self::ATTR_NAMESPACE);
                    $namespacePath = null;
                    if ($namespaceAttribute !== null) {
                        WikiPath::addNamespaceEndSeparatorIfNotPresent($namespaceAttribute);
                        $namespacePath = WikiPath::createPagePathFromPath($namespaceAttribute);
                    }
                    if ($namespacePath === null) {
                        switch ($pageExplorerType) {
                            case self::LIST_TYPE:
                                $requestedPage = MarkupPath::createFromRequestedPage();
                                $namespacePath = $requestedPage->getPathObject()->getParent();
                                if ($namespacePath === null) {
                                    // root
                                    $namespacePath = $requestedPage->getPathObject();
                                }
                                CacheManager::getOrCreateFromRequestedPath()->addDependencyForCurrentSlot(MarkupCacheDependencies::REQUESTED_NAMESPACE_DEPENDENCY);
                                break;
                            case self::TYPE_TREE:
                                try {
                                    $renderedPage = MarkupPath::createPageFromGlobalWikiId();
                                } catch (ExceptionCompile $e) {
                                    LogUtility::msg("The global ID is unknown, we couldn't get the requested page", self::CANONICAL);
                                    return false;
                                }
                                $namespacePath = $renderedPage->getPathObject()->getParent();
                                if ($namespacePath === null) {
                                    // root
                                    $namespacePath = $renderedPage->getPathObject();
                                }
                                break;
                            default:
                                // Should never happens but yeah
                                $renderer->doc .= LogUtility::wrapInRedForHtml("The type of the page explorer ($pageExplorerType) is unknown");
                                return 2;
                        }
                    }


                    /**
                     * Class Prefix
                     */
                    $componentClassPrefix = self::getClassPrefix($pageExplorerType);


                    /**
                     * Rendering
                     */
                    switch ($pageExplorerType) {
                        default:
                        case self::LIST_TYPE:

                            /**
                             * Class
                             */
                            $classContainer = "list-group";
                            $classItem = "list-group-item";

                            /**
                             * Css
                             */
                            PluginUtility::getSnippetManager()->attachCssInternalStyleSheetForSlot($componentClassPrefix);

                            /**
                             * Create the enter content list tag
                             */
                            $renderer->doc .= $pageExplorerTagAttributes
                                ->addClassName($classContainer)
                                ->removeAttributeIfPresent(TagAttributes::WIKI_ID)
                                ->setLogicalTag(self::CANONICAL)
                                ->toHtmlEnterTag("ul");


                            /**
                             * Home
                             */
                            $indexInstructions = $data[self::INDEX_INSTRUCTIONS];
                            $indexAttributes = $data[self::INDEX_ATTRIBUTES];
                            $currentIndexPage = MarkupPath::createPageFromPathObject($namespacePath);
                            if (!($indexInstructions === null && $indexAttributes !== null)) {

                                if ($currentIndexPage->exists()) {


                                    $indexTagAttributes = TagAttributes::createFromCallStackArray($indexAttributes);


                                    /**
                                     * Enter home tag
                                     */
                                    $indexPageType = "index";
                                    $renderer->doc .= $indexTagAttributes
                                        ->addClassName($classItem)
                                        ->setLogicalTag(self::CANONICAL . "-{$pageExplorerType}-{$indexPageType}")
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
                            $parentInstructions = $data[self::PARENT_INSTRUCTIONS];
                            $parentAttributes = $data[self::PARENT_ATTRIBUTES];
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
                                            ->setLogicalTag(self::CANONICAL . "-{$pageExplorerType}-{$pageType}")
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
                                                $renderer->doc .= Icon::createFromComboResource(self::LEVEL_UP_ICON)
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

                            $namespaceEnterTag = TagAttributes::createFromCallStackArray($data[self::NAMESPACE_ATTRIBUTES])
                                ->addClassName($classItem)
                                ->setLogicalTag(self::CANONICAL . "-{$pageExplorerType}-namespace")
                                ->toHtmlEnterTag("li");
                            $pageEnterTag = TagAttributes::createFromCallStackArray($data[self::PAGE_ATTRIBUTES])
                                ->addClassName($classItem)
                                ->setLogicalTag(self::CANONICAL . "-{$pageExplorerType}-page")
                                ->toHtmlEnterTag("li");


                            $pageInstructions = $data[self::PAGE_INSTRUCTIONS];
                            $pageAttributes = $data[self::PAGE_ATTRIBUTES];
                            $namespaceInstructions = $data[self::NAMESPACE_INSTRUCTIONS];
                            $namespaceAttributes = $data[self::NAMESPACE_ATTRIBUTES];


                            $pageNum = 0;
                            foreach (FileSystems::getChildrenContainer($namespacePath) as $subNamespacePath) {

                                // Namespace
                                if (!($namespaceInstructions === null && $namespaceAttributes !== null)) {
                                    try {
                                        $subNamespacePage = MarkupPath::getIndexPageFromNamespace($subNamespacePath->toPathString());
                                    } catch (ExceptionBadSyntax $e) {
                                        LogUtility::msg("Bad syntax for the namespace $namespacePath. Error: {$e->getMessage()}", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
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
                                                $renderer->doc .= Icon::createFromComboResource(self::FOLDER_ICON)
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

                            foreach (FileSystems::getChildrenLeaf($namespacePath) as $childPagePath) {
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
                        case self::TYPE_TREE:

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
                                $pageExplorerTagAttributes->addEmptyOutputAttributeValue("data-" . TagAttributes::WIKI_ID);
                            }


                            $snippetId = self::CANONICAL . "-" . $pageExplorerType;
                            /**
                             * Open the tree until the current page
                             * and make it active
                             */
                            PluginUtility::getSnippetManager()->attachInternalJavascriptForSlot($snippetId);
                            /**
                             * Styling
                             */
                            PluginUtility::getSnippetManager()->attachCssInternalStyleSheetForSlot($snippetId);
                            $renderer->doc .= $pageExplorerTagAttributes->toHtmlEnterTag("nav") . DOKU_LF;
                            $renderer->doc .= "<ul>" . DOKU_LF;

                            try {
                                $tree = PathTreeNode::buildTreeViaFileSystemChildren($namespacePath);
                                self::treeProcessTree($renderer->doc, $tree, $data);
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

    /**
     * Process the
     * @param string $html - the callstack
     * @param PathTreeNode $pathTreeNode
     * @param array $data - the data array from the handler
     * @throws ExceptionBadSyntax
     */
    public
    function treeProcessTree(string &$html, PathTreeNode $pathTreeNode, array $data)
    {

        /**
         * Home Page first
         * @var MarkupPath $homePage
         */
        $homePage = null;
        /**
         * @var TreeNode[] $containerTreeNodes
         */
        $containerTreeNodes = [];
        /**
         * @var MarkupPath[] $nonHomePages
         */
        $nonHomePages = [];

        /**
         * Scanning the tree node to
         * categorize the children as home, page or namespace (container)
         * @var PathTreeNode[] $children
         */
        $children = $pathTreeNode->getChildren();
        foreach ($children as $child) {

            /**
             * Namespace
             */
            if ($child->hasChildren()) {

                $containerTreeNodes[] = $child;

            } else {
                /**
                 * Page
                 */
                $page = MarkupPath::createPageFromPathObject($child->getPath());
                if ($page->isIndexPage()) {
                    $homePage = $page;
                } else {
                    $nonHomePages[] = $page;
                }

            }

        }

        /**
         * First the home page
         */
        if ($homePage !== null) {
            self::treeProcessLeaf($html, $homePage, $data, self::HOME_TYPE);
        }

        /**
         * The subdirectories
         */
        $namespaceInstructions = $data[self::NAMESPACE_INSTRUCTIONS];
        foreach ($containerTreeNodes as $containerTreeNode) {

            /**
             * @var WikiPath $containerPath
             */
            $containerPath = $containerTreeNode->getPath();

            /**
             * Entering: Creating in instructions form
             * the same as in markup form
             *
             * <li>
             *    <button data-bs-target="#id" data-bs-collapse="collapse">
             *      Label
             *    </button>
             *    <div id="id">
             *      <ul>
             *        <li></li>
             *        <li></li>
             *        ....
             *    </div>
             *    ...
             */
            $html .= "<li>";

            // button
            $this->namespaceCounter++;
            $id = StyleUtility::addComboStrapSuffix(Html::toHtmlId("page-explorer-{$containerPath->getWikiId()}-$this->namespaceCounter"));
            $html .= TagAttributes::createEmpty()
                ->addOutputAttributeValue("data-bs-target", "#$id")
                ->addOutputAttributeValue("data-" . TagAttributes::WIKI_ID, $containerPath->getWikiId())
                ->addOutputAttributeValue("data-bs-toggle", "collapse")
                ->addOutputAttributeValue("aria-expanded", "false")
                ->addClassName("btn")
                ->addClassName("align-items-center")
                ->addClassName("rounded")
                ->toHtmlEnterTag("button");

            // Button label

            $subHomePage = MarkupPath::getIndexPageFromNamespace($containerPath->toPathString());
            if ($subHomePage->exists()) {
                if ($namespaceInstructions !== null) {
                    try {
                        $html .= MarkupRenderUtility::renderInstructionsToXhtml($namespaceInstructions, $subHomePage->getMetadataForRendering());
                    } catch (ExceptionCompile $e) {
                        $html .= LogUtility::wrapInRedForHtml("Error while rendering the child directory. Error: {$e->getMessage()}");
                    }
                } else {
                    $html .= $subHomePage->getNameOrDefault();
                }
            } else {
                $namespaceName = self::toNamespaceName($containerPath);
                $html .= $namespaceName;
            }
            // End button
            $html .= "</button>";

            /**
             * Sub and Recursion
             */
            $html .= TagAttributes::createEmpty()
                ->addClassName("collapse")
                ->addOutputAttributeValue(TagAttributes::ID_KEY, "$id")
                ->toHtmlEnterTag("div");
            $html .= "<ul>";
            self::treeProcessTree($html, $containerTreeNode, $data);
            $html .= "</ul>";
            $html .= "</div>";

            /**
             * Closing: Creating in instructions form
             * the same as in markup form
             *
             *   </$pageExplorerTreeListTag>
             * </$pageExplorerTreeTag>
             */
            $html .= "</li>";

        }

        /**
         * Then the other pages
         */
        foreach ($nonHomePages as $page) {
            self::treeProcessLeaf($html, $page, $data, self::PAGE_TYPE);
        }


    }

    /**
     * @param string $html
     * @param MarkupPath $page
     * @param array $data - the data array from the handler
     * @param string $type
     */
    private
    static function treeProcessLeaf(string &$html, MarkupPath $page, array $data, string $type)
    {
        /**
         * In callstack instructions
         * <li>
         *   $instructions
         * </li>
         */
        $pageAttributes = $data[self::PAGE_ATTRIBUTES];
        $pageInstructions = $data[self::PAGE_INSTRUCTIONS];
        if ($pageInstructions === null && $pageAttributes !== null) {
            return;
        }
        if (!FileSystems::exists($page->getPathObject())) {
            LogUtility::error("The given leaf page ($page) does not exist and was not added to the page-explorer tree", self::CANONICAL);
            return;
        }
        if ($page->isHidden()) {
            return;
        }

        $listItemEnterTag = TagAttributes::createEmpty()
            ->setLogicalTag(self::CANONICAL . "-tree-$type")
            ->toHtmlEnterTag("li");

        $listItemContent = "";
        if ($pageInstructions !== null) {
            try {
                $listItemContent = MarkupRenderUtility::renderInstructionsToXhtml($pageInstructions, $page->getMetadataForRendering());
            } catch (ExceptionCompile $e) {
                LogUtility::error("Error while rendering the leaf. Error: {$e->getMessage()}", self::CANONICAL);
                return;
            }
        } else {
            try {
                $listItemContent = LinkMarkup::createFromPageIdOrPath($page->getWikiId())
                    ->toAttributes()
                    ->toHtmlEnterTag("a");
                $listItemContent .= "{$page->getNameOrDefault()}</a>";
            } catch (ExceptionCompile $e) {
                LogUtility::error("Error while rendering the default tree page. Error: {$e->getMessage()}", self::CANONICAL);
            }
        }
        /** @noinspection PhpUnnecessaryCurlyVarSyntaxInspection */
        $html .= "{$listItemEnterTag}{$listItemContent}</li>";

    }
}

