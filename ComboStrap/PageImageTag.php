<?php

namespace ComboStrap;

use Exception;
use syntax_plugin_combo_card;

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
        PageImageTag::DESCENDANT_TYPE,
        PageImageTag::VIGNETTE_TYPE,
        PageImageTag::LOGO_TYPE
    ];
    public const ORDER_OF_PREFERENCE = "order";
    public const DESCENDANT_TYPE = "descendant";
    public const TAG = "pageimage";
    public const META_TYPE = "meta";
    public const NONE_TYPE = "none";
    public const FIRST_TYPE = "first";

    public const TYPES = [
        PageImageTag::META_TYPE,
        PageImageTag::FIRST_TYPE,
        PageImageTag::VIGNETTE_TYPE,
        PageImageTag::DESCENDANT_TYPE,
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
        $type = $tagAttributes->getComponentAttributeValue(TagAttributes::TYPE_KEY, PageImageTag::META_TYPE);
        // the type is first
        $orderOfPreference[] = $type;
        // then the default one
        $default = $tagAttributes->getValueAndRemoveIfPresent(PageImageTag::DEFAULT_ATTRIBUTE);
        if ($default === null) {
            $defaultOrderOfPrecedence = PageImageTag::DEFAULT_ORDER;
        } else {
            $defaultOrderOfPrecedence = explode("|", $default);
        }
        foreach ($defaultOrderOfPrecedence as $defaultImageOrder) {
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


        $path = $tagAttributes->getValueAndRemove(PagePath::PROPERTY_NAME);
        if ($path === null) {
            $contextManager = ContextManager::getOrCreate();
            $path = $contextManager->getAttribute(PagePath::PROPERTY_NAME);
            if ($path === null) {
                // It should never happen, dev error
                LogUtility::error("Internal Error: Bad state: page image cannot retrieve the page path from the context", PageImageTag::CANONICAL);
                return false;
            }
        }

        /**
         * Image selection
         */
        WikiPath::addRootSeparatorIfNotPresent($path);
        $page = MarkupPath::createPageFromQualifiedId($path);

        /**
         * Image Order of precedence
         */
        $order = $data[PageImageTag::ORDER_OF_PREFERENCE];
        $imageFetcher = null;
        foreach ($order as $pageImageProcessing) {
            switch ($pageImageProcessing) {
                case PageImageTag::META_TYPE:
                    try {
                        $imageFetcher = self::selectAndGetBestMetadataPageImageFetcherForRatio($page, $tagAttributes);
                    } catch (ExceptionNotFound $e) {
                        // ok
                    }
                    break;
                case PageImageTag::DESCENDANT_TYPE:
                case "parent": // old
                    $parent = $page;
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
                        $imageFetcher = FirstImage::createForPage($page)
                            ->getLocalImageFetcher();
                    } catch (ExceptionNotFound $e) {
                        continue 2;
                    }
                    break;
                case PageImageTag::VIGNETTE_TYPE:

                    try {
                        $imageFetcher = FetcherVignette::createForPage($page);
                    } catch (ExceptionNotFound|ExceptionBadArgument $e) {
                        LogUtility::error("Error while creating the vignette for the page ($page). Error: {$e->getMessage()}");
                    }
                    break;
                case PageImageTag::LOGO_TYPE:
                    try {
                        $imageFetcher = FetcherSvg::createSvgFromPath(Site::getLogoAsSvgImage());
                    } catch (ExceptionNotFound $e) {
                        LogUtility::msg("No page image could be find for the page ($path)", LogUtility::LVL_MSG_INFO, PageImageTag::CANONICAL);
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
            LogUtility::error("The image could not be build. Error: {$e->getMessage()}", PageImageTag::CANONICAL);
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
        if ($data[PluginUtility::CONTEXT] === syntax_plugin_combo_card::TAG) {
            $tagAttributes->addStyleDeclarationIfNotSet("max-width", "100%");
            $tagAttributes->addStyleDeclarationIfNotSet("max-height", "unset");
        }


        $tagAttributes->setType(self::MARKUP);

        try {
            return MediaMarkup::createFromFetcher($imageFetcher)
                ->setHtmlOrSetterTagAttributes($tagAttributes)
                ->toHtml();
        } catch (ExceptionCompile $e) {
            return "Error while rendering the page image: {$e->getMessage()}";
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
}
