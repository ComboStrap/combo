<?php

namespace ComboStrap;


use Doku_Handler;


class PageExplorerTag
{
    public const PAGE_TYPE = "page";
    public const FOLDER_ICON = "images:page-explorer-folder";
    /**
     * Page canonical and tag pattern
     */
    public const CANONICAL = "page-explorer";
    public const HOME_TYPE = "home";
    public const PAGE_ATTRIBUTES = "page-attributes";
    public const INDEX_INSTRUCTIONS = "index-instructions";
    public const NAMESPACE_ATTRIBUTES = "namespace-attributes";
    public const TYPE_TREE = "tree";
    public const PAGE_INSTRUCTIONS = "page-instructions";
    public const PARENT_ATTRIBUTES = "parent-attributes";
    public const INDEX_ATTRIBUTES = "index-attributes";
    public const NAMESPACE_INSTRUCTIONS = "namespace-instructions";
    /**
     * Tag in Dokuwiki cannot have a `-`
     * This is the last part of the class
     */
    public const LOGICAL_TAG = self::PAGE_EXPLORER_MARKUP;
    /**
     * Namespace attribute
     * that contains the namespace information
     * (ie
     *   * a namespace path
     *   * or current, for the namespace of the current requested page
     */
    public const ATTR_NAMESPACE = "ns";
    public const LEVEL_UP_ICON = "images:page-explorer-icons8-level-up";

    public const NTOC_MARKUP = "ntoc";
    public const PAGE_EXPLORER_MARKUP = "page-explorer";

    public const PARENT_INSTRUCTIONS = "parent-instructions";
    /**
     * Attributes on the home node
     */
    public const LIST_TYPE = "list";
    public const INDEX_TAG = "index";
    /**
     * The pattern
     */
    public const INDEX_HOME_TAG = "home";
    /**
     * Tag in Dokuwiki cannot have a `-`
     * This is the last part of the class
     */
    public const LOGICAL_INDEX_TAG = "pageexplorerhome";
    public const INDEXES_TAG = [PageExplorerTag::INDEX_HOME_TAG, PageExplorerTag::INDEX_TAG];
    /**
     * Implementation of the namespace
     *   * ie the button (in a collapsible menu) http://localhost:63342/bootstrap-5.0.1-examples/sidebars/index.html
     *   * or the directory in a list menu
     *
     * This syntax is not a classic syntax plugin
     * The instructions are captured at the {@link DOKU_LEXER_END}
     * state of {@link syntax_plugin_combo_pageexplorer::handle()}
     * to create/generate the namespaces
     *
     */
    public const NAMESPACE_LOGICAL_TAG = "pageexplorernamespace";
    public const NAMESPACE_SHORT_TAG = "ns";
    public const NAMESPACE_ITEM_TAG = "ns-item";
    public const NAMESPACE_LONG_TAG = "namespace";
    /**
     * Tag in Dokuwiki cannot have a `-`
     * This is the last part of the class
     */
    public const PAGE_LOGICAL_TAG = self::PAGE_TAG;
    /**
     * The pattern
     */
    public const PAGE_TAG = "page";
    public const PAGE_ITEM_TAG = "page-item";
    const PARENT_TAG = "parent";


    /**
     * @param string $html
     * @param MarkupPath $page
     * @param array $data - the data array from the handler
     * @param string $type
     */
    public static function treeProcessLeaf(string &$html, MarkupPath $page, array $data, string $type)
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

    /**
     * @param WikiPath $namespacePath
     * @return string the last part with a uppercase letter and where underscore became a space
     */
    public static function toNamespaceName(WikiPath $namespacePath): string
    {
        try {
            return ucfirst(trim(str_replace("_", " ", $namespacePath->getLastNameWithoutExtension())));
        } catch (ExceptionNotFound $e) {
            // root
            return "";
        }
    }

    /**
     * A class prefix added in elements
     * @param string $type
     * @return string
     */
    public static function getClassPrefix(string $type): string
    {
        return self::CANONICAL . "-$type";
    }

