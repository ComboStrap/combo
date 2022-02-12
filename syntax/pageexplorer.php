<?php


use ComboStrap\CacheManager;
use ComboStrap\CacheDependencies;
use ComboStrap\Call;
use ComboStrap\CallStack;
use ComboStrap\DokuPath;
use ComboStrap\ExceptionCombo;
use ComboStrap\FsWikiUtility;
use ComboStrap\Html;
use ComboStrap\LogUtility;
use ComboStrap\Page;
use ComboStrap\CacheRuntimeDependencies2;
use ComboStrap\PluginUtility;
use ComboStrap\TagAttributes;
use ComboStrap\TemplateUtility;

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
    const HOME_INSTRUCTIONS = "home-instructions";
    const HOME_ATTRIBUTES = "home-attributes";
    const PARENT_INSTRUCTIONS = "parent-instructions";
    const PARENT_ATTRIBUTES = "parent-attributes";

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
     * @param $namespacePath
     * @return string the last part with a uppercase letter and where underscore became a space
     */
    private static function toNamespaceName($namespacePath): string
    {
        $sepPosition = strrpos($namespacePath, DokuPath::PATH_SEPARATOR);
        if ($sepPosition !== false) {
            $namespaceName = ucfirst(trim(str_replace("_", " ", substr($namespacePath, $sepPosition + 1))));
        } else {
            $namespaceName = $namespacePath;
        }
        return $namespaceName;
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
                                    $actualInstructionsStack[] = $actualCall;
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

                $type = $openingTag->getType();
                $componentClassPrefix = self::getClassPrefix($type);

                /**
                 * Default template
                 * if null, no node page present
                 */
                if ($pageAttributes == null) {
                    // attributes are mandatory as array
                    $pageAttributes = [];
                    // default template instructions
                    if ($templatePageInstructions === null) {
                        $templatePageInstructions = [];
                        $templatePageInstructions[] = Call::createComboCall(
                            syntax_plugin_combo_link::TAG,
                            DOKU_LEXER_ENTER,
                            [
                                syntax_plugin_combo_link::ATTRIBUTE_HREF => "\$path",
                                syntax_plugin_combo_link::ATTRIBUTE_HREF_TYPE => syntax_plugin_combo_link::HREF_MARKUP_TYPE_VALUE
                            ],
                            syntax_plugin_combo_pageexplorerpage::TAG,
                            "[[\$path"
                        )->addClassName($componentClassPrefix . "-page-combo");
                        $templatePageInstructions[] = Call::createComboCall(
                            syntax_plugin_combo_pipeline::TAG,
                            DOKU_LEXER_SPECIAL,
                            [PluginUtility::PAYLOAD => ""],
                            "",
                            "<pipeline>\"\$name\" | replace(\"_\",\" \") | capitalize()</pipeline>"
                        );
                        $templatePageInstructions[] = Call::createComboCall(
                            syntax_plugin_combo_link::TAG,
                            DOKU_LEXER_EXIT,
                            [
                                syntax_plugin_combo_link::ATTRIBUTE_HREF => "\$path",
                                syntax_plugin_combo_link::ATTRIBUTE_HREF_TYPE => syntax_plugin_combo_link::HREF_MARKUP_TYPE_VALUE
                            ],
                            syntax_plugin_combo_pageexplorerpage::TAG,
                            "]]"
                        );
                    }
                }

                /**
                 * Home instruction
                 * If unset, set it with the pages
                 */
                if ($homeAttributes == null) {
                    $homeAttributes = [];
                    if ($templateHomeInstructions === null) {
                        $templateHomeInstructions = $templatePageInstructions;
                    }
                }

                if ($parentAttributes === null) {
                    $parentAttributes = [];
                    // default template instructions
                    if ($parentInstructions === null) {
                        $parentInstructions = [];
                        $parentInstructions[] = Call::createComboCall(
                            syntax_plugin_combo_link::TAG,
                            DOKU_LEXER_ENTER,
                            [
                                syntax_plugin_combo_link::ATTRIBUTE_HREF => "\$path",
                                syntax_plugin_combo_link::ATTRIBUTE_HREF_TYPE => syntax_plugin_combo_link::HREF_MARKUP_TYPE_VALUE
                            ],
                            syntax_plugin_combo_pageexplorerparent::TAG,
                            "[[\$path"
                        )->addClassName($componentClassPrefix . "-parent-combo");
                        /**
                         * To not hurt the icon
                         * server and to get
                         * stable test
                         */
                        if (!PluginUtility::isTest()) {
                            $parentIconName = "arrow-left-box";
                            $parentInstructions[] = Call::createComboCall(
                                syntax_plugin_combo_icon::TAG,
                                DOKU_LEXER_SPECIAL,
                                ["name" => $parentIconName],
                                syntax_plugin_combo_pageexplorerparent::TAG,
                                "<icon name=\"$parentIconName\"/>"
                            );
                        }
                        $parentInstructions[] = Call::createComboCall(
                            syntax_plugin_combo_link::TAG,
                            DOKU_LEXER_UNMATCHED,
                            [],
                            syntax_plugin_combo_link::TAG,
                            " ... ",
                            " ... "
                        );
                        $parentInstructions[] = Call::createComboCall(
                            syntax_plugin_combo_pipeline::TAG,
                            DOKU_LEXER_SPECIAL,
                            [PluginUtility::PAYLOAD => ""],
                            "",
                            "<pipeline>\"\$name\" | replace(\"_\",\" \") | capitalize()</pipeline>"
                        );
                        $parentInstructions[] = Call::createComboCall(
                            syntax_plugin_combo_link::TAG,
                            DOKU_LEXER_EXIT,
                            [
                                syntax_plugin_combo_link::ATTRIBUTE_HREF => "\$path",
                                syntax_plugin_combo_link::ATTRIBUTE_HREF_TYPE => syntax_plugin_combo_link::HREF_MARKUP_TYPE_VALUE
                            ],
                            syntax_plugin_combo_pageexplorerparent::TAG,
                            "]]"
                        );
                    }
                }

                if ($namespaceAttributes === null) {
                    $namespaceAttributes = [];
                    // default template instructions
                    if ($namespaceInstructions === null && $type === self::LIST_TYPE) {
                        $namespaceInstructions = [];
                        $namespaceInstructions[] = Call::createComboCall(
                            syntax_plugin_combo_link::TAG,
                            DOKU_LEXER_ENTER,
                            [
                                syntax_plugin_combo_link::ATTRIBUTE_HREF => "\$path",
                                syntax_plugin_combo_link::ATTRIBUTE_HREF_TYPE => syntax_plugin_combo_link::HREF_MARKUP_TYPE_VALUE
                            ],
                            syntax_plugin_combo_pageexplorerparent::TAG,
                            "[[\$path"
                        )->addClassName($componentClassPrefix . "-namespace-combo");
                        /**
                         * To not hurt
                         * the icon server in test
                         * and to get stable test
                         */
                        if (!PluginUtility::isTest()) {
                            $namespaceInstructions[] = Call::createComboCall(
                                syntax_plugin_combo_icon::TAG,
                                DOKU_LEXER_SPECIAL,
                                ["name" => "folder"],
                                syntax_plugin_combo_pageexplorerparent::TAG,
                                "<icon name=\"folder\"/>"
                            );
                        }
                        $namespaceInstructions[] = Call::createComboCall(
                            syntax_plugin_combo_link::TAG,
                            DOKU_LEXER_UNMATCHED,
                            [],
                            syntax_plugin_combo_link::TAG,
                            " ",
                            " "
                        );
                        $namespaceInstructions[] = Call::createComboCall(
                            syntax_plugin_combo_pipeline::TAG,
                            DOKU_LEXER_SPECIAL,
                            [PluginUtility::PAYLOAD => ""],
                            "",
                            "<pipeline>\"\$name\" | replace(\"_\",\" \") | capitalize()</pipeline>"
                        );
                        $namespaceInstructions[] = Call::createComboCall(
                            syntax_plugin_combo_link::TAG,
                            DOKU_LEXER_EXIT,
                            [
                                syntax_plugin_combo_link::ATTRIBUTE_HREF => "\$path",
                                syntax_plugin_combo_link::ATTRIBUTE_HREF_TYPE => syntax_plugin_combo_link::HREF_MARKUP_TYPE_VALUE
                            ],
                            syntax_plugin_combo_pageexplorerparent::TAG,
                            "]]"
                        );
                    }
                }

                if ($namespaceAttributes == null) {
                    if ($namespaceInstructions === null) {
                        $namespaceInstructions = [];
                        $namespaceInstructions[] = Call::createComboCall(
                            syntax_plugin_combo_pipeline::TAG,
                            DOKU_LEXER_SPECIAL,
                            [PluginUtility::PAYLOAD => ""],
                            "",
                            "<pipeline>\"\$name\" | replace(\"_\",\" \") | capitalize()</pipeline>"
                        );
                    }
                }

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $openingTag->getAttributes(),
                    self::NAMESPACE_INSTRUCTIONS => $namespaceInstructions,
                    self::NAMESPACE_ATTRIBUTES => $namespaceAttributes,
                    self::PAGE_INSTRUCTIONS => $templatePageInstructions,
                    self::PAGE_ATTRIBUTES => $pageAttributes,
                    self::HOME_INSTRUCTIONS => $templateHomeInstructions,
                    self::HOME_ATTRIBUTES => $homeAttributes,
                    self::PARENT_INSTRUCTIONS => $parentInstructions,
                    self::PARENT_ATTRIBUTES => $parentAttributes

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
        if ($format == 'xhtml') {

            /** @var Doku_Renderer_xhtml $renderer */
            $state = $data[PluginUtility::STATE];
            switch ($state) {
                case DOKU_LEXER_ENTER :
                    break;
                case DOKU_LEXER_UNMATCHED :
                    $renderer->doc .= PluginUtility::renderUnmatched($data);
                    break;
                case DOKU_LEXER_EXIT :

                    $pageExplorerTagAttributes = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES], self::CANONICAL);

                    /**
                     * NameSpacePath determination
                     */
                    $type = $pageExplorerTagAttributes->getType();
                    $namespacePath = $pageExplorerTagAttributes->getValueAndRemove(self::ATTR_NAMESPACE);
                    if ($namespacePath === null) {
                        switch ($type) {
                            case self::LIST_TYPE:
                                $requestedPage = Page::createPageFromRequestedPage();
                                $parent = $requestedPage->getPath()->getParent();
                                if ($parent !== null) {
                                    $namespacePath = $parent->toString();
                                } else {
                                    $namespacePath = "";
                                }
                                CacheManager::getOrCreate()->addDependency(CacheDependencies::REQUESTED_NAMESPACE_DEPENDENCY);
                                break;
                            case self::TYPE_TREE:
                                $renderedPage = Page::createPageFromGlobalDokuwikiId();
                                $parent = $renderedPage->getPath()->getParent();
                                if ($parent !== null) {
                                    $namespacePath = $parent->toString();
                                } else {
                                    $namespacePath = "";
                                }
                                break;
                            default:
                                // Should never happens but yeah
                                $renderer->doc .= LogUtility::wrapInRedForHtml("The type of the page explorer ($type) is unknown");
                                return 2;
                        }
                    }


                    /**
                     * Class Prefix
                     */
                    $componentClassPrefix = self::getClassPrefix($type);


                    /**
                     * Creating the callstack
                     */
                    switch ($type) {
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
                            PluginUtility::getSnippetManager()->attachCssSnippetForSlot($componentClassPrefix);

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
                            $currentHomePage = Page::getHomePageFromNamespace($namespacePath);
                            $homeInstructions = $data[self::HOME_INSTRUCTIONS];
                            if ($currentHomePage !== null && $homeInstructions !== null) {

                                $homeAttributes = TagAttributes::createFromCallStackArray($data[self::HOME_ATTRIBUTES]);
                                /**
                                 * Enter home tag
                                 */
                                $renderer->doc .= $homeAttributes
                                    ->addClassName($classItem)
                                    ->toHtmlEnterTag("li");
                                /**
                                 * Content
                                 */
                                $instructions = TemplateUtility::generateInstructionsFromDataPage($homeInstructions, $currentHomePage);
                                try {
                                    $renderer->doc .= PluginUtility::renderInstructionsToXhtml($instructions);
                                } catch (ExceptionCombo $e) {
                                    $renderer->doc .= LogUtility::wrapInRedForHtml("Error while rendering the home. Error: {$e->getMessage()}");
                                }
                                /**
                                 * End home tag
                                 */
                                $renderer->doc .= "</li>";

                            }

                            /**
                             * Parent ?
                             */
                            $parentPagePath = FsWikiUtility::getParentPagePath($namespacePath);
                            $parentInstructions = $data[self::PARENT_INSTRUCTIONS];
                            if ($parentPagePath != null && $parentInstructions !== null) {

                                $parentAttributes = TagAttributes::createFromCallStackArray($data[self::PARENT_ATTRIBUTES]);
                                /**
                                 * Enter parent tag
                                 */
                                $renderer->doc .= $parentAttributes
                                    ->addClassName($classItem)
                                    ->toHtmlEnterTag("li");
                                /**
                                 * Content
                                 */
                                $parentInstructionsInstance = TemplateUtility::generateInstructionsFromDataPage($parentInstructions, $parentPagePath);
                                try {
                                    $renderer->doc .= PluginUtility::renderInstructionsToXhtml($parentInstructionsInstance);
                                } catch (ExceptionCombo $e) {
                                    $renderer->doc .= LogUtility::wrapInRedForHtml("Error while rendering the parent. Error: {$e->getMessage()}");
                                }
                                /**
                                 * End parent tag
                                 */
                                $renderer->doc .= "</li>";
                            }

                            /**
                             * Children (Namespaces/Pages)
                             */
                            $namespaceEnterTag = TagAttributes::createFromCallStackArray($data[self::NAMESPACE_ATTRIBUTES])
                                ->addClassName($classItem)
                                ->toHtmlEnterTag("li");
                            $pageEnterTag = TagAttributes::createFromCallStackArray($data[self::PAGE_ATTRIBUTES])
                                ->addClassName($classItem)
                                ->toHtmlEnterTag("li");
                            $pageInstructions = $data[self::PAGE_INSTRUCTIONS];
                            $namespaceInstructions = $data[self::NAMESPACE_INSTRUCTIONS];
                            $pageOrNamespaces = FsWikiUtility::getChildren($namespacePath);
                            $pageNum = 0;
                            foreach ($pageOrNamespaces as $pageOrNamespace) {

                                $pageOrNamespacePath = DokuPath::IdToAbsolutePath($pageOrNamespace['id']);
                                if ($pageOrNamespace['type'] == "d") {

                                    // Namespace
                                    if (!empty($namespaceInstructions)) {
                                        $subNamespacePagePath = FsWikiUtility::getHomePagePath($pageOrNamespacePath);
                                        if ($subNamespacePagePath != null) {
                                            /**
                                             * SubNamespace Enter tag
                                             */
                                            $renderer->doc .= $namespaceEnterTag;

                                            /**
                                             * SubNamespace Content
                                             */
                                            $namespaceInstructionsInstance = TemplateUtility::generateInstructionsFromDataPage($namespaceInstructions, $subNamespacePagePath);
                                            try {
                                                $renderer->doc .= PluginUtility::renderInstructionsToXhtml($namespaceInstructionsInstance);
                                            } catch (ExceptionCombo $e) {
                                                $renderer->doc .= LogUtility::wrapInRedForHtml("Error while rendering the sub-namespace. Error: {$e->getMessage()}");
                                            }
                                            /**
                                             * SubNamespace Exit tag
                                             */
                                            $renderer->doc .= "</li>";
                                        }
                                    }

                                } else {

                                    if (!empty($pageInstructions)) {
                                        $pageNum++;
                                        if ($pageOrNamespacePath !== $currentHomePage) {
                                            /**
                                             * Page Enter tag
                                             */
                                            $renderer->doc .= $pageEnterTag;
                                            /**
                                             * Page Content
                                             */
                                            $pageInstructionsInstance = TemplateUtility::generateInstructionsFromDataPage($pageInstructions, $pageOrNamespacePath);
                                            try {
                                                $renderer->doc .= PluginUtility::renderInstructionsToXhtml($pageInstructionsInstance);
                                            } catch (ExceptionCombo $e) {
                                                $renderer->doc .= LogUtility::wrapInRedForHtml("Error while rendering the page. Error: {$e->getMessage()}");
                                            }
                                            /**
                                             * Page Exit tag
                                             */
                                            $renderer->doc .= "</li>";
                                        }
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
                            $namespaceId = DokuPath::toDokuwikiId($namespacePath);
                            if (!empty($namespaceId)) { // not root
                                $pageExplorerTagAttributes->addHtmlAttributeValue("data-wiki-id", $namespaceId);
                            } else {
                                $pageExplorerTagAttributes->addEmptyHtmlAttributeValue("data-wiki-id");
                            }


                            $snippetId = self::CANONICAL . "-" . $type;
                            /**
                             * Open the tree until the current page
                             * and make it active
                             */
                            PluginUtility::getSnippetManager()->attachJavascriptSnippetForSlot($snippetId);
                            /**
                             * Styling
                             */
                            PluginUtility::getSnippetManager()->attachCssSnippetForSlot($snippetId);
                            $renderer->doc .= $pageExplorerTagAttributes->toHtmlEnterTag("nav") . DOKU_LF;
                            $renderer->doc .= "<ul>" . DOKU_LF;

                            self::treeProcessSubNamespace($renderer->doc, $namespacePath, $data);

                            $renderer->doc .= "</ul>" . DOKU_LF;
                            $renderer->doc .= "</nav>" . DOKU_LF;
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
     * @param string $nameSpacePath
     * @param array $data
     */
    public
    function treeProcessSubNamespace(string &$html, string $nameSpacePath, array $data)
    {

        /**
         * Home Page first
         */
        $homePage = null;
        $childDirectoryIds = [];
        $nonHomePages = [];

        /**
         * Scanning the directory to
         * categorize the children as home, page or namespace
         */
        $childPagesOrNamespaces = FsWikiUtility::getChildren($nameSpacePath);
        foreach ($childPagesOrNamespaces as $pageOrNamespace) {

            $actualNamespaceId = $pageOrNamespace['id'];
            $actualPageOrNamespacePath = DokuPath::IdToAbsolutePath($actualNamespaceId);

            /**
             * Namespace
             */
            if ($pageOrNamespace['type'] == "d") {

                $childDirectoryIds[] = $actualNamespaceId;

            } else {
                /**
                 * Page
                 */
                $page = Page::createPageFromQualifiedPath($actualPageOrNamespacePath);
                if ($page->isHomePage()) {
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
            self::treeProcessLeaf($html, $homePage->getAbsolutePath(), $data[self::PAGE_INSTRUCTIONS]);
        }

        /**
         * The subdirectories
         */
        $namespaceInstructions = $data[self::NAMESPACE_INSTRUCTIONS];
        foreach ($childDirectoryIds as $childDirectoryId) {

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
            $id = Html::toHtmlId("page-explorer-{$childDirectoryId}-{$this->namespaceCounter}-combo");
            $html .= TagAttributes::createEmpty()
                ->addHtmlAttributeValue("data-bs-target", "#$id")
                ->addHtmlAttributeValue("data-" . TagAttributes::WIKI_ID, $childDirectoryId)
                ->addHtmlAttributeValue("data-bs-toggle", "collapse")
                ->addHtmlAttributeValue("aria-expanded", "false")
                ->addClassName("btn")
                ->addClassName("align-items-center")
                ->addClassName("rounded")
                ->toHtmlEnterTag("button");

            // Button label
            $childDirectoryPath = DokuPath::IdToAbsolutePath($childDirectoryId);
            $subHomePage = Page::getHomePageFromNamespace($childDirectoryPath);
            if ($subHomePage !== null) {
                if ($namespaceInstructions !== null) {
                    $namespaceInstructionsInstance = TemplateUtility::generateInstructionsFromDataPage($namespaceInstructions, $subHomePage);
                    try {
                        $html .= PluginUtility::renderInstructionsToXhtml($namespaceInstructionsInstance);
                    } catch (ExceptionCombo $e) {
                        $html .= LogUtility::wrapInRedForHtml("Error while rendering the child directory. Error: {$e->getMessage()}");
                    }
                } else {
                    $html .= $subHomePage->getName();
                }
            } else {
                $namespaceName = self::toNamespaceName($childDirectoryPath);
                $html .= $namespaceName;
            }
            // End button
            $html .= "</button>";

            /**
             * Sub and Recursion
             */
            $html .= TagAttributes::createEmpty()
                ->addClassName("collapse")
                ->addHtmlAttributeValue(TagAttributes::ID_KEY, "$id")
                ->toHtmlEnterTag("div");
            $html .= "<ul>";
            self::treeProcessSubNamespace($html, $childDirectoryPath, $data);
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
            self::treeProcessLeaf($html, $page->getAbsolutePath(), $data[self::PAGE_INSTRUCTIONS]);
        }


    }

    /**
     * @param string $html
     * @param $pageOrNamespacePath
     * @param array|null $pageTemplateInstructions
     */
    private
    static function treeProcessLeaf(string &$html, $pageOrNamespacePath, array $pageTemplateInstructions = null)
    {
        /**
         * In callstack instructions
         * <li>
         *   $instructions
         * </li>
         */
        $html .= "<li>";
        if ($pageTemplateInstructions !== null) {
            $pageInstructionsInstance = TemplateUtility::generateInstructionsFromDataPage($pageTemplateInstructions, $pageOrNamespacePath);
            try {
                $html .= PluginUtility::renderInstructionsToXhtml($pageInstructionsInstance);
            } catch (ExceptionCombo $e) {
                $html .= LogUtility::wrapInRedForHtml("Error while rendering the leaf. Error: {$e->getMessage()}");
            }
        } else {
            $html .= $pageOrNamespacePath;
        }
        $html .= "</li>";


    }
}

