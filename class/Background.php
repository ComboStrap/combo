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
            $backgrounds = $tagAttributes->getValueAndRemove(self::BACKGROUNDS);
            switch (sizeof($backgrounds)) {
                case 1:
                    // Only one background was specified
                    $background = $backgrounds[0];
                    if (!isset($background[TagAttributes::TRANSFORM])) {
                        $tagAttributes->addComponentAttributeValueIfNotEmpty(self::BACKGROUND_IMAGE, $background[self::BACKGROUND_IMAGE]);
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
            $backgroundImageValue = $tagAttributes->getValueAndRemove(self::BACKGROUND_IMAGE);
            if (is_string($backgroundImageValue)) {
                /**
                 * Image background is set by the user
                 */
                $backgroundImageStyleValue = $tagAttributes->getValueAsStringAndRemove(self::BACKGROUND_IMAGE);

            } else {

                if (is_array($backgroundImageValue)) {
                    $media = InternalMediaLink::createFromCallStackArray($backgroundImageValue);
                    $url = $media->getUrl("&");
                    if ($url !== false) {

                        $backgroundImageStyleValue = "url(" . $url . ")";

                        /**
                         * Background-fill for background image
                         */
                        $backgroundFill = $tagAttributes->getValueAsStringAndRemove(self::BACKGROUND_FILL, "cover");
                        switch ($backgroundFill) {
                            case "cover":
                                // it makes the background responsive
                                $tagAttributes->addStyleDeclaration(self::BACKGROUND_SIZE, $backgroundFill);
                                $tagAttributes->addStyleDeclaration(self::BACKGROUND_REPEAT, "no-repeat");
                                $tagAttributes->addStyleDeclaration(self::BACKGROUND_POSITION, "center center");
                                break;
                            case "tile":
                                // background size is then "auto" (ie repeat), the default
                                // background position is not needed (the tile start on the left top corner)
                                $tagAttributes->addStyleDeclaration(self::BACKGROUND_REPEAT, "repeat");
                                break;
                            case "css":
                                // custom, set by the user in own css stylesheet, nothing to do
                                break;
                            default:
                                LogUtility::msg("The background `fill` attribute ($backgroundFill) is unknown. If you want to take over the filling via css, set the `fill` value to `css`.", self::CANONICAL);
                                break;
                        }


                    } else {
                        LogUtility::msg("The image ($media) does not exist", LogUtility::LVL_MSG_WARNING, self::CANONICAL);
                    }
                } else {
                    LogUtility::msg("Internal Error: The background image value ($backgroundImageValue) is not a string nor an array", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                }

            }
        }
        if (!empty($backgroundImageStyleValue)) {
            if ($tagAttributes->hasComponentAttribute(self::BACKGROUND_OPACITY)) {
                $opacity = $tagAttributes->getValueAsStringAndRemove(self::BACKGROUND_OPACITY);
                $finalOpacity = 1 - $opacity;
                $backgroundImageStyleValue = "linear-gradient(to right, rgba(255,255,255, $finalOpacity) 0 100%)," . $backgroundImageStyleValue;
            }
            $tagAttributes->addStyleDeclaration(self::BACKGROUND_IMAGE, $backgroundImageStyleValue);


        }


        /**
         * Background color
         */
        if ($tagAttributes->hasComponentAttribute(self::BACKGROUND_COLOR)) {

            $colorValue = $tagAttributes->getValueAsStringAndRemove(self::BACKGROUND_COLOR);

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
    public static function fromMediaToBackgroundImageStackArray(array $mediaCallStackArray)
    {
        $backgroundProperties = [];
        foreach ($mediaCallStackArray as $key => $property) {
            switch ($key) {
                case TagAttributes::LINKING_KEY:
                case TagAttributes::TITLE_KEY:
                case TagAttributes::ALIGN_KEY:
                case Float::FLOAT_KEY: // Float is when the image is at the right
                case TagAttributes::TYPE_KEY:
                    /**
                     * Attributes not taken
                     */
                    break;
                default:
                    /**
                     * Attributes taken
                     */
                    $backgroundProperties[$key] = $property;
                    break;
            }
        }
        return $backgroundProperties;
    }


}
