<?php


namespace ComboStrap;


/**
 * Class Image
 * @package ComboStrap
 *
 * Image request / response
 *
 * and its attribute
 * (ie a file and its transformation attribute if any such as
 * width, height, ...)
 */
abstract class FetchImage extends FetchAbs
{

    const CANONICAL = "image";

    const TOK = "tok";

    private ?int $requestedWidth = null;
    private ?int $requestedHeight = null;

    private ?string $requestedRatio = null;


    /**
     * Image Fetch constructor.
     *
     */
    public function __construct()
    {
        /**
         * Image can be generated, ie {@link Vignette}, {@link Snapshot}
         */
    }


    /**
     * @param Path $path
     * @return FetchImageRaster|FetchSvg
     * @throws ExceptionBadArgument - if the path is not an image
     * @throws ExceptionBadSyntax - if the image is not encoded
     * @throws ExceptionNotExists - if the image does not exists
     */
    public static function createImageFetchFromPath(Path $path)
    {

        try {
            $mime = FileSystems::getMime($path);
        } catch (ExceptionNotFound $e) {
            throw new ExceptionBadArgument("The file ($path) has an unknown mime, we can't verify if we support it", self::CANONICAL);
        }

        if (!$mime->isImage()) {
            throw new ExceptionBadArgument("The file ($path) has not been detected as being an image, media returned", self::CANONICAL);
        }

        if ($mime->toString() === Mime::SVG) {

            $image = FetchSvg::createEmptySvg()->setOriginalPath($path);

        } else {

            $image = FetchImageRaster::createImageRasterFetchFromPath($path);

        }


        return $image;


    }

    /**
     * @return DokuPath - just to get the id that is mandatory when adding the toc for dokuwiki compliance
     * See {@link FetchImage::addCommonImageQueryParameterToUrl()}
     * @throws ExceptionNotFound - if not used
     */
    function getOriginalPath(): DokuPath
    {
        throw new ExceptionNotFound("Not found by default");
    }

    /**
     * @param string $imageId
     * @param string|null $rev
     * @return FetchSvg|FetchImageRaster
     * @throws ExceptionBadArgument - if the path is not an image
     * @throws ExceptionBadSyntax - if the image is badly encoded
     * @throws ExceptionNotExists - if the image does not exists
     */
    public static function createImageFetchFromId(string $imageId, string $rev = null)
    {
        $dokuPath = DokuPath::createMediaPathFromId($imageId, $rev);
        return self::createImageFetchFromPath($dokuPath);
    }

    /**
     * Utility function to build the common image fetch processing property
     * (e width, height, ratio)
     * @param Url $tagAttributes
     * @return void
     * @throws ExceptionBadArgument
     */
    public function buildSharedImagePropertyFromTagAttributes(Url $tagAttributes)
    {
        try {
            $requestedWidth = $tagAttributes->getQueryPropertyValue(Dimension::WIDTH_KEY);
        } catch (ExceptionNotFound $e) {
            try {
                $requestedWidth = $tagAttributes->getQueryPropertyValue(Dimension::WIDTH_KEY_SHORT);
            } catch (ExceptionNotFound $e) {
                $requestedWidth = null;
            }
        }
        if ($requestedWidth !== null) {
            try {
                $requestedWidthInt = DataType::toInteger($requestedWidth);
            } catch (ExceptionBadArgument $e) {
                throw new ExceptionBadArgument("The width value ($requestedWidth) is not a valid integer", self::CANONICAL, 0, $e);
            }
            $this->setRequestedWidth($requestedWidthInt);
        }
        try {
            $requestedHeight = $tagAttributes->getQueryPropertyValue(Dimension::HEIGHT_KEY);
        } catch (ExceptionNotFound $e) {
            try {
                $requestedHeight = $tagAttributes->getQueryPropertyValue(Dimension::HEIGHT_KEY_SHORT);
            } catch (ExceptionNotFound $e) {
                $requestedHeight = null;
            }
        }
        if ($requestedHeight !== null) {
            try {
                $requestedHeightInt = DataType::toInteger($requestedHeight);
            } catch (ExceptionBadArgument $e) {
                throw new ExceptionBadArgument("The height value ($requestedHeight) is not a valid integer", self::CANONICAL, 0, $e);
            }
            $this->setRequestedHeight($requestedHeightInt);
        }

        try {
            $requestedRatio = $tagAttributes->getQueryPropertyValue(Dimension::RATIO_ATTRIBUTE);
            try {
                $this->requestedRatio = Dimension::convertTextualRatioToNumber($requestedRatio);
            } catch (ExceptionBadSyntax $e) {
                throw new ExceptionBadArgument("The requested ratio ($requestedRatio) is not a valid value ({$e->getMessage()})", self::CANONICAL, 0, $e);
            }
        } catch (ExceptionNotFound $e) {
            // ok
        }


    }

