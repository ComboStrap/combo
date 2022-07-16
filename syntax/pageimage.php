<?php


use ComboStrap\CallStack;
use ComboStrap\ConditionalLength;
use ComboStrap\ContextManager;
use ComboStrap\Dimension;
use ComboStrap\WikiPath;
use ComboStrap\ExceptionBadArgument;
use ComboStrap\ExceptionBadSyntax;
use ComboStrap\ExceptionCompile;
use ComboStrap\ExceptionNotFound;
use ComboStrap\FetcherImage;
use ComboStrap\IFetcherLocalImage;
use ComboStrap\FetcherSvg;
use ComboStrap\FetcherVignette;
use ComboStrap\FirstImage;
use ComboStrap\IconDownloader;
use ComboStrap\LogUtility;
use ComboStrap\MediaMarkup;
use ComboStrap\MarkupPath;
use ComboStrap\PagePath;
use ComboStrap\PluginUtility;
use ComboStrap\Site;
use ComboStrap\TagAttributes;


require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');


/**
 * Set the cache of the bar
 * Ie add the possibility to add a time
 * over {@link \dokuwiki\Parsing\ParserMode\Nocache}
 */
class syntax_plugin_combo_pageimage extends DokuWiki_Syntax_Plugin
{


    const TAG = "pageimage";

    const MARKUP = "page-image";


    const CANONICAL = self::TAG;
    const DEFAULT_ATTRIBUTE = "default";

    const META_TYPE = "meta";
    const DESCENDANT_TYPE = "descendant";
    const VIGNETTE_TYPE = "vignette";
    const FIRST_TYPE = "first";
    const LOGO_TYPE = "logo";
    const NONE_TYPE = "none";

