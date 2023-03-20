<?php

namespace ComboStrap;

use ComboStrap\Meta\Field\FeaturedImage;
use Exception;
use Mpdf\Gif\Image;
use syntax_plugin_combo_iterator;


class PageImageTag
{

    public const CANONICAL = PageImageTag::TAG;
    public const VIGNETTE_TYPE = "vignette";
    public const DEFAULT_ATTRIBUTE = "default";
    public const MARKUP = "page-image";
    public const LOGO_TYPE = "logo";
    public const DEFAULT_ORDER = [
        PageImageTag::FEATURED,
        PageImageTag::FIRST_TYPE,
        PageImageTag::ANCESTOR_TYPE,
        PageImageTag::VIGNETTE_TYPE,
        PageImageTag::LOGO_TYPE
    ];
    public const ORDER_OF_PREFERENCE = "order";
    public const ANCESTOR_TYPE = "ancestor";
    public const TAG = "pageimage";
    public const FEATURED = "featured";
    public const NONE_TYPE = "none";
    public const FIRST_TYPE = "first";

    public const TYPES = [
        PageImageTag::FEATURED,
        PageImageTag::FIRST_TYPE,
        PageImageTag::VIGNETTE_TYPE,
        PageImageTag::ANCESTOR_TYPE,
        PageImageTag::LOGO_TYPE
    ];


    /**
     * Because the pageimage can also be used
     * in a template
     *
     * The calculation are done in the {@link syntax_plugin_combo_pageimage::render render function}
     *
     */
    public static function handle($tagAttributes, $handler): array
    {

        /**
         * Page Image Order Calculation
         */
        $type = $tagAttributes->getComponentAttributeValue(TagAttributes::TYPE_KEY, PageImageTag::FEATURED);
        // the type is first
        $orderOfPreference[] = $type;
        // then the default one
        $default = $tagAttributes->getValueAndRemoveIfPresent(PageImageTag::DEFAULT_ATTRIBUTE);
        if ($default === null) {
            $defaultOrderOfPreference = PageImageTag::DEFAULT_ORDER;
        } else {
            $defaultOrderOfPreference = explode("|", $default);
        }
        foreach ($defaultOrderOfPreference as $defaultImageOrder) {
            if ($defaultImageOrder === $type) {
                continue;
            }
            $orderOfPreference[] = $defaultImageOrder;
        }


        /**
         * Context
         */
        $callStack = CallStack::createFromHandler($handler);
        $context = PageImageTag::TAG;
        $parent = $callStack->moveToParent();
        if ($parent !== false) {
            $context = $parent->getTagName();
        }

        return array(
            PluginUtility::ATTRIBUTES => $tagAttributes->toCallStackArray(),
            PluginUtility::CONTEXT => $context,
            PageImageTag::ORDER_OF_PREFERENCE => $orderOfPreference
        );
    }

