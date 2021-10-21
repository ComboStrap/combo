<?php


namespace ComboStrap;


require_once(__DIR__ . "/PluginUtility.php");

/**
 * Class Image
 * @package ComboStrap
 * An image and its attribute
 * (ie a file and its transformation attribute if any such as
 * width, height, ...)
 */
abstract class Image extends Media
{


    const CANONICAL = "image";


    /**
     * Image constructor.
     * @param $absoluteFileSystemPath
     * @param TagAttributes|null $attributes - the attributes
     */
    public function __construct($absoluteFileSystemPath, $attributes = null)
    {
        if ($attributes === null) {
            $this->attributes = TagAttributes::createEmpty(self::CANONICAL);
        }

        parent::__construct($absoluteFileSystemPath, $attributes);
    }


    public static function createImageFromAbsolutePath($imageIdFromMeta, $rev = null, $attributes = null)
    {

        /**
         * Processing
         */
        $dokuPath = DokuPath::createMediaPathFromAbsolutePath($imageIdFromMeta, $rev);
        $mime = $dokuPath->getMime();

        if (substr($mime, 0, 5) !== 'image') {

            LogUtility::msg("The file ($imageIdFromMeta) has not been detected as being an image, media returned", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
            return null;

        }
        if (substr($mime, 6) == "svg+xml") {

            $image = new ImageSvg($dokuPath->getAbsoluteFileSystemPath(), $attributes);

        } else {

            $image = new ImageRaster($dokuPath->getAbsoluteFileSystemPath(), $attributes);

        }
        $image->setDokuPath($dokuPath);
        return $image;


    }

    public static function createImageFromId(string $imageId,$rev = '',$attributes = null)
    {
        return self::createImageFromAbsolutePath(":$imageId", $rev,$attributes);
    }

    /**
     * Return a height value that is conform to the {@link Image::getIntrinsicAspectRatio()} of the image.
     *
     * @param int|null $breakpointWidth - the width to derive the height from (in case the image is created for responsive lazy loading)
     * if not specified, the requested width and if not specified the intrinsic width
     * @param int|null $requestedHeight
     * @return int the height value attribute in a img
     *
     * Algorithm:
     *   * If the requested height given is not null, return the given height rounded
     *   * If the requested height is null, if the requested width is:
     *         * null: return the intrinsic / natural height
     *         * not null: return the height as being the width scaled down by the {@link Image::getIntrinsicAspectRatio()}
     */
    public function getBreakpointHeight(?int $breakpointWidth): int
    {

        if ($this->getTargetAspectRatio() === false) {
            LogUtility::msg("The ratio of the image ($this) could not be calculated", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
            return $this->getTargetHeight();
        }
        return $this->round($breakpointWidth / $this->getTargetAspectRatio());

    }

    /**
     * Return a width value that is conform to the {@link Image::getIntrinsicAspectRatio()} of the image.
     *
     * @param int|null $requestedWidth - the requested width (may be null)
     * @param int|null $requestedHeight - the request height (may be null)
     * @return int - the width value attribute in a img (in CSS pixel that the image should takes)
     *
     * Algorithm:
     *   * If the requested width given is not null, return the given width
     *   * If the requested width is null, if the requested height is:
     *         * null: return the intrinsic / natural width
     *         * not null: return the width as being the height scaled down by the {@link Image::getIntrinsicAspectRatio()}
     */
    public function getWidthValueScaledDown(?int $requestedWidth, ?int $requestedHeight): int
    {

        if (!empty($requestedWidth) && !empty($requestedHeight)) {
            LogUtility::msg("The requested width ($requestedWidth) and the requested height ($requestedHeight) are not null. You can't scale an image in width and height. The width or the height should be null.", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
        }

        $computedWidth = $requestedWidth;
        if (empty($requestedWidth)) {

            if (empty($requestedHeight)) {

                $computedWidth = $this->getIntrinsicWidth();

            } else {

                if ($this->getIntrinsicAspectRatio() !== false) {
                    $computedWidth = $this->getIntrinsicAspectRatio() * $requestedHeight;
                } else {
                    LogUtility::msg("The aspect ratio of the image ($this) could not be calculated", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
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
        return intval(round($computedWidth));
    }


    /**
     * For a raster image, the internal width
     * for a svg, the defined viewBox
     *
     *
     * @return mixed
     */
    public abstract function getIntrinsicWidth();

    /**
     * For a raster image, the internal height
     * for a svg, the defined `viewBox` value
     *
     * This is needed to calculate the {@link MediaLink::getTargetRatio() target ratio}
     * and pass them to the img tag to avoid layout shift
     *
     * @return mixed
     */
    public abstract function getIntrinsicHeight();

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
    public function getIntrinsicAspectRatio()
    {

        if ($this->getIntrinsicHeight() == null || $this->getIntrinsicWidth() == null) {
            return false;
        } else {
            return $this->getIntrinsicWidth() / $this->getIntrinsicHeight();
        }
    }

    /**
     * The Aspect ratio of the target image (may be the original or the an image scaled down)
     *
     * https://html.spec.whatwg.org/multipage/embedded-content-other.html#attr-dim-height
     * @return float|int|false
     * false if the image is not supported
     *
     * It's needed for an img tag to set the img `width` and `height` that pass the
     * {@link MediaLink::checkWidthAndHeightRatioAndReturnTheGoodValue() check}
     * to avoid layout shift
     */
    public function getTargetAspectRatio()
    {

        if (empty($this->getTargetHeight()) || empty($this->getIntrinsicWidth())) {
            return false;
        } else {
            return $this->getTargetWidth() / $this->getTargetHeight();
        }
    }

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
    public function getRequestedAspectRatio()
    {

        if ($this->getTargetHeight() == null || $this->getTargetWidth() == null) {
            return false;
        } else {
            return $this->getTargetWidth() / $this->getTargetHeight();
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
     * than the target one
     * @param $height
     * @param $width
     */
    public
    function checkLogicalRatioAgainstTargetRatio($width, $height)
    {
        /**
         * Check of height and width dimension
         * as specified here
         *
         * This is about the intrinsic dimension but we have the notion of target dimension
         *
         * https://html.spec.whatwg.org/multipage/embedded-content-other.html#attr-dim-height
         */
        $targetRatio = $this->getTargetAspectRatio();
        if (!(
            $height * $targetRatio >= $width - 1
            &&
            $height * $targetRatio <= $width + 1
        )) {
            // check the second statement
            if (!(
                $width / $targetRatio >= $height - 1
                &&
                $width / $targetRatio <= $height + 1
            )) {

                /**
                 * Programmatic error from the developer
                 */
                $imgTagRatio = $width / $height;
                LogUtility::msg("Internal Error: The width ($width) and height ($height) calculated for the image ($this) does not pass the ratio test. They have a ratio of ($imgTagRatio) while the target dimension ratio is ($targetRatio)");

            }
        }
    }

    /**
     * The Url
     * @return mixed
     */
    public abstract function getAbsoluteUrl();

    /**
     * This is mandatory for HTML
     * The alternate text (the title in Dokuwiki media term)
     * @return null
     *
     * TODO: try to extract it from the metadata file ?
     *
     * An img element must have an alt attribute, except under certain conditions.
     * For details, consult guidance on providing text alternatives for images.
     * https://www.w3.org/WAI/tutorials/images/
     */
    public function getAltNotEmpty()
    {
        $title = $this->getTitle();
        if (empty($title)) {
            $generatedAlt = str_replace($this->getBaseNameWithoutExtension(), "-", " ");
            return str_replace($generatedAlt, "_", " ");
        } else {
            return $title;
        }
    }


    /**
     * The logical height is the calculated height of the target image
     * specified in the query parameters
     *
     * For instance,
     *   * with `200`, the target image has a {@link Image::getTargetWidth() logical width} of 200 and a {@link Image::getTargetHeight() logical height} that is scaled down by the {@link Image::getIntrinsicAspectRatio() instrinsic ratio}
     *   * with ''0x20'', the target image has a {@link Image::getTargetHeight() logical height} of 20 and a {@link Image::getTargetWidth() logical width} that is scaled down by the {@link Image::getIntrinsicAspectRatio() instrinsic ratio}
     *
     * The doc is {@link https://www.dokuwiki.org/images#resizing}
     *
     *
     * @return array|int|mixed|string
     */
    public function getTargetHeight()
    {
        $requestedHeight = $this->getRequestedHeight();
        if (!empty($requestedHeight)) {
            return $requestedHeight;
        }

        /**
         * Scaled down by width
         */
        $requestedWidth = $this->getRequestedWidth();
        if (empty($requestedWidth)) {
            return $this->getIntrinsicHeight();
        }

        if ($this->getIntrinsicAspectRatio() === false) {
            LogUtility::msg("The ratio of the image ($this) could not be calculated", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
            return $this->getIntrinsicHeight();
        }
        return self::round($requestedWidth / $this->getIntrinsicAspectRatio());

    }

    /**
     * The logical width is the width of the target image calculated from the requested dimension
     *
     * For instance,
     *   * with `200`, the target image has a {@link Image::getTargetWidth() logical width} of 200 and a {@link Image::getTargetHeight() logical height} that is scaled down by the {@link Image::getIntrinsicAspectRatio() instrinsic ratio}
     *   * with ''0x20'', the target image has a {@link Image::getTargetHeight() logical height} of 20 and a {@link Image::getTargetWidth() logical width} that is scaled down by the {@link Image::getIntrinsicAspectRatio() instrinsic ratio}
     *
     * The doc is {@link https://www.dokuwiki.org/images#resizing}
     */
    public function getTargetWidth()
    {
        $requestedWidth = $this->getRequestedWidth();

        /**
         * May be 0 (ie empty)
         */
        if (!empty($requestedWidth)) {
            return $requestedWidth;
        }

        /**
         * Empty requested width, may be scaled down by height
         */
        $requestedHeight = $this->getRequestedHeight();
        if (empty($requestedHeight)) {
            return $this->getIntrinsicWidth();
        }

        if ($this->getIntrinsicAspectRatio() === false) {
            LogUtility::msg("The ratio of the image ($this) could not be calculated", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
            return $this->getIntrinsicHeight();
        }

        return self::round($this->getIntrinsicAspectRatio() * $requestedHeight);

    }

    /**
     * @return array|string|null
     */
    public function getRequestedWidth()
    {
        return $this->attributes->getValue(Dimension::WIDTH_KEY);
    }

    /**
     * @return array|string|null
     */
    public function getRequestedHeight()
    {
        return $this->attributes->getValue(Dimension::HEIGHT_KEY);
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
    public static function round(float $param): int
    {
        return intval(round($param));
    }



}
