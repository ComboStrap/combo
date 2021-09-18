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
        $targetRatio = $this->getTargetRatio();
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

    /**
     * Target ratio as explained here
     * https://html.spec.whatwg.org/multipage/embedded-content-other.html#attr-dim-height
     * @return float|int|false
     * false if the image is not supported
     *
     * It's needed for an img tag to set the img `width` and `height` that pass the
     * {@link MediaLink::checkWidthAndHeightRatioAndReturnTheGoodValue() check}
     * to avoid layout shift
     *
     */
    protected function getTargetRatio()
    {
        $image = $this->getDefaultImage();
        if ($image->getHeight() == null || $image->getWidth() == null) {
            return false;
        } else {
            return $image->getWidth() / $image->getHeight();
        }
    }

    /**
     * Return the height that the image should take on the screen
     * for the specified size
     *
     * @param null $localRequestedWidth - the width to derive the height from (in case the image is created for responsive lazy loading)
     * if not specified, the requested width and if not specified the intrinsic width
     * @return int the height value attribute in a img
     */
    public
    function getImgTagHeightValue($localRequestedWidth = null): int
    {

        $image = $this->getDefaultImage();
        /**
         * Cropping is not yet supported.
         */
        $requestedHeight = $this->getRequestedHeight();
        $requestedWidth = $this->getRequestedWidth();
        if (
            $requestedHeight != null
            && $requestedHeight != 0
            && $requestedWidth != null
            && $requestedWidth != 0
        ) {
            global $ID;
            if ($ID != "wiki:syntax") {
                /**
                 * Cropping
                 */
                LogUtility::msg("The width and height has been set on the image ($this) but we don't support yet cropping. Set only the width or the height (0x250)", LogUtility::LVL_MSG_WARNING, self::CANONICAL);
            }
        }

        /**
         * If resize by height, the img tag height is the requested height
         */
        if ($localRequestedWidth == null) {
            if ($requestedHeight != null) {
                return $requestedHeight;
            } else {
                $localRequestedWidth = $this->getRequestedWidth();
                if (empty($localRequestedWidth)) {
                    $localRequestedWidth = $image->getWidth();
                }
            }
        }

        /**
         * Computation
         */
        $computedHeight = $this->getRequestedHeight();
        $targetRatio = $this->getTargetRatio();
        if ($targetRatio !== false) {

            /**
             * Scale the height by target ratio
             */
            $computedHeight = $localRequestedWidth / $this->getTargetRatio();

            /**
             * Check
             */
            if ($requestedHeight != null) {
                if ($requestedHeight < $computedHeight) {
                    LogUtility::msg("The computed height cannot be greater than the requested height");
                }
            }

        }


        /**
         * Rounding to integer
         * The fetch.php file takes int as value for width and height
         * making a rounding if we pass a double (such as 37.5)
         * This is important because the security token is based on width and height
         * and therefore the fetch will failed
         *
         * And not directly {@link intval} because it will make from 3.6, 3 and not 4
         */
        return intval(round($computedHeight));

    }

    /**
     * @return int - the width value attribute in a img (in CSS pixel that the image should takes)
     */
    public
    function getImgTagWidthValue(): int
    {
        $image = $this->getDefaultImage();
        $linkWidth = $this->getRequestedWidth();
        if (empty($linkWidth)) {
            if (empty($this->getRequestedHeight())) {

                $linkWidth = $image->getWidth();

            } else {

                // Height is not empty
                // We derive the width from it
                if ($image->getHeight() != 0
                    && !empty($image->getHeight())
                    && !empty($image->getWidth())
                ) {
                    $linkWidth = $image->getWidth() * ($this->getRequestedHeight() / $image->getHeight());
                }

            }
        }
        /**
         * Rounding to integer
         * The fetch.php file takes int as value for width and height
         * making a rounding if we pass a double (such as 37.5)
         * This is important because the security token is based on width and height
         * and therefore the fetch will failed
         *
         * And this is also ask by the specification
         * a non-null positive integer
         * https://html.spec.whatwg.org/multipage/embedded-content-other.html#attr-dim-height
         *
         * And not {@link intval} because it will make from 3.6, 3 and not 4
         */
        return intval(round($linkWidth));
    }


    function getDefaultImage(): Image
    {
        return $this->getDokuPath();
    }

}
