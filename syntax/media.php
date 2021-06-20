<?php


use ComboStrap\DokuPath;
use ComboStrap\LogUtility;
use ComboStrap\MediaLink;
use ComboStrap\PluginUtility;
use ComboStrap\Tag;
use ComboStrap\TagAttributes;


if (!defined('DOKU_INC')) die();


/**
 * Media
 *
 * Takes over the {@link \dokuwiki\Parsing\ParserMode\Media media mode}
 * that is processed by {@link Doku_Handler_Parse_Media}
 *
 *
 *
 * It can be a internal / external media
 */
class syntax_plugin_combo_media extends DokuWiki_Syntax_Plugin
{


    const TAG = "media";

    /**
     * Used in the move plugin
     * !!! The two last word of the plugin class !!!
     */
    const COMPONENT = 'combo_' . self::TAG;

    /**
     * The attribute that defines if the image is the first image in
     * the component
     *
     */
    const IS_FIRST_IMAGE_KEY = "isFirstImage";

    /**
     * Found at {@link \dokuwiki\Parsing\ParserMode\Media}
     */
    const MEDIA_PATTERN = "\{\{(?:[^>\}]|(?:\}[^\}]))+\}\}";

    /**
     * Enable or disable the image
     */
    const CONF_IMAGE_ENABLE = "imageEnable";


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
        /**
         * An image is not a block (it can be inside paragraph)
         */
        return 'normal';
    }

    function getAllowedTypes()
    {
        return array('substition', 'formatting', 'disabled');
    }

    /**
     * It should be less than {@link \dokuwiki\Parsing\ParserMode\Media::getSort()}
     * (It was 320 at the time of writing this code)
     * @return int
     *
     */
    function getSort()
    {
        return 319;
    }


    function connectTo($mode)
    {
        $enable = $this->getConf(self::CONF_IMAGE_ENABLE, 1);
        if (!$enable) {

            // Inside a card, we need to take over and enable it
            $modes = [
                PluginUtility::getModeForComponent(syntax_plugin_combo_card::TAG),
            ];
            $enable = in_array($mode, $modes);
        }

        if ($enable) {
            $this->Lexer->addSpecialPattern(self::MEDIA_PATTERN, $mode, PluginUtility::getModeForComponent($this->getPluginComponent()));
        }
    }


    function handle($match, $state, $pos, Doku_Handler $handler)
    {

        switch ($state) {


            // As this is a container, this cannot happens but yeah, now, you know
            case DOKU_LEXER_SPECIAL :
                $media = MediaLink::createFromRenderMatch($match);
                $attributes = $media->toCallStackArray();
                $tag = new Tag(self::TAG, $attributes, $state, $handler);
                $parent = $tag->getParent();
                $parentTag = "";
                if (!empty($parent)) {
                    $parentTag = $parent->getName();
                    if ($parentTag == syntax_plugin_combo_link::TAG) {
                        /**
                         * The image is in a link, we don't want another link
                         * to the image
                         */
                        $attributes[TagAttributes::LINKING_KEY] = MediaLink::LINKING_NOLINK_VALUE;
                    }
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
                $attributes = $data[PluginUtility::ATTRIBUTES];
                $media = MediaLink::createFromCallStackArray($attributes);
                if ($media->getScheme() == DokuPath::LOCAL_SCHEME) {
                    $media = MediaLink::createFromCallStackArray($attributes, $renderer->date_at);
                    if ($media->isImage()) {
                        $renderer->doc .= $media->renderMediaTagWithLink();
                        return true;
                    }
                }

                /**
                 * This is not an local internal media image (a video or an url image)
                 * Dokuwiki takes over
                 */
                $type = $attributes[MediaLink::MEDIA_DOKUWIKI_TYPE];
                $src = $attributes['src'];
                $title = $attributes['title'];
                $align = $attributes['align'];
                $width = $attributes['width'];
                $height = $attributes['height'];
                $cache = $attributes['cache'];
                $linking = $attributes['linking'];
                switch ($type) {
                    case MediaLink::INTERNAL_MEDIA_CALL_NAME:
                        $renderer->doc .= $renderer->internalmedia($src, $title, $align, $width, $height, $cache, $linking, true);
                        break;
                    case MediaLink::EXTERNAL_MEDIA_CALL_NAME:
                        $renderer->doc .= $renderer->externalmedia($src, $title, $align, $width, $height, $cache, $linking, true);
                        break;
                    default:
                        LogUtility::msg("The dokuwiki media type ($type) is unknown");
                        break;
                }

                return true;

            case "metadata":

                /**
                 * Keep track of the metadata
                 * @var Doku_Renderer_metadata $renderer
                 */
                self::registerImageMeta($attributes, $renderer);
                return true;

        }
        // unsupported $mode
        return false;
    }

    /**
     * @param array $attributes
     * @param Doku_Renderer_metadata $renderer
     */
    static public function registerImageMeta($attributes, $renderer)
    {
        $type = $attributes[MediaLink::MEDIA_DOKUWIKI_TYPE];
        $src = $attributes['src'];
        if($src==null){
            $src = $attributes[DokuPath::PATH_ATTRIBUTE];
        }
        $title = $attributes['title'];
        $align = $attributes['align'];
        $width = $attributes['width'];
        $height = $attributes['height'];
        $cache = $attributes['cache']; // Cache: https://www.dokuwiki.org/images#caching
        $linking = $attributes['linking'];

        switch ($type) {
            case MediaLink::INTERNAL_MEDIA_CALL_NAME:
                $renderer->internalmedia($src, $title, $align, $width, $height, $cache, $linking);
                break;
            case MediaLink::EXTERNAL_MEDIA_CALL_NAME:
                $renderer->externalmedia($src, $title, $align, $width, $height, $cache, $linking);
                break;
            default:
                LogUtility::msg("The dokuwiki media type ($type)  for metadata registration is unknown");
                break;
        }

    }


}

