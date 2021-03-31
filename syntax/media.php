<?php

// implementation of
// https://developer.mozilla.org/en-US/docs/Web/HTML/Element/cite

// must be run within Dokuwiki
use ComboStrap\RasterImage;
use ComboStrap\InternalMedia;
use ComboStrap\PluginUtility;
use ComboStrap\Tag;
use ComboStrap\TitleUtility;

require_once(__DIR__ . '/../class/RasterImage.php');

if (!defined('DOKU_INC')) die();


/**
 * Internal media
 */
class syntax_plugin_combo_media extends DokuWiki_Syntax_Plugin
{

    // The > in the pattern below is to be able to handle plugin
    // that uses a pattern such as {{changes>.}} from the change plugin
    // https://github.com/cosmocode/changes/blob/master/syntax.php


    const TAG = "media";

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
        $enable = $this->getConf(RasterImage::CONF_LAZY_LOAD_IMAGE_ENABLE);
        if (!$enable) {

            // Inside a card, we need to take over
            $modes = [
                PluginUtility::getModeForComponent(syntax_plugin_combo_card::TAG),
            ];
            $enable = in_array($mode, $modes);
        }

        if ($enable) {
            $this->Lexer->addSpecialPattern(InternalMedia::INTERNAL_MEDIA_PATTERN, $mode, PluginUtility::getModeForComponent($this->getPluginComponent()));
        }
    }


    function handle($match, $state, $pos, Doku_Handler $handler)
    {

        switch ($state) {


            // As this is a container, this cannot happens but yeah, now, you know
            case DOKU_LEXER_SPECIAL :
                $attributes = InternalMedia::getParseAttributes($match);
                $tag = new Tag(self::TAG, $attributes, $state, $handler);
                $parent = $tag->getParent();
                $parentTag = "";
                if (!empty($parent)) {
                    $parentTag = $parent->getName();
                }
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
                $attributes = $data[PluginUtility::ATTRIBUTES];
                $media = InternalMedia::createFromRenderAttributes($attributes);
                if ($media->isImage()) {

                    if ($context === syntax_plugin_combo_card::TAG && $isFirstImage) {

                        /**
                         * First image of a card
                         */
                        $media->addClass("card-img-top");
                        $renderer->doc .= $media->renderMediaTag();
                        $renderer->doc .= syntax_plugin_combo_card::CARD_BODY;

                    } else {
                        $renderer->doc .= $media->renderMediaTag();
                    }

                } else {

                    /**
                     * This is not a media image (a video)
                     * Dokuwiki takes over
                     */
                    $src = $attributes['src'];
                    $title = $attributes['title'];
                    $align = $attributes['align'];
                    $width = $attributes['width'];
                    $height = $attributes['height'];
                    $cache = $attributes['cache'];
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

