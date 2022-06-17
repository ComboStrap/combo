<?php


use ComboStrap\AnalyticsDocument;
use ComboStrap\CallStack;
use ComboStrap\Dimension;
use ComboStrap\DokuPath;
use ComboStrap\ExceptionBadArgument;
use ComboStrap\ExceptionBadSyntax;
use ComboStrap\ExceptionCompile;
use ComboStrap\ExceptionNotFound;
use ComboStrap\ExceptionRuntime;
use ComboStrap\FetchAbs;
use ComboStrap\FileSystems;
use ComboStrap\FirstImage;
use ComboStrap\LogUtility;
use ComboStrap\MediaLink;
use ComboStrap\MediaMarkup;
use ComboStrap\Metadata;
use ComboStrap\Path;
use ComboStrap\PluginUtility;
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


    public static function registerFirstImage(Doku_Renderer_metadata $renderer, Path $path)
    {
        /**
         * {@link Doku_Renderer_metadata::$firstimage} is unfortunately protected
         * and {@link Doku_Renderer_metadata::internalmedia()} does not allow svg as first image
         */
        if (!($path instanceof DokuPath)) {
            return;
        }
        if (!FileSystems::exists($path)) {
            return;
        }
        if (!isset($renderer->meta[FirstImage::FIRST_IMAGE_META_RELATION])) {
            if ($path->getMime()->isImage()) {
                $renderer->meta[FirstImage::FIRST_IMAGE_META_RELATION] = $path->getDokuwikiId();
            }
        }

    }


    /**
     * @param $attributes
     * @param renderer_plugin_combo_analytics $renderer
     */
    public static function updateStatistics($attributes, renderer_plugin_combo_analytics $renderer)
    {
        $markupUrlString = $attributes[MediaMarkup::REF_ATTRIBUTE];
        $renderer->stats[AnalyticsDocument::MEDIA_COUNT]++;
        $markupUrl = MediaMarkup::createFromRef($markupUrlString);
        switch ($markupUrl->getInternalExternalType()) {
            case MediaMarkup::INTERNAL_MEDIA_CALL_NAME:
                $renderer->stats[AnalyticsDocument::INTERNAL_MEDIA_COUNT]++;
                try {
                    $path = $markupUrl->getPath();
                } catch (ExceptionNotFound $e) {
                    LogUtility::internalError("The path of an internal media should be known. We were unable to update the statistics.", self::TAG);
                    return;
                }
                if (!FileSystems::exists($path)) {
                    $renderer->stats[AnalyticsDocument::INTERNAL_BROKEN_MEDIA_COUNT]++;
                }
                break;
            case MediaMarkup::EXTERNAL_MEDIA_CALL_NAME:
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

        // As this is a container, this cannot happens but yeah, now, you know
        if ($state == DOKU_LEXER_SPECIAL) {

            try {
                $mediaMarkup = MediaMarkup::createFromMatch($match);
            } catch (ExceptionBadSyntax $e) {
                LogUtility::error("The media ($match) could not be parsed. Error: {$e->getMessage()}");
                return [];
            }

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
                     * TODO: should be on the exit tag of the {@link syntax_plugin_combo_link::handle() link}
                     *   / {@link syntax_plugin_combo_brand::handle()} brand
                     *   - The image is in a link, we don't want another link to the image
                     *   - In a brand, there is also already a link to the home page, no link to the media
                     */
                    $mediaMarkup->setLinking(MediaMarkup::LINKING_NOLINK_VALUE);
                }
            }

            return array(
                PluginUtility::STATE => $state,
                PluginUtility::ATTRIBUTES => $mediaMarkup->toCallStackArray(),
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
                try {
                    $mediaMarkup = MediaMarkup::createFromCallStackArray($data[PluginUtility::ATTRIBUTES]);
                } catch (ExceptionBadArgument $e) {
                    $renderer->doc .= $e->getMessage();
                    return false;
                }

                try {
                    $isImage = FileSystems::getMime($mediaMarkup->getPath())->isImage();
                } catch (ExceptionNotFound $e) {
                    $isImage = false;
                }
                if (
                    $mediaMarkup->getInternalExternalType() === MediaMarkup::INTERNAL_MEDIA_CALL_NAME
                    && $isImage
                ) {
                    try {
                        $renderer->doc .= MediaLink::createFromMediaMarkup($mediaMarkup)->renderMediaTag();
                    } catch (ExceptionCompile $e) {
                        if (PluginUtility::isDevOrTest()) {
                            throw new ExceptionRuntime("Media Rendering Error. {$e->getMessage()}", MediaLink::CANONICAL, 0, $e);
                        } else {
                            $errorClass = self::SVG_RENDERING_ERROR_CLASS;
                            $message = "Media ({$mediaMarkup}). Error while rendering: {$e->getMessage()}";
                            $renderer->doc .= "<span class=\"text-danger $errorClass\">" . hsc(trim($message)) . "</span>";
                            LogUtility::msg($message, LogUtility::LVL_MSG_ERROR, MediaLink::CANONICAL);
                        }
                    }
                    return true;

                }


                /**
                 * This is not an local internal media image (a video or an url image)
                 * Dokuwiki takes over
                 */
                $mediaType = $mediaMarkup->getInternalExternalType();
                $src = $mediaMarkup->getSrc();
                try {
                    $title = $mediaMarkup->getLabel();
                } catch (ExceptionNotFound $e) {
                    $title = null;
                }
                try {
                    $linking = $mediaMarkup->getLinking();
                } catch (ExceptionNotFound $e) {
                    $linking = null;
                }
                try {
                    $align = $mediaMarkup->getAlign();
                } catch (ExceptionNotFound $e) {
                    $align = null;
                }
                try {
                    $width = $mediaMarkup->getFetchUrl()->getQueryPropertyValue(Dimension::WIDTH_KEY);
                } catch (ExceptionNotFound $e) {
                    $width = null;
                }
                try {
                    $height = $mediaMarkup->getFetchUrl()->getQueryPropertyValue(Dimension::HEIGHT_KEY);
                } catch (ExceptionNotFound $e) {
                    $height = null;
                }
                try {
                    $cache = $height = $mediaMarkup->getFetchUrl()->getQueryPropertyValue(FetchAbs::CACHE_KEY);
                } catch (ExceptionNotFound $e) {
                    // Dokuwiki needs a value
                    // If their is no value it will output it without any value
                    // in the query string.
                    $cache = FetchAbs::CACHE_DEFAULT_VALUE;
                }
                switch ($mediaType) {
                    case MediaMarkup::INTERNAL_MEDIA_CALL_NAME:
                        $renderer->doc .= $renderer->internalmedia($src, $title, $align, $width, $height, $cache, $linking, true);
                        break;
                    case MediaMarkup::EXTERNAL_MEDIA_CALL_NAME:
                        $renderer->doc .= $renderer->externalmedia($src, $title, $align, $width, $height, $cache, $linking, true);
                        break;
                    default:
                        LogUtility::msg("The dokuwiki media type ($mediaType) is unknown");
                        break;
                }
                return true;


            case
            "metadata":

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
    static public function registerImageMeta(array $attributes, Doku_Renderer_metadata $renderer)
    {
        try {
            $mediaMarkup = MediaMarkup::createFromCallStackArray($attributes);
        } catch (ExceptionBadArgument $e) {
            LogUtility::internalError("We can't register the media metadata. Error: {$e->getMessage()}");
            return;
        }
        try {
            $label = $mediaMarkup->getLabel();
        } catch (ExceptionNotFound $e) {
            $label = "";
        }
        $internalExternalType = $mediaMarkup->getInternalExternalType();
        switch ($internalExternalType) {
            case MediaMarkup::INTERNAL_MEDIA_CALL_NAME:
                try {
                    $path = $mediaMarkup->getMarkupRef()->getPath();
                } catch (ExceptionNotFound $e) {
                    LogUtility::internalError("We cannot get the path of the image. Error: {$e->getMessage()}. The image was not registered in the metadata", self::TAG);
                    return;
                }
                self::registerFirstImage($renderer, $path);
                $renderer->internalmedia($mediaMarkup->getSrc(), $label);
                break;
            case MediaMarkup::EXTERNAL_MEDIA_CALL_NAME:
                $renderer->externalmedia($mediaMarkup->getSrc(), $label);
                break;
            default:
                LogUtility::msg("The dokuwiki media type ($internalExternalType) for metadata registration is unknown");
                break;
        }

    }


}

