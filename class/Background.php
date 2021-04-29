<?php


namespace ComboStrap;


/**
 * Process background attribute
 */
class Background
{
    /**
     * Background Logical attribute / Public
     */
    const BACKGROUND_COLOR = 'background-color';
    const BACKGROUND_OPACITY = "background-opacity";
    const BACKGROUND_FILL = "background-fill";
    const BACKGROUND_POSITION = "background-position";

    /**
     * CSS attribute / not public
     */
    const BACKGROUND_IMAGE = 'background-image';
    const BACKGROUND_SIZE = "background-size";
    const BACKGROUND_REPEAT = "background-repeat";

    /**
     * Logical attribute / not public to go from image to background image
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

    /**
     * This default are making the background responsive
     */
    const DEFAULT_ATTRIBUTES = array(
        Background::BACKGROUND_FILL => "cover"
    );


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
                        $tagAttributes->addComponentAttributeValueIfNotEmpty(self::BACKGROUND_OPACITY, $background[self::BACKGROUND_OPACITY]);
                        $tagAttributes->addComponentAttributeValueIfNotEmpty(self::BACKGROUND_POSITION, $background[self::BACKGROUND_POSITION]);
                        $tagAttributes->addComponentAttributeValueIfNotEmpty(self::BACKGROUND_FILL, $background[self::BACKGROUND_FILL]);
                    } else {
                        $backgroundTagAttribute = TagAttributes::createFromCallStackArray($background);
                        $backgroundTagAttribute->addClassName(self::CANONICAL);
                        $backgroundHTML = "<div class=\"backgrounds\">" .
                            $backgroundTagAttribute->toHtmlEnterTag("div") .
                            "</div>" .
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
         * Background-image attribute
         */
        $backgroundImageStyleValue = "";
        if ($tagAttributes->hasComponentAttribute(self::BACKGROUND_IMAGE)) {
            /**
             * Image background is set by the user
             */
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
            if ($tagAttributes->hasComponentAttribute(self::BACKGROUND_OPACITY)) {
                $opacity = $tagAttributes->getValueAndRemove(self::BACKGROUND_OPACITY);
                $finalOpacity = 1 - $opacity;
                $backgroundImageStyleValue = "linear-gradient(to right, rgba(255,255,255, $finalOpacity) 0 100%)," . $backgroundImageStyleValue;
            }
            $tagAttributes->addStyleDeclaration(self::BACKGROUND_IMAGE, $backgroundImageStyleValue);
        }

        /**
         * Background-fill
         */
        if ($tagAttributes->hasComponentAttribute(self::BACKGROUND_FILL)) {
            $backgroundFill = $tagAttributes->getValueAndRemove(self::BACKGROUND_FILL);
            switch ($backgroundFill) {
                case "cover":
                default: // it makes the background responsive
                    $tagAttributes->addStyleDeclaration(self::BACKGROUND_SIZE, $backgroundFill);
                    $tagAttributes->addStyleDeclaration(self::BACKGROUND_REPEAT, "no-repeat");
                    $tagAttributes->addStyleDeclaration(self::BACKGROUND_POSITION, "center center");
                    break;
                case "tile":
                    // background size is then "auto" (ie repeat), the default
                    // background position is not needed (the tile start on the left top corner)
                    $tagAttributes->addStyleDeclaration(self::BACKGROUND_REPEAT, "repeat");
                    break;
            }
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
