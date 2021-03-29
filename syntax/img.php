<?php

// implementation of
// https://developer.mozilla.org/en-US/docs/Web/HTML/Element/cite

// must be run within Dokuwiki
use ComboStrap\Image;
use ComboStrap\PluginUtility;
use ComboStrap\Tag;
use ComboStrap\TitleUtility;

require_once(__DIR__ . '/../class/Image.php');

if (!defined('DOKU_INC')) die();


/**
 * Card image
 * Title
 */
class syntax_plugin_combo_img extends DokuWiki_Syntax_Plugin
{

    // The > in the pattern below is to be able to handle plugin
    // that uses a pattern such as {{changes>.}} from the change plugin
    // https://github.com/cosmocode/changes/blob/master/syntax.php


    const TAG = "img";

    /**
     * Used in the move plugin
     * The two last word of the class
     */
    const COMPONENT = 'combo_img';

    /**
     * The attribute that defines if the image is the first image in
     * the component
     *
     */
    const IS_FIRST_IMAGE_KEY = "isFirstImage";


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
        // Only inside a card
        $modes = [
            PluginUtility::getModeForComponent(syntax_plugin_combo_card::TAG),
        ];
        if (in_array($mode, $modes)) {
            $this->Lexer->addSpecialPattern(Image::IMAGE_PATTERN, $mode, PluginUtility::getModeForComponent($this->getPluginComponent()));
        }
    }


    function handle($match, $state, $pos, Doku_Handler $handler)
    {

        switch ($state) {


            // As this is a container, this cannot happens but yeah, now, you know
            case DOKU_LEXER_SPECIAL :
                $attributes = Image::parse($match);
                $tag = new Tag(self::TAG, $attributes, $state, $handler);
                $parentTag = $tag->getParent()->getName();
                $isFirstSibling = $tag->isFirstMeaningFullSibling();
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $attributes,
                    PluginUtility::CONTEXT => $parentTag,
                    self::IS_FIRST_IMAGE_KEY => $isFirstSibling
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

        $attributes = $data[PluginUtility::ATTRIBUTES];
        switch ($format) {

            case 'xhtml':

                /** @var Doku_Renderer_xhtml $renderer */
                $isFirstImage = $data[self::IS_FIRST_IMAGE_KEY];
                $context = $data[PluginUtility::CONTEXT];
                if ($context === syntax_plugin_combo_card::TAG && $isFirstImage) {

                    /**
                     * First image of a card
                     */
                    PluginUtility::addClass2Attributes("card-img-top", $attributes);
                    $renderer->doc .= Image::render($attributes);
                    $renderer->doc .= syntax_plugin_combo_card::CARD_BODY;

                } else {
                    /**
                     * Renderer function
                     */
                    $src = $attributes['src'];
                    $title = $attributes['title'];
                    $align = $attributes['align'];
                    $width = $attributes['width'];
                    $height = $attributes['height'];
                    $cache = $attributes['cache']; // Cache: https://www.dokuwiki.org/images#caching
                    $linking = $attributes['linking'];
                    $renderer->doc .= $renderer->internalmedia($src, $title, $align, $width, $height, $cache, $linking, true);
                }

                break;

            case "metadata":
                /**
                 * Keep track of the metadata
                 * @var Doku_Renderer_metadata $renderer
                 */
                $src = $attributes['src'];
                $title = $attributes['title'];
                $align = $attributes['align'];
                $width = $attributes['width'];
                $height = $attributes['height'];
                $cache = $attributes['cache']; // Cache: https://www.dokuwiki.org/images#caching
                $linking = $attributes['linking'];
                $renderer->internalmedia($src, $title, $align, $width, $height, $cache, $linking);
                break;

        }
        // unsupported $mode
        return false;
    }


}

