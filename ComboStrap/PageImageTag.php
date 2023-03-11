<?php

namespace ComboStrap;

use Exception;
use Mpdf\Gif\Image;


class PageImageTag
{

    public const CANONICAL = PageImageTag::TAG;
    public const VIGNETTE_TYPE = "vignette";
    public const DEFAULT_ATTRIBUTE = "default";
    public const MARKUP = "page-image";
    public const LOGO_TYPE = "logo";
    public const DEFAULT_ORDER = [
        PageImageTag::META_TYPE,
        PageImageTag::FIRST_TYPE,
        PageImageTag::ANCESTOR_TYPE,
        PageImageTag::VIGNETTE_TYPE,
        PageImageTag::LOGO_TYPE
    ];
    public const ORDER_OF_PREFERENCE = "order";
    public const ANCESTOR_TYPE = "ancestor";
    public const TAG = "pageimage";
    public const META_TYPE = "meta";
    public const NONE_TYPE = "none";
    public const FIRST_TYPE = "first";

    public const TYPES = [
        PageImageTag::META_TYPE,
        PageImageTag::FIRST_TYPE,
        PageImageTag::VIGNETTE_TYPE,
        PageImageTag::ANCESTOR_TYPE,
        PageImageTag::LOGO_TYPE
    ];
    const PATH_ATTRIBUTE = "path";


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
        $type = $tagAttributes->getComponentAttributeValue(TagAttributes::TYPE_KEY, PageImageTag::META_TYPE);
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
        $path = self::getContextPath($tagAttributes);
        $contextPage = MarkupPath::createPageFromPathObject($path);

        /**
         * Image Order of precedence
         */
        $order = $data[PageImageTag::ORDER_OF_PREFERENCE];
        $imageFetcher = null;
        foreach ($order as $pageImageProcessing) {
            switch ($pageImageProcessing) {
                case PageImageTag::META_TYPE:
                    try {
                        $imageFetcher = self::selectAndGetBestMetadataPageImageFetcherForRatio($contextPage, $tagAttributes);
                    } catch (ExceptionNotFound $e) {
                        // ok
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
                            $imageFetcher = self::selectAndGetBestMetadataPageImageFetcherForRatio($parent, $tagAttributes);
                        } catch (ExceptionNotFound $e) {
                            try {
                                $imageFetcher = FirstImage::createForPage($parent)
                                    ->getLocalImageFetcher();
                            } catch (ExceptionNotFound $e) {
                                continue;
                            }
                        }
                        break;
                    }
                    break;
                case PageImageTag::FIRST_TYPE:
                    try {
                        $imageFetcher = FirstImage::createForPage($contextPage)
                            ->getLocalImageFetcher();
                    } catch (ExceptionNotFound $e) {
                        continue 2;
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
            $isIcon = IconDownloader::isInIconDirectory($imageFetcher->getSourcePath());
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

    /**
     * @throws ExceptionNotFound - if the page was not found
     */
    private static function selectAndGetBestMetadataPageImageFetcherForRatio(MarkupPath $page, TagAttributes $tagAttributes): IFetcherLocalImage
    {
        /**
         * Take the image and the page images
         * of the first page with an image
         */
        $selectedPageImage = IFetcherLocalImage::createImageFetchFromPageImageMetadata($page);
        $stringRatio = $tagAttributes->getValue(Dimension::RATIO_ATTRIBUTE);
        if ($stringRatio === null) {
            return $selectedPageImage;
        }

        /**
         * We select the best image for the ratio
         * Best ratio
         */
        $bestRatioDistance = 9999;
        try {
            $targetRatio = Dimension::convertTextualRatioToNumber($stringRatio);
        } catch (ExceptionBadSyntax $e) {
            LogUtility::error("The ratio ($stringRatio) is not a valid ratio. Error: {$e->getMessage()}", PageImageTag::CANONICAL);
            return $selectedPageImage;
        }

        $pageImages = $page->getPageMetadataImages();
        foreach ($pageImages as $pageImage) {
            $path = $pageImage->getImagePath();
            try {
                $fetcherImage = IFetcherLocalImage::createImageFetchFromPath($path);
            } catch (Exception $e) {
                LogUtility::msg("An image object could not be build from ($path). Is it an image file ?. Error: {$e->getMessage()}");
                continue;
            }
            $ratioDistance = $targetRatio - $fetcherImage->getIntrinsicAspectRatio();
            if ($ratioDistance < $bestRatioDistance) {
                $bestRatioDistance = $ratioDistance;
                $selectedPageImage = $fetcherImage;
            }
        }
        return $selectedPageImage;
    }

    public static function getDefaultAttributes(): array
    {
        return [MediaMarkup::LINKING_KEY => MediaMarkup::LINKING_NOLINK_VALUE];
    }

    private static function getContextPath(TagAttributes $tagAttributes): WikiPath
    {
        $pathString = $tagAttributes->getComponentAttributeValueAndRemoveIfPresent(self::PATH_ATTRIBUTE);
        if ($pathString != null) {
            try {
                return WikiPath::createMarkupPathFromPath($pathString);
            } catch (ExceptionBadArgument $e) {
                LogUtility::warning("Error while creating the path for the page image with the path value ($pathString)", self::CANONICAL, $e);
            }
        }

        $executionContext = ExecutionContext::getActualOrCreateFromEnv();

        try {
            $markupHandler = $executionContext->getExecutingMarkupHandler();
            $contextData = $markupHandler
                ->getContextData();
            $path = $contextData[PagePath::PROPERTY_NAME];
            if ($path !== null) {
                try {
                    return WikiPath::createMarkupPathFromPath($path);
                } catch (ExceptionBadArgument $e) {
                    LogUtility::internalError("The path string should be absolute, we should not get this error", self::CANONICAL, $e);
                }
            }
            return $markupHandler->getRequestedContextPath();
        } catch (ExceptionNotFound $e) {
            // no markup handler
        }
        return $executionContext->getContextPath();

    }
}