    public static function render(TagAttributes $tagAttributes, array $data)
    {


        /**
         * Image selection
         */
        $path = syntax_plugin_combo_iterator::getContextPathForComponentThatMayBeInFragment($tagAttributes);
        $contextPage = MarkupPath::createPageFromPathObject($path);

        /**
         * Image Order of precedence
         */
        $order = $data[PageImageTag::ORDER_OF_PREFERENCE];
        $imageFetcher = null;
        foreach ($order as $pageImageProcessing) {
            switch ($pageImageProcessing) {
                case PageImageTag::FEATURED:
                    try {
                        $imagePath = FeaturedImage::createFromResourcePage($contextPage)->getValue();
                    } catch (ExceptionNotFound $e) {
                        // ok
                        continue 2;
                    }
                    try {
                        $imageFetcher = IFetcherLocalImage::createImageFetchFromPath($imagePath);
                    } catch (ExceptionNotExists|ExceptionBadArgument|ExceptionBadSyntax $e) {
                        LogUtility::warning("Error while creating the fetcher for the feature image ($imagePath) and the page ($contextPage). Error: {$e->getMessage()}", self::CANONICAL, $e);
                    }
                    break;
                case PageImageTag::ANCESTOR_TYPE:
                case "parent": // old
                    $parent = $contextPage;
                    while (true) {
                        try {
                            $parent = $parent->getParent();
                        } catch (ExceptionNotFound $e) {
                            break;
                        }
                        try {
                            $imageFetcher = FeaturedImage::createFromResourcePage($contextPage)->getValue();
                        } catch (ExceptionNotFound $e) {
                            continue;
                        }
                        break;
                    }
                    break;
                case PageImageTag::FIRST_TYPE:
                    try {
                        $firstImagePath = FirstRasterImage::createForPage($contextPage)->getValue();
                    } catch (ExceptionNotFound $e) {
                        try {
                            $firstImagePath = FirstSvgImage::createForPage($contextPage)->getValue();
                        } catch (ExceptionNotFound $e) {
                            continue 2;
                        }
                    }
                    try {
                        $imageFetcher = IFetcherLocalImage::createImageFetchFromPath($firstImagePath);
                    } catch (ExceptionBadArgument|ExceptionBadSyntax|ExceptionNotExists $e) {
                        LogUtility::warning("Error while creating the first image handler for the image ($firstImagePath) and the page ($contextPage). Error: {$e->getMessage()}", self::CANONICAL, $e);
                    }
                    break;
                case PageImageTag::VIGNETTE_TYPE:
                    try {
                        $imageFetcher = FetcherVignette::createForPage($contextPage);
                    } catch (ExceptionNotFound|ExceptionBadArgument $e) {
                        LogUtility::warning("Error while creating the vignette for the page ($contextPage). Error: {$e->getMessage()}", self::CANONICAL, $e);
                    }
                    break;
                case PageImageTag::LOGO_TYPE:
                    try {
                        $imageFetcher = FetcherSvg::createSvgFromPath(Site::getLogoAsSvgImage());
                    } catch (ExceptionNotFound $e) {
                        LogUtility::info("No page image could be find for the page ($path)", PageImageTag::CANONICAL);
                    }
                    break;
                case PageImageTag::NONE_TYPE:
                    return false;
                default:
                    LogUtility::error("The image ($pageImageProcessing) is an unknown page image type", PageImageTag::CANONICAL);
                    continue 2;
            }
            if ($imageFetcher !== null) {
                break;
            }
        }

        if ($imageFetcher === null) {
            return false;
        }

        /**
         * Final building
         */
        try {
            $imageFetcher->buildFromTagAttributes($tagAttributes);
        } catch (ExceptionBadArgument|ExceptionBadSyntax|ExceptionCompile $e) {
            LogUtility::error("The image could not be build. Error: {$e->getMessage()}", PageImageTag::CANONICAL, $e);
        }

        /**
         * Svg
         */
        if ($imageFetcher instanceof FetcherSvg) {

            /**
             * This is an illustration image
             * Used by svg to color by default with the primary color for instance
             */
            $imageFetcher->setRequestedType(FetcherSvg::ILLUSTRATION_TYPE);

            /**
             * Zoom applies only to icon not to illustration
             */
            $isIcon = $imageFetcher->isIconStructure();
            if (!$isIcon) {
                $imageFetcher->setRequestedZoom(1);
            } else {
                /**
                 * When the width requested is small, no zoom out
                 */
                try {
                    $width = $imageFetcher->getRequestedWidth();
                    try {
                        $pixelWidth = ConditionalLength::createFromString($width)->toPixelNumber();
                        if ($pixelWidth < 30) {
                            /**
                             * Icon rendering
                             */
                            $imageFetcher->setRequestedZoom(1);
                            $imageFetcher->setRequestedType(FetcherSvg::ICON_TYPE);

                        }
                    } catch (ExceptionCompile $e) {
                        LogUtility::msg("The width value ($width) could not be translated in pixel value. Error: {$e->getMessage()}");
                    }
                } catch (ExceptionNotFound $e) {
                    // no width
                }

            }
        }


        /**
         * Img/Svg Tag
         *
         * Used as an illustration in a card
         * If the image is too small, we allow that it will stretch
         * to take the whole space
         */
        if ($data[PluginUtility::CONTEXT] === CardTag::CARD_TAG) {
            $tagAttributes->addStyleDeclarationIfNotSet("max-width", "100%");
            $tagAttributes->addStyleDeclarationIfNotSet("max-height", "unset");
        }


        $tagAttributes->setType(self::MARKUP);

        try {
            return MediaMarkup::createFromFetcher($imageFetcher)
                ->buildFromTagAttributes($tagAttributes)
                ->toHtml();
        } catch (ExceptionCompile $e) {
            $message = "Error while rendering the page image: {$e->getMessage()}";
            LogUtility::error($message, self::CANONICAL, $e);
            return $message;
        }

    }

    public static function getDefaultAttributes(): array
    {
        return [MediaMarkup::LINKING_KEY => MediaMarkup::LINKING_NOLINK_VALUE];
    }

}
