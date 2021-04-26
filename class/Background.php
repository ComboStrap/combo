<?php


namespace ComboStrap;


/**
 * Process background attribute
 */
class Background
{
    /**
     * Logical attribute
     */
    const BACKGROUND_COLOR = 'background-color';

    /**
     * HTML attribute / not public
     */
    const BACKGROUND_IMAGE = 'background-image';

    /**
     * Logical attribute / not public
     */
    const BACKGROUND_IMAGE_ID = 'background-image-id';
    const BACKGROUND_IMAGE_WIDTH = 'background-image-width';
    const BACKGROUND_IMAGE_HEIGHT = 'background-image-height';
    const BACKGROUND_IMAGE_CACHE = 'background-image-cache';

    /**
     * The page canonical of the documentation
     */
    const CANONICAL = "background";

    /**
     * A component attributes to store backgrounds
     */
    const BACKGROUNDS = "backgrounds";


    public static function processBackgroundAttributes(TagAttributes &$tagAttributes)
    {

        /**
         * Backgrounds set with the {@link \syntax_plugin_combo_background} component
         */
        if ($tagAttributes->hasComponentAttribute(self::BACKGROUNDS)) {
            PluginUtility::getSnippetManager()->attachCssSnippetForBar(self::CANONICAL);
            $backgrounds = $tagAttributes->getValueAsArrayAndRemove(self::BACKGROUNDS);
            switch (sizeof($backgrounds)) {
                case 1:
                    // Only one background was specified
                    $background = $backgrounds[0];
                    if (!isset($background[TagAttributes::TRANSFORM])) {
                        $tagAttributes->addComponentAttributeValueIfNotEmpty(self::BACKGROUND_IMAGE_ID, $background[self::BACKGROUND_IMAGE_ID]);
                        $tagAttributes->addComponentAttributeValueIfNotEmpty(self::BACKGROUND_IMAGE_WIDTH, $background[self::BACKGROUND_IMAGE_WIDTH]);
                        $tagAttributes->addComponentAttributeValueIfNotEmpty(self::BACKGROUND_IMAGE_HEIGHT, $background[self::BACKGROUND_IMAGE_HEIGHT]);
                        $tagAttributes->addComponentAttributeValueIfNotEmpty(self::BACKGROUND_IMAGE_CACHE, $background[self::BACKGROUND_IMAGE_CACHE]);
                        $tagAttributes->addComponentAttributeValueIfNotEmpty(self::BACKGROUND_COLOR, $background[self::BACKGROUND_COLOR]);
                    } else {
                        $backgroundTagAttribute = TagAttributes::createFromCallStackArray($background);
                        $backgroundTagAttribute->addClassName(self::CANONICAL);
                        $backgroundHTML = "<div class=\"backgrounds\">".
                            $backgroundTagAttribute->toHtmlEnterTag("div").
                            "</div>".
                            "</div>";
                        $tagAttributes->addHtmlAfterEnterTag($backgroundHTML);
                    }
                    break;
                default:
                    /**
                     * More than one background
                     * This backgrounds should have been set on the backgrounds
                     */
                    $backgroundHTML = "";
                    foreach ($backgrounds as $background) {
                        $backgroundTagAttribute = TagAttributes::createFromCallStackArray($background);
                        $backgroundTagAttribute->addClassName(self::CANONICAL);
                        $backgroundHTMLEnter = $backgroundTagAttribute->toHtmlEnterTag("div");
                        $backgroundHTML .= $backgroundHTMLEnter . "</div>";
                    }
                    $tagAttributes->addHtmlAfterEnterTag($backgroundHTML);
                    break;
            }
        }

        /**
         * Image background is set by the user
         */
        $backgroundImageStyleValue = "";
        if ($tagAttributes->hasComponentAttribute(self::BACKGROUND_IMAGE)) {
            $backgroundImageStyleValue = $tagAttributes->getValueAndRemove(self::BACKGROUND_IMAGE);
        } else {
            if ($tagAttributes->hasComponentAttribute(self::BACKGROUND_IMAGE_ID)) {
                $callStackImage = array();
                $callStackImage[InternalMediaLink::SRC_KEY] = $tagAttributes->getValueAndRemove(self::BACKGROUND_IMAGE_ID);
                $callStackImage[TagAttributes::WIDTH_KEY] = $tagAttributes->getValueAndRemove(self::BACKGROUND_IMAGE_WIDTH);
                $callStackImage[TagAttributes::HEIGHT_KEY] = $tagAttributes->getValueAndRemove(self::BACKGROUND_IMAGE_HEIGHT);
                $callStackImage[TagAttributes::CACHE_KEY] = $tagAttributes->getValueAndRemove(self::BACKGROUND_IMAGE_CACHE);
                $media = InternalMediaLink::createFromCallStackArray($callStackImage);
                $url = $media->getUrl();
                if ($url !== false) {
                    $backgroundImageStyleValue = "url(" . $url . ")";
                } else {
                    LogUtility::msg("The image ($media) does not exist", LogUtility::LVL_MSG_WARNING, self::CANONICAL);
                }
            }
        }
        if (!empty($backgroundImageStyleValue)) {
            $tagAttributes->addStyleDeclaration(self::BACKGROUND_IMAGE, $backgroundImageStyleValue);
        }

        /**
         * Background color
         */
        if ($tagAttributes->hasComponentAttribute(self::BACKGROUND_COLOR)) {

            $colorValue = $tagAttributes->getValueAndRemove(self::BACKGROUND_COLOR);

            $gradientPrefix = 'gradient-';
            if (strpos($colorValue, $gradientPrefix) === 0) {
                /**
                 * A gradient is an image
                 * Check that there is no image
                 */
                if (!empty($backgroundImageStyleValue)) {
                    LogUtility::msg("An image and a linear gradient color are exclusive because a linear gradient color creates an image. You can't use the linear color (" . $colorValue . ") and the image (" . $backgroundImageStyleValue . ")", LogUtility::LVL_MSG_WARNING, self::CANONICAL);
                } else {
                    $mainColorValue = substr($colorValue, strlen($gradientPrefix));
                    $tagAttributes->addStyleDeclaration(self::BACKGROUND_IMAGE, 'linear-gradient(to top,#fff 0,' . ColorUtility::getColorValue($mainColorValue) . ' 100%)');
                    $tagAttributes->addStyleDeclaration(self::BACKGROUND_COLOR, 'unset!important');
                }
            } else {
                $tagAttributes->addStyleDeclaration(self::BACKGROUND_COLOR, $colorValue);
            }
        }


    }

