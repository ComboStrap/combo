<?php

// implementation of
// https://developer.mozilla.org/en-US/docs/Web/HTML/Element/cite

// must be run within Dokuwiki
use ComboStrap\HeaderUtility;
use ComboStrap\TitleUtility;
use ComboStrap\PluginUtility;
use ComboStrap\StringUtility;
use ComboStrap\Tag;

require_once(__DIR__ . '/../class/HeaderUtility.php');

if (!defined('DOKU_INC')) die();


class syntax_plugin_combo_label extends DokuWiki_Syntax_Plugin
{


    const TAG = "label";

    /**
     * The id of the heading element for a accordion label
     */
    const HEADING_ID = "headingId";
    /**
     * The id of the collapsable target
     */
    const TARGET_ID = "targetId";

    /**
     * An indicator attribute that tells if the accordion is collpased or not
     */
    const COLLAPSED = "collapsed";

    function getType()
    {
        return 'formatting';
    }

    /**
     * How Dokuwiki will add P element
     *
     *  * 'normal' - The plugin can be used inside paragraphs (inline)
     *  * 'block'  - Open paragraphs need to be closed before plugin output - block should not be inside paragraphs
     *  * 'stack'  - Special case. Plugin wraps other paragraphs. - Stacks can contain paragraphs
     *
     * @see DokuWiki_Syntax_Plugin::getPType()
     */
    function getPType()
    {
        return 'normal';
    }

    function getAllowedTypes()
    {
        return array('substition', 'formatting', 'disabled');
    }

    function getSort()
    {
        return 201;
    }


    function connectTo($mode)
    {

        $this->Lexer->addEntryPattern(PluginUtility::getContainerTagPattern(self::TAG), $mode, PluginUtility::getModeForComponent($this->getPluginComponent()));
    }

    public function postConnect()
    {
        $this->Lexer->addExitPattern('</' . self::TAG . '>', PluginUtility::getModeForComponent($this->getPluginComponent()));
    }

    function handle($match, $state, $pos, Doku_Handler $handler)
    {

        switch ($state) {

            case DOKU_LEXER_ENTER:
                $tagAttributes = PluginUtility::getTagAttributes($match);

                $tag = new Tag(self::TAG, $tagAttributes, $state, $handler->calls);
                $parentTag = $tag->getParent();
                $context = null;
                if ($parentTag != null) {
                    $grandfather = $parentTag->getParent();
                    if ($grandfather != null) {
                        if ($grandfather->getName() == syntax_plugin_combo_accordion::TAG) {
                            $id = $parentTag->getAttribute("id");
                            $tagAttributes["id"] = $id;
                            $tagAttributes[self::HEADING_ID] = "heading" . ucfirst($id);
                            $tagAttributes[self::TARGET_ID] = "collapse" . ucfirst($id);
                            $parentAttribute = $parentTag->getAttributes();
                            if (!key_exists(self::COLLAPSED, $parentAttribute)) {
                                // Accordion are collapsed by default
                                $tagAttributes[self::COLLAPSED] = "true";
                            } else {
                                $tagAttributes[self::COLLAPSED] = $parentAttribute[self::COLLAPSED];
                            }
                            $context = syntax_plugin_combo_accordion::TAG;
                        }
                    }
                }

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $tagAttributes,
                    PluginUtility::CONTEXT => $context
                );

            case DOKU_LEXER_UNMATCHED :
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::PAYLOAD => $match);

            case DOKU_LEXER_EXIT :
                $tag = new Tag(self::TAG, array(), $state, $handler->calls);
                $openingTag = $tag->getOpeningTag();
                $context = $openingTag->getContext();
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::CONTEXT => $context,
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

                case DOKU_LEXER_ENTER:

                    $context = $data[PluginUtility::CONTEXT];
                    switch ($context) {
                        case syntax_plugin_combo_accordion::TAG:
                            $attribute = $data[PluginUtility::ATTRIBUTES];
                            $headingId = $attribute[self::HEADING_ID];
                            $collapseId = $attribute[self::TARGET_ID];
                            $collapsed = $attribute[self::COLLAPSED];
                            if ($collapsed == "false") {
                                $collapsedClass = "collapsed";
                            } else {
                                $collapsedClass = "";
                            }
                            $renderer->doc .= "<div class=\"card-header\" id=\"$headingId\">" . DOKU_LF;
                            $renderer->doc .= "<h2 class=\"mb-0\">";
                            $renderer->doc .= "<button class=\"btn btn-link btn-block text-left $collapsedClass\" type=\"button\" data-toggle=\"collapse\" data-target=\"#$collapseId\" aria-expanded=\"true\" aria-controls=\"$collapseId\">";
                            break;
                    }
                    break;

                case DOKU_LEXER_UNMATCHED :
                    $renderer->doc .= PluginUtility::escape($data[PluginUtility::PAYLOAD]);
                    break;

                case DOKU_LEXER_EXIT:
                    $context = $data[PluginUtility::CONTEXT];
                    switch ($context) {
                        case syntax_plugin_combo_accordion::TAG:
                            $attribute = $data[PluginUtility::ATTRIBUTES];
                            $collapseId = $attribute[self::TARGET_ID];
                            $headingId = $attribute[self::HEADING_ID];
                            $collapsed = $attribute[self::COLLAPSED];
                            if ($collapsed == "false") {
                                $showClass = "show";
                            } else {
                                $showClass = "";
                            }
                            $renderer->doc .= "</button></h2></div>";
                            $renderer->doc .= "<div id=\"$collapseId\" class=\"collapse $showClass\" aria-labelledby=\"$headingId\" data-parent=\"#$headingId\">";
                            $renderer->doc .= "<div class=\"card-body\">" . DOKU_LF;
                            break;
                    }
                    break;


            }
        }
        // unsupported $mode
        return false;
    }


}

