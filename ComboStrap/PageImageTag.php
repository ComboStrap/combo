<?php

namespace ComboStrap;

use ComboStrap\Meta\Field\AncestorImage;
use ComboStrap\Meta\Field\FeaturedImage;
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
        PageImageTag::ICON_TYPE,
        PageImageTag::VIGNETTE_TYPE,
        PageImageTag::LOGO_TYPE
    ];
    public const ANCESTOR_TYPE = "ancestor";
    public const TAG = "pageimage";
    public const FEATURED = "featured";
    public const ICON_TYPE = "icon";
    public const NONE_TYPE = "none";
    public const FIRST_TYPE = "first";

    public const TYPES = [
        PageImageTag::FEATURED,
        PageImageTag::FIRST_TYPE,
        PageImageTag::VIGNETTE_TYPE,
        PageImageTag::ANCESTOR_TYPE,
        PageImageTag::LOGO_TYPE,
        PageImageTag::ICON_TYPE
    ];
    const DEFAULT_ZOOM = -4;


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
            PluginUtility::CONTEXT => $context
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
         * Zoom applied only to icon
         * but we get and **delete** it
         * because it's not a standard html attribute
         */
        $zoom = $tagAttributes->getValueAndRemoveIfPresent(Dimension::ZOOM_ATTRIBUTE, self::DEFAULT_ZOOM);

        /**
         * Image Order of precedence
         */
        $order = self::getOrderOfPreference($tagAttributes);
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
                    try {
                        $ancestor = AncestorImage::createFromResourcePage($contextPage)->getValue();
                    } catch (ExceptionNotFound $e) {
                        continue 2;
                    }
                    try {
                        $imageFetcher = IFetcherLocalImage::createImageFetchFromPath($ancestor);
                    } catch (ExceptionBadArgument|ExceptionBadSyntax|ExceptionNotExists $e) {
                        LogUtility::warning("Error while creating the ancestor image handler for the image ($ancestor) and the page ($contextPage). Error: {$e->getMessage()}", self::CANONICAL, $e);
                    }
                    break;
                case PageImageTag::ICON_TYPE:
                    try {
                        $icon = FeaturedIcon::createForPage($contextPage)->getValueOrDefault();
                    } catch (ExceptionNotFound $e) {
                        continue 2;
                    }

                    $width = $tagAttributes->getValueAndRemoveIfPresent(Dimension::WIDTH_KEY);
                    $height = $tagAttributes->getValueAndRemoveIfPresent(Dimension::HEIGHT_KEY);
                    $ratio = $tagAttributes->getValueAndRemoveIfPresent(Dimension::RATIO_ATTRIBUTE);
                    if ($width === null && $height !== null && $ratio === null) {
                        $width = $height;
                    }
                    if ($width !== null && $height !== null && $ratio === null) {
                        $height = $width;
                    }
                    $imageFetcher = FetcherSvg::createSvgFromPath($icon)
                        ->setRequestedZoom($zoom);

                    if ($ratio !== null) {
                        try {
                            $imageFetcher->setRequestedAspectRatio($ratio);
                        } catch (ExceptionBadSyntax $e) {
                            LogUtility::error("The ratio value ($ratio) is not a valid ratio for the icon image ($icon)");
                        }
                    }
                    if ($width !== null) {
                        $imageFetcher->setRequestedWidth($width);
                    }
                    if ($height !== null) {
                        $imageFetcher->setRequestedHeight($height);
                    }

                    break;
                case PageImageTag::FIRST_TYPE:

                    try {
                        $firstImagePath = FirstImage::createForPage($contextPage)->getValue();
                    } catch (ExceptionNotFound $e) {
                        continue 2;
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
             *
             * (Note that because we use icon as page-image type,
             * the buildFromTagAttributes would have set it to icon)
             */
            $imageFetcher->setRequestedType(FetcherSvg::ILLUSTRATION_TYPE);

            /**
             * When the width requested is small, no zoom out
             */
            try {
                $requestedWidth = $imageFetcher->getRequestedWidth();
                try {
                    $pixelWidth = ConditionalLength::createFromString($requestedWidth)->toPixelNumber();
                    if ($pixelWidth < 30) {
                        /**
                         * Icon rendering
                         */
                        $imageFetcher
                            ->setRequestedZoom(1)
                            ->setRequestedType(FetcherSvg::ICON_TYPE);

                    }
                } catch (ExceptionCompile $e) {
                    LogUtility::msg("The width value ($requestedWidth) could not be translated in pixel value. Error: {$e->getMessage()}");
                }
            } catch (ExceptionNotFound $e) {
                // no width
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

    private static function getOrderOfPreference(TagAttributes $tagAttributes): array
    {
        // the type is first
        $type = $tagAttributes->getType();
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
        return $orderOfPreference;
    }

}
