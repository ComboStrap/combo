<?php


use ComboStrap\Align;
use ComboStrap\AnalyticsDocument;
use ComboStrap\CallStack;
use ComboStrap\Dimension;
use ComboStrap\DokuFs;
use ComboStrap\DokuPath;
use ComboStrap\MarkupUrl;
use ComboStrap\ExceptionNotFound;
use ComboStrap\ExceptionRuntime;
use ComboStrap\FetchAbs;
use ComboStrap\FileSystems;
use ComboStrap\FirstImage;
use ComboStrap\FloatAttribute;
use ComboStrap\InternetPath;
use ComboStrap\LogUtility;
use ComboStrap\MediaLink;
use ComboStrap\Metadata;
use ComboStrap\PageImages;
use ComboStrap\PagePath;
use ComboStrap\PluginUtility;
use ComboStrap\Site;
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


    /**
     * @param $match - the match of the renderer (just a shortcut)
     */
    public static function parseMediaMatch($match): array
    {


        /**
         *   * Delete the opening and closing character
         *   * create the url and description
         */
        $match = preg_replace(array('/^{{/', '/}}$/u'), '', $match);
        $parts = explode('|', $match, 2);
        $label = null;
        $markupUrl = $parts[0];
        if (isset($parts[1])) {
            $label = $parts[1];
        }


        /**
         * Media Alignment
         */

        $rightAlign = (bool)preg_match('/^ /', $markupUrl);
        $leftAlign = (bool)preg_match('/ $/', $markupUrl);
        $align = null;
        // Logic = what's that ;)...
        if ($leftAlign & $rightAlign) {
            $align = 'center';
        } else if ($rightAlign) {
            $align = 'right';
        } else if ($leftAlign) {
            $align = 'left';
        }


        return [$markupUrl, $label, $align];


    }

    public static function registerFirstImage(Doku_Renderer_metadata $renderer, $id)
    {
        /**
         * {@link Doku_Renderer_metadata::$firstimage} is unfortunately protected
         * and {@link Doku_Renderer_metadata::internalmedia()} does not allow svg as first image
         */
        if (!isset($renderer->meta[FirstImage::FIRST_IMAGE_META_RELATION])) {
            $path = DokuPath::createMediaPathFromId($id);
            if ($path->getMime()->isImage()) {
                $renderer->meta[FirstImage::FIRST_IMAGE_META_RELATION] = $id;
            }
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
        $scheme = $media->getPath()->getScheme();
        switch ($scheme) {
            case DokuFs::SCHEME:
                $renderer->stats[AnalyticsDocument::INTERNAL_MEDIA_COUNT]++;
                if (!FileSystems::exists($media->getPath())) {
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
                 * @var string $markupUrl
                 * @var string $label
                 */
                [$markupUrl, $label, $align] = self::parseMediaMatch($match);


                /**
                 * We store linking as attribute (to make it possible to change the linking by other plugin)
                 * (ie no linking in heading , ...)
                 */
                $attributes[MarkupUrl::LINKING_KEY] = null;
                $attributes[MarkupUrl::DOKUWIKI_URL_ATTRIBUTE] = $markupUrl;
                $attributes[Align::ALIGN_ATTRIBUTE] = $align;
                $attributes[TagAttributes::TITLE_KEY] = $label;

                /**
                 * Parent
                 */
                $callStack = CallStack::createFromHandler($handler);
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
                        $attributes[MarkupUrl::LINKING_KEY] = MarkupUrl::LINKING_NOLINK_VALUE;
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
                $tagAttributes = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES]);

                /**
                 * The Dokuwiki Url
                 */
                $dokuWikiUrlString = $tagAttributes->getValueAndRemove(MarkupUrl::DOKUWIKI_URL_ATTRIBUTE);
                if ($dokuWikiUrlString === null) {
                    $renderer->doc .= "Internal Error: The media url was not found";
                    return false;
                }
                $dokuwikiUrl = MarkupUrl::createFromUrl($dokuWikiUrlString);

                /**
                 * Linking
                 */
                $linking = $tagAttributes->getValue(MarkupUrl::LINKING_KEY);
                if ($linking === null) {
                    try {
                        $linking = $dokuwikiUrl->toFetchUrl()->getQueryPropertyValueAndRemoveIfPresent(MarkupUrl::LINKING_KEY);
                        $tagAttributes->addComponentAttributeValue(MarkupUrl::LINKING_KEY, $linking);
                    } catch (ExceptionNotFound $e) {
                        // ok
                    }
                }

                /**
                 * Align on the url has precedence
                 * if present
                 */
                try {
                    $align = $dokuwikiUrl->toFetchUrl()->getQueryPropertyValueAndRemoveIfPresent(Align::ALIGN_ATTRIBUTE);
                    $tagAttributes->addComponentAttributeValue(Align::ALIGN_ATTRIBUTE, $align);
                } catch (ExceptionNotFound $e) {
                    // ok
                }
                if ($dokuwikiUrl->getMediaType() === MarkupUrl::INTERNAL_MEDIA_CALL_NAME) {
                    /**
                     * If this is an internal media,
                     * we are using our implementation
                     * and we have a change on attribute specification
                     *
                     * The align attribute on an image parse
                     * is a float right
                     * ComboStrap does a difference between a block right and a float right
                     */
                    if ($tagAttributes->getComponentAttributeValue(Align::ALIGN_ATTRIBUTE) === "right") {
                        $tagAttributes->removeComponentAttribute(Align::ALIGN_ATTRIBUTE);
                        $tagAttributes->addComponentAttributeValue(FloatAttribute::FLOAT_KEY, "right");
                    }

                }

                $mediaLink = MediaLink::createMediaLinkFromPath($dokuwikiUrl->toFetchUrl(), $tagAttributes, $renderer->date_at);
                $media = $mediaLink->getPath();
                if ($media->getPath()->getScheme() === DokuFs::SCHEME) {
                    try {
                        $isImage = FileSystems::getMime($media->getPath())->isImage();
                    } catch (ExceptionNotFound $e) {
                        $isImage = false;
                    }
                    if ($isImage) {
                        try {
                            $renderer->doc .= $mediaLink->renderMediaTagWithLink();
                        } catch (ExceptionNotFound $e) {
                            if (PluginUtility::isDevOrTest()) {
                                throw new ExceptionRuntime("Media Rendering Error. {$e->getMessage()}", MediaLink::CANONICAL, 0, $e);
                            } else {
                                $errorClass = self::SVG_RENDERING_ERROR_CLASS;
                                $message = "Media ({$media->getPath()}). Error while rendering: {$e->getMessage()}";
                                $renderer->doc .= "<span class=\"text-danger $errorClass\">" . hsc(trim($message)) . "</span>";
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
                $mediaType = $dokuwikiUrl->getMediaType();
                $src = $dokuwikiUrl->getSrc();
                $title = $tagAttributes->getComponentAttributeValue(TagAttributes::TITLE_KEY);
                $align = $tagAttributes->getComponentAttributeValue(Align::ALIGN_ATTRIBUTE);
                try {
                    $width = $dokuwikiUrl->toFetchUrl()->getQueryPropertyValue(Dimension::WIDTH_KEY);
                } catch (ExceptionNotFound $e) {
                    $width = null;
                }
                try {
                    $height = $dokuwikiUrl->toFetchUrl()->getQueryPropertyValue(Dimension::HEIGHT_KEY);
                } catch (ExceptionNotFound $e) {
                    $height = null;
                }
                try {
                    $cache = $height = $dokuwikiUrl->toFetchUrl()->getQueryPropertyValue(FetchAbs::CACHE_KEY);
                } catch (ExceptionNotFound $e) {
                    // Dokuwiki needs a value
                    // If their is no value it will output it without any value
                    // in the query string.
                    $cache = "cache";
                }
                switch ($mediaType) {
                    case MarkupUrl::INTERNAL_MEDIA_CALL_NAME:
                        $renderer->doc .= $renderer->internalmedia($src, $title, $align, $width, $height, $cache, $linking, true);
                        break;
                    case MarkupUrl::EXTERNAL_MEDIA_CALL_NAME:
                        $renderer->doc .= $renderer->externalmedia($src, $title, $align, $width, $height, $cache, $linking, true);
                        break;
                    default:
                        LogUtility::msg("The dokuwiki media type ($mediaType) is unknown");
                        break;
                }
                return true;


            case "metadata":

                /**
                 * Keep track of the metadata
                 * @var Doku_Renderer_metadata $renderer
                 */
                $tagAttributes = $data[PluginUtility::ATTRIBUTES];
                self::registerImageMeta($tagAttributes, $renderer);
                return true;

            case renderer_plugin_combo_analytics::RENDERER_FORMAT:

                /**
                 * Special pattern call
                 * @var renderer_plugin_combo_analytics $renderer
                 */
                $tagAttributes = $data[PluginUtility::ATTRIBUTES];
                self::updateStatistics($tagAttributes, $renderer);
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
        $src = $attributes[MarkupUrl::DOKUWIKI_SRC];
        if ($src === null) {
            $src = $attributes[MarkupUrl::DOKUWIKI_URL_ATTRIBUTE];
        }
        $dokuwikiUrl = MarkupUrl::createFromUrl($src);
        $title = $attributes['title'];

        $mediaType = $dokuwikiUrl->getMediaType();
        switch ($mediaType) {
            case MarkupUrl::INTERNAL_MEDIA_CALL_NAME:
                try {
                    self::registerFirstImage($renderer, $dokuwikiUrl->toFetchUrl()->getPath());
                } catch (ExceptionNotFound $e) {
                    LogUtility::internalError("The path should be present on an internal image");
                }
                $renderer->internalmedia($src, $title);
                break;
            case MarkupUrl::EXTERNAL_MEDIA_CALL_NAME:
                $renderer->externalmedia($src, $title);
                break;
            default:
                LogUtility::msg("The dokuwiki media type ($mediaType) for metadata registration is unknown");
                break;
        }

    }


}

