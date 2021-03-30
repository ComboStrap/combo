<?php


// must be run within Dokuwiki
use ComboStrap\Image;
use ComboStrap\PluginUtility;
use ComboStrap\Tag;

if (!defined('DOKU_INC')) die();

/**
 * Implementation of a background
 *
 *
 * Cool calm example of moving square background
 * https://codepen.io/Lewitje/pen/BNNJjo
 * Particles.js
 * https://codepen.io/akey96/pen/oNgeQYX
 * Gradient positioning above a photo
 * https://codepen.io/uzoawili/pen/GypGOy
 * Fire flies
 * https://codepen.io/mikegolus/pen/Jegvym
 */
class syntax_plugin_combo_background extends DokuWiki_Syntax_Plugin
{

    const TAG = "background";
    const TAG_SHORT = "bg";

    /**
     * Syntax Type.
     *
     * Needs to return one of the mode types defined in $PARSER_MODES in parser.php
     * @see DokuWiki_Syntax_Plugin::getType()
     */
    function getType()
    {
        return 'container';
    }

    /**
     * How Dokuwiki will add P element
     *
     * * 'normal' - The plugin can be used inside paragraphs
     *  * 'block'  - Open paragraphs need to be closed before plugin output - block should not be inside paragraphs
     *  * 'stack'  - Special case. Plugin wraps other paragraphs. - Stacks can contain paragraphs
     *
     * @see DokuWiki_Syntax_Plugin::getPType()
     */
    function getPType()
    {
        return 'block';
    }

    /**
     * @return array
     * Allow which kind of plugin inside
     *
     * Array('baseonly','container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs')
     *
     * Return an array of one or more of the mode types {@link $PARSER_MODES} in Parser.php
     */
    function getAllowedTypes()
    {
        return array('baseonly', 'container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs');
    }

    public function accepts($mode)
    {
        if (!$this->getConf(syntax_plugin_combo_preformatted::CONF_PREFORMATTED_ENABLE)) {
            return PluginUtility::disablePreformatted($mode);
        } else {
            return true;
        }
    }

    function getSort()
    {
        return 201;
    }


    function connectTo($mode)
    {

        foreach ($this->getTags() as $tag) {
            $pattern = PluginUtility::getContainerTagPattern($tag);
            $this->Lexer->addEntryPattern($pattern, $mode, PluginUtility::getModeForComponent($this->getPluginComponent()));
        }
    }


    function postConnect()
    {

        foreach ($this->getTags() as $tag) {
            $this->Lexer->addExitPattern('</' . $tag . '>', PluginUtility::getModeForComponent($this->getPluginComponent()));
        }

    }

    function handle($match, $state, $pos, Doku_Handler $handler)
    {

        switch ($state) {

            case DOKU_LEXER_ENTER :
                $defaultAttributes = array();
                $inlineAttributes = PluginUtility::getTagAttributes($match);
                $attributes = PluginUtility::mergeAttributes($inlineAttributes, $defaultAttributes);
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $attributes
                );

            case DOKU_LEXER_UNMATCHED :
                return PluginUtility::handleAndReturnUnmatchedData(self::TAG, $match, $handler);

            case DOKU_LEXER_EXIT :

                $tag = new Tag(self::TAG,array(),$state,$handler);
                $openingTag = $tag->getOpeningTag();
                $callImage = $openingTag->getDescendant(syntax_plugin_combo_img::TAG);
                if ($callImage==null){
                    $callImage = $openingTag->getDescendant(Image::INTERNAL_MEDIA);
                }
                if ($callImage!=null) {
                    $callImage->deleteCall();
                    $image =  Image::createFromCallAttributes($callImage->getAttributes());
                    $openingTag->setAttribute("img",$image->toAttributes());
                }
                return array(
                    PluginUtility::STATE => $state
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
                    $attributes = $data[PluginUtility::ATTRIBUTES];
                    if ()
                    $renderer->doc .= '<div';
                    if (sizeof($attributes) > 0) {
                        $renderer->doc .= ' ' . PluginUtility::array2HTMLAttributes($attributes);
                    }
                    $renderer->doc .= '>';
                    break;

                case DOKU_LEXER_UNMATCHED :
                    $renderer->doc .= PluginUtility::renderUnmatched($data);
                    break;

                case DOKU_LEXER_EXIT :
                    $renderer->doc .= '</div>';
                    break;
            }
            return true;
        }

        // unsupported $mode
        return false;
    }

    private function getTags()
    {
        return [self::TAG, self::TAG_SHORT];
    }


}

