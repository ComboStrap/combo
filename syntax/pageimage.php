<?php


use ComboStrap\CallStack;
use ComboStrap\ConditionalLength;
use ComboStrap\ContextManager;
use ComboStrap\Dimension;
use ComboStrap\DokuPath;
use ComboStrap\ExceptionBadArgument;
use ComboStrap\ExceptionCompile;
use ComboStrap\ExceptionNotFound;
use ComboStrap\FileSystems;
use ComboStrap\FirstImage;
use ComboStrap\Icon;
use ComboStrap\FetchImage;
use ComboStrap\LogUtility;
use ComboStrap\MediaLink;
use ComboStrap\Mime;
use ComboStrap\Page;
use ComboStrap\PagePath;
use ComboStrap\PluginUtility;
use ComboStrap\Site;
use ComboStrap\SvgDocument;
use ComboStrap\TagAttributes;
use ComboStrap\Vignette;


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
        DokuPath::addRootSeparatorIfNotPresent($path);
        $page = Page::createPageFromQualifiedPath($path);

        /**
         * Image Order of precedence
         */
        $order = $data[self::ORDER_OF_PREFERENCE];
        $selectedPageImage = null;
        foreach ($order as $pageImageProcessing) {
            switch ($pageImageProcessing) {
                case self::META_TYPE:
                    try {
                        $selectedPageImage = $this->getMetaImage($page, $tagAttributes);
                    } catch (ExceptionNotFound $e) {
                        // ok
                    }
                    break;
                case self::DESCENDANT_TYPE:
                    $parent = $page;
                    while ($parent = $parent->getParentPage()) {
                        try {
                            $selectedPageImage = $this->getMetaImage($parent, $tagAttributes);
                        } catch (ExceptionNotFound $e) {
                            try {
                                $selectedPageImage = FirstImage::createForPage($parent)->getImageObject();
                            } catch (ExceptionNotFound $e) {
                                continue;
                            }
                        }
                        break;
                    }
                    break;
                case self::FIRST_TYPE:
                    try {
                        $selectedPageImage = FirstImage::createForPage($page)->getImageObject();
                    } catch (ExceptionNotFound $e) {
                        continue 2;
                    }
                    break;
                case self::VIGNETTE_TYPE:

                    try {
                        $selectedPageImage = Vignette::createForPage($page);
                    } catch (ExceptionBadArgument $e) {
                        LogUtility::error("Error while creating the vignette for the page ($page). Error: {$e->getMessage()}");
                    }
                    break;

                case self::LOGO_TYPE:
                    $selectedPageImage = Site::getLogoAsSvgImage();
                    if ($selectedPageImage === null) {
                        LogUtility::msg("No page image could be find for the page ($path)", LogUtility::LVL_MSG_INFO, self::CANONICAL);
                    }
                    break;
                case self::NONE_TYPE:
                    return false;
                default:
                    LogUtility::error("The image ($pageImageProcessing) is an unknown page image type", self::CANONICAL);
                    continue 2;
            }
            if ($selectedPageImage !== null) {
                break;
            }
        }

        if ($selectedPageImage === null) {
            return false;
        }

        /**
         * Image Path was found, no create the request
         */

        /**
         * {@link Dimension::RATIO_ATTRIBUTE Ratio} is part of the request
         * because in svg it is the definition of the viewBox
         * The rendering function takes care of it
         * and it's also passed in the fetch url
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

        /**
         * This is an illustration image
         * Used by svg to color by default with the primary color for instance
         */
        $tagAttributes->setComponentAttributeValue(TagAttributes::TYPE_KEY, SvgDocument::ILLUSTRATION_TYPE);

        /**
         * Zoom applies only to icon not to illustration
         *
         */
        $isIcon = Icon::isInIconDirectory($selectedPageImage->getOriginalPath());
        if (!$isIcon) {
            $tagAttributes->removeComponentAttributeIfPresent(Dimension::ZOOM_ATTRIBUTE);
        } else {
            /**
             * When the width is small, no zoom out
             */
            $width = $tagAttributes->getValue(Dimension::WIDTH_KEY);
            if ($width !== null) {
                try {
                    $pixelWidth = ConditionalLength::createFromString($width)->toPixelNumber();
                    if ($pixelWidth < 30) {
                        /**
                         * Icon rendering
                         */
                        $tagAttributes->removeComponentAttributeIfPresent(Dimension::ZOOM_ATTRIBUTE);
                        $tagAttributes->setComponentAttributeValue(TagAttributes::TYPE_KEY, SvgDocument::ICON_TYPE);

                    }
                } catch (ExceptionCompile $e) {
                    LogUtility::msg("The width value ($width) could not be translated in pixel value. Error: {$e->getMessage()}");
                }
            }
        }

        $mediaLink = MediaLink::createMediaLinkFromPath(
            $selectedPageImage->getOriginalPath(),
            $tagAttributes
        );
        try {
            $renderer->doc .= $mediaLink->renderMediaTag();
        } catch (ExceptionCompile $e) {
            $renderer->doc .= "Error while rendering: {$e->getMessage()}";
        }
        return true;

    }

    /**
     * @throws ExceptionNotFound
     */
    private function getMetaImage(Page $page, TagAttributes $tagAttributes): FetchImage
    {
        /**
         * Take the image and the page images
         * of the first page with an image
         */
        $selectedPageImage = $page->getImage();
        if (!$tagAttributes->hasComponentAttribute(Dimension::RATIO_ATTRIBUTE)) {
            return $selectedPageImage;
        }

        /**
         * We select the best image for the ratio
         */
        $stringRatio = $tagAttributes->getValue(Dimension::RATIO_ATTRIBUTE);
        if (empty($stringRatio)) {

            LogUtility::msg("The ratio value is empty and was therefore not taken into account", LogUtility::LVL_MSG_ERROR, self::CANONICAL);

        } else {

            $bestRatioDistance = 9999;
            $targetRatio = null;
            try {
                $targetRatio = Dimension::convertTextualRatioToNumber($stringRatio);
            } catch (ExceptionCompile $e) {
                LogUtility::msg("The ratio ($stringRatio) is not a valid ratio. Error: {$e->getMessage()}");
            }
            if ($targetRatio !== null) {
                $pageImages = $page->getPageImages();
                foreach ($pageImages as $pageImage) {
                    $image = $pageImage->getImage();
                    try {
                        $ratioDistance = $targetRatio - $image->getIntrinsicAspectRatio();
                    } catch (ExceptionCompile $e) {
                        LogUtility::msg("The page image ($image) of the page ($page) returns an error. Error: {$e->getMessage()}");
                        continue;
                    }
                    if ($ratioDistance < $bestRatioDistance) {
                        $bestRatioDistance = $ratioDistance;
                        $selectedPageImage = $image;
                    }
                }
            }
        }
        return $selectedPageImage;

    }


}

