<?php


require_once(__DIR__ . "/../class/Analytics.php");
require_once(__DIR__ . "/../class/PluginUtility.php");
require_once(__DIR__ . "/../class/LinkUtility.php");
require_once(__DIR__ . "/../class/HtmlUtility.php");

use ComboStrap\Analytics;
use ComboStrap\LinkUtility;
use ComboStrap\LogUtility;
use ComboStrap\Page;
use ComboStrap\PluginUtility;
use ComboStrap\LowQualityPage;
use ComboStrap\Tag;

if (!defined('DOKU_INC')) die();

/**
 *
 * A link pattern to take over the link of Dokuwiki
 * and transform it as a bootstrap link
 *
 * The handle of the move of link is to be found in the
 * admin action {@link action_plugin_combo_linkmove}
 *
 */
class syntax_plugin_combo_link extends DokuWiki_Syntax_Plugin
{
    const TAG = 'link';


    /**
     * Syntax Type.
     *
     * Needs to return one of the mode types defined in $PARSER_MODES in parser.php
     * @see https://www.dokuwiki.org/devel:syntax_plugins#syntax_types
     */
    function getType()
    {
        return 'substition';
    }

    /**
     * How Dokuwiki will add P element
     *
     *  * 'normal' - The plugin can be used inside paragraphs
     *  * 'block'  - Open paragraphs need to be closed before plugin output - block should not be inside paragraphs
     *  * 'stack'  - Special case. Plugin wraps other paragraphs. - Stacks can contain paragraphs
     *
     * @see DokuWiki_Syntax_Plugin::getPType()
     */
    function getPType()
    {
        return 'normal';
    }

    /**
     * @return array
     * Allow which kind of plugin inside
     *
     * No one of array('container', 'baseonly', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs')
     * because we manage self the content and we call self the parser
     */
    function getAllowedTypes()
    {
        return array('substition', 'formatting', 'disabled');
    }

    /**
     * @see Doku_Parser_Mode::getSort()
     * The mode with the lowest sort number will win out
     */
    function getSort()
    {
        return 100;
    }


    function connectTo($mode)
    {

        $this->Lexer->addSpecialPattern(LinkUtility::LINK_PATTERN, $mode, PluginUtility::getModeForComponent($this->getPluginComponent()));

    }


    /**
     * The handler for an internal link
     * based on `internallink` in {@link Doku_Handler}
     * The handler call the good renderer in {@link Doku_Renderer_xhtml} with
     * the parameters (ie for instance internallink)
     * @param string $match
     * @param int $state
     * @param int $pos
     * @param Doku_Handler $handler
     * @return array|bool
     */
    function handle($match, $state, $pos, Doku_Handler $handler)
    {

        /**
         * Because we use the specialPattern, there is only one state ie DOKU_LEXER_SPECIAL
         */
        $attributes = LinkUtility::getAttributes($match);
        $tag = new Tag(self::TAG, $attributes, $state, $handler->calls);
        $parent = $tag->getParent();
        $parentName = "";
        if ($parent != null) {
            $parentName = $parent->getName();
        }
        return array(
            PluginUtility::ATTRIBUTES => $attributes,
            PluginUtility::PARENT_TAG => $parentName
        );


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
        // The data
        switch ($format) {
            case 'xhtml':

                /** @var Doku_Renderer_xhtml $renderer */

                /**
                 * Cache problem may occurs while releasing
                 */
                if (isset($data[PluginUtility::ATTRIBUTES])) {
                    $attributes = $data[PluginUtility::ATTRIBUTES];
                } else {
                    $attributes = $data;
                }

                $type = $attributes[LinkUtility::ATTRIBUTE_TYPE];
                $id = $attributes[LinkUtility::ATTRIBUTE_ID];

                /**
                 * Email are also link for Dokuwiki
                 * And there is no notion of page.
                 * For the low quality page functionality,
                 * we split the process of the internal link
                 */
                $lowLink = false;
                if ($type == LinkUtility::TYPE_INTERNAL) {
                    /**
                     * If this is a low quality internal page,
                     * print a shallow link for the anonymous user
                     */
                    global $ID;
                    $qualifiedPageId = $id;
                    resolve_pageid(getNS($ID), $qualifiedPageId, $exists);
                    $page = new Page($qualifiedPageId);
                    if ($this->getConf(LowQualityPage::CONF_LOW_QUALITY_PAGE_PROTECTION_ENABLE)
                        && $page->isLowQualityPage()) {

                        $lowLink = true;
                    }
                }

                /**
                 * Render the link
                 */
                $htmlLink = LinkUtility::renderLinkDefault($renderer, $attributes, $lowLink);

                /**
                 * Extra styling
                 */
                $parentClassWithoutClass = array(
                    syntax_plugin_combo_button::TAG,
                    syntax_plugin_combo_cite::TAG,
                    syntax_plugin_combo_dropdown::TAG,
                    syntax_plugin_combo_listitem::TAG,
                    syntax_plugin_combo_preformatted::TAG
                );
                if (page_exists($id) && in_array($data[PluginUtility::PARENT_TAG], $parentClassWithoutClass)) {
                    $htmlLink = LinkUtility::deleteDokuWikiClass($htmlLink);
                }

                if ($data[PluginUtility::PARENT_TAG] == syntax_plugin_combo_button::TAG) {
                    // We could also apply the class ie btn-secondary ...
                    $htmlLink = LinkUtility::inheritColorFromParent($htmlLink);
                }

                /**
                 * Add it to the rendering
                 */
                $renderer->doc .= $htmlLink;

                return true;
                break;

            case
            'metadata':

                /**
                 * Keep track of the backlinks ie meta['relation']['references']
                 * @var Doku_Renderer_metadata $renderer
                 */
                if (isset($data[PluginUtility::ATTRIBUTES])) {
                    $attributes = $data[PluginUtility::ATTRIBUTES];
                } else {
                    $attributes = $data;
                }
                LinkUtility::handleMetadata($renderer, $attributes);

                return true;
                break;

            case Analytics::RENDERER_FORMAT:
                /**
                 *
                 * @var renderer_plugin_combo_analytics $renderer
                 */
                $attributes = $data[PluginUtility::ATTRIBUTES];
                LinkUtility::processLinkStats($attributes, $renderer->stats);
                break;

        }
        // unsupported $mode
        return false;
    }


}

