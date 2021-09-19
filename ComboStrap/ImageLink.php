<?php


namespace ComboStrap;


/**
 * Class ImageLink
 * @package ComboStrap
 *
 * A media of image type
 */
abstract class ImageLink extends MediaLink
{

    /**
     * @param $imgTagHeight
     * @param $imgTagWidth
     * @return float|mixed
     */
    public
    function checkWidthAndHeightRatioAndReturnTheGoodValue($imgTagWidth, $imgTagHeight)
    {
        /**
         * Check of height and width dimension
         * as specified here
         * https://html.spec.whatwg.org/multipage/embedded-content-other.html#attr-dim-height
         */
        $targetRatio = $this->getDefaultImage()->getAspectRatio();
        if (!(
            $imgTagHeight * $targetRatio >= $imgTagWidth - 0.5
            &&
            $imgTagHeight * $targetRatio <= $imgTagWidth + 0.5
        )) {
            // check the second statement
            if (!(
                $imgTagWidth / $targetRatio >= $imgTagHeight - 0.5
                &&
                $imgTagWidth / $targetRatio <= $imgTagHeight + 0.5
            )) {
                $requestedHeight = $this->getRequestedHeight();
                $requestedWidth = $this->getRequestedWidth();
                if (
                    !empty($requestedHeight)
                    && !empty($requestedWidth)
                ) {
                    /**
                     * The user has asked for a width and height
                     */
                    $imgTagWidth = round($imgTagHeight * $targetRatio);
                    LogUtility::msg("The width ($requestedWidth) and height ($requestedHeight) specified on the image ($this) does not follow the natural ratio as <a href=\"https://html.spec.whatwg.org/multipage/embedded-content-other.html#attr-dim-height\">required by HTML</a>. The width was then set to ($imgTagWidth).", LogUtility::LVL_MSG_INFO, self::CANONICAL);
                } else {
                    /**
                     * Programmatic error from the developer
                     */
                    $imgTagRatio = $imgTagWidth / $imgTagHeight;
                    LogUtility::msg("Internal Error: The width ($imgTagWidth) and height ($imgTagHeight) calculated for the image ($this) does not pass the ratio test. They have a ratio of ($imgTagRatio) while the natural dimension ratio is ($targetRatio)");
                }
            }
        }
        return $imgTagWidth;
    }


    function getDefaultImage(): Image
    {
        return $this->getDokuPath();
    }

}
