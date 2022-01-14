<?php


use ComboStrap\AnalyticsDocument;
use ComboStrap\CallStack;
use ComboStrap\Dimension;
use ComboStrap\DokuPath;
use ComboStrap\ExceptionCombo;
use ComboStrap\ExceptionComboRuntime;
use ComboStrap\Image;
use ComboStrap\LogUtility;
use ComboStrap\MediaLink;
use ComboStrap\Mime;
use ComboStrap\Page;
use ComboStrap\PagePath;
use ComboStrap\Path;
use ComboStrap\PluginUtility;
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
    function getPType()
    {
        return 'normal';
    }

    function getAllowedTypes()
    {
        return array();
    }

    function getSort()
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
     *
     */
    function render($format, Doku_Renderer $renderer, $data): bool
    {

        switch ($format) {

            case 'xhtml':

                $tagAttributes = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES]);
                if (!$tagAttributes->hasAttribute(PagePath::PROPERTY_NAME)) {

                    LogUtility::msg("The path is mandatory and was not found", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                    return false;
                }

                $path = $tagAttributes->getValueAndRemove(PagePath::PROPERTY_NAME);
                DokuPath::addRootSeparatorIfNotPresent($path);

                /**
                 * Image selection
                 */
                $page = Page::createPageFromQualifiedPath($path);
                $selectedPageImage = $page->getImage();
                if ($selectedPageImage === null) {
                    LogUtility::msg("No page image defined for the page ($path)", LogUtility::LVL_MSG_INFO, self::CANONICAL);
                    return false;
                }

                /**
                 * We select the best image for the ratio
                 *
                 */
                $targetRatio = null;
                if ($tagAttributes->hasComponentAttribute(Dimension::RATIO_ATTRIBUTE)) {
                    $stringRatio = $tagAttributes->getValue(Dimension::RATIO_ATTRIBUTE);
                    if (empty($stringRatio)) {

                        LogUtility::msg("The ratio value is empty and was therefore not taken into account", LogUtility::LVL_MSG_ERROR, self::CANONICAL);

                    } else {

                        $bestRatioDistance = 9999;

                        $targetRatio = Dimension::convertTextualRatioToNumber($stringRatio);

                        foreach ($page->getPageImagesOrDefault() as $pageImage) {
                            $image = $pageImage->getImage();
                            $ratioDistance = $targetRatio - $image->getIntrinsicAspectRatio();
                            if ($ratioDistance < $bestRatioDistance) {
                                $bestRatioDistance = $ratioDistance;
                                $selectedPageImage = $image;
                            }
                        }


                    }
                }

                if ($targetRatio !== null) {

                    $mime = $selectedPageImage->getPath()->getMime()->toString();
                    switch ($mime) {
                        case Mime::SVG:
                            // Ratio is part of the request
                            // because it is the definition of the viewBox
                            // The rendering function takes care of it
                            // and it's also passed in the fetch url
                            break;
                        default:
                            /**
                             * TODO: This code should move into the rendering function
                             */
//                            [$logicalWidthWithRatio, $logicalHeightWithRatio] = Image::getDimensionsWithRatio(
//                                $targetRatio,
//                                $selectedPageImage->getIntrinsicWidth(),
//                                $selectedPageImage->getIntrinsicHeight()
//                            );
//                            if ($logicalWidthWithRatio !== null) {
//                                $tagAttributes->addComponentAttributeValue(Dimension::WIDTH_KEY, $logicalWidthWithRatio);
//                                if ($logicalHeightWithRatio !== null) {
//                                    $tagAttributes->addComponentAttributeValue(Dimension::HEIGHT_KEY, $logicalHeightWithRatio);
//                                }
//                            }
                    }

                }


                /**
                 * Used as an illustration in a card
                 * If the image is too small, we allow that it will stretch
                 * to take the whole space
                 */
                if ($data[PluginUtility::CONTEXT] === syntax_plugin_combo_card::TAG) {
                    $tagAttributes->addStyleDeclarationIfNotSet("max-width", "100%");
                    $tagAttributes->addStyleDeclarationIfNotSet("max-height", "unset");
                }

                $tagAttributes->setComponentAttributeValue(TagAttributes::TYPE_KEY, SvgDocument::ILLUSTRATION_TYPE);


                $mediaLink = MediaLink::createMediaLinkFromPath(
                    $selectedPageImage->getPath(),
                    $tagAttributes
                );
                $renderer->doc .= $mediaLink->renderMediaTag();

                break;


        }
        // unsupported $mode
        return false;
    }


}

