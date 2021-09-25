<?php


use ComboStrap\Analytics;
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

                $tagAttributes = TagAttributes::createFromTagMatch($match);

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $tagAttributes->toCallStackArray()
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
                if (!$tagAttributes->hasAttribute(Analytics::PATH)) {

                    LogUtility::msg("The path is mandatory and was not found", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                    return false;
                }

                $path = $tagAttributes->getValue(Analytics::PATH);
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
                    $bestRatioDistance = 9999;
                    $targetRatio = self::getTargetAspectRatio($tagAttributes->getComponentAttributeValue(self::RATIO_ATTRIBUTE));
                    foreach ($page->getLocalImageSet() as $image) {
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


                $tagAttributes = TagAttributes::createEmpty(self::TAG);
                if ($width !== null) {
                    $tagAttributes->addComponentAttributeValue(Dimension::WIDTH_KEY, $width);
                    if ($height !== null) {
                        $tagAttributes->addComponentAttributeValue(Dimension::HEIGHT_KEY, $height);
                    }
                }
                $mediaLink = MediaLink::createMediaLinkFromAbsolutePath(
                    $selectedPageImage->getAbsolutePath(),
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

