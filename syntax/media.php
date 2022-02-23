<?php


use ComboStrap\AnalyticsDocument;
use ComboStrap\CallStack;
use ComboStrap\DokuFs;
use ComboStrap\ExceptionComboRuntime;
use ComboStrap\InternetPath;
use ComboStrap\LogUtility;
use ComboStrap\MediaLink;
use ComboStrap\Metadata;
use ComboStrap\PageImages;
use ComboStrap\PagePath;
use ComboStrap\PluginUtility;
use ComboStrap\SvgDocument;
use ComboStrap\TagAttributes;
use ComboStrap\ThirdPartyPlugins;


require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');


/**
 * Media
 *
 * Takes over the {@link \dokuwiki\Parsing\ParserMode\Media media mode}
 * that is processed by {@link Doku_Handler_Parse_Media}
 *
 *
 *
 * It can be a internal / external media
 *
 *
 * See:
 * https://developers.google.com/search/docs/advanced/guidelines/google-images
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
     * Found at {@link \dokuwiki\Parsing\ParserMode\Media}
     */
    const MEDIA_PATTERN = "\{\{(?:[^>\}]|(?:\}[^\}]))+\}\}";

    /**
     * Enable or disable the image
     */
    const CONF_IMAGE_ENABLE = "imageEnable";

    /**
     * Svg Rendering error
     */
    const SVG_RENDERING_ERROR_CLASS = "combo-svg-rendering-error";

    /**
     * An attribute to set the class of the link if any
     */
    const LINK_CLASS_ATTRIBUTE = "link-class";

    public static function registerFirstMedia(Doku_Renderer_metadata $renderer, $src)
    {
        /**
         * {@link Doku_Renderer_metadata::$firstimage} is unfortunately protected
         * and {@link Doku_Renderer_metadata::internalmedia()} does not allow svg as first image
         */
        if (!isset($renderer->meta[PageImages::FIRST_IMAGE_META_RELATION])) {
            $renderer->meta[PageImages::FIRST_IMAGE_META_RELATION] = $src;
        }

    }


    /**
     * @param $attributes
     * @param renderer_plugin_combo_analytics $renderer
     */
    public static function updateStatistics($attributes, renderer_plugin_combo_analytics $renderer)
    {
        $media = MediaLink::createFromCallStackArray($attributes);
        $renderer->stats[AnalyticsDocument::MEDIA_COUNT]++;
        $scheme = $media->getMedia()->getPath()->getScheme();
        switch ($scheme) {
            case DokuFs::SCHEME:
                $renderer->stats[AnalyticsDocument::INTERNAL_MEDIA_COUNT]++;
                if (!$media->getMedia()->exists()) {
                    $renderer->stats[AnalyticsDocument::INTERNAL_BROKEN_MEDIA_COUNT]++;
                }
                break;
            case InternetPath::scheme:
                $renderer->stats[AnalyticsDocument::EXTERNAL_MEDIA_COUNT]++;
                break;
        }
    }


    function getType(): string
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
    function getPType(): string
    {
        /**
         * An image is not a block (it can be inside paragraph)
         */
        return 'normal';
    }

    function getAllowedTypes(): array
    {
        return array('substition', 'formatting', 'disabled');
    }

    /**
     * It should be less than {@link \dokuwiki\Parsing\ParserMode\Media::getSort()}
     * (It was 320 at the time of writing this code)
     * @return int
     *
     */
    function getSort(): int
    {
        return 319;
    }


    function connectTo($mode)
    {
        $enable = $this->getConf(self::CONF_IMAGE_ENABLE, 1);
        if (!$enable) {

            // Inside a card, we need to take over and enable it
            $modes = [
                PluginUtility::getModeFromTag(syntax_plugin_combo_card::TAG),
            ];
            $enable = in_array($mode, $modes);
        }

        if ($enable) {
            if ($mode !== PluginUtility::getModeFromPluginName(ThirdPartyPlugins::IMAGE_MAPPING_NAME)) {
                $this->Lexer->addSpecialPattern(self::MEDIA_PATTERN, $mode, PluginUtility::getModeFromTag($this->getPluginComponent()));
            }
        }
    }


    function handle($match, $state, $pos, Doku_Handler $handler): array
    {

        switch ($state) {


            // As this is a container, this cannot happens but yeah, now, you know
            case DOKU_LEXER_SPECIAL :

                /**
                 * Note: The type of image for a svg (icon/illustration) is dedicated
                 * by its structure or is expressly set on type
                 */
                $media = MediaLink::createFromRenderMatch($match);
                $attributes = $media->toCallStackArray();

                $callStack = CallStack::createFromHandler($handler);

                /**
                 * Parent
                 */
                $parent = $callStack->moveToParent();
                $parentTag = "";
                if (!empty($parent)) {
                    $parentTag = $parent->getTagName();
                    if (in_array($parentTag,
                        [syntax_plugin_combo_link::TAG, syntax_plugin_combo_brand::TAG])) {
                        /**
                         * TODO: should be on the exit tag of the link / brand
                         *   - The image is in a link, we don't want another link to the image
                         *   - In a brand, there is also already a link to the home page, no link to the media
                         */
                        $attributes[MediaLink::LINKING_KEY] = MediaLink::LINKING_NOLINK_VALUE;
                    }
                }

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $attributes,
                    PluginUtility::CONTEXT => $parentTag
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
    function render($format, Doku_Renderer $renderer, $data): bool
    {

        switch ($format) {

            case 'xhtml':

                /** @var Doku_Renderer_xhtml $renderer */
                $attributes = $data[PluginUtility::ATTRIBUTES];
                $mediaLink = MediaLink::createFromCallStackArray($attributes, $renderer->date_at);
                $media = $mediaLink->getMedia();
                if ($media->getPath()->getScheme() == DokuFs::SCHEME) {
                    if ($media->getPath()->getMime()->isImage() || $media->getPath()->getExtension() === "svg") {
                        try {
                            $renderer->doc .= $mediaLink->renderMediaTagWithLink();
                        } catch (RuntimeException $e) {
                            if (PluginUtility::isDevOrTest()) {
                                throw new ExceptionComboRuntime("Media Rendering Error. {$e->getMessage()}", MediaLink::CANONICAL, 0, $e);
                            } else {
                                $errorClass = self::SVG_RENDERING_ERROR_CLASS;
                                $message = "Media ({$media->getPath()}). Error while rendering: {$e->getMessage()}";
                                $renderer->doc .= "<span class=\"text-alert $errorClass\">" . hsc(trim($message)) . "</span>";
                                LogUtility::msg($message, LogUtility::LVL_MSG_ERROR, MediaLink::CANONICAL);
                            }
                        }
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
                if ($cache == null) {
                    // Dokuwiki needs a value
                    // If their is no value it will output it without any value
                    // in the query string.
                    $cache = "cache";
                }
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

            case
            "metadata":

                /**
                 * Keep track of the metadata
                 * @var Doku_Renderer_metadata $renderer
                 */
                $attributes = $data[PluginUtility::ATTRIBUTES];
                self::registerImageMeta($attributes, $renderer);
                return true;

            case renderer_plugin_combo_analytics::RENDERER_FORMAT:

                /**
                 * Special pattern call
                 * @var renderer_plugin_combo_analytics $renderer
                 */
                $attributes = $data[PluginUtility::ATTRIBUTES];
                self::updateStatistics($attributes, $renderer);
                return true;

        }
        // unsupported $mode
        return false;
    }

    /**
     * Update the index for the move plugin
     * and {@link Metadata::FIRST_IMAGE_META_RELATION}
     *
     * @param array $attributes
     * @param Doku_Renderer_metadata $renderer
     */
    static public function registerImageMeta($attributes, $renderer)
    {
        $type = $attributes[MediaLink::MEDIA_DOKUWIKI_TYPE];
        $src = $attributes['src'];
        if ($src == null) {
            $src = $attributes[PagePath::PROPERTY_NAME];
        }
        $title = $attributes['title'];
        $align = $attributes['align'];
        $width = $attributes['width'];
        $height = $attributes['height'];
        $cache = $attributes['cache']; // Cache: https://www.dokuwiki.org/images#caching
        $linking = $attributes['linking'];

        switch ($type) {
            case MediaLink::INTERNAL_MEDIA_CALL_NAME:
                if (substr($src, -4) === ".svg") {
                    self::registerFirstMedia($renderer, $src);
                }
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