    const DEFAULT_ORDER = [
        self::META_TYPE,
        self::FIRST_TYPE,
        self::DESCENDANT_TYPE,
        self::VIGNETTE_TYPE,
        self::LOGO_TYPE
    ];
    const ORDER_OF_PREFERENCE = "order";


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
        return 'normal';
    }

    function getAllowedTypes(): array
    {
        return array();
    }

    function getSort(): int
    {
        return 201;
    }


    function connectTo($mode)
    {

        $this->Lexer->addSpecialPattern(PluginUtility::getVoidElementTagPattern(self::MARKUP), $mode, PluginUtility::getModeFromTag($this->getPluginComponent()));

    }


    function handle($match, $state, $pos, Doku_Handler $handler): array
    {

        /**
         * Because the pageimage can also be used
         * in a template
         *
         * The calculation are done in the {@link syntax_plugin_combo_pageimage::render render function}
         *
         */
        if ($state !== DOKU_LEXER_SPECIAL) {
            return [];
        }

        $knownTypes = [self::META_TYPE, self::FIRST_TYPE, self::VIGNETTE_TYPE, self::DESCENDANT_TYPE, self::LOGO_TYPE];
        $tagAttributes = TagAttributes::createFromTagMatch($match, [], $knownTypes);

        /**
         * Page Image Order Calculation
         */
        $type = $tagAttributes->getComponentAttributeValue(TagAttributes::TYPE_KEY, self::META_TYPE);
        // the type is first
        $orderOfPreference[] = $type;
        // then the default one
        $default = $tagAttributes->getValueAndRemoveIfPresent(self::DEFAULT_ATTRIBUTE);
        if ($default === null) {
            $defaultOrderOfPrecedence = self::DEFAULT_ORDER;
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
        $context = self::TAG;
        $parent = $callStack->moveToParent();
        if ($parent !== false) {
            $context = $parent->getTagName();
        }


        return array(
            PluginUtility::STATE => $state,
            PluginUtility::ATTRIBUTES => $tagAttributes->toCallStackArray(),
            PluginUtility::CONTEXT => $context,
            self::ORDER_OF_PREFERENCE => $orderOfPreference
        );


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

        if ($format !== 'xhtml') {
            // unsupported $mode
            return false;
        }

        $tagAttributes = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES]);


        $path = $tagAttributes->getValueAndRemove(PagePath::PROPERTY_NAME);
        if ($path === null) {
            $contextManager = ContextManager::getOrCreate();
            $path = $contextManager->getAttribute(PagePath::PROPERTY_NAME);
            if ($path === null) {
                // It should never happen, dev error
                LogUtility::error("Internal Error: Bad state: page image cannot retrieve the page path from the context", self::CANONICAL);
                return false;
            }
        }

        /**
         * Image selection
         */
        WikiPath::addRootSeparatorIfNotPresent($path);
        $page = MarkupPath::createPageFromQualifiedPath($path);

        /**
         * Image Order of precedence
         */
        $order = $data[self::ORDER_OF_PREFERENCE];
        $imageFetcher = null;
        foreach ($order as $pageImageProcessing) {
            switch ($pageImageProcessing) {
                case self::META_TYPE:
                    try {
                        $imageFetcher = $this->selectAndGetBestMetadataPageImageFetcherForRatio($page, $tagAttributes);
                    } catch (ExceptionNotFound $e) {
                        // ok
                    }
                    break;
                case self::DESCENDANT_TYPE:
                case "parent": // old
                    $parent = $page;
                    while (true) {
                        try {
                            $parent = $parent->getParent();
                        } catch (ExceptionNotFound $e) {
                            break;
                        }
                        try {
                            $imageFetcher = $this->selectAndGetBestMetadataPageImageFetcherForRatio($parent, $tagAttributes);
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
                case self::FIRST_TYPE:
                    try {
                        $imageFetcher = FirstImage::createForPage($page)
                            ->getLocalImageFetcher();
                    } catch (ExceptionNotFound $e) {
                        continue 2;
                    }
                    break;
                case self::VIGNETTE_TYPE:

                    try {
                        $imageFetcher = FetcherVignette::createForPage($page);
                    } catch (ExceptionNotFound|ExceptionBadArgument $e) {
                        LogUtility::error("Error while creating the vignette for the page ($page). Error: {$e->getMessage()}");
                    }
                    break;
                case self::LOGO_TYPE:
                    try {
                        $imageFetcher = FetcherSvg::createSvgFromPath(Site::getLogoAsSvgImage());
                    } catch (ExceptionNotFound $e) {
                        LogUtility::msg("No page image could be find for the page ($path)", LogUtility::LVL_MSG_INFO, self::CANONICAL);
                    }
                    break;
                case self::NONE_TYPE:
                    return false;
                default:
                    LogUtility::error("The image ($pageImageProcessing) is an unknown page image type", self::CANONICAL);
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
            LogUtility::error("The image could not be build. Error: {$e->getMessage()}", self::CANONICAL);
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
         */
        /**
         * Used as an illustration in a card
         * If the image is too small, we allow that it will stretch
         * to take the whole space
         */
        if ($data[PluginUtility::CONTEXT] === syntax_plugin_combo_card::TAG) {
            $tagAttributes->addStyleDeclarationIfNotSet("max-width", "100%");
            $tagAttributes->addStyleDeclarationIfNotSet("max-height", "unset");
        }


        try {
            $renderer->doc .= MediaMarkup::createFromFetcher($imageFetcher)
                ->setHtmlOrSetterTagAttributes($tagAttributes)
                ->toHtml();
        } catch (ExceptionCompile $e) {
            $renderer->doc .= "Error while rendering the page image: {$e->getMessage()}";
        }
        return true;

    }

    /**
     * @throws ExceptionNotFound
     */
    private function selectAndGetBestMetadataPageImageFetcherForRatio(MarkupPath $page, TagAttributes $tagAttributes): FetcherImage
    {

        /**
         * Take the image and the page images
         * of the first page with an image
         */
        $selectedPageImage = IFetcherLocalImage::createImageFetchFromPageImageMetadata($page);
        $stringRatio = $tagAttributes->getValueAndRemoveIfPresent(Dimension::RATIO_ATTRIBUTE);
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
            LogUtility::error("The ratio ($stringRatio) is not a valid ratio. Error: {$e->getMessage()}", self::CANONICAL);
            return $selectedPageImage;
        }

        $pageImages = $page->getPageMetadataImages();
        foreach ($pageImages as $pageImage) {
            $path = $pageImage->getImagePath();
            try {
                $fetcherImage = IFetcherLocalImage::createImageFetchFromPath($path);
            } catch (ExceptionBadArgument $e) {
                LogUtility::msg("An image object could not be build from ($path). Is it an image file ?. Error: {$e->getMessage()}");
                continue;
            }
            try {
                $ratioDistance = $targetRatio - $fetcherImage->getIntrinsicAspectRatio();
            } catch (ExceptionCompile $e) {
                LogUtility::msg("The page image ($fetcherImage) of the page ($page) returns an error. Error: {$e->getMessage()}");
                continue;
            }
            if ($ratioDistance < $bestRatioDistance) {
                $bestRatioDistance = $ratioDistance;
                $selectedPageImage = $fetcherImage;
            }
        }
        return $selectedPageImage;


    }


}

