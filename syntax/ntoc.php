<?php


use ComboStrap\AdsUtility;
use ComboStrap\FsWikiUtility;
use ComboStrap\LogUtility;
use ComboStrap\PluginUtility;
use ComboStrap\RenderUtility;
use ComboStrap\Tag;
use ComboStrap\TemplateUtility;
use ComboStrap\TitleUtility;

require_once(__DIR__ . '/../class/TemplateUtility.php');


/**
 * Class syntax_plugin_combo_ntoc
 * Implementation of a namespace toc
 */
class syntax_plugin_combo_ntoc extends DokuWiki_Syntax_Plugin
{

    const TAG = self::CANONICAL_NTOC;


    /**
     * Ntoc attribute
     */
    const ATTR_NAMESPACE = "ns";
    const NAMESPACE_ITEM = "ns-item";
    const PAGE_ITEM = "page-item";
    const INDEX_ITEM = "index";

    /**
     * Keys of the array passed between {@link handle} and {@link render}
     */
    const PAGE_TEMPLATE_KEY = 'pageTemplate';
    const NS_TEMPLATE_KEY = 'nsTemplate';
    const INDEX_TEMPLATE_KEY = 'indexTemplate';
    const INDEX_ATTRIBUTES_KEY = 'indexAttributes';
    const CANONICAL_NTOC = "ntoc";


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
        return 'normal';
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


    function connectTo($mode)
    {

        $pattern = PluginUtility::getContainerTagPattern(self::TAG);
        $this->Lexer->addEntryPattern($pattern, $mode, PluginUtility::getModeForComponent($this->getPluginComponent()));

        $this->Lexer->addPattern(PluginUtility::getLeafContainerTagPattern(self::PAGE_ITEM), PluginUtility::getModeForComponent($this->getPluginComponent()));
        $this->Lexer->addPattern(PluginUtility::getLeafContainerTagPattern(self::INDEX_ITEM), PluginUtility::getModeForComponent($this->getPluginComponent()));
        $this->Lexer->addPattern(PluginUtility::getLeafContainerTagPattern(self::NAMESPACE_ITEM), PluginUtility::getModeForComponent($this->getPluginComponent()));

    }


    public function postConnect()
    {
        $this->Lexer->addExitPattern('</' . self::TAG . '>', PluginUtility::getModeForComponent($this->getPluginComponent()));

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
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::PAYLOAD => PluginUtility::escape($match));

