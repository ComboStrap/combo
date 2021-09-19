<?php


namespace ComboStrap;

use http\Exception\RuntimeException;

require_once(__DIR__ . "/PluginUtility.php");

/**
 * Class Image
 * @package ComboStrap
 * An image
 */
abstract class Image extends DokuPath
{


    public static function createImageFromAbsolutePath($imageIdFromMeta, $rev = null)
    {

        /**
         * Processing
         */
        $dokuPath = DokuPath::createMediaPathFromAbsolutePath($imageIdFromMeta, $rev);
        $mime = $dokuPath->getMime();

        if (substr($mime, 0, 5) == 'image') {
            if (substr($mime, 6) == "svg+xml") {

                return new ImageSvg($imageIdFromMeta, $rev);

            } else {

                return new ImageRaster($imageIdFromMeta, $rev);

            }
        } else {
            throw new RuntimeException("The file ($imageIdFromMeta) has not been detected as beeing an image.");
        }

    }

    /**
     * Return a height value that is conform to the {@link Image::getAspectRatio()} of the image.
     *
     * @param int|null $requestedWidth - the width to derive the height from (in case the image is created for responsive lazy loading)
     * if not specified, the requested width and if not specified the intrinsic width
     * @param int|null $requestedHeight
     * @return int the height value attribute in a img
     *
     * Algorithm:
     *   * If the requested height given is not null, return the given height rounded
     *   * If the requested height is null, if the requested width is:
     *         * null: return the intrinsic / natural height
     *         * not null: return the height as being the width scaled down by the {@link Image::getAspectRatio()}
     */
    public function getImgTagHeightValue(?int $requestedWidth, ?int $requestedHeight): int
    {

        /**
         * Cropping is not yet supported.
         */
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
                LogUtility::msg("The width and height has been set on the image ($this) but we don't support yet cropping. Set only the width or the height (0x250)", LogUtility::LVL_MSG_WARNING, MediaLink::CANONICAL);
            }
        }

        if (empty($requestedHeight)) {

            if (empty($requestedWidth)) {

                $requestedHeight = $this->getHeight();

            } else {

                // Width is not empty
                // We derive the height from it
                if ($this->getAspectRatio() !== false) {
                    $requestedHeight = $requestedWidth / $this->getAspectRatio();
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
        return intval(round($requestedHeight));

    }

    /**
     * Return a width value that is conform to the {@link Image::getAspectRatio()} of the image.
     *
     * @param int|null $requestedWidth - the requested width (may be null)
     * @param int|null $requestedHeight - the request height (may be null)
     * @return int - the width value attribute in a img (in CSS pixel that the image should takes)
     *
     * Algorithm:
     *   * If the requested width given is not null, return the given width rounded
     *   * If the requested width is null, if the requested height is:
     *         * null: return the intrinsic / natural width
     *         * not null: return the width as being the height scaled down by the {@link Image::getAspectRatio()}
     */
    public function getImgTagWidthValue(?int $requestedWidth, ?int $requestedHeight): int
    {

        if (empty($requestedWidth)) {

            if (empty($requestedHeight)) {

                $requestedWidth = $this->getWidth();

            } else {

                // Height is not empty
                // We derive the width from it
                if ($this->getHeight() != 0
                    && !empty($this->getHeight())
                    && !empty($this->getWidth())
                ) {
                    $requestedWidth = $this->getAspectRatio() * $requestedHeight;
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
        return intval(round($requestedWidth));
    }


    /**
     * For a raster image, the internal width
     * for a svg, the defined viewBox
     *
     * This is needed to calculate the {@link MediaLink::getTargetRatio() target ratio}
     * and pass them to the img tag to avoid layout shift
     *
     * @return mixed
     */
    public abstract function getWidth();

    /**
     * For a raster image, the internal height
     * for a svg, the defined `viewBox` value
     *
     * This is needed to calculate the {@link MediaLink::getTargetRatio() target ratio}
     * and pass them to the img tag to avoid layout shift
     *
     * @return mixed
     */
    public abstract function getHeight();

    /**
     * The Aspect ratio as explained here
     * https://html.spec.whatwg.org/multipage/embedded-content-other.html#attr-dim-height
     * @return float|int|false
     * false if the image is not supported
     *
     * It's needed for an img tag to set the img `width` and `height` that pass the
     * {@link MediaLink::checkWidthAndHeightRatioAndReturnTheGoodValue() check}
     * to avoid layout shift
     */
    public function getAspectRatio()
    {

        if ($this->getHeight() == null || $this->getWidth() == null) {
            return false;
        } else {
            return $this->getWidth() / $this->getHeight();
        }
    }

    /**
     * @return bool if this is raster image, false if this is a vector image
     */
    public function isRaster(): bool
    {
        if ($this->getMime() === ImageSvg::MIME) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Giving width and height, check that the aspect ratio is the same
     * than the intrinsic one
     * @param $height
     * @param $width
     */
    public
    function checkLogicalRatioAgainstIntrinsicRatio($width, $height)
    {
        /**
         * Check of height and width dimension
         * as specified here
         * https://html.spec.whatwg.org/multipage/embedded-content-other.html#attr-dim-height
         */
        $targetRatio = $this->getAspectRatio();
        if (!(
            $height * $targetRatio >= $width - 0.5
            &&
            $height * $targetRatio <= $width + 0.5
        )) {
            // check the second statement
            if (!(
                $width / $targetRatio >= $height - 0.5
                &&
                $width / $targetRatio <= $height + 0.5
            )) {
                if (
                    !empty($width)
                    && !empty($height)
                ) {
                    /**
                     * The user has asked for a width and height
                     */
                    $width = round($height * $targetRatio);
                    LogUtility::msg("The width ($height) and height ($width) specified on the image ($this) does not follow the natural ratio as <a href=\"https://html.spec.whatwg.org/multipage/embedded-content-other.html#attr-dim-height\">required by HTML</a>. The width was then set to ($width).", LogUtility::LVL_MSG_INFO, self::CANONICAL);
                } else {
                    /**
                     * Programmatic error from the developer
                     */
                    $imgTagRatio = $width / $height;
                    LogUtility::msg("Internal Error: The width ($width) and height ($height) calculated for the image ($this) does not pass the ratio test. They have a ratio of ($imgTagRatio) while the natural dimension ratio is ($targetRatio)");
                }
            }
        }
    }

    /**
     * The Url
     * @return mixed
     */
    public abstract function getAbsoluteUrl();

    /**
     * TODO:
     * Alt is the description of the image
     * for screen reader, unfortunately nothing for now
     * @return null
     */
    public function getAlt()
    {
        return null;
    }

}