    /**
     * Return a height value that is conform to the {@link FetchImage::getIntrinsicAspectRatio()} of the image.
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
     *         * not null: return the height as being the width scaled down by the {@link FetchImage::getIntrinsicAspectRatio()}
     */
    public
    function getBreakpointHeight(?int $breakpointWidth): int
    {

        try {
            $targetAspectRatio = $this->getTargetAspectRatio();
        } catch (ExceptionCompile $e) {
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
     * Return a width value that is conform to the {@link FetchImage::getIntrinsicAspectRatio()} of the image.
     *
     * @param int|null $requestedWidth - the requested width (may be null)
     * @param int|null $requestedHeight - the request height (may be null)
     * @return int - the width value attribute in a img (in CSS pixel that the image should takes)
     *
     * Algorithm:
     *   * If the requested width given is not null, return the given width
     *   * If the requested width is null, if the requested height is:
     *         * null: return the intrinsic / natural width
     *         * not null: return the width as being the height scaled down by the {@link FetchImage::getIntrinsicAspectRatio()}
     */
    public
    function getWidthValueScaledDown(?int $requestedWidth, ?int $requestedHeight): int
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
     * @return int in pixel
     */
    public

    abstract function getIntrinsicWidth(): int;

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
     * @return float
     * false if the image is not supported
     *
     * It's needed for an img tag to set the img `width` and `height` that pass the
     * {@link MediaLink::checkWidthAndHeightRatioAndReturnTheGoodValue() check}
     * to avoid layout shift
     *
     */
    public function getTargetAspectRatio()
    {

        return $this->getTargetWidth() / $this->getTargetHeight();

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
     * @throws ExceptionNotFound
     */
    public function getRequestedAspectRatio()
    {

        if ($this->requestedRatio !== null) {
            return $this->requestedRatio;
        }

        /**
         * Note: requested weight and width throw a `not found` if width / height == 0
         * No division by zero then
         */
        return $this->getRequestedWidth() / $this->getRequestedHeight();


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
        } catch (ExceptionCompile $e) {
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
     * The logical height is the calculated height of the target image
     * specified in the query parameters
     *
     * For instance,
     *   * with `200`, the target image has a {@link FetchImage::getTargetWidth() logical width} of 200 and a {@link FetchImage::getTargetHeight() logical height} that is scaled down by the {@link FetchImage::getIntrinsicAspectRatio() instrinsic ratio}
     *   * with ''0x20'', the target image has a {@link FetchImage::getTargetHeight() logical height} of 20 and a {@link FetchImage::getTargetWidth() logical width} that is scaled down by the {@link FetchImage::getIntrinsicAspectRatio() instrinsic ratio}
     *
     * The doc is {@link https://www.dokuwiki.org/images#resizing}
     *
     *
     * @return int
     */
    public function getTargetHeight(): int
    {
        try {
            return $this->getRequestedHeight();
        } catch (ExceptionNotFound $e) {
            // no height
        }

        /**
         * Scaled down by width
         */
        try {
            $width = $this->getRequestedWidth();
            try {
                $ratio = $this->getRequestedAspectRatio();
            } catch (ExceptionNotFound $e) {
                $ratio = $this->getIntrinsicAspectRatio();
            }
            return self::round($width / $ratio);
        } catch (ExceptionNotFound $e) {
            // no width
        }


        /**
         * Scaled down by ratio
         */
        try {
            $ratio = $this->getRequestedAspectRatio();
            [$croppedWidth, $croppedHeight] = FetchImage::getCroppingDimensionsWithRatio(
                $ratio,
                $this->getIntrinsicWidth(),
                $this->getIntrinsicHeight()
            );
            return $croppedHeight;
        } catch (ExceptionBadArgument|ExceptionNotFound $e) {
            // no requested aspect ratio
        }

        return $this->getIntrinsicHeight();

    }

    /**
     * The logical width is the width of the target image calculated from the requested dimension
     *
     * For instance,
     *   * with `200`, the target image has a {@link FetchImage::getTargetWidth() logical width} of 200 and a {@link FetchImage::getTargetHeight() logical height} that is scaled down by the {@link FetchImage::getIntrinsicAspectRatio() instrinsic ratio}
     *   * with ''0x20'', the target image has a {@link FetchImage::getTargetHeight() logical height} of 20 and a {@link FetchImage::getTargetWidth() logical width} that is scaled down by the {@link FetchImage::getIntrinsicAspectRatio() instrinsic ratio}
     *
     * The doc is {@link https://www.dokuwiki.org/images#resizing}
     * @return int
     */
    public function getTargetWidth(): int
    {

        try {
            return $this->getRequestedWidth();
        } catch (ExceptionNotFound $e) {
            // no requested width
        }

        /**
         * Scaled down by Height
         */
        try {
            $height = $this->getRequestedHeight();
            try {
                $ratio = $this->getRequestedAspectRatio();
                if ($ratio === null) {
                    $ratio = $this->getIntrinsicAspectRatio();
                }
                return self::round($ratio * $height);
            } catch (ExceptionBadArgument $e) {
                LogUtility::msg("The intrinsic width of the image ($this) was used because retrieving the ratio returns this error: {$e->getMessage()} ", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                return $this->getIntrinsicWidth();
            }
        } catch (ExceptionNotFound $e) {
            // no requested height
        }


        /**
         * Scaled down by Ratio
         */
        try {
            $ratio = $this->getRequestedAspectRatio();
            [$logicalWidthWithRatio, $logicalHeightWithRatio] = FetchImage::getCroppingDimensionsWithRatio(
                $ratio,
                $this->getIntrinsicWidth(),
                $this->getIntrinsicHeight()
            );
            return $logicalWidthWithRatio;
        } catch (ExceptionNotFound $e) {
            // no ratio requested
        }

        return $this->getIntrinsicWidth();

    }

    /**
     * @return int|null
     * @throws ExceptionNotFound - if no requested width was asked
     */
    public function getRequestedWidth(): int
    {
        if ($this->requestedWidth === null) {
            throw new ExceptionNotFound("No width was requested");
        }
        if ($this->requestedWidth === 0) {
            throw new ExceptionNotFound("Width 0 was requested");
        }
        return $this->requestedWidth;
    }

    /**
     * @return int
     * @throws ExceptionNotFound - if no requested height was asked
     */
    public function getRequestedHeight(): int
    {
        if ($this->requestedHeight === null) {
            throw new ExceptionNotFound("Height not requested");
        }
        if ($this->requestedHeight === 0) {
            throw new ExceptionNotFound("Height 0 requested");
        }
        return $this->requestedHeight;
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
        $logicalHeight = FetchImage::round($logicalWidth / $targetRatio);
        if ($logicalHeight > $intrinsicHeight) {
            /**
             * Cropping by height
             */
            $logicalHeight = $intrinsicHeight;
            $logicalWidth = FetchImage::round($targetRatio * $logicalHeight);
        }
        return [$logicalWidth, $logicalHeight];

    }


    public function setRequestedWidth(int $requestedWidth): FetchImage
    {
        $this->requestedWidth = $requestedWidth;
        return $this;
    }

    public function setRequestedHeight(int $requestedHeight): FetchImage
    {
        $this->requestedHeight = $requestedHeight;
        return $this;
    }

    public function setRequestedAspectRatio(string $requestedRatio)
    {
        $this->requestedRatio = $requestedRatio;
    }


    protected function addCommonImageQueryParameterToUrl(Url $fetchUrl)
    {
        try {
            $requestedWidth = $this->getRequestedWidth();
            $fetchUrl->addQueryParameter(Dimension::WIDTH_KEY_SHORT, $requestedWidth);
        } catch (ExceptionNotFound $e) {
            // ok
            $requestedWidth = null;
        }
        try {
            $requestedHeight = $this->getRequestedHeight();
            $fetchUrl->addQueryParameter(Dimension::HEIGHT_KEY_SHORT, $requestedHeight);
        } catch (ExceptionNotFound $e) {
            // ok
            $requestedHeight = null;
        }

        try {
            if (!($requestedWidth !== null && $requestedHeight !== null)) {
                /**
                 * If the height and width are set, the requested ratio is not null
                 * because it's derived, we put the ratio only if width and height are not defined
                 */
                $fetchUrl->addQueryParameter(Dimension::RATIO_ATTRIBUTE, $this->getRequestedAspectRatio());
            }
        } catch (ExceptionNotFound $e) {
            // ok
        }

        if ($requestedWidth !== null || $requestedHeight !== null) {

            try {
                $id = $this->getOriginalPath()->getDokuwikiId();
            } catch (ExceptionNotFound $e) {
                $id = "";
            }
            $fetchUrl->addQueryParameter(self::TOK, media_get_token($id, $requestedWidth, $requestedHeight));
        }

    }

    public function __toString()
    {
        try {
            return $this->getOriginalPath()->toUriString();
        } catch (ExceptionNotFound $e) {
            return get_class($this);
        }
    }


}
