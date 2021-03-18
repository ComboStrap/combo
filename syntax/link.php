<?php


require_once(__DIR__ . "/../class/Analytics.php");
require_once(__DIR__ . "/../class/PluginUtility.php");
require_once(__DIR__ . "/../class/LinkUtility.php");
require_once(__DIR__ . "/../class/HtmlUtility.php");

use ComboStrap\Analytics;
use ComboStrap\SnippetManager;
use ComboStrap\LinkUtility;
use ComboStrap\PluginUtility;
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
    const COMPONENT = 'combo_link';

    /**
     * The link Tag
     */
    const LINK_TAG = "linkTag";


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

        $this->Lexer->addEntryPattern(LinkUtility::ENTRY_PATTERN, $mode, PluginUtility::getModeForComponent($this->getPluginComponent()));

    }

    public function postConnect()
    {
        $this->Lexer->addExitPattern(LinkUtility::EXIT_PATTERN, PluginUtility::getModeForComponent($this->getPluginComponent()));
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
        switch ($state) {
            case DOKU_LEXER_ENTER:
                $attributes = LinkUtility::parse($match);
                $tag = new Tag(self::TAG, $attributes, $state, $handler);
                $parent = $tag->getParent();
                $parentName = "";
                if ($parent != null) {
                    $parentName = $parent->getName();
                    if ($parentName == syntax_plugin_combo_button::TAG) {
                        $attributes = PluginUtility::mergeAttributes($attributes, $parent->getAttributes());
                    }
                }
                $link = new LinkUtility($attributes[LinkUtility::ATTRIBUTE_REF]);
                $linkTag = $link->getHtmlTag();
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $attributes,
                    PluginUtility::CONTEXT => $parentName,
                    self::LINK_TAG => $linkTag
                );
            case DOKU_LEXER_UNMATCHED:

                /**
                 * Delete the name separator if any
                 */
                $tag = new Tag(self::TAG, array(), $state, $handler);
                $parent = $tag->getParent();
                if ($parent->getName() == self::TAG) {
                    if (strpos($match, '|') === 0) {
                        $match = substr($match, 1);
                    }
                }
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::PAYLOAD => $match
                );

            case DOKU_LEXER_EXIT:
                $tag = new Tag(self::TAG, array(), $state, $handler);
                $openingTag = $tag->getOpeningTag();
                $openingAttributes = $openingTag->getAttributes();
                $linkTag = $openingTag->getData()[self::LINK_TAG];

                if ($openingTag->getPosition() == $tag->getPosition() - 1) {
                    // There is no name
                    $link = new LinkUtility($openingAttributes[LinkUtility::ATTRIBUTE_REF]);
                    $linkName = $link->getName();
                } else {
                    $linkName = "";
                }
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $openingAttributes,
                    PluginUtility::PAYLOAD => $linkName,
                    PluginUtility::CONTEXT => $openingTag->getContext(),
                    self::LINK_TAG => $linkTag
                );
        }
        return true;


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

                PluginUtility::getSnippetManager()->addCssSnippetOnlyOnce(self::TAG);


                $state = $data[PluginUtility::STATE];
                $payload = $data[PluginUtility::PAYLOAD];
                switch ($state) {
                    case DOKU_LEXER_ENTER:
                        $ref = $attributes[LinkUtility::ATTRIBUTE_REF];
                        unset($attributes[LinkUtility::ATTRIBUTE_REF]);
                        $name = $attributes[LinkUtility::ATTRIBUTE_NAME];
                        unset($attributes[LinkUtility::ATTRIBUTE_NAME]);
                        $link = new LinkUtility($ref);
                        if ($name != null) {
                            $link->setName($name);
                        }
                        $link->setAttributes($attributes);


                        /**
                         * Extra styling
                         */
                        $parentTag = $data[PluginUtility::CONTEXT];
                        switch ($parentTag) {
                            case syntax_plugin_combo_button::TAG:
                                $attributes["role"] = "button";
                                syntax_plugin_combo_button::processButtonAttributesToHtmlAttributes($attributes);
                                $htmlLink = $link->renderOpenTag($renderer);
                                break;
                            case syntax_plugin_combo_badge::TAG:
                            case syntax_plugin_combo_cite::TAG:
                            case syntax_plugin_combo_listitem::TAG:
                            case syntax_plugin_combo_preformatted::TAG:
                                $htmlLink = $link->renderOpenTag($renderer);
                                break;
                            case syntax_plugin_combo_dropdown::TAG:
                                PluginUtility::addClass2Attributes("dropdown-item", $attributes);
                                $htmlLink = $link->renderOpenTag($renderer);
                                break;
                            case syntax_plugin_combo_navbarcollapse::COMPONENT:
                                PluginUtility::addClass2Attributes("navbar-link", $attributes);
                                $htmlLink = '<div class="navbar-nav">' . $link->renderOpenTag($renderer);
                                break;
                            case syntax_plugin_combo_navbargroup::COMPONENT:
                                PluginUtility::addClass2Attributes("nav-link", $attributes);
                                $htmlLink = '<li class="nav-item">' . $link->renderOpenTag($renderer);
                                break;
                            default:

                                $htmlLink = $link->renderOpenTag($renderer);

                        }


                        /**
                         * Add it to the rendering
                         */
                        $renderer->doc .= $htmlLink;
                        break;
                    case DOKU_LEXER_UNMATCHED:
                        $renderer->doc .= PluginUtility::escape($payload);
                        break;
                    case DOKU_LEXER_EXIT:

                        // if there is no link name defined, we get the name as ref in the payload
                        // otherwise null string
                        $renderer->doc .= $payload;

                        // html element
                        $context = $data[PluginUtility::CONTEXT];
                        switch ($context) {
                            case syntax_plugin_combo_navbarcollapse::COMPONENT:
                                $renderer->doc .= '</div>';
                                break;
                            case syntax_plugin_combo_navbargroup::COMPONENT:
                                $renderer->doc .= '</li>';
                                break;
                        }

                        $linkTag = $data[self::LINK_TAG];
                        $renderer->doc .= "</$linkTag>";


                }


                return true;
                break;

            case 'metadata':

                $state = $data[PluginUtility::STATE];
                if ($state == DOKU_LEXER_ENTER) {
                    /**
                     * Keep track of the backlinks ie meta['relation']['references']
                     * @var Doku_Renderer_metadata $renderer
                     */
                    if (isset($data[PluginUtility::ATTRIBUTES])) {
                        $attributes = $data[PluginUtility::ATTRIBUTES];
                    } else {
                        $attributes = $data;
                    }
                    $ref = $attributes[LinkUtility::ATTRIBUTE_REF];

                    $link = new LinkUtility($ref);
                    $name = $attributes[LinkUtility::ATTRIBUTE_NAME];
                    if ($name != null) {
                        $link->setName($name);
                    }
                    $link->handleMetadata($renderer);

                    return true;
                }
                break;

            case Analytics::RENDERER_FORMAT:

                $state = $data[PluginUtility::STATE];
                if ($state == DOKU_LEXER_ENTER) {
                    /**
                     *
                     * @var renderer_plugin_combo_analytics $renderer
                     */
                    $attributes = $data[PluginUtility::ATTRIBUTES];
                    $ref = $attributes[LinkUtility::ATTRIBUTE_REF];
                    $link = new LinkUtility($ref);
                    $link->processLinkStats($renderer->stats);
                    break;
                }

        }
        // unsupported $mode
        return false;
    }


}