    public static function handleExit(Doku_Handler $handler)
    {
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
                        case self::PAGE_LOGICAL_TAG:
                            $pageAttributes = $actualCall->getAttributes();
                            continue 3;
                        case self::NAMESPACE_LOGICAL_TAG:
                            $namespaceAttributes = $actualCall->getAttributes();
                            continue 3;
                        case self::LOGICAL_INDEX_TAG:
                            $homeAttributes = $actualCall->getAttributes();
                            continue 3;
                        case self::PARENT_TAG:
                            $parentAttributes = $actualCall->getAttributes();
                            continue 3;
                        default:
                            $actualInstructionsStack[] = $actualCall->toCallArray();
                            continue 3;
                    }
                case DOKU_LEXER_EXIT:
                    switch ($tagName) {
                        case self::PAGE_LOGICAL_TAG:
                            $templatePageInstructions = $actualInstructionsStack;
                            $actualInstructionsStack = [];
                            continue 3;
                        case self::NAMESPACE_LOGICAL_TAG:
                            $namespaceInstructions = $actualInstructionsStack;
                            $actualInstructionsStack = [];
                            continue 3;
                        case self::LOGICAL_INDEX_TAG:
                            $templateHomeInstructions = $actualInstructionsStack;
                            $actualInstructionsStack = [];
                            continue 3;
                        case PageExplorerTag::PARENT_TAG:
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
    }