            case DOKU_LEXER_MATCHED :

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => PluginUtility::getTagAttributes($match),
                    PluginUtility::CONTENT => PluginUtility::getTagContent($match),
                    PluginUtility::TAG => PluginUtility::getTag($match)
                );

            case DOKU_LEXER_EXIT :

                $tag = new Tag(self::TAG, array(), $state, $handler->calls);

                /**
                 * The attributes to send to the render
                 */
                $attributes = array();

                /**
                 * Get the opening tag
                 */
                $openingTag = $tag->getOpeningTag();

                /**
                 * Pattern for a page
                 */
                $pageTag = $openingTag->getDescendant(self::PAGE_ITEM);
                $pageTemplate = null;
                if ($pageTag != null) {
                    $pageTemplate = $pageTag->getData()[PluginUtility::CONTENT];
                }
                $attributes[self::PAGE_TEMPLATE_KEY] = $pageTemplate;

                /**
                 * Pattern for a ns
                 */
                $nsTag = $openingTag->getDescendant(self::NAMESPACE_ITEM);
                $nsTemplate = null;
                if ($nsTag != null) {
                    $nsTemplate = $nsTag->getData()[PluginUtility::CONTENT];
                }
                $attributes[self::NS_TEMPLATE_KEY] = $nsTemplate;

                /**
                 * Pattern for a header
                 */
                $headerTag = $openingTag->getDescendant(self::INDEX_ITEM);
                $headerTemplate = null;
                $headerAttributes = array();
                if ($headerTag != null) {
                    $headerTemplate = $headerTag->getData()[PluginUtility::CONTENT];
                    $headerAttributes = $headerTag->getAttributes();
                }
                $attributes[self::INDEX_TEMPLATE_KEY] = $headerTemplate;
                $attributes[self::INDEX_ATTRIBUTES_KEY] = $headerAttributes;

                if ($pageTemplate == null && $nsTemplate == null && $headerTemplate == null) {
                    LogUtility::msg("There should be at minimum a `" . self::INDEX_ITEM . "`, `" . self::NAMESPACE_ITEM . "` or a `" . self::INDEX_ITEM . "` defined", LogUtility::LVL_MSG_ERROR, self::CANONICAL_NTOC);
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
                case DOKU_LEXER_UNMATCHED :
                    $renderer->doc .= $data[PluginUtility::PAYLOAD];
                    break;

                case DOKU_LEXER_EXIT :

                    $attributes = $data[PluginUtility::ATTRIBUTES];

                    if ($attributes == null) {
                        LogUtility::msg("Ntoc attributes are null. You may need to purge the cache. To do that, you can modify slightly your page or a configuration", LogUtility::LVL_MSG_ERROR, "ntoc");
                        return false;
                    }

                    /**
                     * Get the data
                     */
                    $id = FsWikiUtility::getMainPageId();

                    // Namespace
                    $nameSpacePath = getNS($id);
                    if (array_key_exists(self::ATTR_NAMESPACE, $attributes)) {
                        $nameSpacePath = $attributes[self::ATTR_NAMESPACE];
                        unset($attributes[self::ATTR_NAMESPACE]);
                    }

                    // Ns template
                    $nsTemplate = $attributes[self::NS_TEMPLATE_KEY];
                    unset($attributes[self::NS_TEMPLATE_KEY]);

                    // Header template
                    $headerTemplate = $attributes[self::INDEX_TEMPLATE_KEY];
                    unset($attributes[self::INDEX_TEMPLATE_KEY]);
                    $headerAttributes = $attributes[self::INDEX_ATTRIBUTES_KEY];
                    unset($attributes[self::INDEX_ATTRIBUTES_KEY]);

                    // Page template
                    $pageTemplate = $attributes[self::PAGE_TEMPLATE_KEY];
                    unset($attributes[self::PAGE_TEMPLATE_KEY]);


                    /**
                     * Create the list
                     */
                    $list = "<list";
                    if (sizeof($attributes) > 0) {
                        $list .= ' ' . PluginUtility::array2HTMLAttributes($attributes);
                    }
                    $list .= ">";

                    /**
                     * Get the index page name
                     */
                    $pages = FsWikiUtility::getChildren($nameSpacePath);


                    /**
                     * Header
                     */
                    $pageIndex = FsWikiUtility::getIndex($nameSpacePath);
                    if ($pageIndex != null && $headerTemplate != null) {
                        $pageTitle = TitleUtility::getPageTitle($pageIndex);
                        $tpl = TemplateUtility::render($headerTemplate, $pageIndex, $pageTitle);
                        if (sizeof($headerAttributes) == 0) {
                            $headerAttributes["background-color"] = "light";
                            PluginUtility::addStyleProperty("border-bottom", "1px solid #e5e5e5", $headerAttributes);
                        }
                        $list .= '<li ' . PluginUtility::array2HTMLAttributes($headerAttributes) . '>' . $tpl . '</li>';
                    }
                    $pageNum = 0;

                    foreach ($pages as $page) {

                        // If it's a directory
                        if ($page['type'] == "d" ) {

                            if (!empty($nsTemplate)) {
                                $pageId = FsWikiUtility::getIndex($page['id']);
                                if ($pageId != null) {
                                    $pageTitle = TitleUtility::getPageTitle($pageId);
                                    $tpl = TemplateUtility::render($nsTemplate, $pageId, $pageTitle);
                                    $list .= '<li>' . $tpl . '</li>';
                                }
                            }

                        } else {

                            if (!empty($pageTemplate)) {
                                $pageNum++;
                                $pageId = $page['id'];
                                if (":" . $pageId != $pageIndex && $pageId != $pageIndex ) {
                                    $pageTitle = TitleUtility::getPageTitle($pageId);
                                    $tpl = TemplateUtility::render($pageTemplate, $pageId, $pageTitle);
                                    $list .= '<li>' . $tpl . '</li>';
                                }
                            }
                        }


                    }
                    $list .= "</list>";
                    $renderer->doc .= RenderUtility::renderText2Xhtml($list) . DOKU_LF;
                    break;
            }
            return true;
        }

        // unsupported $mode
        return false;
    }


}

