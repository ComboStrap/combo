<?php


use ComboStrap\Background;
use ComboStrap\CallStack;
use ComboStrap\DokuPath;
use ComboStrap\FsWikiUtility;
use ComboStrap\LogUtility;
use ComboStrap\Page;
use ComboStrap\PluginUtility;
use ComboStrap\RenderUtility;
use ComboStrap\TagAttributes;
use ComboStrap\TemplateUtility;

require_once(__DIR__ . '/../class/TemplateUtility.php');


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
     * Ntoc attribute
     */
    const ATTR_NAMESPACE = "ns";
    const NAMESPACE_ITEM = "namespace";
    const NAMESPACE_OLD = "ns-item";
    const NAMESPACES = [self::ATTR_NAMESPACE, self::NAMESPACE_ITEM, self::NAMESPACE_OLD];

    const PAGE = "page";
    const PAGE_OLD = "page-item";
    const PAGES = [self::PAGE, self::PAGE_OLD];

    const HOME = "home";
    const HOME_OLD = "index";
    const HOMES = [self::HOME, self::HOME_OLD];

    /**
     * Keys of the array passed between {@link handle} and {@link render}
     */
    const PAGE_TEMPLATE_KEY = 'pageTemplate';
    const NS_TEMPLATE_KEY = 'nsTemplate';
    const HOME_TEMPLATE_KEY = 'homeTemplate';

    /**
     * Attributes on the home node
     */
    const HOME_ATTRIBUTES_KEY = 'homeAttributes';


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
            $this->Lexer->addEntryPattern($pattern, $mode, PluginUtility::getModeForComponent($this->getPluginComponent()));
        }

        foreach (self::PAGES as $page) {
            $this->Lexer->addPattern(PluginUtility::getLeafContainerTagPattern($page), PluginUtility::getModeForComponent($this->getPluginComponent()));
        }

        foreach (self::HOMES as $home) {
            $this->Lexer->addPattern(PluginUtility::getLeafContainerTagPattern($home), PluginUtility::getModeForComponent($this->getPluginComponent()));
        }

        foreach (self::NAMESPACES as $namespace) {
            $this->Lexer->addPattern(PluginUtility::getLeafContainerTagPattern($namespace), PluginUtility::getModeForComponent($this->getPluginComponent()));
        }

    }


    public function postConnect()
    {
        foreach (self::COMBO_TAG_PATTERNS as $tag) {
            $this->Lexer->addExitPattern('</' . $tag . '>', PluginUtility::getModeForComponent($this->getPluginComponent()));
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
                $attributes = PluginUtility::getTagAttributes($match);
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
                 * The attributes to send to the render
                 */
                $attributes = array();

                /**
                 * Get the opening tag
                 */
                $openingTag = $callStack->moveToPreviousCorrespondingOpeningCall();

                $found = false;
                while ($callStack->next()) {
                    $actualCall = $callStack->getActualCall();
                    if ($actualCall->getTagName() == self::TAG && $actualCall->getState() == DOKU_LEXER_MATCHED) {
                        $tagName = PluginUtility::getTag($actualCall->getCapturedContent());
                        switch ($tagName) {
                            case self::PAGE:
                            case self::PAGE_OLD:
                                /**
                                 * Pattern for a page
                                 */
                                $pageTemplate = $actualCall->getPayload();
                                $attributes[self::PAGE_TEMPLATE_KEY] = $pageTemplate;
                                $found = true;
                                break;
                            case self::NAMESPACE_ITEM:
                            case self::NAMESPACE_OLD:
                                /**
                                 * Pattern for a namespace
                                 */
                                $nsTemplate = $actualCall->getPayload();
                                $attributes[self::NS_TEMPLATE_KEY] = $nsTemplate;
                                $found = true;
                                break;
                            case self::HOME:
                            case self::HOME_OLD:
                                /**
                                 * Pattern for a header
                                 */
                                $headerTemplate = $actualCall->getPayload();
                                $headerAttributes = $actualCall->getAttributes();
                                $attributes[self::HOME_TEMPLATE_KEY] = $headerTemplate;
                                $attributes[self::HOME_ATTRIBUTES_KEY] = $headerAttributes;
                                $found = true;
                                break;
                            default:
                                LogUtility::msg("The tag ($tagName) is unknown", LogUtility::LVL_MSG_ERROR, self::TAG);
                                break;
                        }
                        $callStack->deleteActualCallAndPrevious();
                    }
                }

                if (!$found) {
                    LogUtility::msg("There should be at minimum a `" . self::HOME . "`, `" . self::NAMESPACE_ITEM . "` or a `" . self::HOME . "` defined", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                }

                /**
                 * Get the attributes
                 */
                $openingTagAttributes = $openingTag->getAttributes();
                $attributes = PluginUtility::mergeAttributes($openingTagAttributes, $attributes);


                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $attributes
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
                    // The attributes are used in the exit
                    break;
                case DOKU_LEXER_UNMATCHED :
                    $renderer->doc .= PluginUtility::renderUnmatched($data);
                    break;

                case DOKU_LEXER_EXIT :

                    /**
                     * data
                     */
                    $attributes = $data[PluginUtility::ATTRIBUTES];
                    if ($attributes == null) {
                        LogUtility::msg("Attributes are null. You may need to purge the cache. To do that, you can modify slightly your page or a configuration", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                        return false;
                    }

                    /**
                     * Start
                     */
                    $tagAttributes = TagAttributes::createFromCallStackArray($attributes, self::CANONICAL);
                    // just an alias
                    $rowTag = syntax_plugin_combo_contentlistitem::MARKI_TAG;

                    /**
                     * Get the data
                     */
                    // Namespace
                    if ($tagAttributes->hasComponentAttribute(self::ATTR_NAMESPACE)) {
                        $nameSpacePath = $tagAttributes->getValueAndRemove(self::ATTR_NAMESPACE);
                    } else {
                        $page = Page::createPageFromEnvironment();
                        $nameSpacePath = $page->getNamespace();
                    }

                    // Ns template
                    $namespaceTemplate = $tagAttributes->getValueAndRemoveIfPresent(self::NS_TEMPLATE_KEY);


                    // Home template
                    $homeTemplate = $tagAttributes->getValueAndRemoveIfPresent(self::HOME_TEMPLATE_KEY);
                    $homeAttributes = $tagAttributes->getValueAndRemoveIfPresent(self::HOME_ATTRIBUTES_KEY, []);


                    // Page template
                    $pageTemplate = $tagAttributes->getValueAndRemoveIfPresent(self::PAGE_TEMPLATE_KEY);


                    /**
                     * Create the content list
                     */
                    $contentListTag = syntax_plugin_combo_contentlist::MARKI_TAG;
                    $tagAttributes->addClassName(self::CANONICAL. "-combo");
                    $list = $tagAttributes->toMarkiEnterTag($contentListTag);


                    /**
                     * Get the index page name
                     */
                    $pageOrNamespaces = FsWikiUtility::getChildren($nameSpacePath);


                    /**
                     * Home
                     */
                    $currentHomePagePath = FsWikiUtility::getHomePagePath($nameSpacePath);
                    if ($currentHomePagePath != null && $homeTemplate != null) {
                        $tpl = TemplateUtility::render($homeTemplate, $currentHomePagePath);
                        $homeTagAttributes = TagAttributes::createFromCallStackArray($homeAttributes);
                        $homeTagAttributes->addComponentAttributeValue(Background::BACKGROUND_COLOR, "light");
                        $homeTagAttributes->addStyleDeclaration("border-bottom", "1px solid #e5e5e5");

                        $list .= $homeTagAttributes->toHtmlEnterTag($rowTag) . $tpl . '</' . $rowTag . '>';
                    }
                    $pageNum = 0;

                    foreach ($pageOrNamespaces as $pageOrNamespace) {

                        $pageOrNamespacePath = DokuPath::IdToAbsolutePath($pageOrNamespace['id']);


                        if ($pageOrNamespace['type'] == "d") {

                            // Namespace
                            if (!empty($namespaceTemplate)) {
                                $subHomePagePath = FsWikiUtility::getHomePagePath($pageOrNamespacePath);
                                if ($subHomePagePath != null) {
                                    $tpl = TemplateUtility::render($namespaceTemplate, $subHomePagePath);
                                    $list .= "<$rowTag>$tpl</$rowTag>";
                                }
                            }

                        } else {

                            if (!empty($pageTemplate)) {
                                $pageNum++;
                                if ($pageOrNamespacePath != $currentHomePagePath) {
                                    $tpl = TemplateUtility::render($pageTemplate, $pageOrNamespacePath);
                                    $list .= "<$rowTag>$tpl</$rowTag>";
                                }
                            }
                        }

                    }
                    $list .= "</$contentListTag>";
                    $renderer->doc .= PluginUtility::render($list) . DOKU_LF;
                    break;
            }
            return true;
        }

        // unsupported $mode
        return false;
    }


}

