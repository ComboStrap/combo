<?php


use ComboStrap\Tag;
use ComboStrap\PluginUtility;

require_once(__DIR__ . '/../class/Tag.php');

/**
 * Just a node to test the {@link Tag} context
 * This is not public
 */
class syntax_plugin_combo_tag extends DokuWiki_Syntax_Plugin
{

    const TAG = "tag";


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
     *  * 'normal' - The plugin can be used inside paragraphs (inline)
     *  * 'block'  - Open paragraphs need to be closed before plugin output - block should not be inside paragraphs
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
        $this->Lexer->addSpecialPattern(PluginUtility::getEmptyTagPattern(self::TAG), $mode, PluginUtility::getModeForComponent($this->getPluginComponent()));

    }

    function postConnect()
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
                $tag = new Tag(self::TAG, $attributes, $state, $handler->calls);
                $attributes['name'] = $tag->getName();
                $attributes['type'] = $tag->getType();
                $attributes['parent'] = $tag->getParent()->getName();;
                $attributes['parent-type'] = $tag->getParent()->getType();;
                $attributes['child-of-blockquote'] = $tag->isChildOf("blockquote");
                $attributes['descendant-of-card'] = $tag->isDescendantOf("card");
                $attributes['has-siblings'] = $tag->hasSiblings();
                $attributes['first-sibling'] = $tag->getAscendantSibling()!==null?$tag->getAscendantSibling()->getName():false;

                $payload = '<tag-enter type="'.$attributes['type'].'" ' . PluginUtility::array2HTMLAttributes($attributes) . '></tag-enter>';

                /**
                 * Attributes needs to be given
                 * in order to save it in the call stack
                 */
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::PAYLOAD => $payload
                );

            case DOKU_LEXER_UNMATCHED :
                $tag = new Tag(self::TAG, array(), $state, $handler->calls);
                $attributes['name'] = $tag->getName();
                $attributes['type'] = $tag->getType();
                $attributes['parent'] = $tag->getParent()->getName();;
                $attributes['parent-type'] = $tag->getParent()->getType();;
                $attributes['child-of-blockquote'] = $tag->isChildOf("blockquote");
                $attributes['descendant-of-card'] = $tag->isDescendantOf("card");
                $attributes['has-siblings'] = $tag->hasSiblings();
                $attributes['first-sibling'] = $tag->getAscendantSibling()!==null?$tag->getAscendantSibling():false;
                $payload = '<tag-unmatched type="'.$attributes['type'].'" ' . PluginUtility::array2HTMLAttributes($attributes) . '></tag-unmatched>';
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::PAYLOAD => $payload
                );

            case DOKU_LEXER_SPECIAL :

                $tag = new Tag(self::TAG, PluginUtility::getTagAttributes($match), $state, $handler->calls);
                $attributes['name'] = $tag->getName();
                $attributes['type'] = $tag->getType();
                $attributes['parent'] = $tag->getParent()->getName();;
                $attributes['parent-type'] = $tag->getParent()->getType();;
                $attributes['child-of-blockquote'] = $tag->isChildOf("blockquote");
                $attributes['descendant-of-card'] = $tag->isDescendantOf("card");
                $attributes['has-siblings'] = $tag->hasSiblings();
                $attributes['first-sibling'] = $tag->getAscendantSibling()->getName();
                $payload = '<tag-special type="'.$attributes['type'].'" ' . PluginUtility::array2HTMLAttributes($attributes) . '></tag-special>';
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::PAYLOAD => $payload
                );

            case DOKU_LEXER_EXIT :

                $tag = new Tag(self::TAG, array(), $state, $handler->calls);
                $attributes['name'] = $tag->getName();
                $attributes['type'] = $tag->getType();
                $attributes['parent'] = $tag->getParent()->getName();;
                $attributes['parent-type'] = $tag->getParent()->getType();;
                $attributes['child-of-blockquote'] = $tag->isChildOf("blockquote");
                $attributes['descendant-of-card'] = $tag->isDescendantOf("card");
                $attributes['has-siblings'] = $tag->hasSiblings();
                $attributes['first-sibling'] = $tag->getAscendantSibling()!==null?$tag->getAscendantSibling()->getName():false;
                $openingTag = $tag->getOpeningTag();
                $attributes['has-descendants'] = $openingTag->hasDescendants();
                $attributes['descendants-count'] = sizeof($openingTag->getDescendants());
                $badgeTag = $openingTag->getDescendant("badge");
                $attributes['has-badge-descendant'] = $badgeTag !== null;
                $attributes['badge-content'] = $badgeTag !== null ? $badgeTag->getContentRecursively(): "";
                $payload = '<tag-exit type="'.$attributes['type'].'" ' . PluginUtility::array2HTMLAttributes($attributes) . '></tag-exit>';
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::PAYLOAD => $payload
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
            $state = $data["state"];
            switch ($state) {
                case DOKU_LEXER_UNMATCHED:
                case DOKU_LEXER_ENTER :
                case DOKU_LEXER_SPECIAL:
                case DOKU_LEXER_EXIT :
                    $renderer->doc .= $data[PluginUtility::PAYLOAD];
                    break;

            }
            return true;
        }

        // unsupported $mode
        return false;
    }


}

