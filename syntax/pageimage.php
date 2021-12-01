<?php


use ComboStrap\AnalyticsDocument;
use ComboStrap\CallStack;
use ComboStrap\Dimension;
use ComboStrap\DokuPath;
use ComboStrap\Image;
use ComboStrap\LogUtility;
use ComboStrap\MediaLink;
use ComboStrap\Page;
use ComboStrap\PluginUtility;
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
    const RATIO_ATTRIBUTE = "ratio";

    /**
     * @param $stringRatio
     * @return float
     */
    public static function getTargetAspectRatio($stringRatio)
    {
        list($width, $height) = explode(":", $stringRatio, 2);
        if(!is_numeric($width)){
            LogUtility::msg("The width value ($width) of the ratio `$stringRatio` is not numeric", LogUtility::LVL_MSG_ERROR,self::CANONICAL);
            return 1;
        }
        if(!is_numeric($height)){
            LogUtility::msg("The width value ($height) of the ratio `$stringRatio` is not numeric", LogUtility::LVL_MSG_ERROR,self::CANONICAL);
            return 1;
        }
        if($height==0){
            LogUtility::msg("The height value of the ratio `$stringRatio` should not be zero", LogUtility::LVL_MSG_ERROR,self::CANONICAL);
            return 1;
        }
        return floatval($width / $height);

    }


    function getType()
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
                if (!$tagAttributes->hasAttribute(AnalyticsDocument::PATH)) {

                    LogUtility::msg("The path is mandatory and was not found", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                    return false;
                }

                $path = $tagAttributes->getValueAndRemove(AnalyticsDocument::PATH);
                DokuPath::addRootSeparatorIfNotPresent($path);

                $page = Page::createPageFromQualifiedPath($path);
                $selectedPageImage = $page->getImage();
                if ($selectedPageImage === null) {
                    LogUtility::msg("No page image defined for the page ($path)", LogUtility::LVL_MSG_INFO, self::CANONICAL);
                    return false;
                }
                $width = null;
                $height = null;
                if ($tagAttributes->hasComponentAttribute(self::RATIO_ATTRIBUTE)) {
                    $stringRatio = $tagAttributes->getValueAndRemove(self::RATIO_ATTRIBUTE);
                    if (empty($stringRatio)) {

                        LogUtility::msg("The ratio value is empty and was therefore not taken into account", LogUtility::LVL_MSG_ERROR, self::CANONICAL);

                    } else {
                        $bestRatioDistance = 9999;

                        $targetRatio = self::getTargetAspectRatio($stringRatio);
                        foreach ($page->getPageImagesOrDefault() as $pageImage) {
                            $image = $pageImage->getImage();
                            $ratioDistance = $targetRatio - $image->getIntrinsicAspectRatio();
                            if ($ratioDistance < $bestRatioDistance) {
                                $bestRatioDistance = $ratioDistance;
                                $selectedPageImage = $image;
                            }
                        }
                        /**
                         * Trying to crop on the width
                         */
                        $width = $selectedPageImage->getIntrinsicWidth();
                        $height = Image::round($width / $targetRatio);
                        if ($height > $selectedPageImage->getIntrinsicHeight()) {
                            /**
                             * Cropping by height
                             */
                            $height = $selectedPageImage->getIntrinsicHeight();
                            $width = Image::round($targetRatio * $height);
                        }
                    }
                }


                if ($width !== null) {
                    $tagAttributes->addComponentAttributeValue(Dimension::WIDTH_KEY, $width);
                    if ($height !== null) {
                        $tagAttributes->addComponentAttributeValue(Dimension::HEIGHT_KEY, $height);
                    }
                }

                /**
                 * Used as an illustration in a card
                 * If the image is too small, we allows that it will stretch
                 */
                if ($data[PluginUtility::CONTEXT] === syntax_plugin_combo_card::TAG) {
                    $tagAttributes->addStyleDeclaration("max-width", "100%");
                    $tagAttributes->addStyleDeclaration("max-height", "unset");
                }

                $mediaLink = MediaLink::createMediaLinkFromAbsolutePath(
                    $selectedPageImage->getDokuPath()->getAbsolutePath(),
                    null,
                    $tagAttributes
                );
                $renderer->doc .= $mediaLink->renderMediaTag();

                break;


        }
        // unsupported $mode
        return false;
    }


}