    public static function renderEnterTag(TagAttributes $pageExplorerTagAttributes, array $data)
    {

        $returnedXhtml = "";
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
                $returnedXhtml .= $pageExplorerTagAttributes
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
                        $returnedXhtml .= $indexTagAttributes
                            ->addClassName($classItem)
                            ->setLogicalTag(PageExplorerTag::CANONICAL . "-{$pageExplorerType}-{$indexPageType}")
                            ->toHtmlEnterTag("li");
                        /**
                         * Content
                         */
                        if ($indexInstructions !== null) {
                            try {
                                $returnedXhtml .= MarkupRenderUtility::renderInstructionsToXhtml($indexInstructions, $currentIndexPage->getMetadataForRendering());
                            } catch (ExceptionCompile $e) {
                                $returnedXhtml .= LogUtility::wrapInRedForHtml("Error while rendering the home. Error: {$e->getMessage()}");
                            }
                        } else {
                            try {
                                $returnedXhtml .= LinkMarkup::createFromPageIdOrPath($currentIndexPage->getWikiId())
                                    ->toAttributes()
                                    ->toHtmlEnterTag("a");
                                $returnedXhtml .= "{$currentIndexPage->getNameOrDefault()}</a>";
                            } catch (ExceptionCompile $e) {
                                $returnedXhtml .= LogUtility::wrapInRedForHtml("Error while rendering the default home. Error: {$e->getMessage()}");
                            }
                        }
                        /**
                         * End home tag
                         */
                        $returnedXhtml .= "</li>";
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
                            $returnedXhtml .= $parentTagAttributes
                                ->addClassName($classItem)
                                ->setLogicalTag(PageExplorerTag::CANONICAL . "-{$pageExplorerType}-{$pageType}")
                                ->toHtmlEnterTag("li");
                            /**
                             * Content
                             */
                            if ($parentInstructions !== null) {
                                try {
                                    $returnedXhtml .= MarkupRenderUtility::renderInstructionsToXhtml($parentInstructions, $parentPage->getMetadataForRendering());
                                } catch (ExceptionCompile $e) {
                                    $returnedXhtml .= LogUtility::wrapInRedForHtml("Error while rendering the parent instructions. Error: {$e->getMessage()}");
                                }
                            } else {
                                try {
                                    $parentWikiId = $parentPage->getPathObject()->getWikiId();
                                    $returnedXhtml .= LinkMarkup::createFromPageIdOrPath($parentWikiId)
                                        ->toAttributes()
                                        ->toHtmlEnterTag("a");
                                    $returnedXhtml .= Icon::createFromComboResource(PageExplorerTag::LEVEL_UP_ICON)
                                        ->toHtml();
                                    $returnedXhtml .= " {$parentPage->getNameOrDefault()}</a>";
                                } catch (ExceptionCompile $e) {
                                    $returnedXhtml .= LogUtility::wrapInRedForHtml("Error while rendering the default parent. Error: {$e->getMessage()}");
                                }
                            }
                            /**
                             * End parent tag
                             */
                            $returnedXhtml .= "</li>";
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
                            $returnedXhtml .= $namespaceEnterTag;

                            /**
                             * SubNamespace Content
                             */
                            if ($namespaceInstructions !== null) {
                                try {
                                    $returnedXhtml .= MarkupRenderUtility::renderInstructionsToXhtml($namespaceInstructions, $subNamespacePage->getMetadataForRendering());
                                } catch (ExceptionCompile $e) {
                                    $returnedXhtml .= LogUtility::wrapInRedForHtml("Error while rendering the sub-namespace. Error: {$e->getMessage()}");
                                }
                            } else {
                                try {
                                    $returnedXhtml .= LinkMarkup::createFromPageIdOrPath($subNamespacePage->getWikiId())
                                        ->toAttributes()
                                        ->toHtmlEnterTag("a");
                                    $returnedXhtml .= Icon::createFromComboResource(PageExplorerTag::FOLDER_ICON)
                                        ->toHtml();
                                    $returnedXhtml .= " {$subNamespacePage->getNameOrDefault()}</a>";
                                } catch (ExceptionCompile $e) {
                                    $returnedXhtml .= LogUtility::wrapInRedForHtml("Error while rendering the default namespace. Error: {$e->getMessage()}");
                                }

                            }
                            /**
                             * SubNamespace Exit tag
                             */
                            $returnedXhtml .= "</li>";
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
                            $returnedXhtml .= $pageEnterTag;
                            /**
                             * Page Content
                             */
                            if ($pageInstructions !== null) {

                                try {
                                    $returnedXhtml .= MarkupRenderUtility::renderInstructionsToXhtmlFromPage($pageInstructions, $childPage);
                                } catch (ExceptionCompile $e) {
                                    $returnedXhtml .= LogUtility::wrapInRedForHtml("Error while rendering the page. Error: {$e->getMessage()}");
                                }
                            } else {
                                try {
                                    $returnedXhtml .= LinkMarkup::createFromPageIdOrPath($childPagePath->getWikiId())
                                        ->toAttributes()
                                        ->toHtmlEnterTag("a");
                                    $returnedXhtml .= "{$childPage->getNameOrDefault()}</a>";
                                } catch (ExceptionCompile $e) {
                                    $returnedXhtml .= LogUtility::wrapInRedForHtml("Error while rendering the default page. Error: {$e->getMessage()}");
                                }
                            }
                            /**
                             * Page Exit tag
                             */
                            $returnedXhtml .= "</li>";
                        }
                    }
                }


                /**
                 * End container tag
                 */
                $returnedXhtml .= "</ul>";
                return $returnedXhtml;

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
                $returnedXhtml .= $pageExplorerTagAttributes->toHtmlEnterTag("nav") . DOKU_LF;
                $returnedXhtml .= "<ul>" . DOKU_LF;

                try {
                    $tree = PathTreeNode::buildTreeViaFileSystemChildren($namespacePath);
                    self::treeProcessTree($returnedXhtml, $tree, $data);
                } catch (ExceptionBadSyntax $e) {
                    $returnedXhtml .= LogUtility::wrapInRedForHtml("Error while rendering the tree sub-namespace. Error: {$e->getMessage()}");
                }

                $returnedXhtml .= "</ul>";
                $returnedXhtml .= "</nav>";
                return $returnedXhtml;

        }
    }

    /**
     * Process the
     * @param string $html - the callstack
     * @param PathTreeNode $pathTreeNode
     * @param array $data - the data array from the handler
     * @throws ExceptionBadSyntax
     */
    public static
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
            PageExplorerTag::treeProcessLeaf($html, $homePage, $data, PageExplorerTag::HOME_TYPE);
        }

        /**
         * The subdirectories
         */
        $namespaceInstructions = $data[PageExplorerTag::NAMESPACE_INSTRUCTIONS];
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


            /**
             * Keep the id unique on the page in order to be able to collapse
             * the good HTML node
             */
            $namespaceId = ExecutionContext::getActualOrCreateFromEnv()->getIdManager()->generateNewHtmlIdForComponent("page-explorer-{$containerPath->getWikiId()}");
            $id = StyleUtility::addComboStrapSuffix($namespaceId);
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

            $subHomePage = MarkupPath::getIndexPageFromNamespace($containerPath->toQualifiedId());
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
                $namespaceName = PageExplorerTag::toNamespaceName($containerPath);
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
            PageExplorerTag::treeProcessLeaf($html, $page, $data, PageExplorerTag::PAGE_TYPE);
        }


    }
}

