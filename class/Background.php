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
    const BACKGROUND_IMAGE_URL = 'background-image-url';

    /**
     * The page canonical of the documentation
     */
    const CANONICAL = "background";


    public static function processBackgroundAttributes(TagAttributes &$tagAttributes)
    {

        /**
         * Image background is set by the user
         */
        $backgroundImageStyleValue = "";
        if ($tagAttributes->hasComponentAttribute(self::BACKGROUND_IMAGE)) {
            $backgroundImageStyleValue = $tagAttributes->getValueAndRemove(self::BACKGROUND_IMAGE);
        } else {
            if ($tagAttributes->hasComponentAttribute(self::BACKGROUND_IMAGE_URL)){
                $valueAndRemove = $tagAttributes->getValueAndRemove(self::BACKGROUND_IMAGE_URL);
                $backgroundImageStyleValue = "url(". $valueAndRemove.")";
            }
        }
        if (!empty($backgroundImageStyleValue)){
            $tagAttributes->addStyleDeclaration(self::BACKGROUND_IMAGE,  $backgroundImageStyleValue );
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
                    LogUtility::msg("An image and a linear gradient color are exclusive because a linear gradient color creates an image. You can't use the linear color (" . $colorValue . ") and the image (".$backgroundImageStyleValue.")",LogUtility::LVL_MSG_WARNING,self::CANONICAL);
                } else {
                    $mainColorValue = substr($colorValue, strlen($gradientPrefix));
                    $tagAttributes->addStyleDeclaration(self::BACKGROUND_IMAGE, 'linear-gradient(to top,#fff 0,' . PluginUtility::getColorValue($mainColorValue) . ' 100%)');
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
                    /**
                     * Attributes not taken
                     */
                    break;
                case "src":
                    $backgroundProperties[self::BACKGROUND_IMAGE_URL] = $property;
                    break;
                case TagAttributes::CACHE_KEY:
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
