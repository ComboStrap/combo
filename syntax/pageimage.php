<?php


use ComboStrap\CallStack;
use ComboStrap\Dimension;
use ComboStrap\DokuPath;
use ComboStrap\ExceptionCombo;
use ComboStrap\FileSystems;
use ComboStrap\Image;
use ComboStrap\LogUtility;
use ComboStrap\MediaLink;
use ComboStrap\Page;
use ComboStrap\PagePath;
use ComboStrap\PluginUtility;
use ComboStrap\Site;
use ComboStrap\SvgDocument;
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

        switch ($state) {


            case DOKU_LEXER_SPECIAL :

                /**
                 * Because the pageimage can also be used
                 * in a template
                 *
                 * The calculation are done in the {@link syntax_plugin_combo_pageimage::render render function}
                 *
                 */
                $tagAttributes = TagAttributes::createFromTagMatch($match);
                $callStack = CallStack::createFromHandler($handler);
                $context = self::TAG;
                $parent = $callStack->moveToParent();
                if ($parent !== false) {
                    $context = $parent->getTagName();
                }


                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $tagAttributes->toCallStackArray(),
                    PluginUtility::CONTEXT => $context
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

                $tagAttributes = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES]);


                $path = $tagAttributes->getValueAndRemove(PagePath::PROPERTY_NAME);
                if ($path === null) {
                    LogUtility::msg("The path attribute is mandatory for a page image");
                    return false;
                }
                DokuPath::addRootSeparatorIfNotPresent($path);

                /**
                 * Image selection
                 */
                $page = Page::createPageFromQualifiedPath($path);
                $selectedPageImage = $page->getImage();// the default image
                /**
                 * We select the best image for the ratio
                 *
                 */
                if ($tagAttributes->hasComponentAttribute(Dimension::RATIO_ATTRIBUTE)) {
                    $stringRatio = $tagAttributes->getValue(Dimension::RATIO_ATTRIBUTE);
                    if (empty($stringRatio)) {

                        LogUtility::msg("The ratio value is empty and was therefore not taken into account", LogUtility::LVL_MSG_ERROR, self::CANONICAL);

                    } else {

                        $bestRatioDistance = 9999;
                        $targetRatio = null;
                        try {
                            $targetRatio = Dimension::convertTextualRatioToNumber($stringRatio);
                        } catch (ExceptionCombo $e) {
                            LogUtility::msg("The ratio ($stringRatio) is not a valid ratio. Error: {$e->getMessage()}");
                        }
                        if ($targetRatio !== null) {
                            $pageImages = $page->getPageImagesOrDefault();
                            foreach ($pageImages as $pageImage) {
                                $image = $pageImage->getImage();
                                try {
                                    $ratioDistance = $targetRatio - $image->getIntrinsicAspectRatio();
                                } catch (ExceptionCombo $e) {
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
                }
                if ($selectedPageImage === null) {
                    $default = $tagAttributes->getValue(self::DEFAULT_ATTRIBUTE);
                    switch ($default) {
                        case null:
                            $selectedPageImage = Site::getLogoAsSvgImage();
                            if ($selectedPageImage === null) {
                                LogUtility::msg("No page image could be find for the page ($path)", LogUtility::LVL_MSG_INFO, self::CANONICAL);
                                return false;
                            }
                            break;
                        case "none":
                            break;
                        default:
                            try {
                                $defaultId = DokuPath::toDokuwikiId($default);
                                $selectedPageImage = Image::createImageFromId($defaultId);
                            } catch (ExceptionCombo $e) {
                                $renderer->doc .= LogUtility::wrapInRedForHtml("The default image value ($default) is not a valid image. Error: {$e->getMessage()}");
                                return false;
                            }
                            if (!FileSystems::exists($selectedPageImage->getPath())) {
                                $renderer->doc .= LogUtility::wrapInRedForHtml("The default image ($default) does not exist.");
                                return false;
                            }
                            break;
                    }
                }

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

                $mediaLink = MediaLink::createMediaLinkFromPath(
                    $selectedPageImage->getPath(),
                    $tagAttributes
                );
                try {
                    $renderer->doc .= $mediaLink->renderMediaTag();
                } catch (ExceptionCombo $e) {
                    $renderer->doc .= "Error while rendering: {$e->getMessage()}";
                }

                break;


        }
        // unsupported $mode
        return false;
    }


}

