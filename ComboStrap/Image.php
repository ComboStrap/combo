<?php


namespace ComboStrap;


use syntax_plugin_combo_card;

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
     * @param Path $path
     * @param TagAttributes|null $attributes - the attributes
     */
    public function __construct(Path $path, $attributes = null)
    {
        if ($attributes === null) {
            $this->attributes = TagAttributes::createEmpty(self::CANONICAL);
        }

        parent::__construct($path, $attributes);
    }


    /**
     * @param Path $path
     * @param null $attributes
     * @return ImageRaster|ImageSvg
     * @throws ExceptionCombo if not valid
     */
    public static function createImageFromPath(Path $path, $attributes = null)
    {

        $mime = $path->getMime();

        if (!$mime->isImage()) {

            throw new ExceptionCombo("The file ($path) has not been detected as being an image, media returned", LogUtility::LVL_MSG_ERROR, self::CANONICAL);

        }
        if ($mime->toString() === Mime::SVG) {

            $image = new ImageSvg($path, $attributes);

        } else {

            $image = new ImageRaster($path, $attributes);

        }
        return $image;


    }

    /**
     * @throws ExceptionCombo if not valid
     */
    public static function createImageFromId(string $imageId, $rev = '', $attributes = null)
    {
        $dokuPath = DokuPath::createMediaPathFromId($imageId, $rev);
        return self::createImageFromPath($dokuPath, $attributes);
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

        try {
            $targetAspectRatio = $this->getTargetAspectRatio();
        } catch (ExceptionCombo $e) {
            LogUtility::msg("The target ratio for the image was set to 1 because we got this error: {$e->getMessage()}");
            $targetAspectRatio = 1;
        }
        if ($targetAspectRatio === 0) {
            LogUtility::msg("The target ratio for the image was set to 1 because its value was 0");
            $targetAspectRatio = 1;
        }
        return $this->round($breakpointWidth / $targetAspectRatio);

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
     * @throws ExceptionCombo
     * @return int in pixel
     */
    public abstract function getIntrinsicWidth(): int;

    /**
     * For a raster image, the internal height
     * for a svg, the defined `viewBox` value
     *
     * This is needed to calculate the {@link MediaLink::getTargetRatio() target ratio}
     * and pass them to the img tag to avoid layout shift
     *
     * @return int in pixel
     */
    public abstract function getIntrinsicHeight(): int;

    /**
     * The Aspect ratio as explained here
     * https://html.spec.whatwg.org/multipage/embedded-content-other.html#attr-dim-height
     * @return float|int|false
     * false if the image is not supported
     *
     * It's needed for an img tag to set the img `width` and `height` that pass the
     * {@link MediaLink::checkWidthAndHeightRatioAndReturnTheGoodValue() check}
     * to avoid layout shift
     * @throws ExceptionCombo
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
     * @throws ExceptionCombo
     */
    public function getTargetAspectRatio()
    {

        $targetHeight = $this->getTargetHeight();
        if ($targetHeight === 0) {
            throw new ExceptionCombo("The target height is equal to zero, we can calculate the target aspect ratio");
        }
        $targetWidth = $this->getTargetWidth();
        return $targetWidth / $targetHeight;

    }

    /**
     * The Aspect ratio as explained here
     * https://html.spec.whatwg.org/multipage/embedded-content-other.html#attr-dim-height
     * @return float|int
     * false if the image is not supported
     *
     * It's needed for an img tag to set the img `width` and `height` that pass the
     * {@link MediaLink::checkWidthAndHeightRatioAndReturnTheGoodValue() check}
     * to avoid layout shift
     * @throws ExceptionCombo
     */
    public function getRequestedAspectRatio()
    {

        $requestedRatio = $this->attributes->getValue(Dimension::RATIO_ATTRIBUTE);
        if ($requestedRatio !== null) {
            try {
                return Dimension::convertTextualRatioToNumber($requestedRatio);
            } catch (ExceptionCombo $e) {
                LogUtility::msg("The requested ratio ($requestedRatio) is not a valid value ({$e->getMessage()})", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
            }
        }

        if (
            $this->getRequestedWidth() !== null
            && $this->getRequestedWidth() !== 0 // default value for not set in dokuwiki
            && $this->getRequestedHeight() !== null) {
            if ($this->getRequestedHeight() === 0) {
                LogUtility::msg("The requested height is 0, we can't calculate the requested ratio", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
            }
            return $this->getRequestedWidth() / $this->getRequestedHeight();
        }

        return null;


    }

    /**
     * @return bool if this is raster image, false if this is a vector image
     */
    public function isRaster(): bool
    {
        if ($this->getPath()->getMime()->toString() === Mime::SVG) {
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
        try {
            $targetRatio = $this->getTargetAspectRatio();
        } catch (ExceptionCombo $e) {
            LogUtility::msg("Unable to check the target ratio because it returns this error: {$e->getMessage()}");
            return;
        }
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
        if (!empty($title)) {
            return $title;
        }
        $generatedAlt = str_replace("-", " ", $this->getPath()->getLastNameWithoutExtension());
        return str_replace($generatedAlt, "_", " ");
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
     * @return int
     * @throws ExceptionCombo
     */
    public function getTargetHeight(): int
    {
        $requestedHeight = $this->getRequestedHeight();
        if (!empty($requestedHeight)) {
            return $requestedHeight;
        }

        /**
         * Scaled down by width
         */
        $width = $this->getRequestedWidth();
        if (!empty($width)) {

            try {
                $ratio = $this->getRequestedAspectRatio();
                if ($ratio === null) {
                    $ratio = $this->getIntrinsicAspectRatio();
                }
                return self::round($width / $ratio);
            } catch (ExceptionCombo $e) {
                LogUtility::msg("The intrinsic height of the image ($this) was used because retrieving the ratio returns this error: {$e->getMessage()} ", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                return $this->getIntrinsicHeight();
            }

        }

        /**
         * Scaled down by ratio
         */
        $ratio = $this->getRequestedAspectRatio();
        if (!empty($ratio)) {
            [$croppedWidth, $croppedHeight] = Image::getCroppingDimensionsWithRatio(
                $ratio,
                $this->getIntrinsicWidth(),
                $this->getIntrinsicHeight()
            );
            return $croppedHeight;
        }

        return $this->getIntrinsicHeight();

    }

    /**
     * The logical width is the width of the target image calculated from the requested dimension
     *
     * For instance,
     *   * with `200`, the target image has a {@link Image::getTargetWidth() logical width} of 200 and a {@link Image::getTargetHeight() logical height} that is scaled down by the {@link Image::getIntrinsicAspectRatio() instrinsic ratio}
     *   * with ''0x20'', the target image has a {@link Image::getTargetHeight() logical height} of 20 and a {@link Image::getTargetWidth() logical width} that is scaled down by the {@link Image::getIntrinsicAspectRatio() instrinsic ratio}
     *
     * The doc is {@link https://www.dokuwiki.org/images#resizing}
     * @throws ExceptionCombo
     */
    public function getTargetWidth(): int
    {

        $requestedWidth = $this->getRequestedWidth();

        /**
         * May be 0 (ie empty)
         */
        if (!empty($requestedWidth)) {
            return $requestedWidth;
        }

        /**
         * Scaled down by Height
         */
        $height = $this->getRequestedHeight();
        if (!empty($height)) {

            try {
                $ratio = $this->getRequestedAspectRatio();
                if ($ratio === null) {
                    $ratio = $this->getIntrinsicAspectRatio();
                }
                return self::round($ratio * $height);
            } catch (ExceptionCombo $e) {
                LogUtility::msg("The intrinsic width of the image ($this) was used because retrieving the ratio returns this error: {$e->getMessage()} ", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                return $this->getIntrinsicWidth();
            }

        }

        /**
         * Scaled down by Ratio
         */
        $ratio = $this->getRequestedAspectRatio();
        if (!empty($ratio)) {
            [$logicalWidthWithRatio, $logicalHeightWithRatio] = Image::getCroppingDimensionsWithRatio(
                $ratio,
                $this->getIntrinsicWidth(),
                $this->getIntrinsicHeight()
            );
            return $logicalWidthWithRatio;
        }

        return $this->getIntrinsicWidth();

    }

    /**
     * @return int|null
     * @throws ExceptionCombo
     */
    public function getRequestedWidth(): ?int
    {
        $value = $this->attributes->getValue(Dimension::WIDTH_KEY);
        if ($value === null) {
            return null;
        }
        try {
            return DataType::toInteger($value);
        } catch (ExceptionCombo $e) {
            throw new ExceptionCombo("The width value ($value) is not a valid integer", self::CANONICAL, $e);
        }
    }

    /**
     * @return int|null
     * @throws ExceptionCombo
     */
    public function getRequestedHeight(): ?int
    {
        $value = $this->attributes->getValue(Dimension::HEIGHT_KEY);
        if ($value === null) {
            return null;
        }
        try {
            return DataType::toInteger($value);
        } catch (ExceptionCombo $e) {
            throw new ExceptionCombo("The height value ($value) is not a valid integer", self::CANONICAL, $e);
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
    public static function round(float $param): int
    {
        return intval(round($param));
    }


    /**
     * Return the width and height of the image
     * after applying a ratio (16x9, 4x3, ..)
     *
     * The new dimension will apply to:
     *   * the viewBox for svg
     *   * the physical dimension for raster image
     *
     * TODO: This function is static because the {@link SvgDocument} is not an image but an xml
     */
    public static function getCroppingDimensionsWithRatio(float $targetRatio, int $intrinsicWidth, int $intrinsicHeight): array
    {

        /**
         * Trying to crop on the width
         */
        $logicalWidth = $intrinsicWidth;
        $logicalHeight = Image::round($logicalWidth / $targetRatio);
        if ($logicalHeight > $intrinsicHeight) {
            /**
             * Cropping by height
             */
            $logicalHeight = $intrinsicHeight;
            $logicalWidth = Image::round($targetRatio * $logicalHeight);
        }
        return [$logicalWidth, $logicalHeight];

    }


}
