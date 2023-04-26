<?php


use ComboStrap\CallStack;
use ComboStrap\CardTag;
use ComboStrap\Dimension;
use ComboStrap\Display;
use ComboStrap\ExceptionBadArgument;
use ComboStrap\ExceptionBadSyntax;
use ComboStrap\ExceptionCompile;
use ComboStrap\ExceptionNotExists;
use ComboStrap\ExceptionNotFound;
use ComboStrap\ExceptionRuntime;
use ComboStrap\FetcherSvg;
use ComboStrap\FileSystems;
use ComboStrap\FirstRasterImage;
use ComboStrap\FirstSvgIllustration;
use ComboStrap\FeaturedIcon;
use ComboStrap\IFetcherAbs;
use ComboStrap\LogUtility;
use ComboStrap\MarkupRef;
use ComboStrap\MediaLink;
use ComboStrap\MediaMarkup;
use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Mime;
use ComboStrap\Path;
use ComboStrap\PluginUtility;
use ComboStrap\TagAttributes;
use ComboStrap\ThirdPartyPlugins;
use ComboStrap\WikiPath;


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
    const CANONICAL = "media";


    public static function registerFirstImage(Doku_Renderer_metadata $renderer, Path $path)
    {
        /**
         * {@link Doku_Renderer_metadata::$firstimage} is unfortunately protected
         * and {@link Doku_Renderer_metadata::internalmedia()} does not allow svg as first image
         */
        if (!($path instanceof WikiPath)) {
            return;
        }
        if (!FileSystems::exists($path)) {
            return;
        }
        /**
         * Image Id check
         */
        $wikiId = $path->getWikiId();
        if (media_isexternal($wikiId)) {
            // The first image is not a local image
            // Don't set
            return;
        }
        try {
            $mime = $path->getMime();
        } catch (ExceptionNotFound $e) {
            LogUtility::internalError("The mime for the path ($path) was not found", self::CANONICAL, $e);
            return;
        }
        if (!isset($renderer->meta[FirstRasterImage::PROPERTY_NAME])) {
            if ($mime->isSupportedRasterImage()) {
                $renderer->meta[FirstRasterImage::PROPERTY_NAME] = $wikiId;
                return;
            }
        }
        if (!isset($renderer->meta[FirstSvgIllustration::PROPERTY_NAME]) || !isset($renderer->meta[FeaturedIcon::FIRST_ICON_PARSED])) {
            if ($mime->toString() === Mime::SVG) {
                try {
                    $isIcon = FetcherSvg::createSvgFromPath(WikiPath::createMediaPathFromId($wikiId))
                        ->isIconStructure();
                } catch (Exception $e) {
                    return;
                }
                if (!$isIcon) {
                    $renderer->meta[FirstSvgIllustration::PROPERTY_NAME] = $wikiId;
                } else {
                    $renderer->meta[FeaturedIcon::FIRST_ICON_PARSED] = $wikiId;
                }
            }
        }

    }


    /**
     * @param $attributes
     * @param renderer_plugin_combo_analytics $renderer
     */
    public static function updateStatistics($attributes, renderer_plugin_combo_analytics $renderer)
    {
        $markupUrlString = $attributes[MarkupRef::REF_ATTRIBUTE];
        $actualMediaCount = $renderer->stats[renderer_plugin_combo_analytics::MEDIA_COUNT] ?? 0;
        $renderer->stats[renderer_plugin_combo_analytics::MEDIA_COUNT] = $actualMediaCount + 1;
        try {
            $markupUrl = MediaMarkup::createFromRef($markupUrlString);
        } catch (ExceptionBadArgument|ExceptionBadSyntax|ExceptionNotFound $e) {
            LogUtility::error("media update statistics: cannot create the media markup", "media", $e);
            return;
        }
        switch ($markupUrl->getInternalExternalType()) {
            case MediaMarkup::INTERNAL_MEDIA_CALL_NAME:
                $actualInternalMediaCount = $renderer->stats[renderer_plugin_combo_analytics::INTERNAL_MEDIA_COUNT] ?? 0;
                $renderer->stats[renderer_plugin_combo_analytics::INTERNAL_MEDIA_COUNT] = $actualInternalMediaCount + 1;
                try {
                    $path = $markupUrl->getPath();
                } catch (ExceptionNotFound $e) {
                    LogUtility::internalError("The path of an internal media should be known. We were unable to update the statistics.", self::TAG);
                    return;
                }
                if (!FileSystems::exists($path)) {
                    $brokenMediaCount = $renderer->stats[renderer_plugin_combo_analytics::INTERNAL_BROKEN_MEDIA_COUNT] ?? 0;
                    $renderer->stats[renderer_plugin_combo_analytics::INTERNAL_BROKEN_MEDIA_COUNT] = $brokenMediaCount + 1;
                }
                break;
            case MediaMarkup::EXTERNAL_MEDIA_CALL_NAME:
                $mediaCount = $renderer->stats[renderer_plugin_combo_analytics::EXTERNAL_MEDIA_COUNT] ?? 0;
                $renderer->stats[renderer_plugin_combo_analytics::EXTERNAL_MEDIA_COUNT] = $mediaCount + 1;
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
                PluginUtility::getModeFromTag(CardTag::CARD_TAG),
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
                $mediaMarkup = MediaMarkup::createFromMarkup($match);
            } catch (ExceptionCompile $e) {
                $message = "The media ($match) could not be parsed. Error: {$e->getMessage()}";
                // to get the trace on test run
                LogUtility::error($message, self::TAG, $e);
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

            $callStackArray = $mediaMarkup->toCallStackArray();
            return array(
                PluginUtility::STATE => $state,
                PluginUtility::ATTRIBUTES => $callStackArray,
                PluginUtility::CONTEXT => $parentTag,
                PluginUtility::TAG => MediaMarkup::TAG
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
     */
    function render($format, Doku_Renderer $renderer, $data): bool
    {

        switch ($format) {

            case 'xhtml':
                /**
                 * @var Doku_Renderer_xhtml $renderer
                 */
                $renderer->doc .= MediaMarkup::renderSpecial($data, $renderer);
                return true;

            case "metadata":

                /**
                 * @var Doku_Renderer_metadata $renderer
                 */
                MediaMarkup::metadata($data, $renderer);
                return true;

            case renderer_plugin_combo_analytics::RENDERER_FORMAT:

                /**
                 * @var renderer_plugin_combo_analytics $renderer
                 */
                MediaMarkup::analytics($data, $renderer);
                return true;

        }
        // unsupported $mode
        return false;
    }

    /**
     * Update the index for the move plugin
     * and {@link Metadata::FIRST_IMAGE_META_RELATION}
     * @param array $attributes
     * @param Doku_Renderer_metadata $renderer
     */
    static public function registerImageMeta(array $attributes, Doku_Renderer_metadata $renderer)
    {
        try {
            $mediaMarkup = MediaMarkup::createFromCallStackArray($attributes);
        } catch (ExceptionNotFound|ExceptionBadArgument|ExceptionBadSyntax $e) {
            LogUtility::internalError("We can't register the media metadata. Error: {$e->getMessage()}");
            return;
        } catch (ExceptionNotExists $e) {
            return;
        }
        try {
            $label = $mediaMarkup->getLabel();
        } catch (ExceptionNotFound $e) {
            $label = "";
        }
        $internalExternalType = $mediaMarkup->getInternalExternalType();
        try {
            $src = $mediaMarkup->getSrc();
        } catch (ExceptionNotFound $e) {
            LogUtility::internalError("No src found, we couldn't register the media in the index. Error: {$e->getMessage()}", self::CANONICAL, $e);
            return;
        }
        switch ($internalExternalType) {
            case MediaMarkup::INTERNAL_MEDIA_CALL_NAME:
                try {
                    $path = $mediaMarkup->getMarkupRef()->getPath();
                } catch (ExceptionNotFound $e) {
                    LogUtility::internalError("We cannot get the path of the image. Error: {$e->getMessage()}. The image was not registered in the metadata", self::TAG);
                    return;
                }
                self::registerFirstImage($renderer, $path);
                $renderer->internalmedia($src, $label);
                break;
            case MediaMarkup::EXTERNAL_MEDIA_CALL_NAME:
                $renderer->externalmedia($src, $label);
                break;
            default:
                LogUtility::msg("The dokuwiki media type ($internalExternalType) for metadata registration is unknown");
                break;
        }

    }


}