    /**
     * Return a background array with background properties
     * from a media {@link InternalMediaLink::toCallStackArray()}
     * @param array $mediaCallStackArray
     * @return array
     */
    public static function fromMediaToBackgroundCallStackArray(array $mediaCallStackArray)
    {
        $backgroundProperties = [];
        foreach ($mediaCallStackArray as $key => $property) {
            switch ($key) {
                case TagAttributes::LINKING_KEY:
                case TagAttributes::TITLE_KEY:
                case TagAttributes::ALIGN_KEY:
                case TagAttributes::TYPE_KEY:
                    /**
                     * Attributes not taken
                     */
                    break;
                case InternalMediaLink::SRC_KEY:
                    $backgroundProperties[self::BACKGROUND_IMAGE_ID] = $property;
                    break;
                case TagAttributes::WIDTH_KEY:
                    $backgroundProperties[self::BACKGROUND_IMAGE_WIDTH] = $property;
                    break;
                case TagAttributes::HEIGHT_KEY:
                    $backgroundProperties[self::BACKGROUND_IMAGE_HEIGHT] = $property;
                    break;
                case TagAttributes::CACHE_KEY:
                    $backgroundProperties[self::BACKGROUND_IMAGE_CACHE] = $property;
                    break;
                default:
                    /**
                     * Attributes taken (example background-color)
                     */
                    $backgroundProperties[$key] = $property;
                    break;
            }
        }
        return $backgroundProperties;
    }


}
